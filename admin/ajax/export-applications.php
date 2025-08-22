<?php
require_once '../../config/config.php';

if (!isLoggedIn() || !hasPermission('whitelist.read')) {
    http_response_code(403);
    die('Keine Berechtigung');
}

$format = $_GET['export'] ?? 'csv';
$statusFilter = $_GET['status'] ?? '';
$scoreFilter = $_GET['score'] ?? '';

try {
    // SQL Query aufbauen
    $sql = "
        SELECT 
            wa.*,
            a.username as reviewed_by_name
        FROM whitelist_applications wa 
        LEFT JOIN admins a ON wa.reviewed_by = a.id 
        WHERE 1=1
    ";
    
    $params = [];
    
    if ($statusFilter) {
        $sql .= " AND wa.status = :status";
        $params['status'] = $statusFilter;
    }
    
    if ($scoreFilter) {
        switch ($scoreFilter) {
            case 'high':
                $sql .= " AND wa.score_percentage >= 70";
                break;
            case 'medium':
                $sql .= " AND wa.score_percentage >= 50 AND wa.score_percentage < 70";
                break;
            case 'low':
                $sql .= " AND wa.score_percentage > 0 AND wa.score_percentage < 50";
                break;
            case 'unscored':
                $sql .= " AND (wa.score_percentage = 0 OR wa.score_percentage IS NULL)";
                break;
        }
    }
    
    $sql .= " ORDER BY wa.created_at DESC";
    
    $applications = fetchAll($sql, $params);
    
    if ($format === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="whitelist-applications-' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // CSV Header
        fputcsv($output, [
            'ID', 'Discord Username', 'Discord ID', 'Status', 'Score %', 
            'Richtige Antworten', 'Gesamt Fragen', 'Eingereicht am', 
            'Bearbeitet von', 'Bearbeitet am', 'Notizen'
        ]);
        
        // CSV Daten
        foreach ($applications as $app) {
            fputcsv($output, [
                $app['id'],
                $app['discord_username'],
                $app['discord_id'],
                $app['status'],
                $app['score_percentage'] ?: '0',
                $app['correct_answers'] ?: '0',
                $app['total_questions'] ?: '0',
                date('d.m.Y H:i', strtotime($app['created_at'])),
                $app['reviewed_by_name'] ?: '',
                $app['reviewed_at'] ? date('d.m.Y H:i', strtotime($app['reviewed_at'])) : '',
                $app['notes'] ?: ''
            ]);
        }
        
        fclose($output);
        exit;
    }
    
} catch (Exception $e) {
    echo 'Export-Fehler: ' . $e->getMessage();
}
?>