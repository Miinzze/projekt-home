<?php
require_once '../../config/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

try {
    // Server-Konfiguration laden
    $serverIP = getServerSetting('server_ip', 'localhost');
    $serverPort = getServerSetting('server_port', '30120');
    $maxPlayers = (int)getServerSetting('max_players', '64');
    
    // FiveM Server API URL
    $apiUrl = "http://{$serverIP}:{$serverPort}/dynamic.json";
    
    // API-Anfrage mit Timeout
    $context = stream_context_create([
        'http' => [
            'timeout' => 5, // 5 Sekunden Timeout
            'method' => 'GET',
            'header' => 'User-Agent: FiveM-WebPanel/1.0'
        ]
    ]);
    
    $response = @file_get_contents($apiUrl, false, $context);
    
    if ($response === false) {
        // Server offline oder nicht erreichbar
        echo json_encode([
            'success' => false,
            'online' => false,
            'current_players' => 0,
            'max_players' => $maxPlayers,
            'server_name' => getServerSetting('server_name', 'Unknown Server'),
            'error' => 'Server nicht erreichbar'
        ]);
        exit;
    }
    
    $serverData = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Ungültige JSON-Antwort vom Server');
    }
    
    // Spielerzahl extrahieren
    $currentPlayers = 0;
    if (isset($serverData['clients'])) {
        $currentPlayers = (int)$serverData['clients'];
    } elseif (isset($serverData['players'])) {
        $currentPlayers = is_array($serverData['players']) ? count($serverData['players']) : (int)$serverData['players'];
    }
    
    // Server-Name extrahieren (falls vorhanden)
    $serverName = getServerSetting('server_name', 'Unknown Server');
    if (isset($serverData['hostname'])) {
        $serverName = $serverData['hostname'];
    }
    
    // Spielerzahl in Datenbank aktualisieren
    setServerSetting('current_players', $currentPlayers);
    setServerSetting('last_status_check', date('Y-m-d H:i:s'));
    
    // Server als online markieren
    setServerSetting('is_online', '1');
    
    echo json_encode([
        'success' => true,
        'online' => true,
        'current_players' => $currentPlayers,
        'max_players' => $maxPlayers,
        'server_name' => $serverName,
        'last_updated' => date('H:i:s'),
        'map_name' => $serverData['mapname'] ?? null,
        'game_type' => $serverData['gametype'] ?? null,
        'resources' => count($serverData['resources'] ?? [])
    ]);
    
} catch (Exception $e) {
    error_log('FiveM API Error: ' . $e->getMessage());
    
    // Server als offline markieren
    setServerSetting('is_online', '0');
    
    echo json_encode([
        'success' => false,
        'online' => false,
        'current_players' => 0,
        'max_players' => getServerSetting('max_players', '64'),
        'server_name' => getServerSetting('server_name', 'Unknown Server'),
        'error' => $e->getMessage()
    ]);
}
?>