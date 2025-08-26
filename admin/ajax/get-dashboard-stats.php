<?php
require_once '../../config/config.php';

// CORS Headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Nicht authentifiziert']);
    exit();
}

try {
    // Basis Server-Statistiken
    $stats = [
        'current_players' => (int)getServerSetting('current_players', '0'),
        'max_players' => (int)getServerSetting('max_players', '64'),
        'server_online' => (bool)getServerSetting('is_online', '1')
    ];
    
    // Admin-Statistiken
    $totalAdmins = fetchOne("SELECT COUNT(*) as count FROM admins WHERE is_active = 1")['count'] ?? 0;
    $activeAdmins = fetchOne("SELECT COUNT(DISTINCT admin_id) as count FROM admin_sessions WHERE last_activity > DATE_SUB(NOW(), INTERVAL 1 HOUR)")['count'] ?? 0;
    
    $stats['total_admins'] = (int)$totalAdmins;
    $stats['active_admins'] = (int)$activeAdmins;
    
    // Content-Statistiken
    $stats['total_rules'] = (int)(fetchOne("SELECT COUNT(*) as count FROM server_rules WHERE is_active = 1")['count'] ?? 0);
    $stats['total_news'] = (int)(fetchOne("SELECT COUNT(*) as count FROM news WHERE is_published = 1")['count'] ?? 0);
    
    // Roadmap-Statistiken
    $stats['total_roadmap_items'] = (int)(fetchOne("SELECT COUNT(*) as count FROM roadmap_items WHERE is_active = 1")['count'] ?? 0);
    $stats['completed_roadmap_items'] = (int)(fetchOne("SELECT COUNT(*) as count FROM roadmap_items WHERE status = 'completed' AND is_active = 1")['count'] ?? 0);
    $stats['in_progress_roadmap_items'] = (int)(fetchOne("SELECT COUNT(*) as count FROM roadmap_items WHERE status = 'in_progress' AND is_active = 1")['count'] ?? 0);
    
    // Whitelist-Statistiken
    $stats['pending_applications'] = (int)(fetchOne("SELECT COUNT(*) as count FROM whitelist_applications WHERE status = 'pending'")['count'] ?? 0);
    $stats['total_whitelist_questions'] = (int)(fetchOne("SELECT COUNT(*) as count FROM whitelist_questions WHERE is_active = 1")['count'] ?? 0);
    $stats['avg_whitelist_score'] = (float)(fetchOne("SELECT AVG(score_percentage) as avg_score FROM whitelist_applications WHERE score_percentage > 0")['avg_score'] ?? 0);
    $stats['high_score_applications'] = (int)(fetchOne("SELECT COUNT(*) as count FROM whitelist_applications WHERE score_percentage >= 70")['count'] ?? 0);
    
    // Aktivitäts-Statistiken
    $stats['recent_activities'] = (int)(fetchOne("SELECT COUNT(*) as count FROM admin_activity_log WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)")['count'] ?? 0);
    $stats['recent_logins'] = (int)(fetchOne("SELECT COUNT(*) as count FROM login_attempts WHERE success = 1 AND attempted_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)")['count'] ?? 0);
    
    // Status-Übersicht für Roadmap
    $roadmapStatus = fetchAll("
        SELECT status, COUNT(*) as count 
        FROM roadmap_items 
        WHERE is_active = 1 
        GROUP BY status
    ");
    
    $statusCounts = [
        'planned' => 0,
        'in_progress' => 0,
        'testing' => 0,
        'completed' => 0,
        'cancelled' => 0
    ];
    
    foreach ($roadmapStatus as $status) {
        $statusCounts[$status['status']] = (int)$status['count'];
    }
    
    $stats['roadmap_status_breakdown'] = $statusCounts;
    
    // Prioritäts-Verteilung für Roadmap
    $roadmapPriorities = fetchAll("
        SELECT priority, COUNT(*) as count 
        FROM roadmap_items 
        WHERE is_active = 1 
        GROUP BY priority
        ORDER BY priority ASC
    ");
    
    $priorityCounts = [];
    foreach ($roadmapPriorities as $priority) {
        $priorityCounts[$priority['priority']] = (int)$priority['count'];
    }
    
    $stats['roadmap_priority_breakdown'] = $priorityCounts;
    
    // Letzte Roadmap-Aktivitäten
    $recentRoadmapActivity = fetchAll("
        SELECT r.title, r.status, r.updated_at, a.username as updated_by_name
        FROM roadmap_items r
        LEFT JOIN admins a ON r.updated_by = a.id
        WHERE r.updated_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
        AND r.is_active = 1
        ORDER BY r.updated_at DESC
        LIMIT 5
    ");
    
    $stats['recent_roadmap_activity'] = $recentRoadmapActivity;
    
    // Server-Performance Metrics (wenn verfügbar)
    if (function_exists('sys_getloadavg')) {
        $load = sys_getloadavg();
        $stats['server_load'] = [
            '1min' => round($load[0], 2),
            '5min' => round($load[1], 2),
            '15min' => round($load[2], 2)
        ];
    }
    
    // Speicherverbrauch
    $stats['memory_usage'] = [
        'current' => memory_get_usage(true),
        'peak' => memory_get_peak_usage(true),
        'limit' => ini_get('memory_limit')
    ];
    
    // Disk Space (nur wenn erlaubt)
    if (function_exists('disk_total_space') && function_exists('disk_free_space')) {
        $totalSpace = disk_total_space('/');
        $freeSpace = disk_free_space('/');
        
        if ($totalSpace && $freeSpace) {
            $stats['disk_usage'] = [
                'total' => $totalSpace,
                'free' => $freeSpace,
                'used' => $totalSpace - $freeSpace,
                'percentage' => round((($totalSpace - $freeSpace) / $totalSpace) * 100, 1)
            ];
        }
    }
    
    // Zeitstempel der letzten Aktualisierung
    $stats['last_update'] = date('Y-m-d H:i:s');
    $stats['update_timestamp'] = time();
    
    // Erfolgreiche Antwort
    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'generated_at' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    error_log("Dashboard Stats Error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Fehler beim Laden der Dashboard-Statistiken',
        'message' => DEBUG_MODE ? $e->getMessage() : 'Ein unerwarteter Fehler ist aufgetreten'
    ]);
}