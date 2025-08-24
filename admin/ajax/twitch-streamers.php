<?php
/**
 * AJAX Endpoint für Twitch Streamer Management
 * Speichern als: admin/ajax/twitch-streamers.php
 */
require_once '../../config/config.php';
require_once '../../config/twitch_api.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Berechtigung prüfen
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Nicht authentifiziert']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'get_streamers':
            handleGetStreamers();
            break;
            
        case 'get_live_streamers':
            handleGetLiveStreamers();
            break;
            
        case 'add_streamer':
            if (!hasPermission('content.create')) {
                throw new Exception('Keine Berechtigung zum Hinzufügen von Streamern');
            }
            handleAddStreamer();
            break;
            
        case 'update_streamer':
            if (!hasPermission('content.update')) {
                throw new Exception('Keine Berechtigung zum Bearbeiten von Streamern');
            }
            handleUpdateStreamer();
            break;
            
        case 'delete_streamer':
            if (!hasPermission('content.delete')) {
                throw new Exception('Keine Berechtigung zum Löschen von Streamern');
            }
            handleDeleteStreamer();
            break;
            
        case 'validate_streamer':
            if (!hasPermission('content.create')) {
                throw new Exception('Keine Berechtigung');
            }
            handleValidateStreamer();
            break;
            
        case 'refresh_streams':
            if (!hasPermission('content.update')) {
                throw new Exception('Keine Berechtigung zum Aktualisieren der Streams');
            }
            handleRefreshStreams();
            break;
            
        case 'toggle_streamer_status':
            if (!hasPermission('content.update')) {
                throw new Exception('Keine Berechtigung');
            }
            handleToggleStreamerStatus();
            break;
            
        default:
            throw new Exception('Unbekannte Aktion: ' . $action);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => time()
    ]);
}

/**
 * Alle Streamer abrufen
 */
function handleGetStreamers() {
    $streamers = getAllStreamers();
    
    // Zusätzliche Daten anreichern
    foreach ($streamers as &$streamer) {
        $streamer['thumbnail_url'] = null;
        $streamer['stream_url'] = 'https://twitch.tv/' . $streamer['streamer_name'];
        
        // Wenn live, Thumbnail URL generieren
        if ($streamer['is_currently_live'] && !empty($streamer['twitch_user_id'])) {
            $twitchAPI = getTwitchAPI();
            if ($twitchAPI) {
                $streamer['thumbnail_url'] = $twitchAPI->getStreamThumbnail(
                    $streamer['streamer_name'], 440, 248
                );
            }
        }
        
        // Zeitstempel formatieren
        if ($streamer['last_live_check']) {
            $streamer['last_live_check_formatted'] = date('d.m.Y H:i', strtotime($streamer['last_live_check']));
        }
        
        if ($streamer['created_at']) {
            $streamer['created_at_formatted'] = date('d.m.Y H:i', strtotime($streamer['created_at']));
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => $streamers,
        'total' => count($streamers),
        'live_count' => count(array_filter($streamers, fn($s) => $s['is_currently_live']))
    ]);
}

/**
 * Nur Live Streamer abrufen
 */
function handleGetLiveStreamers() {
    $liveStreamers = getLiveStreamers();
    
    // Thumbnail URLs hinzufügen
    $twitchAPI = getTwitchAPI();
    foreach ($liveStreamers as &$streamer) {
        $streamer['stream_url'] = 'https://twitch.tv/' . $streamer['streamer_name'];
        
        if ($twitchAPI && !empty($streamer['twitch_user_id'])) {
            $streamer['thumbnail_url'] = $twitchAPI->getStreamThumbnail(
                $streamer['streamer_name'], 440, 248
            );
        }
        
        // Viewer count formatieren
        if ($streamer['viewer_count'] > 0) {
            if ($streamer['viewer_count'] >= 1000) {
                $streamer['viewer_count_formatted'] = number_format($streamer['viewer_count'] / 1000, 1) . 'K';
            } else {
                $streamer['viewer_count_formatted'] = number_format($streamer['viewer_count']);
            }
        }
        
        // Live seit berechnen
        if ($streamer['last_live_check']) {
            $liveTime = time() - strtotime($streamer['last_live_check']);
            if ($liveTime < 3600) {
                $streamer['live_duration'] = floor($liveTime / 60) . ' Min';
            } else {
                $streamer['live_duration'] = floor($liveTime / 3600) . 'h ' . floor(($liveTime % 3600) / 60) . 'min';
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => $liveStreamers,
        'count' => count($liveStreamers),
        'widget_enabled' => getServerSetting('twitch_widget_enabled', '1') === '1',
        'last_update' => date('H:i:s')
    ]);
}

/**
 * Neuen Streamer hinzufügen
 */
function handleAddStreamer() {
    $streamerName = trim($_POST['streamer_name'] ?? '');
    $displayName = trim($_POST['display_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $priorityOrder = (int)($_POST['priority_order'] ?? 0);
    
    if (empty($streamerName)) {
        throw new Exception('Streamer-Name ist erforderlich');
    }
    
    // Streamer-Name normalisieren (nur alphanumerische Zeichen und Unterstriche)
    if (!preg_match('/^[a-zA-Z0-9_]{1,25}$/', $streamerName)) {
        throw new Exception('Ungültiger Streamer-Name. Nur Buchstaben, Zahlen und Unterstriche (max. 25 Zeichen)');
    }
    
    $result = addStreamer($streamerName, $displayName, $description, $priorityOrder);
    
    if ($result['success']) {
        logAdminActivity(
            getCurrentUser()['id'],
            'streamer_added',
            "Twitch Streamer '{$streamerName}' hinzugefügt",
            'streamer',
            $result['streamer_id']
        );
    }
    
    echo json_encode($result);
}

/**
 * Streamer bearbeiten
 */
function handleUpdateStreamer() {
    $streamerId = (int)($_POST['streamer_id'] ?? 0);
    
    if ($streamerId <= 0) {
        throw new Exception('Ungültige Streamer-ID');
    }
    
    $streamer = getStreamerById($streamerId);
    if (!$streamer) {
        throw new Exception('Streamer nicht gefunden');
    }
    
    $updateData = [];
    
    if (isset($_POST['display_name'])) {
        $updateData['display_name'] = trim($_POST['display_name']);
    }
    
    if (isset($_POST['description'])) {
        $updateData['description'] = trim($_POST['description']);
    }
    
    if (isset($_POST['priority_order'])) {
        $updateData['priority_order'] = (int)$_POST['priority_order'];
    }
    
    if (isset($_POST['is_active'])) {
        $updateData['is_active'] = $_POST['is_active'] ? 1 : 0;
    }
    
    if (empty($updateData)) {
        throw new Exception('Keine Daten zum Aktualisieren');
    }
    
    $result = updateStreamer($streamerId, $updateData);
    
    if ($result['success']) {
        logAdminActivity(
            getCurrentUser()['id'],
            'streamer_updated',
            "Twitch Streamer '{$streamer['streamer_name']}' bearbeitet",
            'streamer',
            $streamerId
        );
    }
    
    echo json_encode($result);
}

/**
 * Streamer löschen
 */
function handleDeleteStreamer() {
    $streamerId = (int)($_POST['streamer_id'] ?? $_GET['streamer_id'] ?? 0);
    
    if ($streamerId <= 0) {
        throw new Exception('Ungültige Streamer-ID');
    }
    
    $streamer = getStreamerById($streamerId);
    if (!$streamer) {
        throw new Exception('Streamer nicht gefunden');
    }
    
    $result = deleteStreamer($streamerId);
    
    if ($result['success']) {
        logAdminActivity(
            getCurrentUser()['id'],
            'streamer_deleted',
            "Twitch Streamer '{$streamer['streamer_name']}' gelöscht",
            'streamer',
            $streamerId,
            $streamer
        );
    }
    
    echo json_encode($result);
}

/**
 * Streamer validieren
 */
function handleValidateStreamer() {
    $streamerName = trim($_GET['streamer_name'] ?? $_POST['streamer_name'] ?? '');
    
    if (empty($streamerName)) {
        throw new Exception('Streamer-Name ist erforderlich');
    }
    
    $twitchAPI = getTwitchAPI();
    if (!$twitchAPI) {
        throw new Exception('Twitch API nicht verfügbar');
    }
    
    $result = $twitchAPI->validateStreamer($streamerName);
    echo json_encode($result);
}

/**
 * Stream-Status aller Streamer aktualisieren
 */
function handleRefreshStreams() {
    $twitchAPI = getTwitchAPI();
    if (!$twitchAPI) {
        throw new Exception('Twitch API nicht verfügbar');
    }
    
    $result = $twitchAPI->updateAllStreamersStatus();
    
    if ($result['success']) {
        logAdminActivity(
            getCurrentUser()['id'],
            'streams_refreshed',
            "Stream-Status aktualisiert: {$result['live_count']} von {$result['total_count']} Streamern sind live"
        );
    }
    
    echo json_encode($result);
}

/**
 * Streamer Aktiv/Inaktiv umschalten
 */
function handleToggleStreamerStatus() {
    $streamerId = (int)($_POST['streamer_id'] ?? 0);
    
    if ($streamerId <= 0) {
        throw new Exception('Ungültige Streamer-ID');
    }
    
    $streamer = getStreamerById($streamerId);
    if (!$streamer) {
        throw new Exception('Streamer nicht gefunden');
    }
    
    $newStatus = $streamer['is_active'] ? 0 : 1;
    $statusText = $newStatus ? 'aktiviert' : 'deaktiviert';
    
    $result = updateStreamer($streamerId, ['is_active' => $newStatus]);
    
    if ($result['success']) {
        $result['message'] = "Streamer '{$streamer['streamer_name']}' wurde {$statusText}";
        $result['new_status'] = $newStatus;
        
        logAdminActivity(
            getCurrentUser()['id'],
            'streamer_status_changed',
            "Streamer '{$streamer['streamer_name']}' {$statusText}",
            'streamer',
            $streamerId
        );
    }
    
    echo json_encode($result);
}
?>

<?php
/**
 * Separater AJAX Endpoint für öffentlichen Zugriff (ohne Admin-Login)
 * Speichern als: api/twitch-streams.php
 */

// Für öffentlichen Zugriff ohne Admin-Login
if (basename($_SERVER['PHP_SELF']) === 'twitch-streams.php') {
    require_once '../config/config.php';
    require_once '../config/twitch_api.php';
    
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET');
    header('Cache-Control: public, max-age=60'); // 1 Minute Cache
    
    try {
        // Nur GET-Requests für Live-Streams
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            throw new Exception('Nur GET-Requests erlaubt');
        }
        
        // Widget aktiviert prüfen
        if (getServerSetting('twitch_widget_enabled', '1') !== '1') {
            echo json_encode([
                'success' => true,
                'data' => [],
                'count' => 0,
                'widget_enabled' => false,
                'message' => 'Twitch Widget ist deaktiviert'
            ]);
            exit;
        }
        
        $liveStreamers = getLiveStreamers();
        
        // Thumbnail URLs und zusätzliche Daten hinzufügen
        $twitchAPI = getTwitchAPI();
        foreach ($liveStreamers as &$streamer) {
            $streamer['stream_url'] = 'https://twitch.tv/' . $streamer['streamer_name'];
            
            if ($twitchAPI && !empty($streamer['twitch_user_id'])) {
                $streamer['thumbnail_url'] = $twitchAPI->getStreamThumbnail(
                    $streamer['streamer_name'], 440, 248
                );
            }
            
            // Nur öffentlich relevante Daten zurückgeben
            $publicData = [
                'id' => $streamer['id'],
                'streamer_name' => $streamer['streamer_name'],
                'display_name' => $streamer['display_name'],
                'description' => $streamer['description'],
                'stream_url' => $streamer['stream_url'],
                'thumbnail_url' => $streamer['thumbnail_url'] ?? null,
                'profile_image_url' => $streamer['profile_image_url'],
                'last_stream_title' => $streamer['last_stream_title'],
                'last_stream_game' => $streamer['last_stream_game'],
                'viewer_count' => $streamer['viewer_count'],
                'viewer_count_formatted' => $streamer['viewer_count'] >= 1000 
                    ? number_format($streamer['viewer_count'] / 1000, 1) . 'K' 
                    : number_format($streamer['viewer_count']),
                'priority_order' => $streamer['priority_order']
            ];
            
            $streamer = $publicData;
        }
        
        echo json_encode([
            'success' => true,
            'data' => $liveStreamers,
            'count' => count($liveStreamers),
            'widget_enabled' => true,
            'widget_title' => getServerSetting('twitch_widget_title', 'Live Streams'),
            'show_viewer_count' => getServerSetting('twitch_show_viewer_count', '1') === '1',
            'show_game_name' => getServerSetting('twitch_show_game_name', '1') === '1',
            'last_update' => date('H:i:s'),
            'cache_time' => 60
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'widget_enabled' => false
        ]);
    }
}
?>