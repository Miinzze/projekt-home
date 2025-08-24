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
    
    if ($applicationId <= 0) {
        throw new Exception('Invalid application ID');
    }
    
    if (empty($appointmentDate) || empty($appointmentTime)) {
        throw new Exception('Datum und Uhrzeit sind erforderlich');
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
    
    // Termin-Datum/Zeit kombinieren und validieren
    $appointmentDateTime = $appointmentDate . ' ' . $appointmentTime;
    if (!strtotime($appointmentDateTime)) {
        throw new Exception('Ungültiges Datum/Zeit Format');
    }
    
    // Überprüfen ob das Datum in der Vergangenheit liegt
    if (strtotime($appointmentDateTime) < time()) {
        throw new Exception('Der Termin kann nicht in der Vergangenheit liegen');
    }
    
    // Termin-Nachricht aus den Einstellungen laden
    $messageTemplate = getServerSetting(
        'appointment_message_template', 
        'Hallo {username}!\n\nDeine Whitelist-Bewerbung wurde geprüft und du bist für ein Gespräch vorgesehen.\n\nTermin: {appointment_date}\nUhrzeit: {appointment_time}\n\nBitte melde dich zur angegebenen Zeit im Discord-Channel #whitelist-gespräche.\n\nViel Erfolg!\nDein {server_name} Team'
    );
    
    // Datum formatieren
    $formattedDate = date('d.m.Y', strtotime($appointmentDate));
    $formattedTime = date('H:i', strtotime($appointmentTime));
    $formattedDateTime = date('d.m.Y H:i', strtotime($appointmentDateTime));
    
    // Platzhalter in der Nachricht ersetzen
    $message = str_replace([
        '{username}',
        '{server_name}',
        '{appointment_date}',
        '{appointment_time}',
        '{appointment_datetime}'
    ], [
        $application['discord_username'],
        $serverName,
        $formattedDate,
        $formattedTime,
        $formattedDateTime
    ], $messageTemplate);
    
    // Discord PM senden
    $discordResult = sendDiscordDirectMessage($application['discord_id'], $message, $botToken);
    
    if (!$discordResult['success']) {
        throw new Exception('Discord-Nachricht konnte nicht gesendet werden: ' . $discordResult['error']);
    }
    
    // Bewerbung aktualisieren
    $updateData = [
        'appointment_date' => $appointmentDateTime,
        'appointment_message' => $message,
        'appointment_sent_at' => date('Y-m-d H:i:s'),
        'status' => 'closed',
        'reviewed_by' => getCurrentUser()['id'],
        'reviewed_at' => date('Y-m-d H:i:s')
    ];
    
    $result = updateData('whitelist_applications', $updateData, 'id = :id', ['id' => $applicationId]);
    
    if (!$result) {
        throw new Exception('Bewerbung konnte nicht aktualisiert werden');
    }
    
    // Admin-Aktivität protokollieren
    if (function_exists('logAdminActivity')) {
        logAdminActivity(
            getCurrentUser()['id'],
            'whitelist_appointment_sent',
            "Termin-Nachricht an {$application['discord_username']} gesendet für {$formattedDateTime}",
            'whitelist_application',
            $applicationId,
            null,
            [
                'appointment_date' => $appointmentDateTime,
                'message_length' => strlen($message),
                'discord_id' => $application['discord_id'],
                'discord_username' => $application['discord_username']
            ]
        );
    }
    
    // Erfolgreiche Response
    echo json_encode([
        'success' => true,
        'message' => 'Termin-Nachricht erfolgreich gesendet',
        'appointment_date' => $appointmentDateTime,
        'formatted_date' => $formattedDateTime,
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
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            return [
                'success' => false,
                'error' => 'cURL Fehler: ' . $curlError
            ];
        }
        
        if ($httpCode !== 200) {
            $errorResponse = json_decode($response, true);
            return [
                'success' => false,
                'error' => 'DM Channel konnte nicht erstellt werden (HTTP ' . $httpCode . '): ' . 
                          ($errorResponse['message'] ?? 'Unbekannter Fehler')
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
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            return [
                'success' => false,
                'error' => 'cURL Fehler beim Senden: ' . $curlError
            ];
        }
        
        if ($httpCode === 200 || $httpCode === 201) {
            $messageResponse = json_decode($response, true);
            return [
                'success' => true,
                'message_id' => $messageResponse['id'] ?? null
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