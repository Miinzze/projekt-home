<?php
// Datenbankkonfiguration für Shared Hosting
define('DB_HOST', 'localhost');
define('DB_NAME', 'd04487e8');
define('DB_USER', 'd04487e8');
define('DB_PASS', 'Gufxc6YeVnPjcBjyNHGY');
define('DB_CHARSET', 'utf8mb4');

// PDO Optionen
$pdo_options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
];

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        $pdo_options
    );
} catch (PDOException $e) {
    error_log("Datenbankverbindungsfehler: " . $e->getMessage());
    die("Datenbankverbindung fehlgeschlagen. Bitte versuchen Sie es später erneut.");
}

// Hilfsfunktionen
function executeQuery($sql, $params = []) {
    global $pdo;
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("SQL Fehler: " . $e->getMessage());
        return false;
    }
}

function fetchOne($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt ? $stmt->fetch() : false;
}

function fetchAll($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt ? $stmt->fetchAll() : false;
}

/**
 * Datenbank-Schema für erweiterte Benutzerverwaltung erstellen
 */
function createUserManagementTables() {
    global $pdo;
    
    try {
        // Erweiterte Admins-Tabelle
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `admins` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `username` varchar(50) NOT NULL UNIQUE,
                `email` varchar(100) NOT NULL UNIQUE,
                `password` varchar(255) NOT NULL,
                `role` enum('super_admin','admin','moderator','support') NOT NULL DEFAULT 'admin',
                `permissions` JSON NULL,
                `first_name` varchar(50) NOT NULL,
                `last_name` varchar(50) NOT NULL,
                `avatar_url` varchar(255) NULL,
                `phone` varchar(20) NULL,
                `is_active` tinyint(1) NOT NULL DEFAULT 1,
                `last_login` datetime NULL,
                `login_count` int(11) NOT NULL DEFAULT 0,
                `failed_login_attempts` int(11) NOT NULL DEFAULT 0,
                `locked_until` datetime NULL,
                `password_changed_at` datetime NULL,
                `must_change_password` tinyint(1) NOT NULL DEFAULT 0,
                `two_factor_enabled` tinyint(1) NOT NULL DEFAULT 0,
                `two_factor_secret` varchar(32) NULL,
                `api_key` varchar(64) NULL UNIQUE,
                `notes` text NULL,
                `created_by` int(11) NULL,
                `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_username` (`username`),
                KEY `idx_email` (`email`),
                KEY `idx_role` (`role`),
                KEY `idx_is_active` (`is_active`),
                KEY `idx_created_by` (`created_by`),
                FOREIGN KEY (`created_by`) REFERENCES `admins`(`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Rollen und Berechtigungen
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `roles` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(50) NOT NULL UNIQUE,
                `display_name` varchar(100) NOT NULL,
                `description` text NULL,
                `permissions` JSON NOT NULL,
                `is_system` tinyint(1) NOT NULL DEFAULT 0,
                `is_active` tinyint(1) NOT NULL DEFAULT 1,
                `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_name` (`name`),
                KEY `idx_is_active` (`is_active`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Benutzer-Sitzungen für erweiterte Sicherheit
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `admin_sessions` (
                `id` varchar(128) NOT NULL,
                `admin_id` int(11) NOT NULL,
                `ip_address` varchar(45) NOT NULL,
                `user_agent` text NULL,
                `data` text NULL,
                `last_activity` datetime NOT NULL,
                `expires_at` datetime NOT NULL,
                `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_admin_id` (`admin_id`),
                KEY `idx_expires_at` (`expires_at`),
                KEY `idx_last_activity` (`last_activity`),
                FOREIGN KEY (`admin_id`) REFERENCES `admins`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Erweiterte Login-Attempts Tabelle
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `login_attempts` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `ip_address` varchar(45) NOT NULL,
                `username` varchar(50) NULL,
                `success` tinyint(1) NOT NULL DEFAULT 0,
                `failure_reason` varchar(100) NULL,
                `user_agent` text NULL,
                `attempted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_ip_address` (`ip_address`),
                KEY `idx_username` (`username`),
                KEY `idx_success` (`success`),
                KEY `idx_attempted_at` (`attempted_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Aktivitäts-Log für Admins
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `admin_activity_log` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `admin_id` int(11) NOT NULL,
                `action` varchar(100) NOT NULL,
                `description` text NULL,
                `target_type` varchar(50) NULL,
                `target_id` int(11) NULL,
                `old_values` JSON NULL,
                `new_values` JSON NULL,
                `ip_address` varchar(45) NOT NULL,
                `user_agent` text NULL,
                `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_admin_id` (`admin_id`),
                KEY `idx_action` (`action`),
                KEY `idx_target` (`target_type`, `target_id`),
                KEY `idx_created_at` (`created_at`),
                FOREIGN KEY (`admin_id`) REFERENCES `admins`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Standard-Rollen erstellen
        $roles = [
            [
                'name' => 'super_admin',
                'display_name' => 'Super Administrator',
                'description' => 'Vollzugriff auf alle Funktionen einschließlich Benutzerverwaltung',
                'permissions' => json_encode([
                    'users.create', 'users.read', 'users.update', 'users.delete',
                    'roles.manage', 'settings.manage', 'whitelist.manage',
                    'news.manage', 'rules.manage', 'logs.view', 'system.backup'
                ]),
                'is_system' => 1
            ],
            [
                'name' => 'admin',
                'display_name' => 'Administrator',
                'description' => 'Zugriff auf die meisten Funktionen außer Benutzerverwaltung',
                'permissions' => json_encode([
                    'users.read', 'settings.manage', 'whitelist.manage',
                    'news.manage', 'rules.manage', 'logs.view'
                ]),
                'is_system' => 1
            ],
            [
                'name' => 'moderator',
                'display_name' => 'Moderator',
                'description' => 'Kann Whitelist-Bewerbungen und News verwalten',
                'permissions' => json_encode([
                    'whitelist.manage', 'news.create', 'news.update', 'logs.view'
                ]),
                'is_system' => 1
            ],
            [
                'name' => 'support',
                'display_name' => 'Support',
                'description' => 'Kann nur Whitelist-Bewerbungen einsehen und bearbeiten',
                'permissions' => json_encode([
                    'whitelist.read', 'whitelist.update'
                ]),
                'is_system' => 1
            ]
        ];

        foreach ($roles as $role) {
            $pdo->prepare("
                INSERT IGNORE INTO roles (name, display_name, description, permissions, is_system)
                VALUES (?, ?, ?, ?, ?)
            ")->execute([
                $role['name'],
                $role['display_name'],
                $role['description'],
                $role['permissions'],
                $role['is_system']
            ]);
        }

        // Standard-Admin erstellen falls noch keiner existiert
        $existingAdmin = $pdo->query("SELECT COUNT(*) as count FROM admins")->fetch();
        if ($existingAdmin['count'] == 0) {
            $pdo->prepare("
                INSERT INTO admins (username, email, password, role, first_name, last_name, is_active, password_changed_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ")->execute([
                'admin',
                'admin@projekt-z.eu',
                password_hash('admin123!', PASSWORD_DEFAULT),
                'super_admin',
                'System',
                'Administrator',
                1
            ]);
        }

        return true;
    } catch (PDOException $e) {
        error_log("Fehler beim Erstellen der Tabellen: " . $e->getMessage());
        return false;
    }
}

// Automatisch Tabellen erstellen
createUserManagementTables();

/**
 * Erweiterte Insert-Funktion
 */
function insertData($table, $data) {
    global $pdo;
    $columns = array_keys($data);
    $placeholders = ':' . implode(', :', $columns);
    $sql = "INSERT INTO $table (" . implode(', ', $columns) . ") VALUES ($placeholders)";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($data);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log("Insert Fehler: " . $e->getMessage());
        return false;
    }
}

/**
 * Erweiterte Update-Funktion
 */
function updateData($table, $data, $where, $whereParams = []) {
    global $pdo;
    $setClause = '';
    foreach (array_keys($data) as $column) {
        $setClause .= "$column = :$column, ";
    }
    $setClause = rtrim($setClause, ', ');
    
    $sql = "UPDATE $table SET $setClause WHERE $where";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge($data, $whereParams));
        return $stmt->rowCount();
    } catch (PDOException $e) {
        error_log("Update Fehler: " . $e->getMessage());
        return false;
    }
}

/**
 * Benutzer-spezifische Funktionen
 */

function getUserById($id) {
    return fetchOne("SELECT * FROM admins WHERE id = :id", ['id' => $id]);
}

function getUserByUsername($username) {
    return fetchOne("SELECT * FROM admins WHERE username = :username", ['username' => $username]);
}

function getUserByEmail($email) {
    return fetchOne("SELECT * FROM admins WHERE email = :email", ['email' => $email]);
}

function getAllUsers($includeInactive = false) {
    $sql = "SELECT a.*, r.display_name as role_display_name, c.username as created_by_name 
            FROM admins a 
            LEFT JOIN roles r ON a.role = r.name 
            LEFT JOIN admins c ON a.created_by = c.id";
    
    if (!$includeInactive) {
        $sql .= " WHERE a.is_active = 1";
    }
    
    $sql .= " ORDER BY a.created_at DESC";
    
    return fetchAll($sql);
}

function getRoleByName($name) {
    return fetchOne("SELECT * FROM roles WHERE name = :name", ['name' => $name]);
}

function getAllRoles($includeInactive = false) {
    $sql = "SELECT * FROM roles";
    if (!$includeInactive) {
        $sql .= " WHERE is_active = 1";
    }
    $sql .= " ORDER BY name ASC";
    
    return fetchAll($sql);
}

/**
 * Aktivitäts-Logging
 */
function logAdminActivity($adminId, $action, $description = null, $targetType = null, $targetId = null, $oldValues = null, $newValues = null) {
    $data = [
        'admin_id' => $adminId,
        'action' => $action,
        'description' => $description,
        'target_type' => $targetType,
        'target_id' => $targetId,
        'old_values' => $oldValues ? json_encode($oldValues) : null,
        'new_values' => $newValues ? json_encode($newValues) : null,
        'ip_address' => getUserIP(),
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
    ];
    
    return insertData('admin_activity_log', $data);
}

/**
 * Session-Management
 */
function createAdminSession($adminId, $sessionData = []) {
    global $pdo;
    
    $sessionId = bin2hex(random_bytes(64));
    $expiresAt = date('Y-m-d H:i:s', time() + SESSION_TIMEOUT);
    
    $data = [
        'id' => $sessionId,
        'admin_id' => $adminId,
        'ip_address' => getUserIP(),
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'data' => json_encode($sessionData),
        'last_activity' => date('Y-m-d H:i:s'),
        'expires_at' => $expiresAt
    ];
    
    if (insertData('admin_sessions', $data)) {
        return $sessionId;
    }
    
    return false;
}

function validateAdminSession($sessionId, $adminId) {
    $session = fetchOne(
        "SELECT * FROM admin_sessions WHERE id = :session_id AND admin_id = :admin_id AND expires_at > NOW()",
        ['session_id' => $sessionId, 'admin_id' => $adminId]
    );
    
    if ($session) {
        // Session-Aktivität aktualisieren
        updateData('admin_sessions', 
            ['last_activity' => date('Y-m-d H:i:s')], 
            'id = :id', 
            ['id' => $sessionId]
        );
        return true;
    }
    
    return false;
}

function destroyAdminSession($sessionId) {
    return executeQuery("DELETE FROM admin_sessions WHERE id = :id", ['id' => $sessionId]);
}

function cleanupExpiredSessions() {
    return executeQuery("DELETE FROM admin_sessions WHERE expires_at < NOW()");
}

// Automatisch abgelaufene Sessions bereinigen
cleanupExpiredSessions();
?>