<?php
/**
 * Twitch API Integration für Stream-Management
 * Speichern als: config/twitch_api.php
 */

class TwitchAPI {
    private $clientId;
    private $clientSecret;
    private $accessToken;
    private $baseUrl = 'https://api.twitch.tv/helix';
    
    public function __construct() {
        $this->clientId = getServerSetting('twitch_client_id', '');
        $this->clientSecret = getServerSetting('twitch_client_secret', '');
        
        if (empty($this->clientId) || empty($this->clientSecret)) {
            throw new Exception('Twitch API Credentials nicht konfiguriert');
        }
        
        $this->accessToken = $this->getAccessToken();
    }
    
    /**
     * OAuth2 Access Token abrufen
     */
    private function getAccessToken() {
        // Prüfen ob cached Token noch gültig ist
        $cachedToken = getServerSetting('twitch_access_token', '');
        $tokenExpiry = getServerSetting('twitch_token_expiry', '0');
        
        if (!empty($cachedToken) && time() < $tokenExpiry) {
            return $cachedToken;
        }
        
        // Neuen Token anfordern
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://id.twitch.tv/oauth2/token',
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'grant_type' => 'client_credentials'
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded'
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception('Twitch OAuth Token Request fehlgeschlagen: HTTP ' . $httpCode);
        }
        
        $data = json_decode($response, true);
        
        if (!isset($data['access_token'])) {
            throw new Exception('Ungültige Twitch API Response');
        }
        
        // Token cachen (mit 1 Stunde Puffer vor Ablauf)
        $expiryTime = time() + ($data['expires_in'] - 3600);
        setServerSetting('twitch_access_token', $data['access_token']);
        setServerSetting('twitch_token_expiry', $expiryTime);
        
        return $data['access_token'];
    }
    
    /**
     * API Request ausführen
     */
    private function makeRequest($endpoint, $params = []) {
        $url = $this->baseUrl . $endpoint;
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                'Client-ID: ' . $this->clientId,
                'Authorization: Bearer ' . $this->accessToken,
                'Content-Type: application/json'
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if (!empty($curlError)) {
            throw new Exception('cURL Fehler: ' . $curlError);
        }
        
        if ($httpCode === 401) {
            // Token abgelaufen, neuen anfordern
            $this->accessToken = $this->getAccessToken();
            return $this->makeRequest($endpoint, $params);
        }
        
        if ($httpCode !== 200) {
            throw new Exception('Twitch API Fehler: HTTP ' . $httpCode . ' - ' . $response);
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Benutzer-Informationen abrufen
     */
    public function getUsers($usernames) {
        if (empty($usernames)) {
            return [];
        }
        
        if (is_string($usernames)) {
            $usernames = [$usernames];
        }
        
        $response = $this->makeRequest('/users', [
            'login' => implode('&login=', $usernames)
        ]);
        
        return $response['data'] ?? [];
    }
    
    /**
     * Live Streams abrufen
     */
    public function getStreams($userIds) {
        if (empty($userIds)) {
            return [];
        }
        
        if (is_string($userIds)) {
            $userIds = [$userIds];
        }
        
        $params = [];
        foreach ($userIds as $userId) {
            $params['user_id'] = $userId;
        }
        
        $response = $this->makeRequest('/streams', [
            'user_id' => implode('&user_id=', $userIds)
        ]);
        
        return $response['data'] ?? [];
    }
    
    /**
     * Spiel-Informationen abrufen
     */
    public function getGames($gameIds) {
        if (empty($gameIds)) {
            return [];
        }
        
        if (is_string($gameIds)) {
            $gameIds = [$gameIds];
        }
        
        $response = $this->makeRequest('/games', [
            'id' => implode('&id=', $gameIds)
        ]);
        
        return $response['data'] ?? [];
    }
    
    /**
     * Alle konfigurierten Streamer auf Live-Status prüfen
     */
    public function updateAllStreamersStatus() {
        try {
            // Alle aktiven Streamer aus DB laden
            $streamers = fetchAll("
                SELECT id, streamer_name, twitch_user_id, display_name
                FROM twitch_streamers 
                WHERE is_active = 1 
                ORDER BY priority_order ASC
            ");
            
            if (empty($streamers)) {
                return ['success' => true, 'message' => 'Keine aktiven Streamer konfiguriert'];
            }
            
            // Streamer ohne User ID - erst User Daten holen
            $streamersNeedingUserId = array_filter($streamers, function($s) {
                return empty($s['twitch_user_id']);
            });
            
            if (!empty($streamersNeedingUserId)) {
                $usernames = array_column($streamersNeedingUserId, 'streamer_name');
                $userData = $this->getUsers($usernames);
                
                // User IDs in DB aktualisieren
                foreach ($userData as $user) {
                    $streamerId = null;
                    foreach ($streamersNeedingUserId as $streamer) {
                        if (strtolower($streamer['streamer_name']) === strtolower($user['login'])) {
                            $streamerId = $streamer['id'];
                            break;
                        }
                    }
                    
                    if ($streamerId) {
                        updateData('twitch_streamers', [
                            'twitch_user_id' => $user['id'],
                            'display_name' => $user['display_name'],
                            'profile_image_url' => $user['profile_image_url'],
                            'offline_image_url' => $user['offline_image_url'] ?? null
                        ], 'id = :id', ['id' => $streamerId]);
                    }
                }
                
                // Streamers neu laden mit aktualisierten User IDs
                $streamers = fetchAll("
                    SELECT id, streamer_name, twitch_user_id, display_name
                    FROM twitch_streamers 
                    WHERE is_active = 1 AND twitch_user_id IS NOT NULL
                    ORDER BY priority_order ASC
                ");
            }
            
            if (empty($streamers)) {
                return ['success' => false, 'message' => 'Keine gültigen Twitch User IDs gefunden'];
            }
            
            // Live Streams prüfen
            $userIds = array_column($streamers, 'twitch_user_id');
            $liveStreams = $this->getStreams($userIds);
            
            // Game Informationen für Live Streams holen
            $gameIds = array_unique(array_filter(array_column($liveStreams, 'game_id')));
            $games = [];
            if (!empty($gameIds)) {
                $gamesData = $this->getGames($gameIds);
                foreach ($gamesData as $game) {
                    $games[$game['id']] = $game['name'];
                }
            }
            
            // Alle Streamer als offline markieren
            executeQuery("UPDATE twitch_streamers SET is_currently_live = 0, last_live_check = NOW()");
            
            // Live Streamer aktualisieren
            $liveCount = 0;
            foreach ($liveStreams as $stream) {
                $gameName = $games[$stream['game_id']] ?? $stream['game_name'] ?? 'Unbekannt';
                
                updateData('twitch_streamers', [
                    'is_currently_live' => 1,
                    'last_stream_title' => $stream['title'],
                    'last_stream_game' => $gameName,
                    'viewer_count' => $stream['viewer_count'],
                    'last_live_check' => date('Y-m-d H:i:s')
                ], 'twitch_user_id = :user_id', ['user_id' => $stream['user_id']]);
                
                $liveCount++;
            }
            
            return [
                'success' => true, 
                'message' => "Stream-Status aktualisiert: {$liveCount} von " . count($streamers) . " Streamern sind live",
                'live_count' => $liveCount,
                'total_count' => count($streamers),
                'last_update' => date('Y-m-d H:i:s')
            ];
            
        } catch (Exception $e) {
            error_log('Twitch API Update Error: ' . $e->getMessage());
            return [
                'success' => false, 
                'error' => $e->getMessage(),
                'last_error' => date('Y-m-d H:i:s')
            ];
        }
    }
    
    /**
     * Stream Thumbnail URL generieren
     */
    public function getStreamThumbnail($userId, $width = 440, $height = 248) {
        return "https://static-cdn.jtvnw.net/previews-ttv/live_user_{$userId}-{$width}x{$height}.jpg?t=" . time();
    }
    
    /**
     * Streamer validieren (prüft ob Username existiert)
     */
    public function validateStreamer($username) {
        try {
            $userData = $this->getUsers([$username]);
            
            if (empty($userData)) {
                return [
                    'valid' => false,
                    'error' => 'Twitch User nicht gefunden'
                ];
            }
            
            $user = $userData[0];
            
            return [
                'valid' => true,
                'user_id' => $user['id'],
                'display_name' => $user['display_name'],
                'description' => $user['description'] ?? '',
                'profile_image' => $user['profile_image_url'],
                'view_count' => $user['view_count'] ?? 0,
                'created_at' => $user['created_at'] ?? null
            ];
            
        } catch (Exception $e) {
            return [
                'valid' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}

/**
 * Helper Functions für Twitch Integration
 */

/**
 * Twitch API Instanz erstellen
 */
function getTwitchAPI() {
    static $twitchAPI = null;
    
    if ($twitchAPI === null) {
        try {
            $twitchAPI = new TwitchAPI();
        } catch (Exception $e) {
            error_log('Twitch API Init Error: ' . $e->getMessage());
            return null;
        }
    }
    
    return $twitchAPI;
}

/**
 * Live Streamer aus Datenbank abrufen
 */
function getLiveStreamers() {
    return fetchAll("
        SELECT 
            ts.*,
            CASE 
                WHEN ts.is_currently_live = 1 THEN ts.last_live_check 
                ELSE NULL 
            END as live_since
        FROM twitch_streamers ts
        WHERE ts.is_active = 1 
        AND ts.is_currently_live = 1
        ORDER BY ts.priority_order ASC, ts.viewer_count DESC
    ");
}

/**
 * Alle konfigurierten Streamer abrufen
 */
function getAllStreamers() {
    return fetchAll("
        SELECT * FROM twitch_streamers 
        ORDER BY priority_order ASC, created_at ASC
    ");
}

/**
 * Streamer nach ID abrufen
 */
function getStreamerById($id) {
    return fetchOne("SELECT * FROM twitch_streamers WHERE id = :id", ['id' => $id]);
}

/**
 * Neuen Streamer hinzufügen
 */
function addStreamer($streamerName, $displayName, $description = '', $priorityOrder = 0) {
    // Validierung über Twitch API
    $twitchAPI = getTwitchAPI();
    if (!$twitchAPI) {
        return ['success' => false, 'message' => 'Twitch API nicht verfügbar'];
    }
    
    $validation = $twitchAPI->validateStreamer($streamerName);
    
    if (!$validation['valid']) {
        return ['success' => false, 'message' => $validation['error']];
    }
    
    // Prüfen ob Streamer bereits existiert
    $existing = fetchOne("SELECT id FROM twitch_streamers WHERE streamer_name = :name", 
                        ['name' => $streamerName]);
    
    if ($existing) {
        return ['success' => false, 'message' => 'Streamer bereits vorhanden'];
    }
    
    // Streamer hinzufügen
    $streamerId = insertData('twitch_streamers', [
        'streamer_name' => $streamerName,
        'display_name' => $displayName ?: $validation['display_name'],
        'twitch_user_id' => $validation['user_id'],
        'description' => $description ?: $validation['description'],
        'priority_order' => $priorityOrder,
        'profile_image_url' => $validation['profile_image'],
        'is_active' => 1
    ]);
    
    if ($streamerId) {
        return [
            'success' => true, 
            'message' => 'Streamer erfolgreich hinzugefügt',
            'streamer_id' => $streamerId
        ];
    } else {
        return ['success' => false, 'message' => 'Datenbankfehler beim Hinzufügen'];
    }
}

/**
 * Streamer bearbeiten
 */
function updateStreamer($id, $data) {
    $result = updateData('twitch_streamers', $data, 'id = :id', ['id' => $id]);
    
    if ($result !== false) {
        return ['success' => true, 'message' => 'Streamer aktualisiert'];
    } else {
        return ['success' => false, 'message' => 'Fehler beim Aktualisieren'];
    }
}

/**
 * Streamer löschen
 */
function deleteStreamer($id) {
    $result = executeQuery("DELETE FROM twitch_streamers WHERE id = :id", ['id' => $id]);
    
    if ($result) {
        return ['success' => true, 'message' => 'Streamer gelöscht'];
    } else {
        return ['success' => false, 'message' => 'Fehler beim Löschen'];
    }
}
?>