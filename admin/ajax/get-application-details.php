<?php
require_once '../../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !hasPermission('whitelist.read')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Keine Berechtigung']);
    exit;
}

$applicationId = (int)($_GET['id'] ?? 0);

if ($applicationId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Ungültige Bewerbungs-ID']);
    exit;
}

try {
    $application = fetchOne("
        SELECT * FROM whitelist_applications 
        WHERE id = :id
    ", ['id' => $applicationId]);
    
    if (!$application) {
        echo json_encode(['success' => false, 'error' => 'Bewerbung nicht gefunden']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'application' => $application
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>