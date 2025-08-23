<?php
/**
 * AJAX Endpoint für Bewerbungsdetails
 * Datei: admin/ajax/get-application-details.php
 */

require_once '../../config/config.php';

// CORS Headers für AJAX
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Nur GET und POST erlauben
if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Login und Berechtigung prüfen
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Nicht angemeldet']);
    exit;
}

if (!hasPermission('whitelist.read')) {
    http_response_code(403);
    echo json_encode(['error' => 'Keine Berechtigung für Whitelist-Zugriff']);
    exit;
}

// Application ID aus GET oder POST holen
$applicationId = 0;
if (isset($_GET['id'])) {
    $applicationId = (int)$_GET['id'];
} elseif (isset($_POST['id'])) {
    $applicationId = (int)$_POST['id'];
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // JSON Body parsen
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if ($data && isset($data['id'])) {
        $applicationId = (int)$data['id'];
    }
}

if ($applicationId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Ungültige oder fehlende Bewerbungs-ID']);
    exit;
}

try {
    // Hauptbewerbung laden
    $application = fetchOne("
        SELECT 
            wa.*,
            a.username as reviewed_by_name,
            a.first_name as reviewed_by_first_name,
            a.last_name as reviewed_by_last_name
        FROM whitelist_applications wa 
        LEFT JOIN admins a ON wa.reviewed_by = a.id 
        WHERE wa.id = :id
    ", ['id' => $applicationId]);
    
    if (!$application) {
        http_response_code(404);
        echo json_encode(['error' => 'Bewerbung nicht gefunden']);
        exit;
    }
    
    // Antworten mit Fragen laden
    $answers = fetchAll("
        SELECT 
            wa.*,
            wq.question,
            wq.question_type,
            wq.options,
            wq.correct_answer,
            wq.question_order,
            wq.is_required
        FROM whitelist_answers wa
        LEFT JOIN whitelist_questions wq ON wa.question_id = wq.id
        WHERE wa.application_id = :id
        ORDER BY wq.question_order ASC, wq.id ASC, wa.id ASC
    ", ['id' => $applicationId]);
    
    // Discord Bot Status prüfen
    $botEnabled = getServerSetting('discord_bot_enabled', '0') === '1';
    $botToken = getServerSetting('discord_bot_token', '');
    $botConfigured = !empty($botToken);
    
    // Berechtigungen prüfen
    $canUpdate = hasPermission('whitelist.update');
    $canSendAppointment = (
        $application['status'] === 'pending' && 
        $canUpdate &&
        $botEnabled && 
        $botConfigured
    );
    
    // Statistiken berechnen
    $totalAnswers = count($answers);
    $correctAnswers = 0;
    $autoEvaluatedCount = 0;
    
    foreach ($answers as $answer) {
        if ($answer['is_correct']) {
            $correctAnswers++;
        }
        if ($answer['auto_evaluated']) {
            $autoEvaluatedCount++;
        }
    }
    
    $scorePercentage = $totalAnswers > 0 ? ($correctAnswers / $totalAnswers) * 100 : 0;
    
    // Response zusammenstellen
    $response = [
        'success' => true,
        'application' => [
            'id' => (int)$application['id'],
            'discord_id' => $application['discord_id'],
            'discord_username' => $application['discord_username'],
            'discord_avatar' => $application['discord_avatar'],
            'status' => $application['status'],
            'score_percentage' => (float)$application['score_percentage'],
            'total_questions' => (int)$application['total_questions'],
            'correct_answers' => (int)$application['correct_answers'],
            'created_at' => $application['created_at'],
            'reviewed_at' => $application['reviewed_at'],
            'reviewed_by' => (int)$application['reviewed_by'],
            'reviewed_by_name' => $application['reviewed_by_name'],
            'reviewed_by_full_name' => trim(($application['reviewed_by_first_name'] ?? '') . ' ' . ($application['reviewed_by_last_name'] ?? '')),
            'notes' => $application['notes'],
            'appointment_date' => $application['appointment_date'],
            'appointment_message' => $application['appointment_message'],
            'appointment_sent_at' => $application['appointment_sent_at']
        ],
        'answers' => array_map(function($answer) {
            return [
                'id' => (int)$answer['id'],
                'question_id' => (int)$answer['question_id'],
                'question' => $answer['question'],
                'question_type' => $answer['question_type'],
                'question_order' => (int)$answer['question_order'],
                'is_required' => (bool)$answer['is_required'],
                'options' => $answer['options'] ? json_decode($answer['options'], true) : null,
                'correct_answer' => $answer['correct_answer'],
                'user_answer' => $answer['answer'],
                'is_correct' => (bool)$answer['is_correct'],
                'auto_evaluated' => (bool)$answer['auto_evaluated']
            ];
        }, $answers),
        'statistics' => [
            'total_answers' => $totalAnswers,
            'correct_answers' => $correctAnswers,
            'score_percentage' => round($scorePercentage, 2),
            'auto_evaluated_count' => $autoEvaluatedCount,
            'manual_evaluated_count' => $totalAnswers - $autoEvaluatedCount
        ],
        'permissions' => [
            'can_update' => $canUpdate,
            'can_approve' => hasPermission('whitelist.approve'),
            'can_reject' => hasPermission('whitelist.reject'),
            'can_delete' => hasPermission('whitelist.delete'),
            'can_send_appointment' => $canSendAppointment
        ],
        'discord_bot' => [
            'enabled' => $botEnabled,
            'configured' => $botConfigured,
            'can_send_messages' => $botEnabled && $botConfigured
        ],
        'server_settings' => [
            'server_name' => getServerSetting('server_name', 'Zombie RP Server'),
            'passing_score' => (int)getServerSetting('whitelist_passing_score', 70),
            'auto_approve' => getServerSetting('whitelist_auto_approve', '0') === '1'
        ]
    ];
    
    // Debug-Informationen hinzufügen (nur im Debug-Modus)
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        $response['debug'] = [
            'request_method' => $_SERVER['REQUEST_METHOD'],
            'application_id' => $applicationId,
            'user_id' => getCurrentUser()['id'] ?? null,
            'user_permissions' => array_intersect([
                'whitelist.read',
                'whitelist.update', 
                'whitelist.approve',
                'whitelist.reject'
            ], $GLOBALS['userPermissions'] ?? []),
            'query_time' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']
        ];
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    error_log("Database error in get-application-details.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Datenbankfehler beim Laden der Bewerbungsdaten',
        'code' => 'DB_ERROR'
    ]);
    
} catch (Exception $e) {
    error_log("General error in get-application-details.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Serverfehler beim Laden der Bewerbungsdaten',
        'code' => 'SERVER_ERROR',
        'message' => DEBUG_MODE ? $e->getMessage() : 'Internal server error'
    ]);
}

/**
 * Hilfsfunktionen für bessere Datenverarbeitung
 */

function formatDiscordAvatar($userId, $avatarHash) {
    if (empty($avatarHash)) {
        return null;
    }
    
    $extension = (strpos($avatarHash, 'a_') === 0) ? 'gif' : 'png';
    return "https://cdn.discordapp.com/avatars/{$userId}/{$avatarHash}.{$extension}?size=128";
}

function getApplicationStatusColor($status) {
    switch ($status) {
        case 'pending':
            return '#f59e0b'; // Orange
        case 'approved':
            return '#10b981'; // Green
        case 'rejected':
            return '#ef4444'; // Red
        case 'closed':
            return '#6b7280'; // Gray
        default:
            return '#6b7280';
    }
}

function getApplicationStatusLabel($status) {
    switch ($status) {
        case 'pending':
            return '🟡 Noch offen';
        case 'approved':
            return '✅ Genehmigt';
        case 'rejected':
            return '❌ Abgelehnt';
        case 'closed':
            return '⚫ Geschlossen';
        default:
            return ucfirst($status);
    }
}

// Log der API-Nutzung für Monitoring
if (function_exists('logAdminActivity')) {
    logAdminActivity(
        getCurrentUser()['id'],
        'whitelist_details_viewed',
        "Bewerbungsdetails angezeigt (ID: {$applicationId})",
        'whitelist_application',
        $applicationId,
        null,
        [
            'method' => $_SERVER['REQUEST_METHOD'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'ip_address' => getUserIP()
        ]
    );
}
?>