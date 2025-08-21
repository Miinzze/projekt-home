<?php
require_once '../config/config.php';

// Debug Logging
function debugLog($message) {
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        error_log("[APPLY_DEBUG] " . $message);
    }
}

debugLog("Apply.php started - Session ID: " . session_id());

// Session-Recovery für verloren gegangene Discord-Daten
function recoverDiscordSession() {
    // Prüfen ob Cookie-Backup existiert
    if (isset($_COOKIE['discord_user_backup'])) {
        debugLog("Attempting session recovery from cookie...");
        
        $backupData = json_decode(base64_decode($_COOKIE['discord_user_backup']), true);
        
        if ($backupData && isset($backupData['user']) && isset($backupData['time'])) {
            // Prüfen ob Backup nicht zu alt ist (1 Stunde)
            if ((time() - $backupData['time']) < 3600) {
                debugLog("Session recovery successful from cookie");
                $_SESSION['discord_user'] = $backupData['user'];
                $_SESSION['discord_auth_time'] = $backupData['time'];
                $_SESSION['discord_session_token'] = $backupData['token'] ?? '';
                
                return $backupData['user'];
            } else {
                debugLog("Cookie backup too old, deleting...");
                setcookie('discord_user_backup', '', time() - 3600, '/whitelist/', $_SERVER['HTTP_HOST'] ?? '');
            }
        }
    }
    
    // Manual Recovery Parameter (für Debug)
    if (isset($_GET['manual_user']) && defined('DEBUG_MODE') && DEBUG_MODE) {
        debugLog("Attempting manual session recovery...");
        
        $userData = json_decode(base64_decode($_GET['manual_user']), true);
        if ($userData && isset($userData['id'])) {
            debugLog("Manual recovery successful");
            $_SESSION['discord_user'] = $userData;
            $_SESSION['discord_auth_time'] = time();
            
            return $userData;
        }
    }
    
    return null;
}

// Clear Session Parameter (für Debug)
if (isset($_GET['clear_session'])) {
    debugLog("Clearing session by request");
    session_destroy();
    session_start();
    setcookie('discord_user_backup', '', time() - 3600, '/whitelist/', $_SERVER['HTTP_HOST'] ?? '');
    header('Location: apply.php');
    exit;
}

debugLog("Apply.php started");

// Prüfen ob Whitelist aktiviert ist
$whitelistEnabled = getServerSetting('whitelist_enabled', '1');
$whitelistActive = getServerSetting('whitelist_active', '1');

debugLog("Whitelist enabled: $whitelistEnabled, active: $whitelistActive");

if (!$whitelistEnabled || !$whitelistActive) {
    setFlashMessage('error', 'Die Whitelist-Bewerbungen sind momentan nicht verfügbar.');
    redirect('../index.php#whitelist');
}

// Discord Konfiguration prüfen
$discordConfig = getDiscordConfig();
if (empty($discordConfig['client_id'])) {
    setFlashMessage('error', 'Discord-Authentifizierung ist nicht konfiguriert.');
    redirect('../index.php#whitelist');
}

// Discord User aus Session oder Recovery
$discordUser = $_SESSION['discord_user'] ?? null;
$questions = [];
$error = '';

debugLog("Initial Discord user: " . ($discordUser ? $discordUser['username'] : 'NONE'));

// Session Recovery versuchen wenn kein User
if (!$discordUser) {
    debugLog("No Discord user in session, attempting recovery...");
    $discordUser = recoverDiscordSession();
    
    if ($discordUser) {
        debugLog("Recovery successful: " . $discordUser['username']);
    } else {
        debugLog("Recovery failed - user needs to re-authenticate");
    }
}

if ($discordUser) {
    debugLog("Discord user verified: " . $discordUser['username']);
    
    // Fragen laden - mit Debug
    debugLog("Loading whitelist questions...");
    
    try {
        $questions = getWhitelistQuestions();
        debugLog("Questions loaded: " . count($questions));
        
        if (empty($questions)) {
            debugLog("No questions found - checking manually");
            
            // Manual check
            $manualCheck = fetchAll("SELECT * FROM whitelist_questions WHERE is_active = 1");
            debugLog("Manual question check: " . count($manualCheck) . " active questions found");
            
            if (!empty($manualCheck)) {
                $error = 'Whitelist-Fragen gefunden, aber getWhitelistQuestions() gibt leeres Array zurück. Debug erforderlich.';
            } else {
                $error = 'Keine aktiven Whitelist-Fragen konfiguriert. Bitte kontaktieren Sie den Administrator.';
            }
        }
    } catch (Exception $e) {
        debugLog("Error loading questions: " . $e->getMessage());
        $error = 'Fehler beim Laden der Whitelist-Fragen: ' . $e->getMessage();
    }
    
    // Prüfen ob bereits eine offene Bewerbung existiert
    try {
        $existingApplication = fetchOne(
            "SELECT id FROM whitelist_applications WHERE discord_id = :discord_id AND status = 'pending'",
            ['discord_id' => $discordUser['id']]
        );
        
        debugLog("Existing application check: " . ($existingApplication ? "Found ID " . $existingApplication['id'] : "None"));
        
        if ($existingApplication) {
            $error = 'Du hast bereits eine offene Whitelist-Bewerbung. Bitte warte auf die Bearbeitung durch unser Team.';
        }
    } catch (Exception $e) {
        debugLog("Error checking existing application: " . $e->getMessage());
    }
} else {
    debugLog("No Discord user available - will show login");
}

// Form submission (unverändert)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $discordUser && !$existingApplication && empty($error)) {
    debugLog("Processing form submission");
    
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    if (!validateCSRFToken($csrfToken)) {
        $error = 'Ungültiger Sicherheitstoken. Bitte versuchen Sie es erneut.';
        debugLog("CSRF token validation failed");
    } else {
        $answers = [];
        $allAnswered = true;
        
        foreach ($questions as $question) {
            $answerId = 'answer_' . $question['id'];
            $answer = trim($_POST[$answerId] ?? '');
            
            if ($question['is_required'] && empty($answer)) {
                $allAnswered = false;
                $error = 'Bitte beantworten Sie alle Pflichtfragen.';
                debugLog("Required question not answered: " . $question['id']);
                break;
            }
            
            if (!empty($answer)) {
                $answers[$question['id']] = $answer;
            }
        }
        
        if ($allAnswered && empty($error)) {
            debugLog("Creating application with " . count($answers) . " answers");
            
            $applicationId = createWhitelistApplication($discordUser, $answers);
            
            if ($applicationId) {
                debugLog("Application created successfully: ID $applicationId");
                
                // Automatische Genehmigung prüfen
                $application = fetchOne("SELECT score_percentage, status FROM whitelist_applications WHERE id = :id", ['id' => $applicationId]);
                
                if ($application && $application['status'] === 'approved') {
                    setFlashMessage('success', 'Herzlichen Glückwunsch! Ihre Whitelist-Bewerbung wurde automatisch genehmigt aufgrund Ihrer hohen Punktzahl (' . round($application['score_percentage'], 1) . '%). Sie können jetzt auf dem Server spielen!');
                } else {
                    setFlashMessage('success', 'Ihre Whitelist-Bewerbung wurde erfolgreich eingereicht! Unser Team wird Ihre Bewerbung prüfen und sich bei Ihnen melden.');
                }
                
                // Session und Cookies aufräumen
                unset($_SESSION['discord_user']);
                unset($_SESSION['discord_auth_time']);
                unset($_SESSION['discord_session_token']);
                setcookie('discord_user_backup', '', time() - 3600, '/whitelist/', $_SERVER['HTTP_HOST'] ?? '');
                
                redirect('../index.php#whitelist');
            } else {
                debugLog("Failed to create application");
                $error = 'Fehler beim Einreichen der Bewerbung. Bitte versuchen Sie es später erneut.';
            }
        }
    }
}

// Discord Auth URL generieren mit verbesserter State-Verwaltung
$discordAuthUrl = '';
if (!$discordUser) {
    $state = bin2hex(random_bytes(16));
    $_SESSION['discord_state'] = $state;
    
    // Cookie-Backup für State (für den Fall dass Session verloren geht)
    setcookie('discord_state_backup', $state, time() + 1800, '/whitelist/', $_SERVER['HTTP_HOST'] ?? '', isset($_SERVER['HTTPS']), true);
    
    $params = [
        'client_id' => $discordConfig['client_id'],
        'redirect_uri' => $discordConfig['redirect_uri'],
        'response_type' => 'code',
        'scope' => 'identify',
        'state' => $state
    ];
    $discordAuthUrl = 'https://discord.com/api/oauth2/authorize?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Whitelist Bewerbung - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Existing CSS unchanged... */
        .whitelist-container {
            min-height: 100vh;
            padding: 2rem;
            background: #0a0a0a;
        }
        
        .whitelist-card {
            max-width: 800px;
            margin: 2rem auto;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.08), rgba(255, 255, 255, 0.03));
            border: 1px solid rgba(255, 68, 68, 0.3);
            border-radius: 16px;
            padding: 2rem;
            backdrop-filter: blur(20px);
        }
        
        .debug-panel {
            position: fixed;
            top: 10px;
            right: 10px;
            background: rgba(0,0,0,0.9);
            border: 1px solid #ff4444;
            border-radius: 5px;
            padding: 10px;
            max-width: 350px;
            font-family: monospace;
            font-size: 11px;
            color: #0f0;
            z-index: 9999;
            max-height: 300px;
            overflow-y: auto;
        }
        
        .debug-actions {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #333;
        }
        
        .debug-actions a {
            color: #5865f2;
            text-decoration: none;
            display: block;
            margin: 2px 0;
            padding: 2px 5px;
            background: #333;
            border-radius: 3px;
            font-size: 10px;
        }
        
        .session-recovery-notice {
            background: rgba(255, 193, 7, 0.1);
            border: 1px solid rgba(255, 193, 7, 0.3);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            color: #ffc107;
        }
        
        /* Rest der CSS bleibt unverändert... */
        .discord-auth {
            text-align: center;
            padding: 3rem 2rem;
        }
        
        .discord-login-btn {
            background: #5865f2;
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .discord-login-btn:hover {
            background: #4752c4;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(88, 101, 242, 0.3);
        }
        
        .user-info {
            background: rgba(88, 101, 242, 0.1);
            border: 1px solid rgba(88, 101, 242, 0.3);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-avatar {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            border: 2px solid #5865f2;
        }
        
        .error-message {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #ef4444;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #ccc;
            text-decoration: none;
            margin-bottom: 2rem;
            transition: color 0.3s ease;
        }
        
        .back-link:hover {
            color: #ff4444;
        }
    </style>
</head>
<body>
    <!-- Debug Panel erweitert -->
    <?php if (defined('DEBUG_MODE') && DEBUG_MODE): ?>
    <div class="debug-panel">
        <strong>🔍 Apply.php Debug</strong><br>
        <strong>Session:</strong><br>
        - ID: <?php echo session_id(); ?><br>
        - Discord User: <?php echo $discordUser ? '✅ ' . $discordUser['username'] : '❌ None'; ?><br>
        - Auth Time: <?php echo isset($_SESSION['discord_auth_time']) ? date('H:i:s', $_SESSION['discord_auth_time']) : 'None'; ?><br>
        - Session Token: <?php echo isset($_SESSION['discord_session_token']) ? '✅' : '❌'; ?><br>
        <strong>Cookies:</strong><br>
        - Backup: <?php echo isset($_COOKIE['discord_user_backup']) ? '✅' : '❌'; ?><br>
        - State: <?php echo isset($_COOKIE['discord_state_backup']) ? '✅' : '❌'; ?><br>
        <strong>System:</strong><br>
        - Questions: <?php echo count($questions); ?><br>
        - Whitelist: <?php echo $whitelistEnabled && $whitelistActive ? '✅' : '❌'; ?><br>
        - Error: <?php echo $error ? '⚠️' : '✅'; ?><br>
        - Existing App: <?php echo isset($existingApplication) && $existingApplication ? '⚠️' : '✅'; ?><br>
        
        <div class="debug-actions">
            <strong>🛠️ Debug Actions:</strong>
            <a href="?clear_session=1">🗑️ Clear Session</a>
            <a href="debug-questions.php">🔧 Debug Questions</a>
            <a href="discord-callback.php?bypass_state=1&code=debug&state=debug">🔄 Test Callback</a>
            <a href="../admin/dashboard.php?page=whitelist_questions">⚙️ Admin Panel</a>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="whitelist-container">
        <div class="whitelist-card">
            <a href="../index.php#whitelist" class="back-link">
                ← Zurück zur Hauptseite
            </a>
            
            <h1 style="color: #ff4444; text-align: center; margin-bottom: 2rem;">
                📋 Whitelist Bewerbung
            </h1>
            
            <!-- Session Recovery Notice -->
            <?php if ($discordUser && isset($_SESSION['discord_auth_time']) && (time() - $_SESSION['discord_auth_time']) > 300): ?>
                <div class="session-recovery-notice">
                    <strong>⚠️ Session Recovery:</strong> Ihre Discord-Anmeldung wurde aus einem Backup wiederhergestellt. 
                    Falls Probleme auftreten, melden Sie sich bitte erneut an.
                </div>
            <?php endif; ?>
            
            <?php if (!$discordUser): ?>
                <!-- Discord Authentifizierung -->
                <div class="discord-auth">
                    <div style="font-size: 4rem; margin-bottom: 1rem;">🔐</div>
                    <h2 style="color: #ff4444; margin-bottom: 1rem;">Discord Authentifizierung</h2>
                    <p style="color: #ccc; margin-bottom: 2rem; line-height: 1.6;">
                        Um eine Whitelist-Bewerbung einzureichen, müssen Sie sich zuerst mit Ihrem Discord-Account anmelden. 
                        Dies ermöglicht es uns, Sie bei Fragen zu kontaktieren.
                    </p>
                    
                    <a href="<?php echo htmlspecialchars($discordAuthUrl); ?>" class="discord-login-btn">
                        <svg width="24" height="18" viewBox="0 0 71 55" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M60.1045 4.8978C55.5792 2.8214 50.7265 1.2916 45.6527 0.41542C45.5603 0.39851 45.468 0.440769 45.4204 0.525289C44.7963 1.6353 44.105 3.0834 43.6209 4.2216C38.1637 3.4046 32.7345 3.4046 27.3892 4.2216C26.905 3.0581 26.1886 1.6353 25.5617 0.525289C25.5141 0.443589 25.4218 0.40133 25.3294 0.41542C20.2584 1.2888 15.4057 2.8186 10.8776 4.8978C10.8384 4.9147 10.8048 4.9429 10.7825 4.9795C1.57795 18.7309 -0.943561 32.1443 0.293408 45.3914C0.299005 45.4562 0.335386 45.5182 0.385761 45.5576C6.45866 50.0174 12.3413 52.7249 18.1147 54.5195C18.2071 54.5477 18.305 54.5139 18.3638 54.4378C19.7295 52.5728 20.9469 50.6063 21.9907 48.5383C22.0523 48.4172 21.9935 48.2735 21.8676 48.2256C19.9366 47.4931 18.0979 46.6 16.3292 45.5858C16.1893 45.5041 16.1781 45.304 16.3068 45.2082C16.679 44.9293 17.0513 44.6391 17.4067 44.3461C17.5185 44.2606 17.6538 44.2456 17.7735 44.3015C22.2416 46.4104 27.0281 47.4649 31.9327 47.4649C36.8373 47.4649 41.6238 46.4104 46.0919 44.3015C46.2116 44.2456 46.3469 44.2606 46.4587 44.3461C46.8141 44.6391 47.1864 44.9293 47.5586 45.2082C47.6873 45.304 47.6761 45.5041 47.5362 45.5858C45.7675 46.6 43.9288 47.4931 41.9978 48.2256C41.8719 48.2735 41.8131 48.4172 41.8747 48.5383C42.9185 50.6063 44.1359 52.5728 45.5016 54.4378C45.5604 54.5139 45.6583 54.5477 45.7507 54.5195C51.5241 52.7249 57.4067 50.0174 63.4796 45.5576C63.53 45.5182 63.5664 45.4562 63.572 45.3914C64.9777 30.2978 61.4065 17.014 55.1045 4.9795C55.0822 4.9429 55.0486 4.9147 55.0094 4.8978H60.1045Z" fill="currentColor"/>
                        </svg>
                        Mit Discord anmelden
                    </a>
                    
                    <div style="margin-top: 2rem; padding: 1rem; background: rgba(255, 255, 255, 0.05); border-radius: 8px;">
                        <small style="color: #ccc;">
                            <strong>Datenschutz:</strong> Wir verwenden nur Ihren Discord-Benutzernamen für die Whitelist-Bewerbung. 
                            Ihre Daten werden nicht an Dritte weitergegeben und wir tracken keine IP-Adressen oder E-Mail-Adressen.
                        </small>
                    </div>
                    
                    <?php if (defined('DEBUG_MODE') && DEBUG_MODE): ?>
                    <div style="margin-top: 2rem; padding: 1rem; background: rgba(255, 68, 68, 0.1); border: 1px solid rgba(255, 68, 68, 0.3); border-radius: 8px;">
                        <h4 style="color: #ff4444; margin-bottom: 1rem;">🔧 Debug-Modus aktiv</h4>
                        <p style="color: #ccc; margin-bottom: 1rem; font-size: 0.9rem;">
                            Session-Recovery ist aktiviert. Falls die Discord-Anmeldung fehlschlägt, 
                            wird versucht die Session aus einem Cookie-Backup wiederherzustellen.
                        </p>
                        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                            <a href="?clear_session=1" style="color: #fff; background: #dc3545; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 0.8rem;">🗑️ Session löschen</a>
                            <a href="debug-questions.php" style="color: #fff; background: #6c757d; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 0.8rem;">🔍 Fragen debuggen</a>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
            <?php else: ?>
                <!-- Bewerbungsformular -->
                <div class="user-info">
                    <?php if (!empty($discordUser['avatar'])): ?>
                        <img src="<?php echo htmlspecialchars($discordUser['avatar']); ?>" alt="Avatar" class="user-avatar">
                    <?php else: ?>
                        <div class="user-avatar" style="background: #5865f2; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                            <?php echo strtoupper(substr($discordUser['username'], 0, 2)); ?>
                        </div>
                    <?php endif; ?>
                    <div>
                        <div style="color: white; font-weight: 600; font-size: 1.1rem;">
                            <?php echo htmlspecialchars($discordUser['username']); ?>
                        </div>
                        <div style="color: #ccc; font-size: 0.9rem;">
                            Eingeloggt über Discord
                            <?php if (isset($_SESSION['discord_auth_time'])): ?>
                                <span style="font-size: 0.8rem; opacity: 0.7;">
                                    (seit <?php echo date('H:i:s', $_SESSION['discord_auth_time']); ?>)
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <?php if ($error): ?>
                    <div class="error-message">
                        <?php echo htmlspecialchars($error); ?>
                        
                        <?php if (defined('DEBUG_MODE') && DEBUG_MODE): ?>
                        <div style="margin-top: 1rem; padding: 1rem; background: rgba(255,255,255,0.1); border-radius: 5px;">
                            <strong>🔧 Debug-Hilfe:</strong><br>
                            <a href="debug-questions.php" style="color: #5865f2;">→ Whitelist-Fragen Debug öffnen</a><br>
                            <a href="../admin/dashboard.php?page=whitelist_questions" style="color: #5865f2;">→ Admin Panel (Fragen verwalten)</a>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                <?php elseif (empty($questions)): ?>
                    <div class="no-questions-warning">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">⚠️</div>
                        <h3 style="color: #ffc107; margin-bottom: 1rem;">Keine Whitelist-Fragen konfiguriert</h3>
                        <p style="margin-bottom: 2rem;">
                            Das Whitelist-System ist noch nicht vollständig eingerichtet. 
                            Bitte kontaktieren Sie den Administrator.
                        </p>
                        
                        <?php if (defined('DEBUG_MODE') && DEBUG_MODE): ?>
                        <div style="background: rgba(0,0,0,0.3); padding: 1rem; border-radius: 5px; text-align: left;">
                            <strong>🔧 Debug-Aktionen:</strong><br>
                            <a href="debug-questions.php" style="color: #5865f2; text-decoration: none; background: #333; padding: 5px 10px; border-radius: 3px; margin: 5px; display: inline-block;">
                                🔍 Questions Debug
                            </a>
                            <a href="../admin/dashboard.php?page=whitelist_questions" style="color: #5865f2; text-decoration: none; background: #333; padding: 5px 10px; border-radius: 3px; margin: 5px; display: inline-block;">
                                ⚙️ Admin Panel
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                <?php else: ?>
                    <!-- Rest des Bewerbungsformulars bleibt unverändert -->
                    <?php 
                    $passingScore = getServerSetting('whitelist_passing_score', '70');
                    $autoApprove = getServerSetting('whitelist_auto_approve', '0');
                    ?>
                    <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 8px; padding: 1rem; margin: 2rem 0; color: #10b981;">
                        <h4 style="margin: 0 0 0.5rem 0; color: #10b981;">🎯 Bewertungssystem</h4>
                        <p style="margin: 0;">
                            Ihre Antworten werden automatisch bewertet. 
                            <?php if ($autoApprove): ?>
                            Bei einer Punktzahl von <?php echo $passingScore; ?>% oder höher werden Sie automatisch genehmigt!
                            <?php else: ?>
                            Unser Team wird Ihre Bewerbung anhand Ihrer Antworten bewerten.
                            <?php endif; ?>
                        </p>
                    </div>
                    
                    <!-- Bewerbungsformular -->
                    <form method="POST" action="" id="whitelistForm">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <p style="color: #ccc; margin-bottom: 2rem; line-height: 1.6;">
                            Bitte beantworten Sie die folgenden Fragen ehrlich und ausführlich. 
                            Ihre Antworten helfen uns dabei, Sie besser kennenzulernen und sicherzustellen, 
                            dass Sie gut zu unserem Server passen.
                        </p>
                        
                        <?php foreach ($questions as $index => $question): ?>
                            <div style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px; padding: 1.5rem; margin-bottom: 1.5rem; position: relative;" class="question-card <?php echo !empty($question['correct_answer']) ? 'has-correct-answer' : ''; ?>">
                                <div style="color: #ff4444; font-weight: 600; margin-bottom: 1rem; font-size: 1.1rem;">
                                    Frage <?php echo $index + 1; ?><?php echo $question['is_required'] ? ' <span style="color: #ff4444; font-weight: bold;">*</span>' : ''; ?>
                                </div>
                                <div style="margin-bottom: 1rem; color: white; line-height: 1.5;">
                                    <?php echo htmlspecialchars($question['question']); ?>
                                </div>
                                
                                <?php if ($question['question_type'] === 'multiple_choice'): ?>
                                    <?php 
                                    $options = json_decode($question['options'], true) ?: [];
                                    ?>
                                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                                        <?php foreach ($options as $optionIndex => $option): ?>
                                            <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem; background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 6px; cursor: pointer; transition: all 0.3s ease;">
                                                <input type="radio" 
                                                       name="answer_<?php echo $question['id']; ?>" 
                                                       value="<?php echo htmlspecialchars($option); ?>"
                                                       style="margin: 0; accent-color: #ff4444;"
                                                       <?php echo $question['is_required'] ? 'required' : ''; ?>>
                                                <span><?php echo htmlspecialchars($option); ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <textarea name="answer_<?php echo $question['id']; ?>" 
                                              style="width: 100%; padding: 0.75rem; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 8px; color: white; font-size: 1rem; transition: all 0.3s ease; box-sizing: border-box; min-height: 100px; resize: vertical;" 
                                              rows="4" 
                                              placeholder="Ihre Antwort..."
                                              <?php echo $question['is_required'] ? 'required' : ''; ?>></textarea>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        
                        <div style="text-align: center; padding: 2rem 0; border-top: 1px solid rgba(255, 255, 255, 0.1); margin-top: 2rem;">
                            <div style="margin-bottom: 2rem; padding: 1rem; background: rgba(255, 68, 68, 0.1); border: 1px solid rgba(255, 68, 68, 0.3); border-radius: 8px;">
                                <h4 style="color: #ff4444; margin-bottom: 1rem;">📋 Wichtige Hinweise</h4>
                                <ul style="color: #ccc; text-align: left; line-height: 1.6;">
                                    <li>Nach dem Absenden wird Ihre Bewerbung automatisch bewertet</li>
                                    <?php if ($autoApprove): ?>
                                    <li>Bei einer hohen Punktzahl (≥<?php echo $passingScore; ?>%) werden Sie sofort genehmigt</li>
                                    <?php endif; ?>
                                    <li>Unser Team prüft alle Bewerbungen und kontaktiert Sie bei Bedarf</li>
                                    <li>Bitte haben Sie Geduld - die Bearbeitung kann bis zu 48 Stunden dauern</li>
                                    <li>Wir tracken keine IP-Adressen oder E-Mail-Adressen für Ihre Privatsphäre</li>
                                </ul>
                            </div>
                            
                            <button type="submit" style="font-size: 1.1rem; padding: 1rem 2rem; background: #ff4444; color: white; border: none; border-radius: 8px; cursor: pointer; transition: all 0.3s ease;">
                                📤 Bewerbung einreichen
                            </button>
                            
                            <div style="margin-top: 1rem;">
                                <small style="color: #ccc;">
                                    Mit dem Absenden bestätigen Sie, dass alle Angaben wahrheitsgemäß sind.
                                </small>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        console.log('Apply.php Debug Info:');
        console.log('Discord User:', <?php echo json_encode($discordUser); ?>);
        console.log('Questions Count:', <?php echo count($questions); ?>);
        console.log('Error:', <?php echo json_encode($error); ?>);
        console.log('Session Auth Time:', <?php echo isset($_SESSION['discord_auth_time']) ? $_SESSION['discord_auth_time'] : 'null'; ?>);
        console.log('Cookie Backup Available:', <?php echo isset($_COOKIE['discord_user_backup']) ? 'true' : 'false'; ?>);
        
        // Form validation bleibt unverändert...
        document.getElementById('whitelistForm')?.addEventListener('submit', function(e) {
            const requiredFields = this.querySelectorAll('[required]');
            let allValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    allValid = false;
                    field.style.borderColor = '#ef4444';
                    field.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.2)';
                } else {
                    field.style.borderColor = '';
                    field.style.boxShadow = '';
                }
            });
            
            if (!allValid) {
                e.preventDefault();
                alert('Bitte füllen Sie alle Pflichtfelder aus.');
                
                const firstInvalid = this.querySelector('[required][style*="border-color"]');
                if (firstInvalid) {
                    firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstInvalid.focus();
                }
            }
        });
        
        document.querySelectorAll('[required]').forEach(field => {
            field.addEventListener('input', function() {
                if (this.value.trim()) {
                    this.style.borderColor = '#10b981';
                    this.style.boxShadow = '0 0 0 3px rgba(16, 185, 129, 0.1)';
                } else {
                    this.style.borderColor = '';
                    this.style.boxShadow = '';
                }
            });
        });
    </script>
</body>
</html>