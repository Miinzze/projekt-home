<?php
require_once '../../config/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Nur GET-Requests erlauben
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Berechtigung prüfen
if (!isLoggedIn() || !hasPermission('whitelist.update')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Keine Berechtigung']);
    exit;
}

try {
    // Discord Bot-Konfiguration laden
    $botToken = getServerSetting('discord_bot_token');
    $botEnabled = getServerSetting('discord_bot_enabled', '0');
    
    if (!$botEnabled) {
        throw new Exception('Discord Bot ist deaktiviert');
    }
    
    if (empty($botToken)) {
        throw new Exception('Discord Bot Token ist nicht konfiguriert');
    }
    
    // Bot Status über Discord API prüfen
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://discord.com/api/v10/users/@me');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bot ' . $botToken,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        throw new Exception('Netzwerkfehler: ' . $curlError);
    }
    
    if ($httpCode !== 200) {
        $errorResponse = json_decode($response, true);
        $errorMessage = isset($errorResponse['message']) ? $errorResponse['message'] : 'HTTP ' . $httpCode;
        throw new Exception('Discord API Fehler: ' . $errorMessage);
    }
    
    $botData = json_decode($response, true);
    
    if (!$botData || !isset($botData['id'])) {
        throw new Exception('Ungültige Bot-Daten erhalten');
    }
    
    // Bot verfügbar
    echo json_encode([
        'success' => true,
        'bot_available' => true,
        'bot_username' => $botData['username'] ?? 'Unbekannt',
        'bot_id' => $botData['id'],
        'bot_verified' => $botData['verified'] ?? false,
        'message' => 'Discord Bot ist verfügbar und bereit'
    ]);
    
} catch (Exception $e) {
    // Bot nicht verfügbar
    error_log('Discord bot check error: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'bot_available' => false,
        'error' => $e->getMessage(),
        'troubleshooting' => [
            'Bot Token prüfen',
            'Bot Permissions verifizieren', 
            'Discord Server Status prüfen',
            'Internet-Verbindung testen'
        ]
    ]);
}
?>