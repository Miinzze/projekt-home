<?php
require_once '../../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Nicht angemeldet']);
    exit;
}

try {
    $stats = [];
    
    // Server-Statistiken
    $stats['current_players'] = getServerSetting('current_players', '0');
    $stats['max_players'] = getServerSetting('max_players', '64');
    $stats['server_online'] = getServerSetting('is_online', '1');
    
    // Admin-Statistiken
    if (hasPermission('users.read')) {
        $stats['total_admins'] = fetchOne("SELECT COUNT(*) as count FROM admins WHERE is_active = 1")['count'] ?? 0;
        $stats['active_admins'] = fetchOne("SELECT COUNT(DISTINCT admin_id) as count FROM admin_sessions WHERE last_activity > DATE_SUB(NOW(), INTERVAL 1 HOUR)")['count'] ?? 0;
    }
    
    // Content-Statistiken
    if (hasPermission('rules.read')) {
        $stats['total_rules'] = fetchOne("SELECT COUNT(*) as count FROM server_rules WHERE is_active = 1")['count'] ?? 0;
    }
    
    if (hasPermission('news.read')) {
        $stats['total_news'] = fetchOne("SELECT COUNT(*) as count FROM news WHERE is_published = 1")['count'] ?? 0;
    }
    
    // Whitelist-Statistiken
    if (hasPermission('whitelist.read')) {
        $stats['pending_applications'] = fetchOne("SELECT COUNT(*) as count FROM whitelist_applications WHERE status = 'pending'")['count'] ?? 0;
        $stats['total_questions'] = fetchOne("SELECT COUNT(*) as count FROM whitelist_questions WHERE is_active = 1")['count'] ?? 0;
        $stats['avg_score'] = fetchOne("SELECT AVG(score_percentage) as avg_score FROM whitelist_applications WHERE score_percentage > 0")['avg_score'] ?? 0;
        $stats['high_score_apps'] = fetchOne("SELECT COUNT(*) as count FROM whitelist_applications WHERE score_percentage >= 70")['count'] ?? 0;
    }
    
    // Roadmap-Statistiken
    if (hasPermission('settings.read')) {
        $roadmapStats = fetchOne("
            SELECT 
                COUNT(*) as total_items,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_items,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_items,
                SUM(CASE WHEN priority = 1 THEN 1 ELSE 0 END) as high_priority_items,
                ROUND((SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 1) as completion_percentage
            FROM roadmap_items 
            WHERE is_active = 1
        ");
        
        if ($roadmapStats) {
            $stats = array_merge($stats, $roadmapStats);
        }
    }
    
    // Aktivitäten
    if (hasPermission('activity.read')) {
        $stats['recent_activities'] = fetchOne("SELECT COUNT(*) as count FROM admin_activity_log WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)")['count'] ?? 0;
    }
    
    // Login-Statistiken
    if (hasPermission('logs.read')) {
        $stats['recent_logins'] = fetchOne("SELECT COUNT(*) as count FROM login_attempts WHERE success = 1 AND attempted_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)")['count'] ?? 0;
    }
    
    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'timestamp' => time()
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>