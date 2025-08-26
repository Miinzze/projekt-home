<?php
/**
 * Hauptkonfiguration für FiveM Zombie RP Website
 * Erweitert mit Benutzerverwaltung, Berechtigungssystem und Roadmap-Management
 */

// Zeitzone setzen
date_default_timezone_set('Europe/Berlin');

// Fehlerberichterstattung (in Produktion auf false setzen)
define('DEBUG_MODE', false);

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Website Einstellungen
define('SITE_NAME', 'Zombie RP Server');
define('SITE_URL', 'http://projekt-z.eu');
define('ADMIN_EMAIL', 'admin@projekt-z.eu');

// Discord OAuth2 Einstellungen
define('DISCORD_API_URL', 'https://discord.com/api/v10');

// Sicherheitseinstellungen
define('SESSION_TIMEOUT', 3600); // 1 Stunde in Sekunden
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 Minuten in Sekunden
define('CSRF_TOKEN_EXPIRE', 1800); // 30 Minuten

// Passwort Einstellungen
define('MIN_PASSWORD_LENGTH', 8);
define('PASSWORD_COST', 12);
define('PASSWORD_REQUIRE_UPPERCASE', true);
define('PASSWORD_REQUIRE_LOWERCASE', true);
define('PASSWORD_REQUIRE_NUMBERS', true);
define('PASSWORD_REQUIRE_SPECIAL', true);
define('PASSWORD_HISTORY_COUNT', 5); // Anzahl der letzten Passwörter die nicht wiederverwendet werden können

// Whitelist Scoring Einstellungen
define('WHITELIST_PASSING_SCORE', 70);
define('WHITELIST_AUTO_APPROVE', false);

// Benutzer-Management Einstellungen
define('USER_LOCKOUT_DURATION', 1800); // 30 Minuten in Sekunden
define('MAX_FAILED_LOGIN_ATTEMPTS', 5);
define('SESSION_REGENERATE_INTERVAL', 300); // 5 Minuten
define('API_RATE_LIMIT', 100); // Anfragen pro Stunde
define('AVATAR_MAX_SIZE', 2097152); // 2MB in Bytes
define('AVATAR_ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/gif']);

// Roadmap-Einstellungen
define('ROADMAP_ITEMS_PER_PAGE', 10);
define('ROADMAP_MAX_PRIORITY', 5);
define('ROADMAP_AUTO_COMPLETE_ON_DATE', false); // Automatisch als "completed" markieren wenn Datum erreicht

// Streamer-Integration Einstellungen
define('TWITCH_API_URL', 'https://api.twitch.tv/helix');
define('TWITCH_MAX_STREAMS', 5);
define('TWITCH_CACHE_TIME', 300); // 5 Minuten

// Standard-Berechtigungen definieren (ERWEITERT)
define('AVAILABLE_PERMISSIONS', [
    // Benutzer-Management
    'users.create' => 'Neue Benutzer erstellen',
    'users.read' => 'Benutzer anzeigen',
    'users.update' => 'Benutzer bearbeiten',
    'users.delete' => 'Benutzer löschen',
    'users.activate' => 'Benutzer aktivieren/deaktivieren',
    'users.reset_password' => 'Passwörter zurücksetzen',
    
    // Rollen-Management
    'roles.create' => 'Neue Rollen erstellen',
    'roles.read' => 'Rollen anzeigen',
    'roles.update' => 'Rollen bearbeiten',
    'roles.delete' => 'Rollen löschen',
    'roles.assign' => 'Rollen zuweisen',
    
    // System-Einstellungen
    'settings.read' => 'Einstellungen anzeigen',
    'settings.update' => 'Einstellungen bearbeiten',
    'settings.backup' => 'System-Backup erstellen',
    'settings.restore' => 'System-Backup wiederherstellen',
    
    // Whitelist-Management
    'whitelist.read' => 'Whitelist-Bewerbungen anzeigen',
    'whitelist.update' => 'Whitelist-Bewerbungen bearbeiten',
    'whitelist.approve' => 'Whitelist-Bewerbungen genehmigen',
    'whitelist.reject' => 'Whitelist-Bewerbungen ablehnen',
    'whitelist.delete' => 'Whitelist-Bewerbungen löschen',
    'whitelist.questions.manage' => 'Whitelist-Fragen verwalten',
    
    // News-Management
    'news.create' => 'News erstellen',
    'news.read' => 'News anzeigen',
    'news.update' => 'News bearbeiten',
    'news.delete' => 'News löschen',
    'news.publish' => 'News veröffentlichen',
    
    // Regeln-Management
    'rules.create' => 'Regeln erstellen',
    'rules.read' => 'Regeln anzeigen',
    'rules.update' => 'Regeln bearbeiten',
    'rules.delete' => 'Regeln löschen',
    
    // ROADMAP-MANAGEMENT (NEU)
    'roadmap.create' => 'Roadmap-Einträge erstellen',
    'roadmap.read' => 'Roadmap-Einträge anzeigen',
    'roadmap.update' => 'Roadmap-Einträge bearbeiten',
    'roadmap.delete' => 'Roadmap-Einträge löschen',
    'roadmap.publish' => 'Roadmap-Einträge veröffentlichen',
    'roadmap.export' => 'Roadmap-Daten exportieren',
    
    // STREAMER-MANAGEMENT (NEU)
    'streamers.read' => 'Streamer-Daten anzeigen',
    'streamers.update' => 'Streamer-Daten bearbeiten',
    'streamers.add' => 'Neue Streamer hinzufügen',
    'streamers.remove' => 'Streamer entfernen',
    'streamers.config' => 'Streamer-Konfiguration bearbeiten',
    
    // Logs und Überwachung
    'logs.read' => 'System-Logs anzeigen',
    'logs.delete' => 'System-Logs löschen',
    'activity.read' => 'Aktivitätslogs anzeigen',
    
    // API-Zugriff
    'api.access' => 'API-Zugriff',
    'api.admin' => 'Admin-API Zugriff'
]);

// Status-Konfiguration für Roadmap-Einträge
define('ROADMAP_STATUSES', [
    'planned' => [
        'label' => 'Geplant',
        'color' => '#6b7280',
        'icon' => '📋',
        'description' => 'Feature ist für die Zukunft vorgesehen'
    ],
    'in_progress' => [
        'label' => 'In Arbeit',
        'color' => '#f59e0b',
        'icon' => '⚙️',
        'description' => 'Entwicklung läuft aktiv'
    ],
    'testing' => [
        'label' => 'Testing',
        'color' => '#8b5cf6',
        'icon' => '🧪',
        'description' => 'Feature wird getestet und optimiert'
    ],
    'completed' => [
        'label' => 'Abgeschlossen',
        'color' => '#10b981',
        'icon' => '✅',
        'description' => 'Feature ist live und verfügbar'
    ],
    'cancelled' => [
        'label' => 'Abgebrochen',
        'color' => '#ef4444',
        'icon' => '❌',
        'description' => 'Feature wird nicht umgesetzt'
    ]
]);

// Prioritäts-Konfiguration für Roadmap-Einträge
define('ROADMAP_PRIORITIES', [
    1 => [
        'label' => 'Sehr hoch',
        'color' => '#ff4444',
        'icon' => '🔥',
        'description' => 'Kritische Features, Bugfixes oder Updates'
    ],
    2 => [
        'label' => 'Hoch',
        'color' => '#ff8800',
        'icon' => '🟠',
        'description' => 'Wichtige Features für das Gameplay'
    ],
    3 => [
        'label' => 'Normal',
        'color' => '#ffaa00',
        'icon' => '🟡',
        'description' => 'Standard-Features und Verbesserungen'
    ],
    4 => [
        'label' => 'Niedrig',
        'color' => '#0088ff',
        'icon' => '🔵',
        'description' => 'Nice-to-have Features'
    ],
    5 => [
        'label' => 'Sehr niedrig',
        'color' => '#888888',
        'icon' => '⚪',
        'description' => 'Zukunftsideen und Experimente'
    ]
]);

// Session-Konfiguration
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.gc_maxlifetime', SESSION_TIMEOUT);
    
    session_start();
}

// Include Datenbankverbindung
if (file_exists(__DIR__ . '/database.php')) {
    require_once __DIR__ . '/database.php';
}

/**
 * CSRF Token Funktionen
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token']) || 
        !isset($_SESSION['csrf_token_time']) || 
        (time() - $_SESSION['csrf_token_time']) > CSRF_TOKEN_EXPIRE) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && 
           isset($_SESSION['csrf_token_time']) &&
           hash_equals($_SESSION['csrf_token'], $token) &&
           (time() - $_SESSION['csrf_token_time']) <= CSRF_TOKEN_EXPIRE;
}

/**
 * Erweiterte Session-Verwaltung
 */
function secureSession() {
    // Session Timeout prüfen
    if (isset($_SESSION['last_activity']) && 
        (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        destroyUserSession();
        return false;
    }
    
    // Session regenerieren (alle 5 Minuten)
    if (isset($_SESSION['last_regeneration']) && 
        (time() - $_SESSION['last_regeneration']) > SESSION_REGENERATE_INTERVAL) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
    
    $_SESSION['last_activity'] = time();
    
    // Session Hijacking Schutz
    if (!isset($_SESSION['user_agent'])) {
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
    } elseif ($_SESSION['user_agent'] !== ($_SERVER['HTTP_USER_AGENT'] ?? '')) {
        destroyUserSession();
        return false;
    }
    
    return true;
}

/**
 * Benutzer-Authentifizierung
 */
function isLoggedIn() {
    if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_username'])) {
        return false;
    }
    
    if (!secureSession()) {
        return false;
    }
    
    // Benutzer-Status in Datenbank prüfen
    if (function_exists('getUserById')) {
        $user = getUserById($_SESSION['admin_id']);
        if (!$user || !$user['is_active']) {
            destroyUserSession();
            return false;
        }
        
        // Account-Sperrung prüfen
        if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
            destroyUserSession();
            return false;
        }
    }
    
    return true;
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    if (function_exists('getUserById')) {
        return getUserById($_SESSION['admin_id']);
    }
    
    return null;
}

function destroyUserSession() {
    // Admin-Session in DB löschen falls vorhanden
    if (isset($_SESSION['session_id']) && function_exists('destroyAdminSession')) {
        destroyAdminSession($_SESSION['session_id']);
    }
    
    session_destroy();
    session_start();
}

/**
 * Erweitertetes Berechtigungs-System
 */
function hasPermission($permission) {
    $user = getCurrentUser();
    if (!$user) {
        return false;
    }
    
    // Super-Admin hat alle Rechte
    if ($user['role'] === 'super_admin') {
        return true;
    }
    
    // Individuelle Berechtigungen prüfen
    $userPermissions = [];
    if ($user['permissions']) {
        $userPermissions = json_decode($user['permissions'], true) ?: [];
    }
    
    // Rollen-basierte Berechtigungen laden
    if (function_exists('getRoleByName')) {
        $role = getRoleByName($user['role']);
        if ($role && $role['permissions']) {
            $rolePermissions = json_decode($role['permissions'], true) ?: [];
            $userPermissions = array_merge($userPermissions, $rolePermissions);
        }
    }
    
    return in_array($permission, $userPermissions);
}

function requirePermission($permission) {
    if (!hasPermission($permission)) {
        if (isAjaxRequest()) {
            http_response_code(403);
            echo json_encode(['error' => 'Keine Berechtigung für diese Aktion']);
            exit();
        } else {
            setFlashMessage('error', 'Sie haben keine Berechtigung für diese Aktion.');
            redirect('dashboard.php');
        }
    }
}

function isAjaxRequest() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Roadmap-spezifische Funktionen
 */
function getRoadmapStatusLabel($status) {
    $statuses = ROADMAP_STATUSES;
    return $statuses[$status]['label'] ?? ucfirst($status);
}

function getRoadmapStatusColor($status) {
    $statuses = ROADMAP_STATUSES;
    return $statuses[$status]['color'] ?? '#6b7280';
}

function getRoadmapStatusIcon($status) {
    $statuses = ROADMAP_STATUSES;
    return $statuses[$status]['icon'] ?? '📋';
}

function getRoadmapPriorityLabel($priority) {
    $priorities = ROADMAP_PRIORITIES;
    return $priorities[$priority]['label'] ?? 'Normal';
}

function getRoadmapPriorityColor($priority) {
    $priorities = ROADMAP_PRIORITIES;
    return $priorities[$priority]['color'] ?? '#ffaa00';
}

function getRoadmapPriorityIcon($priority) {
    $priorities = ROADMAP_PRIORITIES;
    return $priorities[$priority]['icon'] ?? '🟡';
}

function createRoadmapItem($title, $description, $status = 'planned', $priority = 3, $estimatedDate = null, $createdBy = null) {
    if (!function_exists('insertData')) {
        return false;
    }
    
    if (!$createdBy) {
        $currentUser = getCurrentUser();
        $createdBy = $currentUser['id'] ?? null;
    }
    
    if (!$createdBy) {
        return false;
    }
    
    return insertData('roadmap_items', [
        'title' => $title,
        'description' => $description,
        'status' => $status,
        'priority' => $priority,
        'estimated_completion_date' => $estimatedDate,
        'created_by' => $createdBy
    ]);
}

function updateRoadmapItem($id, $data, $updatedBy = null) {
    if (!function_exists('updateData')) {
        return false;
    }
    
    if (!$updatedBy) {
        $currentUser = getCurrentUser();
        $updatedBy = $currentUser['id'] ?? null;
    }
    
    if ($updatedBy) {
        $data['updated_by'] = $updatedBy;
    }
    
    return updateData('roadmap_items', $data, 'id = :id', ['id' => $id]);
}

function deleteRoadmapItem($id) {
    if (!function_exists('executeQuery')) {
        return false;
    }
    
    return executeQuery("DELETE FROM roadmap_items WHERE id = :id", ['id' => $id]);
}

function getRoadmapItems($status = null, $priority = null, $isActive = true, $limit = null) {
    if (!function_exists('fetchAll')) {
        return [];
    }
    
    $sql = "SELECT r.*, 
                   creator.username as created_by_name, creator.first_name as creator_first_name, creator.last_name as creator_last_name,
                   updater.username as updated_by_name, updater.first_name as updater_first_name, updater.last_name as updater_last_name
            FROM roadmap_items r 
            LEFT JOIN admins creator ON r.created_by = creator.id
            LEFT JOIN admins updater ON r.updated_by = updater.id
            WHERE 1=1";
    
    $params = [];
    
    if ($status !== null) {
        $sql .= " AND r.status = :status";
        $params['status'] = $status;
    }
    
    if ($priority !== null) {
        $sql .= " AND r.priority = :priority";
        $params['priority'] = $priority;
    }
    
    if ($isActive !== null) {
        $sql .= " AND r.is_active = :is_active";
        $params['is_active'] = $isActive ? 1 : 0;
    }
    
    $sql .= " ORDER BY r.priority ASC, r.created_at DESC";
    
    if ($limit) {
        $sql .= " LIMIT :limit";
        $params['limit'] = $limit;
    }
    
    return fetchAll($sql, $params);
}

function getRoadmapItemsByStatus($status, $isActive = true) {
    return getRoadmapItems($status, null, $isActive);
}

function getRoadmapItemsByPriority($priority, $isActive = true) {
    return getRoadmapItems(null, $priority, $isActive);
}

/**
 * Passwort-Validierung
 */
function validatePassword($password) {
    $errors = [];
    
    if (strlen($password) < MIN_PASSWORD_LENGTH) {
        $errors[] = 'Passwort muss mindestens ' . MIN_PASSWORD_LENGTH . ' Zeichen lang sein.';
    }
    
    if (PASSWORD_REQUIRE_UPPERCASE && !preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Passwort muss mindestens einen Großbuchstaben enthalten.';
    }
    
    if (PASSWORD_REQUIRE_LOWERCASE && !preg_match('/[a-z]/', $password)) {
        $errors[] = 'Passwort muss mindestens einen Kleinbuchstaben enthalten.';
    }
    
    if (PASSWORD_REQUIRE_NUMBERS && !preg_match('/[0-9]/', $password)) {
        $errors[] = 'Passwort muss mindestens eine Zahl enthalten.';
    }
    
    if (PASSWORD_REQUIRE_SPECIAL && !preg_match('/[^a-zA-Z0-9]/', $password)) {
        $errors[] = 'Passwort muss mindestens ein Sonderzeichen enthalten.';
    }
    
    return $errors;
}

function isPasswordCompromised($password) {
    // Einfache Blacklist für häufige Passwörter
    $commonPasswords = [
        'password', '123456', '123456789', 'qwerty', 'abc123',
        'password123', 'admin', 'letmein', 'welcome', 'monkey'
    ];
    
    return in_array(strtolower($password), $commonPasswords);
}

/**
 * Login-Versuche und Account-Sperrung
 */
function checkLoginAttempts($ip, $username = null) {
    if (!function_exists('fetchOne')) {
        return 0;
    }
    
    $sql = "SELECT COUNT(*) as attempts FROM login_attempts 
            WHERE ip_address = :ip AND success = 0 
            AND attempted_at > DATE_SUB(NOW(), INTERVAL :lockout_time SECOND)";
    
    $params = [
        'ip' => $ip,
        'lockout_time' => LOGIN_LOCKOUT_TIME
    ];
    
    if ($username) {
        $sql .= " AND username = :username";
        $params['username'] = $username;
    }
    
    $result = fetchOne($sql, $params);
    return $result ? $result['attempts'] : 0;
}

function logLoginAttempt($ip, $username, $success, $failureReason = null) {
    if (!function_exists('insertData')) {
        return false;
    }
    
    $data = [
        'ip_address' => $ip,
        'username' => $username,
        'success' => $success ? 1 : 0,
        'failure_reason' => $failureReason,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
    ];
    
    $result = insertData('login_attempts', $data);
    
    // Bei mehreren fehlgeschlagenen Versuchen Account sperren
    if (!$success && $username && function_exists('updateData')) {
        $attempts = checkLoginAttempts($ip, $username);
        if ($attempts >= MAX_FAILED_LOGIN_ATTEMPTS) {
            lockUserAccount($username);
        }
    }
    
    return $result;
}

function lockUserAccount($username) {
    if (!function_exists('updateData')) {
        return false;
    }
    
    $lockUntil = date('Y-m-d H:i:s', time() + USER_LOCKOUT_DURATION);
    
    return updateData('admins', 
        [
            'locked_until' => $lockUntil,
            'failed_login_attempts' => 0
        ],
        'username = :username',
        ['username' => $username]
    );
}

function unlockUserAccount($userId) {
    if (!function_exists('updateData')) {
        return false;
    }
    
    return updateData('admins',
        [
            'locked_until' => null,
            'failed_login_attempts' => 0
        ],
        'id = :id',
        ['id' => $userId]
    );
}

/**
 * Server-Einstellungen
 */
function getUserIP() {
    $ip_keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
    
    foreach ($ip_keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = $_SERVER[$key];
            if (strpos($ip, ',') !== false) {
                $ip = trim(explode(',', $ip)[0]);
            }
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function getServerSetting($key, $default = '') {
    if (!function_exists('fetchOne')) {
        return $default;
    }
    
    $sql = "SELECT setting_value FROM server_settings WHERE setting_key = :key";
    $result = fetchOne($sql, ['key' => $key]);
    return $result ? $result['setting_value'] : $default;
}

function setServerSetting($key, $value, $type = 'text', $description = '') {
    if (!function_exists('fetchOne') || !function_exists('updateData') || !function_exists('insertData')) {
        return false;
    }
    
    $existing = fetchOne("SELECT id FROM server_settings WHERE setting_key = :key", ['key' => $key]);
    
    if ($existing) {
        return updateData('server_settings', 
            ['setting_value' => $value], 
            'setting_key = :key', 
            ['key' => $key]
        );
    } else {
        return insertData('server_settings', [
            'setting_key' => $key,
            'setting_value' => $value,
            'setting_type' => $type,
            'description' => $description
        ]);
    }
}

/**
 * Discord Integration
 */
function getDiscordConfig() {
    return [
        'client_id' => getServerSetting('discord_client_id'),
        'client_secret' => getServerSetting('discord_client_secret'),
        'redirect_uri' => getServerSetting('discord_redirect_uri', SITE_URL . '/whitelist/discord-callback.php'),
        'scope' => 'identify',
        'auth_url' => 'https://discord.com/api/oauth2/authorize',
        'token_url' => 'https://discord.com/api/oauth2/token',
        'user_url' => 'https://discord.com/api/v10/users/@me'
    ];
}

function getDiscordAuthUrl($state = null) {
    $config = getDiscordConfig();
    
    if (empty($config['client_id'])) {
        return false;
    }
    
    if (!$state) {
        $state = bin2hex(random_bytes(16));
        $_SESSION['discord_state'] = $state;
    }
    
    $params = [
        'client_id' => $config['client_id'],
        'redirect_uri' => $config['redirect_uri'],
        'response_type' => 'code',
        'scope' => $config['scope'],
        'state' => $state
    ];
    
    return $config['auth_url'] . '?' . http_build_query($params);
}

function exchangeDiscordCode($code) {
    $config = getDiscordConfig();
    
    $data = [
        'client_id' => $config['client_id'],
        'client_secret' => $config['client_secret'],
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => $config['redirect_uri']
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $config['token_url']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        return json_decode($response, true);
    }
    
    return false;
}

function getDiscordUser($accessToken) {
    $config = getDiscordConfig();
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $config['user_url']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        return json_decode($response, true);
    }
    
    return false;
}

/**
 * Whitelist-Funktionen
 */
function getWhitelistQuestions($limit = null) {
    if (!function_exists('fetchAll')) {
        return [];
    }
    
    if ($limit === null) {
        $limit = (int)getServerSetting('whitelist_questions_count', 5);
    }
    
    $sql = "SELECT * FROM whitelist_questions 
            WHERE is_active = 1 
            ORDER BY question_order ASC, id ASC 
            LIMIT :limit";
    
    return fetchAll($sql, ['limit' => $limit]);
}

function evaluateAnswer($question, $userAnswer) {
    if (empty($question['correct_answer'])) {
        return null;
    }
    
    $correctAnswer = $question['correct_answer'];
    $userAnswer = trim($userAnswer);
    
    if ($question['question_type'] === 'multiple_choice') {
        return strcasecmp($userAnswer, $correctAnswer) === 0;
    } else {
        $keywords = array_map('trim', explode(',', $correctAnswer));
        $userAnswerLower = strtolower($userAnswer);
        
        foreach ($keywords as $keyword) {
            if (strpos($userAnswerLower, strtolower($keyword)) !== false) {
                return true;
            }
        }
        return false;
    }
}

function createWhitelistApplication($discordData, $answers) {
    if (!function_exists('insertData') || !function_exists('fetchOne')) {
        return false;
    }
    
    $existing = fetchOne(
        "SELECT id FROM whitelist_applications WHERE discord_id = :discord_id AND status = 'pending'",
        ['discord_id' => $discordData['id']]
    );
    
    if ($existing) {
        return false;
    }
    
    $applicationId = insertData('whitelist_applications', [
        'discord_id' => $discordData['id'],
        'discord_username' => $discordData['username'] . '#' . ($discordData['discriminator'] ?? '0'),
        'discord_avatar' => $discordData['avatar'] ? 
            'https://cdn.discordapp.com/avatars/' . $discordData['id'] . '/' . $discordData['avatar'] . '.png' : null
    ]);
    
    if (!$applicationId) {
        return false;
    }
    
    $totalQuestions = 0;
    $correctAnswers = 0;
    
    $questions = fetchAll("SELECT * FROM whitelist_questions WHERE is_active = 1");
    $questionMap = [];
    foreach ($questions as $q) {
        $questionMap[$q['id']] = $q;
    }
    
    foreach ($answers as $questionId => $answer) {
        $totalQuestions++;
        
        $isCorrect = false;
        $autoEvaluated = false;
        
        if (isset($questionMap[$questionId])) {
            $question = $questionMap[$questionId];
            $evaluation = evaluateAnswer($question, $answer);
            
            if ($evaluation !== null) {
                $isCorrect = $evaluation;
                $autoEvaluated = true;
                if ($isCorrect) {
                    $correctAnswers++;
                }
            }
        }
        
        insertData('whitelist_answers', [
            'application_id' => $applicationId,
            'question_id' => $questionId,
            'answer' => $answer,
            'is_correct' => $isCorrect ? 1 : 0,
            'auto_evaluated' => $autoEvaluated ? 1 : 0
        ]);
    }
    
    $scorePercentage = $totalQuestions > 0 ? ($correctAnswers / $totalQuestions) * 100 : 0;
    
    $updateData = [
        'total_questions' => $totalQuestions,
        'correct_answers' => $correctAnswers,
        'score_percentage' => round($scorePercentage, 2)
    ];
    
    if (WHITELIST_AUTO_APPROVE && $scorePercentage >= WHITELIST_PASSING_SCORE) {
        $updateData['status'] = 'approved';
        $updateData['reviewed_at'] = date('Y-m-d H:i:s');
        $updateData['notes'] = 'Automatisch genehmigt aufgrund hoher Punktzahl (' . round($scorePercentage, 1) . '%)';
    }
    
    updateData('whitelist_applications', $updateData, 'id = :id', ['id' => $applicationId]);
    
    return $applicationId;
}

/**
 * Utility-Funktionen
 */
function redirect($url, $permanent = false) {
    if (!headers_sent()) {
        header('Location: ' . $url, true, $permanent ? 301 : 302);
        exit();
    } else {
        echo '<script>window.location.href="' . $url . '";</script>';
        exit();
    }
}

function setFlashMessage($type, $message) {
    $_SESSION['flash_messages'][] = ['type' => $type, 'message' => $message];
}

function getFlashMessages() {
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

function generateApiKey() {
    return bin2hex(random_bytes(32));
}

function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}

function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function isValidUsername($username) {
    return preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username);
}

/**
 * Fallback-Funktionen für DB-Operationen
 */
if (!function_exists('insertData')) {
    function insertData($table, $data) {
        if (!isset($GLOBALS['pdo'])) {
            return false;
        }
        
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
}

if (!function_exists('updateData')) {
    function updateData($table, $data, $where, $whereParams = []) {
        if (!isset($GLOBALS['pdo'])) {
            return false;
        }
        
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
}

// Session sicher initialisieren
secureSession();
?>