<?php
require_once '../config/config.php';

// Bereits eingeloggt? Dann zur Dashboard weiterleiten
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';
$loginBlocked = false;
$accountLocked = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';
    $userIP = getUserIP();
    
    // CSRF Token validieren
    if (!validateCSRFToken($csrfToken)) {
        $error = 'Ungültiger Sicherheitstoken. Bitte versuchen Sie es erneut.';
    }
    // Login-Versuche prüfen
    elseif (checkLoginAttempts($userIP) >= MAX_LOGIN_ATTEMPTS) {
        $loginBlocked = true;
        $error = 'Zu viele fehlgeschlagene Login-Versuche von dieser IP-Adresse. Bitte warten Sie ' . (LOGIN_LOCKOUT_TIME / 60) . ' Minuten.';
    }
    // Eingaben validieren
    elseif (empty($username) || empty($password)) {
        $error = 'Bitte füllen Sie alle Felder aus.';
        logLoginAttempt($userIP, $username, false, 'Leere Felder');
    } else {
        // Benutzer aus Datenbank laden
        $admin = getUserByUsername($username);
        
        if (!$admin) {
            $error = 'Ungültiger Benutzername oder Passwort.';
            logLoginAttempt($userIP, $username, false, 'Benutzer nicht gefunden');
        } elseif (!$admin['is_active']) {
            $error = 'Ihr Account wurde deaktiviert. Wenden Sie sich an einen Administrator.';
            logLoginAttempt($userIP, $username, false, 'Account deaktiviert');
        } elseif ($admin['locked_until'] && strtotime($admin['locked_until']) > time()) {
            $accountLocked = true;
            $lockEndTime = date('d.m.Y H:i', strtotime($admin['locked_until']));
            $error = 'Ihr Account ist gesperrt bis ' . $lockEndTime . ' aufgrund zu vieler fehlgeschlagener Login-Versuche.';
            logLoginAttempt($userIP, $username, false, 'Account gesperrt');
        } elseif (!password_verify($password, $admin['password'])) {
            $error = 'Ungültiger Benutzername oder Passwort.';
            logLoginAttempt($userIP, $username, false, 'Falsches Passwort');
            
            // Fehlversuch-Zähler erhöhen
            $failedAttempts = ($admin['failed_login_attempts'] ?? 0) + 1;
            updateData('admins', 
                ['failed_login_attempts' => $failedAttempts], 
                'id = :id', 
                ['id' => $admin['id']]
            );
            
            // Account nach zu vielen Versuchen sperren
            if ($failedAttempts >= MAX_FAILED_LOGIN_ATTEMPTS) {
                lockUserAccount($username);
                $error = 'Ihr Account wurde aufgrund zu vieler fehlgeschlagener Login-Versuche für ' . (USER_LOCKOUT_DURATION / 60) . ' Minuten gesperrt.';
            }
        } else {
            // Erfolgreiche Anmeldung
            logLoginAttempt($userIP, $username, true);
            
            // Session regenerieren für Sicherheit
            session_regenerate_id(true);
            
            // Admin-Session erstellen
            $sessionId = createAdminSession($admin['id']);
            
            // Admin-Daten in Session speichern
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_role'] = $admin['role'];
            $_SESSION['admin_permissions'] = $admin['permissions'];
            $_SESSION['session_id'] = $sessionId;
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $_SESSION['last_activity'] = time();
            $_SESSION['last_regeneration'] = time();
            
            // Benutzer-Daten aktualisieren
            updateData('admins', [
                'last_login' => date('Y-m-d H:i:s'),
                'login_count' => ($admin['login_count'] ?? 0) + 1,
                'failed_login_attempts' => 0,
                'locked_until' => null
            ], 'id = :id', ['id' => $admin['id']]);
            
            // Aktivität protokollieren
            logAdminActivity($admin['id'], 'login', 'Erfolgreiche Anmeldung');
            
            // Passwort-Änderung erforderlich?
            if ($admin['must_change_password']) {
                setFlashMessage('warning', 'Sie müssen Ihr Passwort ändern, bevor Sie fortfahren können.');
                redirect('change-password.php');
            }
            
            setFlashMessage('success', 'Willkommen im Admin-Panel, ' . htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']) . '!');
            
            // Redirect zur gewünschten Seite oder Dashboard
            $redirectTo = $_SESSION['redirect_after_login'] ?? 'dashboard.php';
            unset($_SESSION['redirect_after_login']);
            redirect($redirectTo);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 107, 53, 0.2);
            border-radius: 16px;
            padding: 3rem;
            width: 100%;
            max-width: 450px;
            backdrop-filter: blur(20px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-logo {
            font-family: 'Orbitron', monospace;
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }
        
        .login-subtitle {
            color: var(--gray);
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        
        .server-status {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            border-radius: 20px;
            font-size: 0.8rem;
            color: var(--success);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: var(--danger);
        }
        
        .alert-warning {
            background: rgba(255, 193, 7, 0.1);
            border: 1px solid rgba(255, 193, 7, 0.3);
            color: #ffc107;
        }
        
        .login-form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        .form-group {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--gray);
            font-size: 1rem;
            transition: color 0.3s ease;
        }
        
        .password-toggle:hover {
            color: var(--primary);
        }
        
        .form-control {
            padding-right: 45px;
        }
        
        .login-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 1rem 0;
            font-size: 0.9rem;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--gray);
        }
        
        .forgot-password {
            color: var(--secondary);
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .forgot-password:hover {
            color: var(--primary);
        }
        
        .back-link {
            text-align: center;
            margin-top: 2rem;
        }
        
        .back-link a {
            color: var(--gray);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }
        
        .back-link a:hover {
            color: var(--primary);
        }
        
        .security-info {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 1rem;
            margin-top: 2rem;
            font-size: 0.8rem;
            color: var(--gray);
        }
        
        .form-control:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .login-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 1rem;
            font-size: 0.8rem;
        }
        
        .stat-item {
            text-align: center;
            padding: 0.5rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 6px;
        }
        
        .caps-warning {
            color: #ffc107;
            font-size: 0.8rem;
            margin-top: 0.25rem;
            display: none;
        }
        
        @media (max-width: 480px) {
            .login-card {
                padding: 2rem;
                margin: 1rem;
            }
            
            .login-stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Background -->
    <div class="bg-video"></div>
    <div class="bg-overlay"></div>
    
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-logo"><?php echo SITE_NAME; ?></div>
                <div class="login-subtitle">Administrator Login</div>
                <div class="server-status">
                    <span>🟢</span>
                    <span>System Online</span>
                </div>
            </div>
            
            <?php if ($error): ?>
                <div class="alert <?php echo ($loginBlocked || $accountLocked) ? 'alert-warning' : 'alert-error'; ?>">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form class="login-form" method="POST" action="" id="loginForm">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="form-group">
                    <label for="username">🧑‍💼 Benutzername</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        class="form-control" 
                        value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                        <?php echo ($loginBlocked || $accountLocked) ? 'disabled' : 'required'; ?>
                        autocomplete="username"
                        maxlength="50"
                        pattern="[a-zA-Z0-9_]{3,20}"
                        title="3-20 Zeichen, nur Buchstaben, Zahlen und Unterstriche"
                    >
                </div>
                
                <div class="form-group">
                    <label for="password">🔐 Passwort</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-control" 
                        <?php echo ($loginBlocked || $accountLocked) ? 'disabled' : 'required'; ?>
                        autocomplete="current-password"
                        minlength="<?php echo MIN_PASSWORD_LENGTH; ?>"
                    >
                    <span class="password-toggle" onclick="togglePassword()" id="toggleIcon">👁️</span>
                    <div class="caps-warning" id="capsWarning">⚠️ Caps Lock ist aktiviert</div>
                </div>
                
                <div class="login-options">
                    <label class="remember-me">
                        <input type="checkbox" name="remember_me" id="remember_me">
                        <span>Angemeldet bleiben</span>
                    </label>
                    <a href="forgot-password.php" class="forgot-password">Passwort vergessen?</a>
                </div>
                
                <button 
                    type="submit" 
                    class="btn btn-primary" 
                    style="width: 100%;"
                    <?php echo ($loginBlocked || $accountLocked) ? 'disabled' : ''; ?>
                    id="loginButton"
                >
                    <?php if ($loginBlocked): ?>
                        🔒 Login gesperrt
                    <?php elseif ($accountLocked): ?>
                        🚫 Account gesperrt
                    <?php else: ?>
                        🚀 Anmelden
                    <?php endif; ?>
                </button>
            </form>
            
            <div class="back-link">
                <a href="../index.php">← Zurück zur Hauptseite</a>
            </div>
            
            <div class="security-info">
                <strong>🔒 Sicherheitshinweise:</strong>
                <ul style="margin: 0.5rem 0 0 1rem; padding: 0;">
                    <li>Alle Login-Versuche werden protokolliert</li>
                    <li>Nach <?php echo MAX_LOGIN_ATTEMPTS; ?> fehlgeschlagenen Versuchen wird die IP für <?php echo LOGIN_LOCKOUT_TIME / 60; ?> Minuten gesperrt</li>
                    <li>Nach <?php echo MAX_FAILED_LOGIN_ATTEMPTS; ?> fehlgeschlagenen Account-Versuchen wird der Account für <?php echo USER_LOCKOUT_DURATION / 60; ?> Minuten gesperrt</li>
                    <li>Verwenden Sie nur sichere Netzwerke für den Admin-Zugang</li>
                </ul>
                
                <div class="login-stats">
                    <div class="stat-item">
                        <div>📊 Aktive Admins</div>
                        <div><?php echo fetchOne("SELECT COUNT(*) as count FROM admins WHERE is_active = 1 AND last_login > DATE_SUB(NOW(), INTERVAL 7 DAY)")['count'] ?? 0; ?></div>
                    </div>
                    <div class="stat-item">
                        <div>🕐 Letzte 24h Logins</div>
                        <div><?php echo fetchOne("SELECT COUNT(*) as count FROM login_attempts WHERE success = 1 AND attempted_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)")['count'] ?? 0; ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-Focus auf erstes Eingabefeld
        document.addEventListener('DOMContentLoaded', function() {
            const firstInput = document.querySelector('input:not([disabled])');
            if (firstInput) {
                firstInput.focus();
            }
            
            // Remember Me aus localStorage laden
            const rememberMe = localStorage.getItem('admin_remember_me');
            if (rememberMe === 'true') {
                document.getElementById('remember_me').checked = true;
                const savedUsername = localStorage.getItem('admin_username');
                if (savedUsername) {
                    document.getElementById('username').value = savedUsername;
                    document.getElementById('password').focus();
                }
            }
        });
        
        // Password Toggle Funktionalität
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.textContent = '🙈';
            } else {
                passwordField.type = 'password';
                toggleIcon.textContent = '👁️';
            }
        }
        
        // Form Validation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            const rememberMe = document.getElementById('remember_me').checked;
            
            if (!username || !password) {
                e.preventDefault();
                alert('Bitte füllen Sie alle Felder aus.');
                return false;
            }
            
            if (username.length < 3 || username.length > 20) {
                e.preventDefault();
                alert('Der Benutzername muss zwischen 3 und 20 Zeichen lang sein.');
                return false;
            }
            
            if (!/^[a-zA-Z0-9_]+$/.test(username)) {
                e.preventDefault();
                alert('Der Benutzername darf nur Buchstaben, Zahlen und Unterstriche enthalten.');
                return false;
            }
            
            if (password.length < <?php echo MIN_PASSWORD_LENGTH; ?>) {
                e.preventDefault();
                alert('Das Passwort muss mindestens <?php echo MIN_PASSWORD_LENGTH; ?> Zeichen lang sein.');
                return false;
            }
            
            // Remember Me Funktionalität
            if (rememberMe) {
                localStorage.setItem('admin_remember_me', 'true');
                localStorage.setItem('admin_username', username);
            } else {
                localStorage.removeItem('admin_remember_me');
                localStorage.removeItem('admin_username');
            }
            
            // Submit Button deaktivieren um Doppel-Submits zu verhindern
            const submitButton = document.getElementById('loginButton');
            submitButton.disabled = true;
            submitButton.innerHTML = '⏳ Anmeldung läuft...';
        });
        
        // Caps Lock Detection
        document.getElementById('password').addEventListener('keyup', function(e) {
            const capsLockOn = e.getModifierState && e.getModifierState('CapsLock');
            const warning = document.getElementById('capsWarning');
            
            if (capsLockOn) {
                warning.style.display = 'block';
            } else {
                warning.style.display = 'none';
            }
        });
        
        // Keyboard Shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl+Enter für schnelles Login
            if (e.ctrlKey && e.key === 'Enter') {
                document.getElementById('loginForm').submit();
            }
            
            // Escape zum Löschen der Felder
            if (e.key === 'Escape') {
                document.getElementById('username').value = '';
                document.getElementById('password').value = '';
                document.getElementById('username').focus();
            }
        });
        
        <?php if ($loginBlocked || $accountLocked): ?>
        // Countdown für gesperrte Logins
        let remainingTime = <?php echo $loginBlocked ? LOGIN_LOCKOUT_TIME : (strtotime($admin['locked_until'] ?? '') - time()); ?>;
        const button = document.getElementById('loginButton');
        const usernameField = document.getElementById('username');
        const passwordField = document.getElementById('password');
        
        if (remainingTime > 0) {
            const countdown = setInterval(() => {
                remainingTime--;
                const minutes = Math.floor(remainingTime / 60);
                const seconds = remainingTime % 60;
                const timeString = `${minutes}:${seconds.toString().padStart(2, '0')}`;
                
                button.textContent = `🔒 Gesperrt (${timeString})`;
                
                if (remainingTime <= 0) {
                    clearInterval(countdown);
                    location.reload();
                }
            }, 1000);
        }
        <?php endif; ?>
        
        // Anti-Bruteforce: Verzögerung nach fehlgeschlagenen Versuchen
        <?php if (isset($_POST['username']) && !empty($error) && !$loginBlocked && !$accountLocked): ?>
        setTimeout(function() {
            document.getElementById('password').value = '';
            document.getElementById('password').focus();
        }, 1000);
        <?php endif; ?>
        
        // Session Timeout Warning
        let sessionTimeout = <?php echo SESSION_TIMEOUT; ?> * 1000; // Convert to milliseconds
        let warningShown = false;
        
        setTimeout(function() {
            if (!warningShown) {
                warningShown = true;
                if (confirm('Ihre Session läuft in 5 Minuten ab. Möchten Sie angemeldet bleiben?')) {
                    // Ping Server um Session zu verlängern
                    fetch('ping-session.php', {method: 'POST'})
                        .then(() => warningShown = false)
                        .catch(() => console.log('Session ping failed'));
                }
            }
        }, sessionTimeout - 300000); // 5 Minuten vor Ablauf warnen
        
        // Form Auto-Save für Disaster Recovery
        let formData = {};
        document.getElementById('username').addEventListener('input', function() {
            formData.username = this.value;
            sessionStorage.setItem('login_form_backup', JSON.stringify(formData));
        });
        
        // Form-Daten wiederherstellen bei Reload
        window.addEventListener('load', function() {
            const backup = sessionStorage.getItem('login_form_backup');
            if (backup) {
                try {
                    const data = JSON.parse(backup);
                    if (data.username && !document.getElementById('username').value) {
                        document.getElementById('username').value = data.username;
                    }
                } catch (e) {
                    console.log('Could not restore form data');
                }
            }
        });
        
        // Cleanup backup nach erfolgreichem Submit
        document.getElementById('loginForm').addEventListener('submit', function() {
            sessionStorage.removeItem('login_form_backup');
        });
        
        // Security: Clear form data on page unload
        window.addEventListener('beforeunload', function() {
            document.getElementById('password').value = '';
        });
    </script>
</body>
</html>