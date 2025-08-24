<?php
require_once '../../config/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

if (!isLoggedIn() || !hasPermission('whitelist.read')) {
    http_response_code(403);
    echo json_encode([
        'success' => false, 
        'error' => 'Keine Berechtigung',
        'code' => 'FORBIDDEN'
    ]);
    exit;
}

try {
    // Bot-Konfiguration laden
    $botToken = getServerSetting('discord_bot_token', '');
    $botEnabled = getServerSetting('discord_bot_enabled', '0');
    
    error_log('🤖 Discord Bot Check - Enabled: ' . $botEnabled . ', Token length: ' . strlen($botToken));
    
    if ($botEnabled !== '1') {
        echo json_encode([
            'success' => false,
            'error' => 'Discord Bot ist deaktiviert',
            'code' => 'BOT_DISABLED',
            'bot_enabled' => false,
            'bot_configured' => !empty($botToken)
        ]);
        exit;
    }
    
    if (empty($botToken)) {
        echo json_encode([
            'success' => false,
            'error' => 'Discord Bot Token nicht konfiguriert',
            'code' => 'BOT_NOT_CONFIGURED',
            'bot_enabled' => true,
            'bot_configured' => false
        ]);
        exit;
    }
    
    // Bot Status über Discord API prüfen
    error_log('🤖 Prüfe Discord Bot via API...');
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://discord.com/api/v10/users/@me');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bot ' . $botToken,
        'User-Agent: WhitelistBot/1.0'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if (!empty($curlError)) {
        error_log('❌ CURL Error: ' . $curlError);
        echo json_encode([
            'success' => false,
            'error' => 'Verbindungsfehler: ' . $curlError,
            'code' => 'CONNECTION_ERROR',
            'bot_enabled' => true,
            'bot_configured' => true
        ]);
        exit;
    }
    
    error_log("🤖 Discord API Response: HTTP $httpCode - " . substr($response, 0, 200));
    
    if ($httpCode === 200) {
        $botData = json_decode($response, true);
        
        if ($botData && isset($botData['username'])) {
            error_log('✅ Bot erfolgreich verifiziert: ' . $botData['username']);
            
            echo json_encode([
                'success' => true,
                'message' => 'Discord Bot ist bereit',
                'bot_name' => $botData['username'],
                'bot_id' => $botData['id'] ?? null,
                'discriminator' => $botData['discriminator'] ?? '0000',
                'verified' => $botData['verified'] ?? false,
                'bot_enabled' => true,
                'bot_configured' => true,
                'can_send_messages' => true
            ]);
        } else {
            error_log('❌ Ungültige Bot-Response: ' . $response);
            echo json_encode([
                'success' => false,
                'error' => 'Ungültige Discord API Response',
                'code' => 'INVALID_RESPONSE',
                'bot_enabled' => true,
                'bot_configured' => true,
                'raw_response' => DEBUG_MODE ? $response : null
            ]);
        }
    } else {
        // Spezifische Discord-Fehlercodes behandeln
        $errorData = json_decode($response, true);
        $errorMessage = 'Bot nicht erreichbar (HTTP ' . $httpCode . ')';
        $errorCode = 'HTTP_' . $httpCode;
        
        if ($errorData && isset($errorData['message'])) {
            $errorMessage .= ': ' . $errorData['message'];
            
            // Discord-spezifische Fehlercodes
            if (isset($errorData['code'])) {
                switch ($errorData['code']) {
                    case 0:
                        $errorMessage = 'Bot Token ist ungültig oder abgelaufen';
                        $errorCode = 'INVALID_TOKEN';
                        break;
                    case 40001:
                        $errorMessage = 'Bot hat keine notwendigen Berechtigungen';
                        $errorCode = 'INSUFFICIENT_PERMISSIONS';
                        break;
                    case 50001:
                        $errorMessage = 'Bot hat keinen Zugriff auf diese Ressource';
                        $errorCode = 'MISSING_ACCESS';
                        break;
                }
            }
        } else if ($httpCode === 401) {
            $errorMessage = 'Bot Token ist ungültig oder abgelaufen';
            $errorCode = 'INVALID_TOKEN';
        } else if ($httpCode === 429) {
            $errorMessage = 'Discord API Rate-Limit erreicht';
            $errorCode = 'RATE_LIMITED';
        }
        
        error_log("❌ Discord API Error: $errorMessage");
        
        echo json_encode([
            'success' => false,
            'error' => $errorMessage,
            'code' => $errorCode,
            'http_code' => $httpCode,
            'bot_enabled' => true,
            'bot_configured' => true,
            'can_send_messages' => false,
            'raw_response' => DEBUG_MODE ? $response : null
        ]);
    }
    
} catch (Exception $e) {
    error_log('❌ Exception in Discord Bot Check: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => 'Server-Fehler beim Prüfen des Discord Bots: ' . $e->getMessage(),
        'code' => 'SERVER_ERROR',
        'bot_enabled' => false,
        'bot_configured' => false,
        'can_send_messages' => false,
        'debug_info' => DEBUG_MODE ? [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ] : null
    ]);
}
?>