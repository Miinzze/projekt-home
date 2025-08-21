<?php
/**
 * Erweiterte Benutzerverwaltung und Permissions-System für Project Z RP
 * 
 * Diese Datei erweitert die bestehende config.php um erweiterte Benutzer- und Rollenverwaltung
 * 
 * Verwendung:
 * 1. Diese Datei in config/user_management.php speichern
 * 2. In config.php einbinden: require_once __DIR__ . '/user_management.php';
 * 3. Datenbank-Schema ausführen
 */

/**
 * Benutzer erstellen
 */
function createUser($userData) {
    // Validierung
    $errors = validateUserData($userData);
    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }
    
    // Prüfen ob Benutzername oder E-Mail bereits existiert
    $existingUser = fetchOne(
        "SELECT id FROM admins WHERE username = :username OR email = :email",
        ['username' => $userData['username'], 'email' => $userData['email']]
    );
    
    if ($existingUser) {
        return ['success' => false, 'errors' => ['Benutzername oder E-Mail bereits vorhanden']];
    }
    
    // Passwort hashen
    $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
    
    // Standard-Berechtigungen für Rolle abrufen
    $rolePermissions = getRolePermissions($userData['role']);
    
    // Benutzer erstellen
    $userId = insertData('admins', [
        'username' => $userData['username'],
        'email' => $userData['email'],
        'password' => $hashedPassword,
        'role' => $userData['role'],
        'permissions' => $userData['custom_permissions'] ?? $rolePermissions,
        'first_name' => $userData['first_name'] ?? null,
        'last_name' => $userData['last_name'] ?? null,
        'phone' => $userData['phone'] ?? null,
        'is_active' => $userData['is_active'] ?? 1,
        'email_verified' => $userData['email_verified'] ?? 0,
        'created_by' => $_SESSION['admin_id'] ?? null
    ]);
    
    if ($userId) {
        // Audit-Log
        logAdminAction('user_created', 'user', $userId, null, [
            'username' => $userData['username'],
            'email' => $userData['email'],
            'role' => $userData['role']
        ]);
        
        return ['success' => true, 'user_id' => $userId];
    }
    
    return ['success' => false, 'errors' => ['Fehler beim Erstellen des Benutzers']];
}

/**
 * Benutzer bearbeiten
 */
function updateUser($userId, $userData) {
    // Prüfen ob Benutzer existiert
    $currentUser = fetchOne("SELECT * FROM admins WHERE id = :id", ['id' => $userId]);
    if (!$currentUser) {
        return ['success' => false, 'errors' => ['Benutzer nicht gefunden']];
    }
    
    // Validierung
    $errors = validateUserData($userData, $userId);
    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }
    
    // Update-Daten vorbereiten
    $updateData = [
        'username' => $userData['username'],
        'email' => $userData['email'],
        'role' => $userData['role'],
        'first_name' => $userData['first_name'] ?? null,
        'last_name' => $userData['last_name'] ?? null,
        'phone' => $userData['phone'] ?? null,
        'is_active' => $userData['is_active'] ?? 1
    ];
    
    // Passwort nur aktualisieren wenn neues gesetzt
    if (!empty($userData['password'])) {
        $updateData['password'] = password_hash($userData['password'], PASSWORD_DEFAULT);
        $updateData['last_password_change'] = date('Y-m-d H:i:s');
    }
    
    // Custom Permissions setzen wenn angegeben
    if (isset($userData['custom_permissions'])) {
        $updateData['permissions'] = $userData['custom_permissions'];
    } else {
        // Standard-Permissions der Rolle verwenden
        $updateData['permissions'] = getRolePermissions($userData['role']);
    }
    
    // Benutzer aktualisieren
    $result = updateData('admins', $updateData, 'id = :id', ['id' => $userId]);
    
    if ($result !== false) {
        // Audit-Log
        logAdminAction('user_updated', 'user', $userId, $currentUser, $updateData);
        
        return ['success' => true];
    }
    
    return ['success' => false, 'errors' => ['Fehler beim Aktualisieren des Benutzers']];
}

/**
 * Benutzer löschen
 */
function deleteUser($userId) {
    // Prüfen ob Benutzer existiert
    $user = fetchOne("SELECT * FROM admins WHERE id = :id", ['id' => $userId]);
    if (!user) {
        return ['success' => false, 'errors' => ['Benutzer nicht gefunden']];
    }
    
    // Prüfen ob letzter Super-Admin
    if ($user['role'] === 'super_admin') {
        $superAdminCount = fetchOne("SELECT COUNT(*) as count FROM admins WHERE role = 'super_admin' AND is_active = 1")['count'];
        if ($superAdminCount <= 1) {
            return ['success' => false, 'errors' => ['Der letzte Super-Administrator kann nicht gelöscht werden']];
        }
    }
    
    // Benutzer deaktivieren statt löschen (Soft Delete)
    $result = updateData('admins', [
        'is_active' => 0,
        'username' => $user['username'] . '_deleted_' . time(),
        'email' => $user['email'] . '_deleted_' . time()
    ], 'id = :id', ['id' => $userId]);
    
    if ($result !== false) {
        // Alle Sessions des Benutzers beenden
        executeQuery("UPDATE user_sessions SET is_active = 0 WHERE user_id = :user_id", ['user_id' => $userId]);
        
        // Audit-Log
        logAdminAction('user_deleted', 'user', $userId, $user, null);
        
        return ['success' => true];
    }
    
    return ['success' => false, 'errors' => ['Fehler beim Löschen des Benutzers']];
}

/**
 * Benutzer-Daten validieren
 */
function validateUserData($data, $excludeUserId = null) {
    $errors = [];
    
    // Username validierung
    if (empty($data['username'])) {
        $errors[] = 'Benutzername ist erforderlich';
    } elseif (strlen($data['username']) < 3) {
        $errors[] = 'Benutzername muss mindestens 3 Zeichen lang sein';
    } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $data['username'])) {
        $errors[] = 'Benutzername darf nur Buchstaben, Zahlen, Bindestriche und Unterstriche enthalten';
    } else {
        // Prüfen ob Username bereits verwendet wird
        $whereClause = "username = :username";
        $params = ['username' => $data['username']];
        
        if ($excludeUserId) {
            $whereClause .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeUserId;
        }
        
        $existing = fetchOne("SELECT id FROM admins WHERE $whereClause", $params);
        if ($existing) {
            $errors[] = 'Benutzername bereits vergeben';
        }
    }
    
    // E-Mail validierung
    if (empty($data['email'])) {
        $errors[] = 'E-Mail ist erforderlich';
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Ungültige E-Mail-Adresse';
    } else {
        // Prüfen ob E-Mail bereits verwendet wird
        $whereClause = "email = :email";
        $params = ['email' => $data['email']];
        
        if ($excludeUserId) {
            $whereClause .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeUserId;
        }
        
        $existing = fetchOne("SELECT id FROM admins WHERE $whereClause", $params);
        if ($existing) {
            $errors[] = 'E-Mail-Adresse bereits vergeben';
        }
    }
    
    // Passwort validierung (nur bei neuen Benutzern oder wenn Passwort gesetzt)
    if (!$excludeUserId || !empty($data['password'])) {
        if (empty($data['password'])) {
            $errors[] = 'Passwort ist erforderlich';
        } elseif (strlen($data['password']) < MIN_PASSWORD_LENGTH) {
            $errors[] = 'Passwort muss mindestens ' . MIN_PASSWORD_LENGTH . ' Zeichen lang sein';
        }
    }
    
    // Rolle validierung
    if (empty($data['role'])) {
        $errors[] = 'Rolle ist erforderlich';
    } else {
        $validRoles = getAvailableRoles();
        if (!array_key_exists($data['role'], $validRoles)) {
            $errors[] = 'Ungültige Rolle ausgewählt';
        }
    }
    
    return $errors;
}

/**
 * Verfügbare Rollen abrufen
 */
function getAvailableRoles() {
    $roles = fetchAll("SELECT role_name, role_display_name FROM user_roles WHERE is_active = 1 ORDER BY role_display_name");
    $roleArray = [];
    foreach ($roles as $role) {
        $roleArray[$role['role_name']] = $role['role_display_name'];
    }
    return $roleArray;
}

/**
 * Berechtigungen einer Rolle abrufen
 */
function getRolePermissions($roleName) {
    $role = fetchOne("SELECT permissions FROM user_roles WHERE role_name = :role", ['role' => $roleName]);
    return $role ? $role['permissions'] : '';
}

/**
 * Benutzer-Berechtigungen prüfen
 */
function hasPermission($permission, $userId = null) {
    if ($userId === null) {
        $userId = $_SESSION['admin_id'] ?? null;
    }
    
    if (!$userId) {
        return false;
    }
    
    // Super-Admin hat alle Berechtigungen
    $user = fetchOne("SELECT role, permissions FROM admins WHERE id = :id AND is_active = 1", ['id' => $userId]);
    if (!$user) {
        return false;
    }
    
    if ($user['role'] === 'super_admin') {
        return true;
    }
    
    // Custom Permissions prüfen
    if (!empty($user['permissions'])) {
        $permissions = explode(',', $user['permissions']);
        if (in_array('all', $permissions) || in_array($permission, $permissions)) {
            return true;
        }
    }
    
    // Rollen-Permissions prüfen
    $rolePermissions = getRolePermissions($user['role']);
    if ($rolePermissions) {
        $permissions = explode(',', $rolePermissions);
        if (in_array('all', $permissions) || in_array($permission, $permissions)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Middleware für Permission-Check
 */
function requirePermission($permission) {
    if (!hasPermission($permission)) {
        if (isAjaxRequest()) {
            http_response_code(403);
            echo json_encode(['error' => 'Keine Berechtigung']);
            exit;
        } else {
            setFlashMessage('error', 'Sie haben keine Berechtigung für diese Aktion.');
            redirect('dashboard.php');
        }
    }
}

/**
 * Ajax-Request prüfen
 */
function isAjaxRequest() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Admin-Aktion protokollieren
 */
function logAdminAction($action, $targetType = null, $targetId = null, $oldValues = null, $newValues = null) {
    $userId = $_SESSION['admin_id'] ?? null;
    $ipAddress = getUserIP();
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    insertData('admin_audit_log', [
        'user_id' => $userId,
        'action' => $action,
        'target_type' => $targetType,
        'target_id' => $targetId,
        'old_values' => $oldValues ? json_encode($oldValues) : null,
        'new_values' => $newValues ? json_encode($newValues) : null,
        'ip_address' => $ipAddress,
        'user_agent' => $userAgent
    ]);
}

/**
 * Alle Benutzer abrufen
 */
function getAllUsers($includeInactive = false) {
    $whereClause = $includeInactive ? "" : "WHERE is_active = 1";
    return fetchAll("
        SELECT 
            a.id, a.username, a.email, a.role, a.first_name, a.last_name, 
            a.is_active, a.email_verified, a.last_login, a.created_at,
            r.role_display_name,
            creator.username as created_by_username
        FROM admins a 
        LEFT JOIN user_roles r ON a.role = r.role_name
        LEFT JOIN admins creator ON a.created_by = creator.id
        $whereClause
        ORDER BY a.created_at DESC
    ");
}

/**
 * Benutzer-Details abrufen
 */
function getUserDetails($userId) {
    return fetchOne("
        SELECT 
            a.*,
            r.role_display_name,
            creator.username as created_by_username
        FROM admins a 
        LEFT JOIN user_roles r ON a.role = r.role_name
        LEFT JOIN admins creator ON a.created_by = creator.id
        WHERE a.id = :id
    ", ['id' => $userId]);
}

/**
 * Erweiterte Session-Verwaltung
 */
function createUserSession($userId, $sessionId = null) {
    if (!$sessionId) {
        $sessionId = session_id();
    }
    
    $expiresAt = date('Y-m-d H:i:s', time() + SESSION_TIMEOUT);
    
    return insertData('user_sessions', [
        'user_id' => $userId,
        'session_id' => $sessionId,
        'ip_address' => getUserIP(),
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'expires_at' => $expiresAt
    ]);
}

/**
 * Session aktualisieren
 */
function updateUserSession($sessionId) {
    $expiresAt = date('Y-m-d H:i:s', time() + SESSION_TIMEOUT);
    
    return updateData('user_sessions', [
        'last_activity' => date('Y-m-d H:i:s'),
        'expires_at' => $expiresAt
    ], 'session_id = :session_id AND is_active = 1', ['session_id' => $sessionId]);
}

/**
 * Session beenden
 */
function destroyUserSession($sessionId) {
    return updateData('user_sessions', [
        'is_active' => 0
    ], 'session_id = :session_id', ['session_id' => $sessionId]);
}

/**
 * Alle Sessions eines Benutzers beenden
 */
function destroyAllUserSessions($userId) {
    return updateData('user_sessions', [
        'is_active' => 0
    ], 'user_id = :user_id', ['user_id' => $userId]);
}

/**
 * Aktive Sessions eines Benutzers abrufen
 */
function getUserActiveSessions($userId) {
    return fetchAll("
        SELECT session_id, ip_address, user_agent, last_activity, created_at 
        FROM user_sessions 
        WHERE user_id = :user_id AND is_active = 1 AND expires_at > NOW()
        ORDER BY last_activity DESC
    ", ['user_id' => $userId]);
}

/**
 * Abgelaufene Sessions bereinigen
 */
function cleanupExpiredSessions() {
    return executeQuery("
        UPDATE user_sessions 
        SET is_active = 0 
        WHERE expires_at < NOW() OR last_activity < DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ");
}

/**
 * Rolle erstellen
 */
function createRole($roleData) {
    // Validierung
    if (empty($roleData['role_name']) || empty($roleData['role_display_name'])) {
        return ['success' => false, 'errors' => ['Rollenname und Anzeigename sind erforderlich']];
    }
    
    // Prüfen ob Rolle bereits existiert
    $existing = fetchOne("SELECT id FROM user_roles WHERE role_name = :role_name", [
        'role_name' => $roleData['role_name']
    ]);
    
    if ($existing) {
        return ['success' => false, 'errors' => ['Rolle bereits vorhanden']];
    }
    
    // Rolle erstellen
    $roleId = insertData('user_roles', [
        'role_name' => $roleData['role_name'],
        'role_display_name' => $roleData['role_display_name'],
        'description' => $roleData['description'] ?? null,
        'permissions' => $roleData['permissions'] ?? '',
        'is_system_role' => 0
    ]);
    
    if ($roleId) {
        logAdminAction('role_created', 'role', $roleId, null, $roleData);
        return ['success' => true, 'role_id' => $roleId];
    }
    
    return ['success' => false, 'errors' => ['Fehler beim Erstellen der Rolle']];
}

/**
 * Rolle bearbeiten
 */
function updateRole($roleId, $roleData) {
    // Prüfen ob Rolle existiert
    $currentRole = fetchOne("SELECT * FROM user_roles WHERE id = :id", ['id' => $roleId]);
    if (!$currentRole) {
        return ['success' => false, 'errors' => ['Rolle nicht gefunden']];
    }
    
    // System-Rollen können nicht bearbeitet werden
    if ($currentRole['is_system_role']) {
        return ['success' => false, 'errors' => ['System-Rollen können nicht bearbeitet werden']];
    }
    
    // Rolle aktualisieren
    $result = updateData('user_roles', [
        'role_display_name' => $roleData['role_display_name'],
        'description' => $roleData['description'] ?? null,
        'permissions' => $roleData['permissions'] ?? '',
        'is_active' => $roleData['is_active'] ?? 1
    ], 'id = :id', ['id' => $roleId]);
    
    if ($result !== false) {
        logAdminAction('role_updated', 'role', $roleId, $currentRole, $roleData);
        return ['success' => true];
    }
    
    return ['success' => false, 'errors' => ['Fehler beim Aktualisieren der Rolle']];
}

/**
 * Rolle löschen
 */
function deleteRole($roleId) {
    // Prüfen ob Rolle existiert
    $role = fetchOne("SELECT * FROM user_roles WHERE id = :id", ['id' => $roleId]);
    if (!$role) {
        return ['success' => false, 'errors' => ['Rolle nicht gefunden']];
    }
    
    // System-Rollen können nicht gelöscht werden
    if ($role['is_system_role']) {
        return ['success' => false, 'errors' => ['System-Rollen können nicht gelöscht werden']];
    }
    
    // Prüfen ob Rolle noch von Benutzern verwendet wird
    $usersWithRole = fetchOne("SELECT COUNT(*) as count FROM admins WHERE role = :role", [
        'role' => $role['role_name']
    ]);
    
    if ($usersWithRole['count'] > 0) {
        return ['success' => false, 'errors' => [
            'Rolle wird noch von ' . $usersWithRole['count'] . ' Benutzer(n) verwendet'
        ]];
    }
    
    // Rolle löschen
    $result = executeQuery("DELETE FROM user_roles WHERE id = :id", ['id' => $roleId]);
    
    if ($result) {
        logAdminAction('role_deleted', 'role', $roleId, $role, null);
        return ['success' => true];
    }
    
    return ['success' => false, 'errors' => ['Fehler beim Löschen der Rolle']];
}

/**
 * Alle verfügbaren Permissions abrufen
 */
function getAllPermissions() {
    return fetchAll("
        SELECT permission_key, permission_name, description, category
        FROM permissions 
        ORDER BY category, permission_name
    ");
}

/**
 * Permissions nach Kategorie gruppiert abrufen
 */
function getPermissionsByCategory() {
    $permissions = getAllPermissions();
    $grouped = [];
    
    foreach ($permissions as $permission) {
        $category = $permission['category'];
        if (!isset($grouped[$category])) {
            $grouped[$category] = [];
        }
        $grouped[$category][] = $permission;
    }
    
    return $grouped;
}

/**
 * Erweiterte Login-Funktion mit Session-Management
 */
function authenticateUser($username, $password, $rememberMe = false) {
    // Rate Limiting prüfen
    $userIP = getUserIP();
    if (checkLoginAttempts($userIP) >= MAX_LOGIN_ATTEMPTS) {
        return ['success' => false, 'error' => 'Zu viele fehlgeschlagene Versuche'];
    }
    
    // Benutzer laden
    $user = fetchOne("
        SELECT id, username, password, email, role, is_active, email_verified, 
               two_factor_enabled, login_attempts, locked_until
        FROM admins 
        WHERE username = :username OR email = :username
    ", ['username' => $username]);
    
    if (!$user) {
        logLoginAttempt($userIP, $username, false);
        return ['success' => false, 'error' => 'Ungültige Anmeldedaten'];
    }
    
    // Account-Status prüfen
    if (!$user['is_active']) {
        logLoginAttempt($userIP, $username, false);
        return ['success' => false, 'error' => 'Account ist deaktiviert'];
    }
    
    // Account-Sperrung prüfen
    if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
        logLoginAttempt($userIP, $username, false);
        return ['success' => false, 'error' => 'Account ist temporär gesperrt'];
    }
    
    // Passwort prüfen
    if (!password_verify($password, $user['password'])) {
        // Login-Versuche erhöhen
        $attempts = $user['login_attempts'] + 1;
        $lockUntil = null;
        
        if ($attempts >= MAX_LOGIN_ATTEMPTS) {
            $lockUntil = date('Y-m-d H:i:s', time() + LOGIN_LOCKOUT_TIME);
        }
        
        updateData('admins', [
            'login_attempts' => $attempts,
            'locked_until' => $lockUntil
        ], 'id = :id', ['id' => $user['id']]);
        
        logLoginAttempt($userIP, $username, false);
        return ['success' => false, 'error' => 'Ungültige Anmeldedaten'];
    }
    
    // Erfolgreicher Login
    session_regenerate_id(true);
    
    // Session-Daten setzen
    $_SESSION['admin_id'] = $user['id'];
    $_SESSION['admin_username'] = $user['username'];
    $_SESSION['admin_role'] = $user['role'];
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $_SESSION['last_activity'] = time();
    
    // Login-Versuche zurücksetzen
    updateData('admins', [
        'login_attempts' => 0,
        'locked_until' => null,
        'last_login' => date('Y-m-d H:i:s')
    ], 'id = :id', ['id' => $user['id']]);
    
    // Session in Datenbank speichern
    createUserSession($user['id']);
    
    // Login protokollieren
    logLoginAttempt($userIP, $username, true);
    logAdminAction('user_login', 'user', $user['id']);
    
    return ['success' => true, 'user' => $user, 'requires_2fa' => $user['two_factor_enabled']];
}

/**
 * Benutzer ausloggen
 */
function logoutUser($destroyAllSessions = false) {
    $userId = $_SESSION['admin_id'] ?? null;
    $sessionId = session_id();
    
    if ($userId) {
        if ($destroyAllSessions) {
            destroyAllUserSessions($userId);
        } else {
            destroyUserSession($sessionId);
        }
        
        logAdminAction('user_logout', 'user', $userId);
    }
    
    // PHP Session zerstören
    session_destroy();
    
    // Neue Session starten für Flash Messages
    session_start();
    session_regenerate_id(true);
}

/**
 * Passwort-Stärke prüfen
 */
function validatePasswordStrength($password) {
    $errors = [];
    
    if (strlen($password) < MIN_PASSWORD_LENGTH) {
        $errors[] = 'Passwort muss mindestens ' . MIN_PASSWORD_LENGTH . ' Zeichen lang sein';
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Passwort muss mindestens einen Großbuchstaben enthalten';
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Passwort muss mindestens einen Kleinbuchstaben enthalten';
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Passwort muss mindestens eine Ziffer enthalten';
    }
    
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = 'Passwort muss mindestens ein Sonderzeichen enthalten';
    }
    
    return $errors;
}

/**
 * Passwort ändern
 */
function changeUserPassword($userId, $currentPassword, $newPassword) {
    // Aktuellen Benutzer laden
    $user = fetchOne("SELECT password FROM admins WHERE id = :id", ['id' => $userId]);
    if (!$user) {
        return ['success' => false, 'errors' => ['Benutzer nicht gefunden']];
    }
    
    // Aktuelles Passwort prüfen
    if (!password_verify($currentPassword, $user['password'])) {
        return ['success' => false, 'errors' => ['Aktuelles Passwort ist falsch']];
    }
    
    // Neues Passwort validieren
    $errors = validatePasswordStrength($newPassword);
    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }
    
    // Passwort aktualisieren
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $result = updateData('admins', [
        'password' => $hashedPassword,
        'last_password_change' => date('Y-m-d H:i:s')
    ], 'id = :id', ['id' => $userId]);
    
    if ($result !== false) {
        logAdminAction('password_changed', 'user', $userId);
        return ['success' => true];
    }
    
    return ['success' => false, 'errors' => ['Fehler beim Ändern des Passworts']];
}

/**
 * Two-Factor Authentication aktivieren
 */
function enableTwoFactor($userId) {
    require_once 'vendor/autoload.php'; // Google2FA Library
    
    $google2fa = new PragmaRX\Google2FA\Google2FA();
    $secretKey = $google2fa->generateSecretKey();
    
    // Secret in Datenbank speichern (noch nicht aktiviert)
    $result = updateData('admins', [
        'two_factor_secret' => $secretKey
    ], 'id = :id', ['id' => $userId]);
    
    if ($result !== false) {
        $user = fetchOne("SELECT username FROM admins WHERE id = :id", ['id' => $userId]);
        $qrCodeUrl = $google2fa->getQRCodeUrl(
            SITE_NAME,
            $user['username'],
            $secretKey
        );
        
        return ['success' => true, 'secret' => $secretKey, 'qr_code' => $qrCodeUrl];
    }
    
    return ['success' => false, 'errors' => ['Fehler beim Aktivieren von 2FA']];
}

/**
 * Two-Factor Code verifizieren
 */
function verifyTwoFactorCode($userId, $code) {
    require_once 'vendor/autoload.php';
    
    $user = fetchOne("SELECT two_factor_secret FROM admins WHERE id = :id", ['id' => $userId]);
    if (!$user || !$user['two_factor_secret']) {
        return false;
    }
    
    $google2fa = new PragmaRX\Google2FA\Google2FA();
    return $google2fa->verifyKey($user['two_factor_secret'], $code);
}

/**
 * Audit Log abrufen
 */
function getAuditLog($limit = 100, $filters = []) {
    $whereConditions = [];
    $params = [];
    
    if (!empty($filters['user_id'])) {
        $whereConditions[] = "al.user_id = :user_id";
        $params['user_id'] = $filters['user_id'];
    }
    
    if (!empty($filters['action'])) {
        $whereConditions[] = "al.action = :action";
        $params['action'] = $filters['action'];
    }
    
    if (!empty($filters['target_type'])) {
        $whereConditions[] = "al.target_type = :target_type";
        $params['target_type'] = $filters['target_type'];
    }
    
    if (!empty($filters['date_from'])) {
        $whereConditions[] = "al.created_at >= :date_from";
        $params['date_from'] = $filters['date_from'];
    }
    
    if (!empty($filters['date_to'])) {
        $whereConditions[] = "al.created_at <= :date_to";
        $params['date_to'] = $filters['date_to'];
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    $params['limit'] = $limit;
    
    return fetchAll("
        SELECT 
            al.*,
            a.username as user_username
        FROM admin_audit_log al
        LEFT JOIN admins a ON al.user_id = a.id
        $whereClause
        ORDER BY al.created_at DESC
        LIMIT :limit
    ", $params);
}