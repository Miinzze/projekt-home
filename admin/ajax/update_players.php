<?php
require_once '../../config/config.php';

// Header für JSON Response setzen
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Nur POST-Requests erlauben
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    // JSON Input lesen
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data');
    }
    
    // Validierung der Eingabedaten
    if (!isset($data['current_players'])) {
        throw new Exception('Missing current_players parameter');
    }
    
    $currentPlayers = (int)$data['current_players'];
    $maxPlayers = (int)getServerSetting('max_players', 64);
    
    // Plausibilitätsprüfung
    if ($currentPlayers < 0 || $currentPlayers > $maxPlayers) {
        throw new Exception('Invalid player count');
    }
    
    // Rate Limiting (einfache Implementierung)
    $userIP = getUserIP();
    $rateLimitKey = 'player_update_' . $userIP;
    
    if (isset($_SESSION[$rateLimitKey]) && 
        (time() - $_SESSION[$rateLimitKey]) < 5) {
        throw new Exception('Rate limit exceeded');
    }
    
    $_SESSION[$rateLimitKey] = time();
    
    // Player Count in Datenbank aktualisieren
    $result = setServerSetting('current_players', $currentPlayers);
    
    if (!$result) {
        throw new Exception('Database update failed');
    }
    
    // Zusätzliche Server-Statistiken aktualisieren
    setServerSetting('last_player_update', date('Y-m-d H:i:s'));
    
    // Erfolgreiche Response
    echo json_encode([
        'success' => true,
        'current_players' => $currentPlayers,
        'max_players' => $maxPlayers,
        'timestamp' => time(),
        'message' => 'Player count updated successfully'
    ]);
    
} catch (Exception $e) {
    // Fehler protokollieren
    error_log('Player update error: ' . $e->getMessage());
    
    // Fehler-Response
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => time()
    ]);
}
?>