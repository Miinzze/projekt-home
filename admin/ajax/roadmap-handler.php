<?php
/**
 * AJAX Handler für Roadmap-Management
 * Datei: admin/ajax/roadmap-handler.php
 */

require_once '../../config/config.php';

// CORS Headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Authentifizierung prüfen
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Nicht authentifiziert']);
    exit();
}

// Aktion bestimmen
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'get_roadmap_items':
            handleGetRoadmapItems();
            break;
            
        case 'get_roadmap_item':
            handleGetRoadmapItem();
            break;
            
        case 'update_roadmap_progress':
            handleUpdateRoadmapProgress();
            break;
            
        case 'bulk_update_status':
            handleBulkUpdateStatus();
            break;
            
        case 'get_roadmap_stats':
            handleGetRoadmapStats();
            break;
            
        case 'reorder_roadmap_items':
            handleReorderRoadmapItems();
            break;
            
        case 'duplicate_roadmap_item':
            handleDuplicateRoadmapItem();
            break;
            
        case 'archive_roadmap_item':
            handleArchiveRoadmapItem();
            break;
            
        case 'add_roadmap_comment':
            handleAddRoadmapComment();
            break;
            
        case 'get_roadmap_comments':
            handleGetRoadmapComments();
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Ungültige Aktion']);
            break;
    }
    
} catch (Exception $e) {
    error_log("Roadmap AJAX Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Ein unerwarteter Fehler ist aufgetreten',
        'debug' => DEBUG_MODE ? $e->getMessage() : null
    ]);
}

/**
 * Alle Roadmap-Items abrufen
 */
function handleGetRoadmapItems() {
    if (!hasPermission('roadmap.read')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Keine Berechtigung']);
        return;
    }
    
    $status = $_GET['status'] ?? null;
    $priority = $_GET['priority'] ?? null;
    $includeInactive = $_GET['include_inactive'] === 'true';
    
    $sql = "SELECT r.*, 
                   creator.username as created_by_name, creator.first_name as creator_first_name, creator.last_name as creator_last_name,
                   updater.username as updated_by_name, updater.first_name as updater_first_name, updater.last_name as updater_last_name,
                   assignee.username as assignee_name, assignee.first_name as assignee_first_name, assignee.last_name as assignee_last_name
            FROM roadmap_items r 
            LEFT JOIN admins creator ON r.created_by = creator.id
            LEFT JOIN admins updater ON r.updated_by = updater.id
            LEFT JOIN admins assignee ON r.assignee_id = assignee.id
            WHERE 1=1";
    
    $params = [];
    
    if (!$includeInactive) {
        $sql .= " AND r.is_active = 1";
    }
    
    if ($status) {
        $sql .= " AND r.status = :status";
        $params['status'] = $status;
    }
    
    if ($priority) {
        $sql .= " AND r.priority = :priority";
        $params['priority'] = $priority;
    }
    
    $sql .= " ORDER BY r.priority ASC, r.created_at DESC";
    
    $items = fetchAll($sql, $params);
    
    // Tags parsen
    foreach ($items as &$item) {
        if ($item['tags']) {
            $item['tags'] = json_decode($item['tags'], true) ?: [];
        } else {
            $item['tags'] = [];
        }
    }
    
    echo json_encode([
        'success' => true,
        'items' => $items,
        'total' => count($items)
    ]);
}

/**
 * Einzelnes Roadmap-Item abrufen
 */
function handleGetRoadmapItem() {
    if (!hasPermission('roadmap.read')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Keine Berechtigung']);
        return;
    }
    
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Ungültige ID']);
        return;
    }
    
    $item = fetchOne("
        SELECT r.*, 
               creator.username as created_by_name, creator.first_name as creator_first_name, creator.last_name as creator_last_name,
               updater.username as updated_by_name, updater.first_name as updater_first_name, updater.last_name as updater_last_name,
               assignee.username as assignee_name, assignee.first_name as assignee_first_name, assignee.last_name as assignee_last_name
        FROM roadmap_items r 
        LEFT JOIN admins creator ON r.created_by = creator.id
        LEFT JOIN admins updater ON r.updated_by = updater.id
        LEFT JOIN admins assignee ON r.assignee_id = assignee.id
        WHERE r.id = :id
    ", ['id' => $id]);
    
    if (!$item) {
        echo json_encode(['success' => false, 'error' => 'Item nicht gefunden']);
        return;
    }
    
    // Tags parsen
    if ($item['tags']) {
        $item['tags'] = json_decode($item['tags'], true) ?: [];
    } else {
        $item['tags'] = [];
    }
    
    echo json_encode([
        'success' => true,
        'item' => $item
    ]);
}

/**
 * Roadmap-Progress aktualisieren
 */
function handleUpdateRoadmapProgress() {
    if (!hasPermission('roadmap.update')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Keine Berechtigung']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $id = (int)($input['id'] ?? 0);
    $progress = (int)($input['progress'] ?? 0);
    
    if ($id <= 0 || $progress < 0 || $progress > 100) {
        echo json_encode(['success' => false, 'error' => 'Ungültige Daten']);
        return;
    }
    
    $currentUser = getCurrentUser();
    
    $result = updateData('roadmap_items', [
        'progress_percentage' => $progress,
        'updated_by' => $currentUser['id']
    ], 'id = :id', ['id' => $id]);
    
    if ($result !== false) {
        // Aktivität loggen
        logAdminActivity(
            $currentUser['id'],
            'roadmap_progress_updated',
            "Roadmap-Progress auf {$progress}% aktualisiert",
            'roadmap_item',
            $id,
            null,
            ['progress' => $progress]
        );
        
        echo json_encode([
            'success' => true,
            'message' => 'Progress aktualisiert'
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Update fehlgeschlagen']);
    }
}

/**
 * Bulk-Status Update
 */
function handleBulkUpdateStatus() {
    if (!hasPermission('roadmap.update')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Keine Berechtigung']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $ids = $input['ids'] ?? [];
    $status = $input['status'] ?? '';
    
    if (empty($ids) || !in_array($status, ['planned', 'in_progress', 'testing', 'completed', 'cancelled'])) {
        echo json_encode(['success' => false, 'error' => 'Ungültige Daten']);
        return;
    }
    
    $currentUser = getCurrentUser();
    $updatedCount = 0;
    
    foreach ($ids as $id) {
        $id = (int)$id;
        if ($id > 0) {
            $result = updateData('roadmap_items', [
                'status' => $status,
                'updated_by' => $currentUser['id']
            ], 'id = :id', ['id' => $id]);
            
            if ($result !== false) {
                $updatedCount++;
                
                // Aktivität loggen
                logAdminActivity(
                    $currentUser['id'],
                    'roadmap_bulk_status_updated',
                    "Roadmap-Status auf '{$status}' geändert (Bulk)",
                    'roadmap_item',
                    $id,
                    null,
                    ['status' => $status]
                );
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'updated_count' => $updatedCount,
        'message' => "{$updatedCount} Items aktualisiert"
    ]);
}

/**
 * Roadmap-Statistiken abrufen
 */
function handleGetRoadmapStats() {
    if (!hasPermission('roadmap.read')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Keine Berechtigung']);
        return;
    }
    
    // Basis-Statistiken
    $stats = [];
    
    $stats['total'] = fetchOne("SELECT COUNT(*) as count FROM roadmap_items WHERE is_active = 1")['count'] ?? 0;
    $stats['planned'] = fetchOne("SELECT COUNT(*) as count FROM roadmap_items WHERE status = 'planned' AND is_active = 1")['count'] ?? 0;
    $stats['in_progress'] = fetchOne("SELECT COUNT(*) as count FROM roadmap_items WHERE status = 'in_progress' AND is_active = 1")['count'] ?? 0;
    $stats['testing'] = fetchOne("SELECT COUNT(*) as count FROM roadmap_items WHERE status = 'testing' AND is_active = 1")['count'] ?? 0;
    $stats['completed'] = fetchOne("SELECT COUNT(*) as count FROM roadmap_items WHERE status = 'completed' AND is_active = 1")['count'] ?? 0;
    $stats['cancelled'] = fetchOne("SELECT COUNT(*) as count FROM roadmap_items WHERE status = 'cancelled' AND is_active = 1")['count'] ?? 0;
    
    // Prioritäts-Verteilung
    $priorityStats = fetchAll("
        SELECT priority, COUNT(*) as count 
        FROM roadmap_items 
        WHERE is_active = 1 
        GROUP BY priority 
        ORDER BY priority ASC
    ");
    
    $stats['priorities'] = [];
    foreach ($priorityStats as $prio) {
        $stats['priorities'][$prio['priority']] = (int)$prio['count'];
    }
    
    // Durchschnittlicher Progress
    $avgProgress = fetchOne("
        SELECT AVG(progress_percentage) as avg_progress 
        FROM roadmap_items 
        WHERE is_active = 1 AND status IN ('in_progress', 'testing')
    ")['avg_progress'] ?? 0;
    
    $stats['average_progress'] = round($avgProgress, 1);
    
    // Items mit nahenden Deadlines
    $upcomingDeadlines = fetchAll("
        SELECT id, title, estimated_completion_date, status
        FROM roadmap_items 
        WHERE is_active = 1 
        AND estimated_completion_date IS NOT NULL 
        AND estimated_completion_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        AND status NOT IN ('completed', 'cancelled')
        ORDER BY estimated_completion_date ASC
    ");
    
    $stats['upcoming_deadlines'] = $upcomingDeadlines;
    
    // Letzte Aktivitäten
    $recentActivity = fetchAll("
        SELECT r.title, al.action, al.description, al.created_at, a.username
        FROM admin_activity_log al
        JOIN roadmap_items r ON al.target_id = r.id AND al.target_type = 'roadmap_item'
        JOIN admins a ON al.admin_id = a.id
        WHERE al.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
        ORDER BY al.created_at DESC
        LIMIT 10
    ");
    
    $stats['recent_activity'] = $recentActivity;
    
    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'generated_at' => date('Y-m-d H:i:s')
    ]);
}

/**
 * Roadmap-Items neu anordnen
 */
function handleReorderRoadmapItems() {
    if (!hasPermission('roadmap.update')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Keine Berechtigung']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $items = $input['items'] ?? [];
    
    if (empty($items)) {
        echo json_encode(['success' => false, 'error' => 'Keine Items angegeben']);
        return;
    }
    
    $currentUser = getCurrentUser();
    $updatedCount = 0;
    
    foreach ($items as $index => $item) {
        $id = (int)($item['id'] ?? 0);
        $priority = (int)($item['priority'] ?? $index + 1);
        
        if ($id > 0) {
            $result = updateData('roadmap_items', [
                'priority' => $priority,
                'updated_by' => $currentUser['id']
            ], 'id = :id', ['id' => $id]);
            
            if ($result !== false) {
                $updatedCount++;
            }
        }
    }
    
    // Aktivität loggen
    logAdminActivity(
        $currentUser['id'],
        'roadmap_reordered',
        "Roadmap-Items neu angeordnet ({$updatedCount} Items)",
        'roadmap_item',
        null,
        null,
        ['updated_count' => $updatedCount]
    );
    
    echo json_encode([
        'success' => true,
        'updated_count' => $updatedCount,
        'message' => 'Items neu angeordnet'
    ]);
}

/**
 * Roadmap-Item duplizieren
 */
function handleDuplicateRoadmapItem() {
    if (!hasPermission('roadmap.create')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Keine Berechtigung']);
        return;
    }
    
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Ungültige ID']);
        return;
    }
    
    $originalItem = fetchOne("SELECT * FROM roadmap_items WHERE id = :id", ['id' => $id]);
    if (!$originalItem) {
        echo json_encode(['success' => false, 'error' => 'Item nicht gefunden']);
        return;
    }
    
    $currentUser = getCurrentUser();
    
    // Neues Item erstellen
    $newId = insertData('roadmap_items', [
        'title' => $originalItem['title'] . ' (Kopie)',
        'description' => $originalItem['description'],
        'status' => 'planned', // Kopien beginnen als "geplant"
        'priority' => $originalItem['priority'],
        'estimated_completion_date' => $originalItem['estimated_completion_date'],
        'progress_percentage' => 0, // Progress zurücksetzen
        'tags' => $originalItem['tags'],
        'is_active' => 1,
        'is_public' => $originalItem['is_public'],
        'created_by' => $currentUser['id']
    ]);
    
    if ($newId) {
        // Aktivität loggen
        logAdminActivity(
            $currentUser['id'],
            'roadmap_item_duplicated',
            "Roadmap-Item '{$originalItem['title']}' dupliziert",
            'roadmap_item',
            $newId,
            null,
            ['original_id' => $id, 'new_id' => $newId]
        );
        
        echo json_encode([
            'success' => true,
            'new_id' => $newId,
            'message' => 'Item erfolgreich dupliziert'
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Duplizierung fehlgeschlagen']);
    }
}

/**
 * Roadmap-Item archivieren
 */
function handleArchiveRoadmapItem() {
    if (!hasPermission('roadmap.update')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Keine Berechtigung']);
        return;
    }
    
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Ungültige ID']);
        return;
    }
    
    $currentUser = getCurrentUser();
    
    $result = updateData('roadmap_items', [
        'is_active' => 0,
        'updated_by' => $currentUser['id']
    ], 'id = :id', ['id' => $id]);
    
    if ($result !== false) {
        // Aktivität loggen
        logAdminActivity(
            $currentUser['id'],
            'roadmap_item_archived',
            "Roadmap-Item archiviert",
            'roadmap_item',
            $id,
            null,
            ['archived' => true]
        );
        
        echo json_encode([
            'success' => true,
            'message' => 'Item archiviert'
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Archivierung fehlgeschlagen']);
    }
}

/**
 * Roadmap-Kommentar hinzufügen
 */
function handleAddRoadmapComment() {
    if (!hasPermission('roadmap.read')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Keine Berechtigung']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $roadmapItemId = (int)($input['roadmap_item_id'] ?? 0);
    $comment = trim($input['comment'] ?? '');
    $isPublic = (bool)($input['is_public'] ?? false);
    
    if ($roadmapItemId <= 0 || empty($comment)) {
        echo json_encode(['success' => false, 'error' => 'Ungültige Daten']);
        return;
    }
    
    $currentUser = getCurrentUser();
    
    $commentId = insertData('roadmap_comments', [
        'roadmap_item_id' => $roadmapItemId,
        'admin_id' => $currentUser['id'],
        'comment' => $comment,
        'is_public' => $isPublic ? 1 : 0
    ]);
    
    if ($commentId) {
        echo json_encode([
            'success' => true,
            'comment_id' => $commentId,
            'message' => 'Kommentar hinzugefügt'
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Kommentar konnte nicht hinzugefügt werden']);
    }
}

/**
 * Roadmap-Kommentare abrufen
 */
function handleGetRoadmapComments() {
    if (!hasPermission('roadmap.read')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Keine Berechtigung']);
        return;
    }
    
    $roadmapItemId = (int)($_GET['roadmap_item_id'] ?? 0);
    $includePrivate = hasPermission('roadmap.update');
    
    if ($roadmapItemId <= 0) {
        echo json_encode(['success' => false, 'error' => 'Ungültige ID']);
        return;
    }
    
    $sql = "SELECT rc.*, a.username, a.first_name, a.last_name
            FROM roadmap_comments rc
            JOIN admins a ON rc.admin_id = a.id
            WHERE rc.roadmap_item_id = :roadmap_item_id";
    
    if (!$includePrivate) {
        $sql .= " AND rc.is_public = 1";
    }
    
    $sql .= " ORDER BY rc.created_at DESC";
    
    $comments = fetchAll($sql, ['roadmap_item_id' => $roadmapItemId]);
    
    echo json_encode([
        'success' => true,
        'comments' => $comments,
        'total' => count($comments)
    ]);
}
?>