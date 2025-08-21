<?php
require_once '../config/config.php';

// Login und Berechtigung prüfen
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = 'users.php';
    redirect('login.php');
}

// Basis-Berechtigung für Benutzer-Ansicht prüfen
requirePermission('users.read');

$currentUser = getCurrentUser();
$error = '';
$success = '';

// POST-Anfragen verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    if (!validateCSRFToken($csrfToken)) {
        $error = 'Ungültiger Sicherheitstoken.';
    } else {
        switch ($action) {
            case 'create_user':
                if (hasPermission('users.create')) {
                    $result = handleCreateUser();
                    if ($result['success']) {
                        $success = $result['message'];
                    } else {
                        $error = $result['message'];
                    }
                } else {
                    $error = 'Keine Berechtigung zum Erstellen von Benutzern.';
                }
                break;
                
            case 'update_user':
                if (hasPermission('users.update')) {
                    $result = handleUpdateUser();
                    if ($result['success']) {
                        $success = $result['message'];
                    } else {
                        $error = $result['message'];
                    }
                } else {
                    $error = 'Keine Berechtigung zum Bearbeiten von Benutzern.';
                }
                break;
                
            case 'delete_user':
                if (hasPermission('users.delete')) {
                    $result = handleDeleteUser();
                    if ($result['success']) {
                        $success = $result['message'];
                    } else {
                        $error = $result['message'];
                    }
                } else {
                    $error = 'Keine Berechtigung zum Löschen von Benutzern.';
                }
                break;
                
            case 'toggle_user_status':
                if (hasPermission('users.activate')) {
                    $result = handleToggleUserStatus();
                    if ($result['success']) {
                        $success = $result['message'];
                    } else {
                        $error = $result['message'];
                    }
                } else {
                    $error = 'Keine Berechtigung zum Aktivieren/Deaktivieren von Benutzern.';
                }
                break;
                
            case 'unlock_user':
                if (hasPermission('users.update')) {
                    $result = handleUnlockUser();
                    if ($result['success']) {
                        $success = $result['message'];
                    } else {
                        $error = $result['message'];
                    }
                } else {
                    $error = 'Keine Berechtigung zum Entsperren von Benutzern.';
                }
                break;
                
            case 'reset_password':
                if (hasPermission('users.reset_password')) {
                    $result = handleResetPassword();
                    if ($result['success']) {
                        $success = $result['message'];
                    } else {
                        $error = $result['message'];
                    }
                } else {
                    $error = 'Keine Berechtigung zum Zurücksetzen von Passwörtern.';
                }
                break;
                
            case 'generate_api_key':
                if (hasPermission('api.admin')) {
                    $result = handleGenerateApiKey();
                    if ($result['success']) {
                        $success = $result['message'];
                    } else {
                        $error = $result['message'];
                    }
                } else {
                    $error = 'Keine Berechtigung zum Generieren von API-Schlüsseln.';
                }
                break;
        }
        
        // Nach POST-Aktion redirecten um Reload-Problem zu vermeiden
        if ($success) {
            setFlashMessage('success', $success);
        }
        if ($error) {
            setFlashMessage('error', $error);
        }
        redirect('users.php');
    }
}

// Flash Messages verarbeiten
$flashMessages = getFlashMessages();

// Handler-Funktionen
function handleCreateUser() {
    $username = sanitizeInput($_POST['username'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $firstName = sanitizeInput($_POST['first_name'] ?? '');
    $lastName = sanitizeInput($_POST['last_name'] ?? '');
    $role = sanitizeInput($_POST['role'] ?? 'admin');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $mustChangePassword = isset($_POST['must_change_password']) ? 1 : 0;
    $permissions = $_POST['permissions'] ?? [];
    
    // Validierung
    if (empty($username) || empty($email) || empty($firstName) || empty($lastName) || empty($password)) {
        return ['success' => false, 'message' => 'Alle Pflichtfelder müssen ausgefüllt werden.'];
    }
    
    if (!isValidUsername($username)) {
        return ['success' => false, 'message' => 'Ungültiger Benutzername. Nur 3-20 Zeichen, Buchstaben, Zahlen und Unterstriche erlaubt.'];
    }
    
    if (!isValidEmail($email)) {
        return ['success' => false, 'message' => 'Ungültige E-Mail-Adresse.'];
    }
    
    if ($password !== $confirmPassword) {
        return ['success' => false, 'message' => 'Passwörter stimmen nicht überein.'];
    }
    
    $passwordErrors = validatePassword($password);
    if (!empty($passwordErrors)) {
        return ['success' => false, 'message' => implode(' ', $passwordErrors)];
    }
    
    if (isPasswordCompromised($password)) {
        return ['success' => false, 'message' => 'Das Passwort ist zu häufig verwendet und unsicher.'];
    }
    
    // Prüfen ob Benutzername oder E-Mail bereits existiert
    if (getUserByUsername($username)) {
        return ['success' => false, 'message' => 'Ein Benutzer mit diesem Benutzernamen existiert bereits.'];
    }
    
    if (getUserByEmail($email)) {
        return ['success' => false, 'message' => 'Ein Benutzer mit dieser E-Mail-Adresse existiert bereits.'];
    }
    
    // Rolle validieren
    $validRoles = getAllRoles(true);
    $roleExists = false;
    foreach ($validRoles as $validRole) {
        if ($validRole['name'] === $role) {
            $roleExists = true;
            break;
        }
    }
    
    if (!$roleExists) {
        return ['success' => false, 'message' => 'Ungültige Rolle ausgewählt.'];
    }
    
    // Benutzer erstellen
    $userData = [
        'username' => $username,
        'email' => $email,
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'role' => $role,
        'first_name' => $firstName,
        'last_name' => $lastName,
        'phone' => $phone ?: null,
        'is_active' => $isActive,
        'must_change_password' => $mustChangePassword,
        'permissions' => !empty($permissions) ? json_encode($permissions) : null,
        'password_changed_at' => date('Y-m-d H:i:s'),
        'created_by' => getCurrentUser()['id']
    ];
    
    $userId = insertData('admins', $userData);
    
    if ($userId) {
        // Aktivität protokollieren
        logAdminActivity(
            getCurrentUser()['id'],
            'user_created',
            "Benutzer '{$username}' erstellt",
            'admin',
            $userId,
            null,
            $userData
        );
        
        return ['success' => true, 'message' => "Benutzer '{$username}' wurde erfolgreich erstellt."];
    } else {
        return ['success' => false, 'message' => 'Fehler beim Erstellen des Benutzers.'];
    }
}

function handleUpdateUser() {
    $userId = (int)($_POST['user_id'] ?? 0);
    $username = sanitizeInput($_POST['username'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $firstName = sanitizeInput($_POST['first_name'] ?? '');
    $lastName = sanitizeInput($_POST['last_name'] ?? '');
    $role = sanitizeInput($_POST['role'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $mustChangePassword = isset($_POST['must_change_password']) ? 1 : 0;
    $permissions = $_POST['permissions'] ?? [];
    $notes = sanitizeInput($_POST['notes'] ?? '');
    
    if ($userId <= 0) {
        return ['success' => false, 'message' => 'Ungültige Benutzer-ID.'];
    }
    
    // Benutzer laden
    $user = getUserById($userId);
    if (!$user) {
        return ['success' => false, 'message' => 'Benutzer nicht gefunden.'];
    }
    
    // Sich selbst deaktivieren verhindern
    $currentUser = getCurrentUser();
    if ($userId == $currentUser['id'] && !$isActive) {
        return ['success' => false, 'message' => 'Sie können sich nicht selbst deaktivieren.'];
    }
    
    // Super-Admin-Schutz
    if ($user['role'] === 'super_admin' && $currentUser['role'] !== 'super_admin') {
        return ['success' => false, 'message' => 'Nur Super-Administratoren können andere Super-Administratoren bearbeiten.'];
    }
    
    // Validierung
    if (empty($username) || empty($email) || empty($firstName) || empty($lastName)) {
        return ['success' => false, 'message' => 'Alle Pflichtfelder müssen ausgefüllt werden.'];
    }
    
    if (!isValidUsername($username)) {
        return ['success' => false, 'message' => 'Ungültiger Benutzername.'];
    }
    
    if (!isValidEmail($email)) {
        return ['success' => false, 'message' => 'Ungültige E-Mail-Adresse.'];
    }
    
    // Prüfen ob Benutzername oder E-Mail bereits von anderem Benutzer verwendet wird
    $existingUsername = getUserByUsername($username);
    if ($existingUsername && $existingUsername['id'] != $userId) {
        return ['success' => false, 'message' => 'Dieser Benutzername wird bereits verwendet.'];
    }
    
    $existingEmail = getUserByEmail($email);
    if ($existingEmail && $existingEmail['id'] != $userId) {
        return ['success' => false, 'message' => 'Diese E-Mail-Adresse wird bereits verwendet.'];
    }
    
    // Alte Daten für Aktivitätslog speichern
    $oldData = $user;
    
    // Update-Daten vorbereiten
    $updateData = [
        'username' => $username,
        'email' => $email,
        'first_name' => $firstName,
        'last_name' => $lastName,
        'role' => $role,
        'phone' => $phone ?: null,
        'is_active' => $isActive,
        'must_change_password' => $mustChangePassword,
        'permissions' => !empty($permissions) ? json_encode($permissions) : null,
        'notes' => $notes ?: null
    ];
    
    $result = updateData('admins', $updateData, 'id = :id', ['id' => $userId]);
    
    if ($result !== false) {
        // Aktivität protokollieren
        logAdminActivity(
            $currentUser['id'],
            'user_updated',
            "Benutzer '{$username}' bearbeitet",
            'admin',
            $userId,
            $oldData,
            array_merge($updateData, ['id' => $userId])
        );
        
        return ['success' => true, 'message' => "Benutzer '{$username}' wurde erfolgreich aktualisiert."];
    } else {
        return ['success' => false, 'message' => 'Fehler beim Aktualisieren des Benutzers.'];
    }
}

function handleDeleteUser() {
    $userId = (int)($_POST['user_id'] ?? 0);
    
    if ($userId <= 0) {
        return ['success' => false, 'message' => 'Ungültige Benutzer-ID.'];
    }
    
    $user = getUserById($userId);
    if (!$user) {
        return ['success' => false, 'message' => 'Benutzer nicht gefunden.'];
    }
    
    $currentUser = getCurrentUser();
    
    // Sich selbst löschen verhindern
    if ($userId == $currentUser['id']) {
        return ['success' => false, 'message' => 'Sie können sich nicht selbst löschen.'];
    }
    
    // Super-Admin-Schutz
    if ($user['role'] === 'super_admin' && $currentUser['role'] !== 'super_admin') {
        return ['success' => false, 'message' => 'Nur Super-Administratoren können andere Super-Administratoren löschen.'];
    }
    
    // Letzten Super-Admin schützen
    if ($user['role'] === 'super_admin') {
        $superAdminCount = fetchOne("SELECT COUNT(*) as count FROM admins WHERE role = 'super_admin' AND is_active = 1")['count'] ?? 0;
        if ($superAdminCount <= 1) {
            return ['success' => false, 'message' => 'Der letzte Super-Administrator kann nicht gelöscht werden.'];
        }
    }
    
    $username = $user['username'];
    
    // Benutzer löschen (CASCADE löscht auch Sessions und Activity Logs)
    $result = executeQuery("DELETE FROM admins WHERE id = :id", ['id' => $userId]);
    
    if ($result) {
        // Aktivität protokollieren
        logAdminActivity(
            $currentUser['id'],
            'user_deleted',
            "Benutzer '{$username}' gelöscht",
            'admin',
            $userId,
            $user,
            null
        );
        
        return ['success' => true, 'message' => "Benutzer '{$username}' wurde erfolgreich gelöscht."];
    } else {
        return ['success' => false, 'message' => 'Fehler beim Löschen des Benutzers.'];
    }
}

function handleToggleUserStatus() {
    $userId = (int)($_POST['user_id'] ?? 0);
    
    if ($userId <= 0) {
        return ['success' => false, 'message' => 'Ungültige Benutzer-ID.'];
    }
    
    $user = getUserById($userId);
    if (!$user) {
        return ['success' => false, 'message' => 'Benutzer nicht gefunden.'];
    }
    
    $currentUser = getCurrentUser();
    
    // Sich selbst deaktivieren verhindern
    if ($userId == $currentUser['id'] && $user['is_active']) {
        return ['success' => false, 'message' => 'Sie können sich nicht selbst deaktivieren.'];
    }
    
    $newStatus = $user['is_active'] ? 0 : 1;
    $statusText = $newStatus ? 'aktiviert' : 'deaktiviert';
    
    $result = updateData('admins', ['is_active' => $newStatus], 'id = :id', ['id' => $userId]);
    
    if ($result !== false) {
        // Bei Deaktivierung alle Sessions des Benutzers löschen
        if (!$newStatus) {
            executeQuery("DELETE FROM admin_sessions WHERE admin_id = :admin_id", ['admin_id' => $userId]);
        }
        
        // Aktivität protokollieren
        logAdminActivity(
            $currentUser['id'],
            'user_status_changed',
            "Benutzer '{$user['username']}' {$statusText}",
            'admin',
            $userId,
            ['is_active' => $user['is_active']],
            ['is_active' => $newStatus]
        );
        
        return ['success' => true, 'message' => "Benutzer '{$user['username']}' wurde {$statusText}."];
    } else {
        return ['success' => false, 'message' => 'Fehler beim Ändern des Benutzerstatus.'];
    }
}

function handleUnlockUser() {
    $userId = (int)($_POST['user_id'] ?? 0);
    
    if ($userId <= 0) {
        return ['success' => false, 'message' => 'Ungültige Benutzer-ID.'];
    }
    
    $user = getUserById($userId);
    if (!$user) {
        return ['success' => false, 'message' => 'Benutzer nicht gefunden.'];
    }
    
    $result = unlockUserAccount($user['username']);
    
    if ($result) {
        // Aktivität protokollieren
        logAdminActivity(
            getCurrentUser()['id'],
            'user_unlocked',
            "Benutzer '{$user['username']}' entsperrt",
            'admin',
            $userId,
            ['locked_until' => $user['locked_until']],
            ['locked_until' => null]
        );
        
        return ['success' => true, 'message' => "Benutzer '{$user['username']}' wurde entsperrt."];
    } else {
        return ['success' => false, 'message' => 'Fehler beim Entsperren des Benutzers.'];
    }
}

function handleResetPassword() {
    $userId = (int)($_POST['user_id'] ?? 0);
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if ($userId <= 0) {
        return ['success' => false, 'message' => 'Ungültige Benutzer-ID.'];
    }
    
    $user = getUserById($userId);
    if (!$user) {
        return ['success' => false, 'message' => 'Benutzer nicht gefunden.'];
    }
    
    if (empty($newPassword)) {
        return ['success' => false, 'message' => 'Neues Passwort ist erforderlich.'];
    }
    
    if ($newPassword !== $confirmPassword) {
        return ['success' => false, 'message' => 'Passwörter stimmen nicht überein.'];
    }
    
    $passwordErrors = validatePassword($newPassword);
    if (!empty($passwordErrors)) {
        return ['success' => false, 'message' => implode(' ', $passwordErrors)];
    }
    
    if (isPasswordCompromised($newPassword)) {
        return ['success' => false, 'message' => 'Das Passwort ist zu häufig verwendet und unsicher.'];
    }
    
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    $result = updateData('admins', [
        'password' => $hashedPassword,
        'password_changed_at' => date('Y-m-d H:i:s'),
        'must_change_password' => 1, // Benutzer muss Passwort beim nächsten Login ändern
        'failed_login_attempts' => 0,
        'locked_until' => null
    ], 'id = :id', ['id' => $userId]);
    
    if ($result !== false) {
        // Alle Sessions des Benutzers löschen
        executeQuery("DELETE FROM admin_sessions WHERE admin_id = :admin_id", ['admin_id' => $userId]);
        
        // Aktivität protokollieren
        logAdminActivity(
            getCurrentUser()['id'],
            'password_reset',
            "Passwort für Benutzer '{$user['username']}' zurückgesetzt",
            'admin',
            $userId
        );
        
        return ['success' => true, 'message' => "Passwort für Benutzer '{$user['username']}' wurde zurückgesetzt. Der Benutzer muss das Passwort beim nächsten Login ändern."];
    } else {
        return ['success' => false, 'message' => 'Fehler beim Zurücksetzen des Passworts.'];
    }
}

function handleGenerateApiKey() {
    $userId = (int)($_POST['user_id'] ?? 0);
    
    if ($userId <= 0) {
        return ['success' => false, 'message' => 'Ungültige Benutzer-ID.'];
    }
    
    $user = getUserById($userId);
    if (!$user) {
        return ['success' => false, 'message' => 'Benutzer nicht gefunden.'];
    }
    
    $apiKey = generateApiKey();
    
    $result = updateData('admins', ['api_key' => $apiKey], 'id = :id', ['id' => $userId]);
    
    if ($result !== false) {
        // Aktivität protokollieren
        logAdminActivity(
            getCurrentUser()['id'],
            'api_key_generated',
            "API-Schlüssel für Benutzer '{$user['username']}' generiert",
            'admin',
            $userId
        );
        
        return ['success' => true, 'message' => "Neuer API-Schlüssel für Benutzer '{$user['username']}' wurde generiert: " . $apiKey];
    } else {
        return ['success' => false, 'message' => 'Fehler beim Generieren des API-Schlüssels.'];
    }
}

// Benutzer laden
$users = getAllUsers(true);
$roles = getAllRoles(true);
$availablePermissions = AVAILABLE_PERMISSIONS;
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Benutzer-Management - <?php echo SITE_NAME; ?></title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&display=swap" rel="stylesheet">
    
    <!-- Styles -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    
    <style>
        .users-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .users-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: var(--gray);
            font-size: 0.9rem;
        }
        
        .users-filters {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        
        .filter-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .users-table {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            overflow: hidden;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 0.9rem;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-details h4 {
            margin: 0;
            color: var(--text);
            font-size: 1rem;
        }
        
        .user-details small {
            color: var(--gray);
            display: block;
            margin-top: 0.25rem;
        }
        
        .role-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: capitalize;
        }
        
        .role-super_admin {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        
        .role-admin {
            background: rgba(59, 130, 246, 0.2);
            color: #3b82f6;
            border: 1px solid rgba(59, 130, 246, 0.3);
        }
        
        .role-moderator {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        
        .role-support {
            background: rgba(245, 158, 11, 0.2);
            color: #f59e0b;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-active {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        
        .status-inactive {
            background: rgba(107, 114, 128, 0.2);
            color: #6b7280;
            border: 1px solid rgba(107, 114, 128, 0.3);
        }
        
        .status-locked {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .btn-small {
            padding: 0.4rem 0.8rem;
            font-size: 0.8rem;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            backdrop-filter: blur(10px);
        }
        
        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .modal-content {
            background: rgba(30, 41, 59, 0.95);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 2rem;
            width: 100%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .modal-title {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary);
        }
        
        .close-modal {
            background: none;
            border: none;
            color: var(--gray);
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        
        .close-modal:hover {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text);
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .form-group-full {
            grid-column: 1 / -1;
        }
        
        .permissions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .permission-category {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 1rem;
        }
        
        .permission-category h4 {
            color: var(--primary);
            margin-bottom: 1rem;
            font-size: 1rem;
        }
        
        .permission-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }
        
        .permission-item label {
            font-size: 0.9rem;
            color: var(--text);
            cursor: pointer;
        }
        
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .users-filters {
                flex-direction: column;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Background Effects -->
    <div class="bg-video"></div>
    <div class="bg-overlay"></div>
    
    <!-- Header -->
    <header class="dashboard-header">
        <div class="dashboard-nav">
            <div class="dashboard-title">👥 Benutzer-Management</div>
            <div class="user-info">
                <span>👋 <?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?></span>
                <a href="dashboard.php" class="btn btn-secondary">← Dashboard</a>
                <a href="logout.php" class="logout-btn">🚪 Abmelden</a>
            </div>
        </div>
    </header>
    
    <div class="admin-container">
        <!-- Flash Messages -->
        <?php if (!empty($flashMessages)): ?>
        <div class="flash-messages">
            <?php foreach ($flashMessages as $message): ?>
            <div class="flash-message <?php echo $message['type']; ?>">
                <?php echo htmlspecialchars($message['message']); ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <!-- Benutzer-Statistiken -->
        <div class="users-stats">
            <?php
            $totalUsers = count($users);
            $activeUsers = count(array_filter($users, fn($u) => $u['is_active']));
            $lockedUsers = count(array_filter($users, fn($u) => $u['locked_until'] && strtotime($u['locked_until']) > time()));
            $recentLogins = fetchOne("SELECT COUNT(DISTINCT admin_id) as count FROM admin_sessions WHERE last_activity > DATE_SUB(NOW(), INTERVAL 24 HOUR)")['count'] ?? 0;
            ?>
            <div class="stat-card">
                <div class="stat-number"><?php echo $totalUsers; ?></div>
                <div class="stat-label">👥 Gesamt Benutzer</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $activeUsers; ?></div>
                <div class="stat-label">✅ Aktive Benutzer</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $lockedUsers; ?></div>
                <div class="stat-label">🔒 Gesperrte Benutzer</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $recentLogins; ?></div>
                <div class="stat-label">📊 Aktive Sessions (24h)</div>
            </div>
        </div>
        
        <!-- Header mit Aktionen -->
        <div class="users-header">
            <h2>👥 Benutzer verwalten</h2>
            <?php if (hasPermission('users.create')): ?>
            <button onclick="openCreateUserModal()" class="btn btn-primary">
                ➕ Neuen Benutzer erstellen
            </button>
            <?php endif; ?>
        </div>
        
        <!-- Filter -->
        <div class="users-filters">
            <div class="filter-group">
                <label for="roleFilter">🎭 Rolle:</label>
                <select id="roleFilter" onchange="filterUsers()" class="form-control" style="width: auto;">
                    <option value="">Alle Rollen</option>
                    <?php foreach ($roles as $role): ?>
                    <option value="<?php echo htmlspecialchars($role['name']); ?>">
                        <?php echo htmlspecialchars($role['display_name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="statusFilter">📊 Status:</label>
                <select id="statusFilter" onchange="filterUsers()" class="form-control" style="width: auto;">
                    <option value="">Alle Status</option>
                    <option value="active">Aktiv</option>
                    <option value="inactive">Inaktiv</option>
                    <option value="locked">Gesperrt</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="searchFilter">🔍 Suche:</label>
                <input 
                    type="text" 
                    id="searchFilter" 
                    placeholder="Name, E-Mail oder Benutzername..." 
                    onkeyup="filterUsers()"
                    class="form-control"
                    style="width: 300px;"
                >
            </div>
            
            <div class="filter-group">
                <button onclick="resetFilters()" class="btn btn-secondary">🔄 Filter zurücksetzen</button>
            </div>
        </div>
        
        <!-- Benutzer-Tabelle -->
        <div class="users-table">
            <table class="table">
                <thead>
                    <tr>
                        <th>👤 Benutzer</th>
                        <th>🎭 Rolle</th>
                        <th>📊 Status</th>
                        <th>📅 Letzter Login</th>
                        <th>🔑 API-Schlüssel</th>
                        <th>⚡ Aktionen</th>
                    </tr>
                </thead>
                <tbody id="usersTableBody">
                    <?php foreach ($users as $user): ?>
                    <?php
                    $isLocked = $user['locked_until'] && strtotime($user['locked_until']) > time();
                    $status = $isLocked ? 'locked' : ($user['is_active'] ? 'active' : 'inactive');
                    $canEdit = hasPermission('users.update') && ($user['role'] !== 'super_admin' || $currentUser['role'] === 'super_admin');
                    $canDelete = hasPermission('users.delete') && $user['id'] != $currentUser['id'] && ($user['role'] !== 'super_admin' || $currentUser['role'] === 'super_admin');
                    ?>
                    <tr data-role="<?php echo htmlspecialchars($user['role']); ?>" 
                        data-status="<?php echo $status; ?>" 
                        data-search="<?php echo htmlspecialchars(strtolower($user['username'] . ' ' . $user['email'] . ' ' . $user['first_name'] . ' ' . $user['last_name'])); ?>">
                        
                        <td>
                            <div class="user-info">
                                <div class="user-avatar">
                                    <?php if ($user['avatar_url']): ?>
                                        <img src="<?php echo htmlspecialchars($user['avatar_url']); ?>" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                                    <?php else: ?>
                                        <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="user-details">
                                    <h4><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h4>
                                    <small>@<?php echo htmlspecialchars($user['username']); ?></small>
                                    <small><?php echo htmlspecialchars($user['email']); ?></small>
                                    <?php if ($user['phone']): ?>
                                    <small>📞 <?php echo htmlspecialchars($user['phone']); ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        
                        <td>
                            <span class="role-badge role-<?php echo htmlspecialchars($user['role']); ?>">
                                <?php echo htmlspecialchars($user['role_display_name'] ?? ucfirst($user['role'])); ?>
                            </span>
                        </td>
                        
                        <td>
                            <span class="status-badge status-<?php echo $status; ?>">
                                <?php if ($isLocked): ?>
                                    🔒 Gesperrt
                                    <small style="display: block; margin-top: 0.25rem;">
                                        bis <?php echo date('d.m.Y H:i', strtotime($user['locked_until'])); ?>
                                    </small>
                                <?php elseif ($user['is_active']): ?>
                                    ✅ Aktiv
                                <?php else: ?>
                                    ❌ Inaktiv
                                <?php endif; ?>
                            </span>
                            
                            <?php if ($user['must_change_password']): ?>
                            <small style="color: #f59e0b; display: block; margin-top: 0.25rem;">
                                🔄 Passwort ändern erforderlich
                            </small>
                            <?php endif; ?>
                        </td>
                        
                        <td>
                            <?php if ($user['last_login']): ?>
                                <?php echo date('d.m.Y H:i', strtotime($user['last_login'])); ?>
                                <small style="display: block; color: var(--gray);">
                                    (<?php echo $user['login_count'] ?? 0; ?> mal eingeloggt)
                                </small>
                            <?php else: ?>
                                <span style="color: var(--gray);">Noch nie</span>
                            <?php endif; ?>
                        </td>
                        
                        <td>
                            <?php if ($user['api_key']): ?>
                                <code style="font-size: 0.8rem; background: rgba(255,255,255,0.1); padding: 0.25rem; border-radius: 4px;">
                                    <?php echo substr($user['api_key'], 0, 8); ?>...
                                </code>
                            <?php else: ?>
                                <span style="color: var(--gray);">Kein Schlüssel</span>
                            <?php endif; ?>
                        </td>
                        
                        <td>
                            <div class="action-buttons">
                                <?php if ($canEdit): ?>
                                <button onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)" 
                                        class="btn btn-small btn-edit">✏️ Bearbeiten</button>
                                <?php endif; ?>
                                
                                <?php if (hasPermission('users.activate') && $user['id'] != $currentUser['id']): ?>
                                <button onclick="toggleUserStatus(<?php echo $user['id']; ?>, <?php echo $user['is_active'] ? 'false' : 'true'; ?>)" 
                                        class="btn btn-small <?php echo $user['is_active'] ? 'btn-warning' : 'btn-success'; ?>">
                                    <?php echo $user['is_active'] ? '🚫 Deaktivieren' : '✅ Aktivieren'; ?>
                                </button>
                                <?php endif; ?>
                                
                                <?php if ($isLocked && hasPermission('users.update')): ?>
                                <button onclick="unlockUser(<?php echo $user['id']; ?>)" 
                                        class="btn btn-small btn-success">🔓 Entsperren</button>
                                <?php endif; ?>
                                
                                <?php if (hasPermission('users.reset_password')): ?>
                                <button onclick="resetPassword(<?php echo $user['id']; ?>)" 
                                        class="btn btn-small btn-warning">🔑 Passwort</button>
                                <?php endif; ?>
                                
                                <?php if (hasPermission('api.admin')): ?>
                                <button onclick="generateApiKey(<?php echo $user['id']; ?>)" 
                                        class="btn btn-small btn-secondary">🗝️ API</button>
                                <?php endif; ?>
                                
                                <?php if ($canDelete): ?>
                                <button onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')" 
                                        class="btn btn-small btn-delete">🗑️ Löschen</button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Create User Modal -->
    <div id="createUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">👤 Neuen Benutzer erstellen</h3>
                <button class="close-modal" onclick="closeModal('createUserModal')">&times;</button>
            </div>
            
            <form method="POST" action="" id="createUserForm">
                <input type="hidden" name="action" value="create_user">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="create_username">🧑‍💼 Benutzername *</label>
                        <input type="text" 
                               id="create_username" 
                               name="username" 
                               class="form-control" 
                               required 
                               pattern="[a-zA-Z0-9_]{3,20}"
                               title="3-20 Zeichen, nur Buchstaben, Zahlen und Unterstriche"
                               maxlength="20">
                    </div>
                    
                    <div class="form-group">
                        <label for="create_email">📧 E-Mail *</label>
                        <input type="email" 
                               id="create_email" 
                               name="email" 
                               class="form-control" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="create_first_name">👤 Vorname *</label>
                        <input type="text" 
                               id="create_first_name" 
                               name="first_name" 
                               class="form-control" 
                               required 
                               maxlength="50">
                    </div>
                    
                    <div class="form-group">
                        <label for="create_last_name">👤 Nachname *</label>
                        <input type="text" 
                               id="create_last_name" 
                               name="last_name" 
                               class="form-control" 
                               required 
                               maxlength="50">
                    </div>
                    
                    <div class="form-group">
                        <label for="create_phone">📞 Telefon</label>
                        <input type="tel" 
                               id="create_phone" 
                               name="phone" 
                               class="form-control" 
                               maxlength="20">
                    </div>
                    
                    <div class="form-group">
                        <label for="create_role">🎭 Rolle *</label>
                        <select id="create_role" name="role" class="form-control" required>
                            <?php foreach ($roles as $role): ?>
                            <option value="<?php echo htmlspecialchars($role['name']); ?>">
                                <?php echo htmlspecialchars($role['display_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="create_password">🔐 Passwort *</label>
                        <input type="password" 
                               id="create_password" 
                               name="password" 
                               class="form-control" 
                               required 
                               minlength="<?php echo MIN_PASSWORD_LENGTH; ?>">
                        <small style="color: var(--gray); font-size: 0.8rem;">
                            Mindestens <?php echo MIN_PASSWORD_LENGTH; ?> Zeichen, Groß-/Kleinbuchstaben, Zahlen und Sonderzeichen
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="create_confirm_password">🔐 Passwort bestätigen *</label>
                        <input type="password" 
                               id="create_confirm_password" 
                               name="confirm_password" 
                               class="form-control" 
                               required>
                    </div>
                    
                    <div class="form-group-full">
                        <div style="display: flex; gap: 2rem; margin-bottom: 1rem;">
                            <label style="display: flex; align-items: center; gap: 0.5rem;">
                                <input type="checkbox" name="is_active" checked>
                                <span>✅ Benutzer ist aktiv</span>
                            </label>
                            
                            <label style="display: flex; align-items: center; gap: 0.5rem;">
                                <input type="checkbox" name="must_change_password" checked>
                                <span>🔄 Passwort beim ersten Login ändern</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group-full">
                        <h4 style="color: var(--primary); margin-bottom: 1rem;">🔑 Zusätzliche Berechtigungen</h4>
                        <div class="permissions-grid">
                            <?php 
                            $permissionCategories = [
                                'Benutzer' => ['users.create', 'users.read', 'users.update', 'users.delete', 'users.activate', 'users.reset_password'],
                                'Rollen' => ['roles.create', 'roles.read', 'roles.update', 'roles.delete', 'roles.assign'],
                                'System' => ['settings.read', 'settings.update', 'settings.backup', 'settings.restore'],
                                'Whitelist' => ['whitelist.read', 'whitelist.update', 'whitelist.approve', 'whitelist.reject', 'whitelist.delete', 'whitelist.questions.manage'],
                                'Content' => ['news.create', 'news.read', 'news.update', 'news.delete', 'news.publish', 'rules.create', 'rules.read', 'rules.update', 'rules.delete'],
                                'Logs' => ['logs.read', 'logs.delete', 'activity.read'],
                                'API' => ['api.access', 'api.admin']
                            ];
                            
                            foreach ($permissionCategories as $category => $permissions): ?>
                            <div class="permission-category">
                                <h4><?php echo $category; ?></h4>
                                <?php foreach ($permissions as $permission): ?>
                                    <?php if (isset($availablePermissions[$permission])): ?>
                                    <div class="permission-item">
                                        <input type="checkbox" 
                                               name="permissions[]" 
                                               value="<?php echo htmlspecialchars($permission); ?>" 
                                               id="create_perm_<?php echo str_replace('.', '_', $permission); ?>">
                                        <label for="create_perm_<?php echo str_replace('.', '_', $permission); ?>">
                                            <?php echo htmlspecialchars($availablePermissions[$permission]); ?>
                                        </label>
                                    </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                    <button type="button" onclick="closeModal('createUserModal')" class="btn btn-secondary">
                        ❌ Abbrechen
                    </button>
                    <button type="submit" class="btn btn-primary">
                        ✅ Benutzer erstellen
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit User Modal -->
    <div id="editUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">✏️ Benutzer bearbeiten</h3>
                <button class="close-modal" onclick="closeModal('editUserModal')">&times;</button>
            </div>
            
            <form method="POST" action="" id="editUserForm">
                <input type="hidden" name="action" value="update_user">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="user_id" id="edit_user_id">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="edit_username">🧑‍💼 Benutzername *</label>
                        <input type="text" 
                               id="edit_username" 
                               name="username" 
                               class="form-control" 
                               required 
                               pattern="[a-zA-Z0-9_]{3,20}"
                               maxlength="20">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_email">📧 E-Mail *</label>
                        <input type="email" 
                               id="edit_email" 
                               name="email" 
                               class="form-control" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_first_name">👤 Vorname *</label>
                        <input type="text" 
                               id="edit_first_name" 
                               name="first_name" 
                               class="form-control" 
                               required 
                               maxlength="50">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_last_name">👤 Nachname *</label>
                        <input type="text" 
                               id="edit_last_name" 
                               name="last_name" 
                               class="form-control" 
                               required 
                               maxlength="50">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_phone">📞 Telefon</label>
                        <input type="tel" 
                               id="edit_phone" 
                               name="phone" 
                               class="form-control" 
                               maxlength="20">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_role">🎭 Rolle *</label>
                        <select id="edit_role" name="role" class="form-control" required>
                            <?php foreach ($roles as $role): ?>
                            <option value="<?php echo htmlspecialchars($role['name']); ?>">
                                <?php echo htmlspecialchars($role['display_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group-full">
                        <div style="display: flex; gap: 2rem; margin-bottom: 1rem;">
                            <label style="display: flex; align-items: center; gap: 0.5rem;">
                                <input type="checkbox" name="is_active" id="edit_is_active">
                                <span>✅ Benutzer ist aktiv</span>
                            </label>
                            
                            <label style="display: flex; align-items: center; gap: 0.5rem;">
                                <input type="checkbox" name="must_change_password" id="edit_must_change_password">
                                <span>🔄 Passwort beim nächsten Login ändern</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group-full">
                        <label for="edit_notes">📝 Notizen</label>
                        <textarea id="edit_notes" 
                                  name="notes" 
                                  class="form-control" 
                                  rows="3"
                                  placeholder="Optionale Notizen zum Benutzer..."></textarea>
                    </div>
                    
                    <div class="form-group-full">
                        <h4 style="color: var(--primary); margin-bottom: 1rem;">🔑 Zusätzliche Berechtigungen</h4>
                        <div class="permissions-grid">
                            <?php foreach ($permissionCategories as $category => $permissions): ?>
                            <div class="permission-category">
                                <h4><?php echo $category; ?></h4>
                                <?php foreach ($permissions as $permission): ?>
                                    <?php if (isset($availablePermissions[$permission])): ?>
                                    <div class="permission-item">
                                        <input type="checkbox" 
                                               name="permissions[]" 
                                               value="<?php echo htmlspecialchars($permission); ?>" 
                                               id="edit_perm_<?php echo str_replace('.', '_', $permission); ?>">
                                        <label for="edit_perm_<?php echo str_replace('.', '_', $permission); ?>">
                                            <?php echo htmlspecialchars($availablePermissions[$permission]); ?>
                                        </label>
                                    </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                    <button type="button" onclick="closeModal('editUserModal')" class="btn btn-secondary">
                        ❌ Abbrechen
                    </button>
                    <button type="submit" class="btn btn-primary">
                        ✅ Änderungen speichern
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Reset Password Modal -->
    <div id="resetPasswordModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h3 class="modal-title">🔑 Passwort zurücksetzen</h3>
                <button class="close-modal" onclick="closeModal('resetPasswordModal')">&times;</button>
            </div>
            
            <form method="POST" action="" id="resetPasswordForm">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="user_id" id="reset_user_id">
                
                <div class="form-group">
                    <label for="reset_new_password">🔐 Neues Passwort *</label>
                    <input type="password" 
                           id="reset_new_password" 
                           name="new_password" 
                           class="form-control" 
                           required 
                           minlength="<?php echo MIN_PASSWORD_LENGTH; ?>">
                    <small style="color: var(--gray); font-size: 0.8rem;">
                        Mindestens <?php echo MIN_PASSWORD_LENGTH; ?> Zeichen, Groß-/Kleinbuchstaben, Zahlen und Sonderzeichen
                    </small>
                </div>
                
                <div class="form-group">
                    <label for="reset_confirm_password">🔐 Passwort bestätigen *</label>
                    <input type="password" 
                           id="reset_confirm_password" 
                           name="confirm_password" 
                           class="form-control" 
                           required>
                </div>
                
                <div style="background: rgba(255, 193, 7, 0.1); border: 1px solid rgba(255, 193, 7, 0.3); border-radius: 8px; padding: 1rem; margin: 1rem 0; color: #f59e0b;">
                    <strong>⚠️ Hinweis:</strong>
                    <ul style="margin: 0.5rem 0 0 1rem; padding: 0;">
                        <li>Der Benutzer wird automatisch abgemeldet</li>
                        <li>Das Passwort muss beim nächsten Login geändert werden</li>
                        <li>Account-Sperrungen werden aufgehoben</li>
                    </ul>
                </div>
                
                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                    <button type="button" onclick="closeModal('resetPasswordModal')" class="btn btn-secondary">
                        ❌ Abbrechen
                    </button>
                    <button type="submit" class="btn btn-warning">
                        🔑 Passwort zurücksetzen
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Modal-Funktionen
        function openCreateUserModal() {
            document.getElementById('createUserModal').classList.add('active');
            document.getElementById('create_username').focus();
        }
        
        function editUser(user) {
            // Form mit Benutzerdaten füllen
            document.getElementById('edit_user_id').value = user.id;
            document.getElementById('edit_username').value = user.username;
            document.getElementById('edit_email').value = user.email;
            document.getElementById('edit_first_name').value = user.first_name;
            document.getElementById('edit_last_name').value = user.last_name;
            document.getElementById('edit_phone').value = user.phone || '';
            document.getElementById('edit_role').value = user.role;
            document.getElementById('edit_is_active').checked = user.is_active == 1;
            document.getElementById('edit_must_change_password').checked = user.must_change_password == 1;
            document.getElementById('edit_notes').value = user.notes || '';
            
            // Berechtigungen setzen
            const userPermissions = user.permissions ? JSON.parse(user.permissions) : [];
            document.querySelectorAll('#editUserModal input[name="permissions[]"]').forEach(checkbox => {
                checkbox.checked = userPermissions.includes(checkbox.value);
            });
            
            document.getElementById('editUserModal').classList.add('active');
        }
        
        function resetPassword(userId) {
            document.getElementById('reset_user_id').value = userId;
            document.getElementById('resetPasswordModal').classList.add('active');
            document.getElementById('reset_new_password').focus();
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }
        
        // Filter-Funktionen
        function filterUsers() {
            const roleFilter = document.getElementById('roleFilter').value.toLowerCase();
            const statusFilter = document.getElementById('statusFilter').value.toLowerCase();
            const searchFilter = document.getElementById('searchFilter').value.toLowerCase();
            
            const rows = document.querySelectorAll('#usersTableBody tr');
            let visibleCount = 0;
            
            rows.forEach(row => {
                const role = row.getAttribute('data-role');
                const status = row.getAttribute('data-status');
                const searchData = row.getAttribute('data-search');
                
                let show = true;
                
                if (roleFilter && role !== roleFilter) {
                    show = false;
                }
                
                if (statusFilter && status !== statusFilter) {
                    show = false;
                }
                
                if (searchFilter && !searchData.includes(searchFilter)) {
                    show = false;
                }
                
                if (show) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Anzahl aktualisieren (falls ein Counter existiert)
            const counter = document.getElementById('visibleUsersCount');
            if (counter) {
                counter.textContent = visibleCount;
            }
        }
        
        function resetFilters() {
            document.getElementById('roleFilter').value = '';
            document.getElementById('statusFilter').value = '';
            document.getElementById('searchFilter').value = '';
            filterUsers();
        }
        
        // Action-Funktionen
        function toggleUserStatus(userId, activate) {
            const action = activate ? 'aktivieren' : 'deaktivieren';
            if (confirm(`Sind Sie sicher, dass Sie diesen Benutzer ${action} möchten?`)) {
                submitForm('toggle_user_status', { user_id: userId });
            }
        }
        
        function unlockUser(userId) {
            if (confirm('Sind Sie sicher, dass Sie diesen Benutzer entsperren möchten?')) {
                submitForm('unlock_user', { user_id: userId });
            }
        }
        
        function generateApiKey(userId) {
            if (confirm('Einen neuen API-Schlüssel generieren? Der alte Schlüssel wird ungültig.')) {
                submitForm('generate_api_key', { user_id: userId });
            }
        }
        
        function deleteUser(userId, username) {
            if (confirm(`Sind Sie SICHER, dass Sie den Benutzer "${username}" DAUERHAFT löschen möchten?\n\nDiese Aktion kann NICHT rückgängig gemacht werden!`)) {
                submitForm('delete_user', { user_id: userId });
            }
        }
        
        function submitForm(action, data) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '';
            
            // CSRF Token hinzufügen
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = 'csrf_token';
            csrfInput.value = '<?php echo generateCSRFToken(); ?>';
            form.appendChild(csrfInput);
            
            // Action hinzufügen
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = action;
            form.appendChild(actionInput);
            
            // Daten hinzufügen
            for (const [key, value] of Object.entries(data)) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = value;
                form.appendChild(input);
            }
            
            document.body.appendChild(form);
            form.submit();
        }
        
        // Form-Validierung
        document.getElementById('createUserForm').addEventListener('submit', function(e) {
            const password = document.getElementById('create_password').value;
            const confirmPassword = document.getElementById('create_confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Die Passwörter stimmen nicht überein.');
                return false;
            }
            
            if (password.length < <?php echo MIN_PASSWORD_LENGTH; ?>) {
                e.preventDefault();
                alert('Das Passwort muss mindestens <?php echo MIN_PASSWORD_LENGTH; ?> Zeichen lang sein.');
                return false;
            }
        });
        
        document.getElementById('resetPasswordForm').addEventListener('submit', function(e) {
            const password = document.getElementById('reset_new_password').value;
            const confirmPassword = document.getElementById('reset_confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Die Passwörter stimmen nicht überein.');
                return false;
            }
        });
        
        // Keyboard Shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl+N für neuen Benutzer
            if (e.ctrlKey && e.key === 'n') {
                e.preventDefault();
                <?php if (hasPermission('users.create')): ?>
                openCreateUserModal();
                <?php endif; ?>
            }
            
            // Escape zum Schließen von Modals
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal.active').forEach(modal => {
                    modal.classList.remove('active');
                });
            }
        });
        
        // Modal außerhalb klicken zum Schließen
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                }
            });
        });
        
        // Auto-Save für Suchfilter
        document.getElementById('searchFilter').addEventListener('input', function() {
            localStorage.setItem('users_search_filter', this.value);
        });
        
        // Filter beim Laden wiederherstellen
        document.addEventListener('DOMContentLoaded', function() {
            const savedSearch = localStorage.getItem('users_search_filter');
            if (savedSearch) {
                document.getElementById('searchFilter').value = savedSearch;
                filterUsers();
            }
        });
    </script>
</body>
</html>