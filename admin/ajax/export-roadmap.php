<?php
require_once '../../config/config.php';

if (!isLoggedIn() || !hasPermission('settings.read')) {
    http_response_code(403);
    die('Keine Berechtigung');
}

$format = $_GET['format'] ?? 'json';

try {
    $roadmapItems = fetchAll("
        SELECT r.*, 
               creator.username as created_by_name,
               updater.username as updated_by_name
        FROM roadmap_items r 
        LEFT JOIN admins creator ON r.created_by = creator.id
        LEFT JOIN admins updater ON r.updated_by = updater.id
        WHERE r.is_active = 1
        ORDER BY r.priority ASC, r.created_at DESC
    ");
    
    if ($format === 'json') {
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="roadmap-' . date('Y-m-d') . '.json"');
        
        $export = [
            'export_date' => date('Y-m-d H:i:s'),
            'server_name' => getServerSetting('server_name', 'Zombie RP Server'),
            'total_items' => count($roadmapItems),
            'roadmap_items' => $roadmapItems
        ];
        
        echo json_encode($export, JSON_PRETTY_PRINT);
        exit;
    }
    
} catch (Exception $e) {
    echo 'Export-Fehler: ' . $e->getMessage();
}
?>