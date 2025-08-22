<?php
require_once '../../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !hasPermission('whitelist.update')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Keine Berechtigung']);
    exit;
}

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data');
    }
    
    $applicationId = (int)($data['application_id'] ?? 0);
    $status = $data['status'] ?? '';
    $notes = trim($data['notes'] ?? '');
    
    if ($applicationId <= 0 || !in_array($status, ['pending', 'approved', 'rejected', 'closed'])) {
        throw new Exception('Ungültige Daten');
    }
    
    $oldApp = fetchOne("SELECT * FROM whitelist_applications WHERE id = :id", ['id' => $applicationId]);
    
    if (!$oldApp) {
        throw new Exception('Bewerbung nicht gefunden');
    }
    
    $result = updateData('whitelist_applications', [
        'status' => $status,
        'notes' => $notes,
        'reviewed_by' => getCurrentUser()['id'],
        'reviewed_at' => date('Y-m-d H:i:s')
    ], 'id = :id', ['id' => $applicationId]);
    
    if ($result !== false) {
        logAdminActivity(
            getCurrentUser()['id'],
            'whitelist_status_updated',
            "Whitelist-Bewerbung Status geändert: {$status}",
            'whitelist_application',
            $applicationId,
            ['status' => $oldApp['status']],
            ['status' => $status, 'notes' => $notes]
        );
        
        echo json_encode([
            'success' => true,
            'message' => 'Status erfolgreich aktualisiert'
        ]);
    } else {
        throw new Exception('Fehler beim Aktualisieren des Status');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>