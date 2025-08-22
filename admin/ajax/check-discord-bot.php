<?php
require_once '../../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !hasPermission('settings.read')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Keine Berechtigung']);
    exit;
}

try {
    $botToken = getServerSetting('discord_bot_token');
    $botEnabled = getServerSetting('discord_bot_enabled', '0');
    
    if (!$botEnabled) {
        echo json_encode([
            'success' => false,
            'error' => 'Discord Bot ist deaktiviert'
        ]);
        exit;
    }
    
    if (empty($botToken)) {
        echo json_encode([
            'success' => false,
            'error' => 'Discord Bot Token nicht konfiguriert'
        ]);
        exit;
    }
    
    // Bot Status prüfen
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://discord.com/api/v10/users/@me');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bot ' . $botToken
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $botData = json_decode($response, true);
        echo json_encode([
            'success' => true,
            'bot_name' => $botData['username'] ?? 'Unknown Bot',
            'bot_id' => $botData['id'] ?? null,
            'discriminator' => $botData['discriminator'] ?? null,
            'verified' => $botData['verified'] ?? false
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Bot nicht erreichbar oder ungültiger Token (HTTP ' . $httpCode . ')'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Verbindungsfehler: ' . $e->getMessage()
    ]);
}
?>