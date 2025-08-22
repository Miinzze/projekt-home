<?php
require_once '../../config/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Nur POST-Requests erlauben
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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
    // JSON Input lesen
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data');
    }
    
    // Validierung der Eingabedaten
    if (!isset($data['application_id'])) {
        throw new Exception('Missing application_id parameter');
    }
    
    $applicationId = (int)$data['application_id'];
    $appointmentDate = $data['appointment_date'] ?? null;
    $appointmentTime = $data['appointment_time'] ?? null;
    $customMessage = $data['custom_message'] ?? '';
    
    if ($applicationId <= 0) {
        throw new Exception('Invalid application ID');
    }
    
    // Bewerbung laden
    $application = fetchOne("
        SELECT * FROM whitelist_applications 
        WHERE id = :id AND status = 'pending'
    ", ['id' => $applicationId]);
    
    if (!$application) {
        throw new Exception('Bewerbung nicht gefunden oder nicht im Status "pending"');
    }
    
    // Discord Bot-Konfiguration prüfen
    $botToken = getServerSetting('discord_bot_token');
    $botEnabled = getServerSetting('discord_bot_enabled', '0');
    $serverName = getServerSetting('server_name', 'Zombie RP Server');
    
    if (!$botEnabled || empty($botToken)) {
        throw new Exception('Discord Bot ist nicht konfiguriert oder deaktiviert');
    }
    
    // Termin-Datum/Zeit verarbeiten
    $appointmentDateTime = null;
    if ($appointmentDate && $appointmentTime) {
        $appointmentDateTime = $appointmentDate . ' ' . $appointmentTime;
        if (!strtotime($appointmentDateTime)) {
            throw new Exception('Ungültiges Datum/Zeit Format');
        }
    }
    
    // Nachricht erstellen
    $messageTemplate = $customMessage ?: getServerSetting(
        'appointment_message_template', 
        'Hallo {username}!\n\nDeine Whitelist-Bewerbung wurde geprüft und du bist für ein Gespräch vorgesehen.\n\nTermin: {appointment_date}\nUhrzeit: {appointment_time}\n\nBitte melde dich zur angegebenen Zeit im Discord-Channel #whitelist-gespräche.\n\nViel Erfolg!\nDein {server_name} Team'
    );
    
    // Platzhalter ersetzen
    $message = str_replace([
        '{username}',
        '{server_name}',
        '{appointment_date}',
        '{appointment_time}',
        '{appointment_datetime}'
    ], [
        $application['discord_username'],
        $serverName,
        $appointmentDate ?: 'Wird noch bekannt gegeben',
        $appointmentTime ?: 'Wird noch bekannt gegeben',
        $appointmentDateTime ? date('d.m.Y H:i', strtotime($appointmentDateTime)) : 'Wird noch bekannt gegeben'
    ], $messageTemplate);
    
    // Discord PM senden
    $discordResult = sendDiscordDirectMessage($application['discord_id'], $message, $botToken);
    
    if (!$discordResult['success']) {
        throw new Exception('Discord-Nachricht konnte nicht gesendet werden: ' . $discordResult['error']);
    }
    
    // Bewerbung aktualisieren
    $updateData = [
        'appointment_message' => $message,
        'appointment_sent_at' => date('Y-m-d H:i:s'),
        'status' => 'closed',
        'reviewed_by' => getCurrentUser()['id'],
        'reviewed_at' => date('Y-m-d H:i:s')
    ];
    
    if ($appointmentDateTime) {
        $updateData['appointment_date'] = $appointmentDateTime;
    }
    
    $result = updateData('whitelist_applications', $updateData, 'id = :id', ['id' => $applicationId]);
    
    if (!$result) {
        throw new Exception('Bewerbung konnte nicht aktualisiert werden');
    }
    
    // Admin-Aktivität protokollieren
    if (function_exists('logAdminActivity')) {
        logAdminActivity(
            getCurrentUser()['id'],
            'whitelist_appointment_sent',
            "Termin-Nachricht an {$application['discord_username']} gesendet",
            'whitelist_application',
            $applicationId,
            null,
            [
                'appointment_date' => $appointmentDateTime,
                'message_length' => strlen($message),
                'discord_id' => $application['discord_id']
            ]
        );
    }
    
    // Erfolgreiche Response
    echo json_encode([
        'success' => true,
        'message' => 'Termin-Nachricht erfolgreich gesendet',
        'appointment_date' => $appointmentDateTime,
        'discord_username' => $application['discord_username'],
        'timestamp' => time()
    ]);
    
} catch (Exception $e) {
    // Fehler protokollieren
    error_log('Discord appointment error: ' . $e->getMessage());
    
    // Fehler-Response
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => time()
    ]);
}

/**
 * Discord Direct Message senden
 */
function sendDiscordDirectMessage($userId, $message, $botToken) {
    try {
        // 1. DM Channel erstellen
        $dmChannelData = [
            'recipient_id' => $userId
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://discord.com/api/v10/users/@me/channels');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dmChannelData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bot ' . $botToken,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            return [
                'success' => false,
                'error' => 'DM Channel konnte nicht erstellt werden (HTTP ' . $httpCode . ')'
            ];
        }
        
        $dmChannel = json_decode($response, true);
        if (!$dmChannel || !isset($dmChannel['id'])) {
            return [
                'success' => false,
                'error' => 'Ungültige DM Channel Response'
            ];
        }
        
        // 2. Nachricht in DM Channel senden
        $messageData = [
            'content' => $message,
            'flags' => 0
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://discord.com/api/v10/channels/' . $dmChannel['id'] . '/messages');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($messageData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bot ' . $botToken,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 || $httpCode === 201) {
            return [
                'success' => true,
                'message_id' => json_decode($response, true)['id'] ?? null
            ];
        } else {
            $errorResponse = json_decode($response, true);
            return [
                'success' => false,
                'error' => 'Nachricht konnte nicht gesendet werden (HTTP ' . $httpCode . '): ' . 
                          ($errorResponse['message'] ?? 'Unbekannter Fehler')
            ];
        }
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => 'Discord API Fehler: ' . $e->getMessage()
        ];
    }
}
?>