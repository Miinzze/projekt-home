<?php
/**
 * AJAX Endpoint für automatische Stream-Status Updates
 * Speichern als: admin/ajax/update-stream-status.php
 */

require_once '../../config/config.php';
require_once '../../config/twitch_api.php';

// Nur AJAX-Requests erlauben
if (!isAjaxRequest()) {
    http_response_code(400);
    die('Bad Request');
}

// Rate Limiting - Max. 1 Request pro Minute pro IP
$clientIP = getUserIP();
$rateLimitKey = 'stream_update_' . md5($clientIP);
$lastUpdate = $_SESSION[$rateLimitKey] ?? 0;

if (time() - $lastUpdate < 60) {
    http_response_code(429);
    echo json_encode([
        'success' => false,
        'error' => 'Rate limit exceeded. Please wait before updating again.'
    ]);
    exit();
}

$_SESSION[$rateLimitKey] = time();

// Content-Type Header setzen
header('Content-Type: application/json');

try {
    // Prüfen ob automatische Updates aktiviert sind
    $autoUpdate = getServerSetting('twitch_auto_update', '1');
    if (!$autoUpdate) {
        echo json_encode([
            'success' => false,
            'error' => 'Automatic updates are disabled'
        ]);
        exit();
    }
    
    // Twitch API initialisieren
    $twitchAPI = getTwitchAPI();
    if (!$twitchAPI) {
        throw new Exception('Twitch API not configured');
    }
    
    // Letztes Update prüfen
    $lastAutoUpdate = getServerSetting('twitch_last_auto_update', '0');
    $updateInterval = (int)getServerSetting('twitch_update_interval', '300');
    
    if ((time() - (int)$lastAutoUpdate) < $updateInterval) {
        echo json_encode([
            'success' => true,
            'updated' => false,
            'message' => 'Update interval not reached',
            'next_update' => (int)$lastAutoUpdate + $updateInterval,
            'time_remaining' => ((int)$lastAutoUpdate + $updateInterval) - time()
        ]);
        exit();
    }
    
    // Stream-Status aktualisieren
    $result = $twitchAPI->updateAllStreamersStatus();
    
    if ($result['success']) {
        // Timestamp für letztes Update setzen
        setServerSetting('twitch_last_auto_update', time());
        setServerSetting('twitch_last_update_result', json_encode($result));
        
        // Log für Admin-Aktivität
        if (function_exists('logAdminActivity') && isset($_SESSION['admin_id'])) {
            logAdminActivity(
                $_SESSION['admin_id'],
                'stream_auto_update',
                'Automatic stream status update: ' . $result['message'],
                'system',
                null,
                null,
                [
                    'live_count' => $result['live_count'] ?? 0,
                    'total_count' => $result['total_count'] ?? 0,
                    'trigger' => 'ajax_auto_update'
                ]
            );
        }
        
        echo json_encode([
            'success' => true,
            'updated' => true,
            'message' => $result['message'],
            'live_count' => $result['live_count'] ?? 0,
            'total_count' => $result['total_count'] ?? 0,
            'last_update' => time(),
            'next_update' => time() + $updateInterval
        ]);
    } else {
        // Fehler loggen
        error_log('Twitch Auto-Update Error: ' . ($result['error'] ?? 'Unknown error'));
        
        echo json_encode([
            'success' => false,
            'updated' => false,
            'error' => $result['error'] ?? 'Unknown error occurred',
            'last_error' => time()
        ]);
    }
    
} catch (Exception $e) {
    // Fehler-Behandlung
    error_log('Stream Status Update Error: ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error: ' . $e->getMessage(),
        'code' => $e->getCode()
    ]);
}
?>