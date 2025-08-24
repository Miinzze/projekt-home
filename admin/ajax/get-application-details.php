<?php
/**
 * Vereinfachte und robuste Version für Bewerbungsdetails
 */

// Error Reporting aktivieren für Debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../config/config.php';

// JSON Headers setzen
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Login prüfen
    if (!function_exists('isLoggedIn')) {
        throw new Exception('isLoggedIn function not found');
    }
    
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Nicht angemeldet']);
        exit;
    }
    
    // Berechtigung prüfen (falls Funktion existiert)
    if (function_exists('hasPermission')) {
        if (!hasPermission('whitelist.read')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Keine Berechtigung']);
            exit;
        }
    }
    
    // Application ID ermitteln
    $applicationId = 0;
    
    if (isset($_GET['id'])) {
        $applicationId = (int)$_GET['id'];
    } elseif (isset($_POST['id'])) {
        $applicationId = (int)$_POST['id'];
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = file_get_contents('php://input');
        if ($input) {
            $data = json_decode($input, true);
            if ($data && isset($data['id'])) {
                $applicationId = (int)$data['id'];
            }
        }
    }
    
    if ($applicationId <= 0) {
        throw new Exception('Ungültige Bewerbungs-ID: ' . $applicationId);
    }
    
    // Bewerbung laden - einfache Version
    if (!function_exists('fetchOne')) {
        throw new Exception('fetchOne function not found');
    }
    
    $application = fetchOne("
        SELECT * FROM whitelist_applications 
        WHERE id = ?
    ", [$applicationId]);
    
    if (!$application) {
        throw new Exception('Bewerbung nicht gefunden mit ID: ' . $applicationId);
    }
    
    // Avatar URL formatieren
    $avatarUrl = null;
    if (!empty($application['discord_avatar'])) {
        if (strpos($application['discord_avatar'], 'http') === 0) {
            $avatarUrl = $application['discord_avatar'];
        } else {
            $extension = (strpos($application['discord_avatar'], 'a_') === 0) ? 'gif' : 'png';
            $avatarUrl = "https://cdn.discordapp.com/avatars/{$application['discord_id']}/{$application['discord_avatar']}.{$extension}?size=128";
        }
    }
    
    // Einfache Response
    $response = [
        'success' => true,
        'application' => [
            'id' => (int)$application['id'],
            'discord_id' => (string)$application['discord_id'],
            'discord_username' => (string)($application['discord_username'] ?? 'Unbekannt'),
            'discord_avatar' => $avatarUrl,
            'status' => (string)($application['status'] ?? 'pending'),
            'score_percentage' => (float)($application['score_percentage'] ?? 0),
            'total_questions' => (int)($application['total_questions'] ?? 0),
            'correct_answers' => (int)($application['correct_answers'] ?? 0),
            'created_at' => (string)($application['created_at'] ?? ''),
            'reviewed_at' => $application['reviewed_at'] ?? null,
            'reviewed_by' => (int)($application['reviewed_by'] ?? 0),
            'notes' => (string)($application['notes'] ?? ''),
            'appointment_date' => $application['appointment_date'] ?? null,
            'appointment_message' => (string)($application['appointment_message'] ?? ''),
            'appointment_sent_at' => $application['appointment_sent_at'] ?? null,
            'ip_address' => (string)($application['ip_address'] ?? ''),
        ],
        'permissions' => [
            'can_update' => true,
            'can_approve' => true,
            'can_reject' => true,
            'can_delete' => true,
            'can_send_appointment' => ($application['status'] === 'pending')
        ],
        'discord_bot' => [
            'enabled' => true,
            'configured' => true,
            'can_send_messages' => true
        ]
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    error_log("Database error in get-application-details.php: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Datenbankfehler: ' . $e->getMessage(),
        'type' => 'database_error'
    ]);
    
} catch (Exception $e) {
    error_log("General error in get-application-details.php: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'type' => 'general_error'
    ]);
}

// Debug-Informationen
if (isset($_GET['debug'])) {
    echo json_encode([
        'debug' => [
            'php_version' => PHP_VERSION,
            'request_method' => $_SERVER['REQUEST_METHOD'],
            'get_params' => $_GET,
            'post_params' => $_POST,
            'functions_exist' => [
                'isLoggedIn' => function_exists('isLoggedIn'),
                'hasPermission' => function_exists('hasPermission'),
                'fetchOne' => function_exists('fetchOne'),
                'getCurrentUser' => function_exists('getCurrentUser')
            ]
        ]
    ]);
}
?>