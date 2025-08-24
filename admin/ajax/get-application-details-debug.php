<?php
/**
 * Debug-Version zum Testen
 * Speichern Sie diese Datei als: admin/ajax/get-application-details-debug.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=utf-8');

try {
    // 1. Config laden
    echo json_encode(['step' => 1, 'message' => 'Starting debug...']) . "\n";
    
    if (!file_exists('../../config/config.php')) {
        throw new Exception('config.php not found');
    }
    
    require_once '../../config/config.php';
    echo json_encode(['step' => 2, 'message' => 'Config loaded']) . "\n";
    
    // 2. ID ermitteln
    $applicationId = (int)($_GET['id'] ?? 0);
    echo json_encode(['step' => 3, 'message' => 'Application ID: ' . $applicationId]) . "\n";
    
    if ($applicationId <= 0) {
        throw new Exception('Invalid application ID');
    }
    
    // 3. Funktionen prüfen
    $functions = [
        'isLoggedIn' => function_exists('isLoggedIn'),
        'hasPermission' => function_exists('hasPermission'), 
        'fetchOne' => function_exists('fetchOne'),
        'getCurrentUser' => function_exists('getCurrentUser')
    ];
    echo json_encode(['step' => 4, 'functions' => $functions]) . "\n";
    
    // 4. Login prüfen
    if (function_exists('isLoggedIn')) {
        $isLoggedIn = isLoggedIn();
        echo json_encode(['step' => 5, 'logged_in' => $isLoggedIn]) . "\n";
        
        if (!$isLoggedIn) {
            throw new Exception('Not logged in');
        }
    }
    
    // 5. Datenbank-Verbindung prüfen
    if (isset($GLOBALS['pdo'])) {
        echo json_encode(['step' => 6, 'message' => 'PDO connection exists']) . "\n";
    } elseif (isset($GLOBALS['db'])) {
        echo json_encode(['step' => 6, 'message' => 'DB connection exists']) . "\n";
    } else {
        echo json_encode(['step' => 6, 'message' => 'No DB connection found']) . "\n";
    }
    
    // 6. Einfache Datenbankabfrage
    $sql = "SELECT * FROM whitelist_applications WHERE id = ?";
    echo json_encode(['step' => 7, 'sql' => $sql, 'params' => [$applicationId]]) . "\n";
    
    if (function_exists('fetchOne')) {
        $application = fetchOne($sql, [$applicationId]);
        echo json_encode(['step' => 8, 'found_application' => !empty($application)]) . "\n";
        
        if ($application) {
            // Nur die wichtigsten Felder
            $result = [
                'id' => $application['id'],
                'discord_username' => $application['discord_username'] ?? 'Unknown',
                'discord_id' => $application['discord_id'] ?? '0',
                'status' => $application['status'] ?? 'pending',
                'created_at' => $application['created_at'] ?? ''
            ];
            
            echo json_encode(['step' => 9, 'success' => true, 'application' => $result]) . "\n";
        } else {
            throw new Exception('Application not found');
        }
    } else {
        throw new Exception('fetchOne function not available');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]) . "\n";
}
?>