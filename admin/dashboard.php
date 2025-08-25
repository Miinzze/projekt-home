<?php
require_once '../config/config.php';
require_once '../config/twitch_api.php';

// Login prüfen
if (!isLoggedIn()) {
    redirect('login.php');
}

$currentUser = getCurrentUser();

// Aktuelle Seite ermitteln
$page = $_GET['page'] ?? 'overview';
$allowedPages = ['overview', 'settings', 'rules', 'news', 'users', 'logs', 'whitelist', 'whitelist_questions', 'activity', 'streamers'];

if (!in_array($page, $allowedPages)) {
    $page = 'overview';
}

// Berechtigung für die Seite prüfen
switch ($page) {
    case 'users':
        requirePermission('users.read');
        break;
    case 'settings':
        requirePermission('settings.read');
        break;
    case 'whitelist':
        requirePermission('whitelist.read');
        break;
    case 'whitelist_questions':
        requirePermission('whitelist.questions.manage');
        break;
    case 'logs':
        requirePermission('logs.read');
        break;
    case 'activity':
        requirePermission('activity.read');
        break;
    case 'streamers':
        requirePermission('settings.update');
        break;
}

// Flash Messages verarbeiten
$flashMessages = getFlashMessages();

// POST-Anfragen verarbeiten (bestehende Handler beibehalten)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    if (!validateCSRFToken($csrfToken)) {
        setFlashMessage('error', 'Ungültiger Sicherheitstoken.');
    } else {
        switch ($action) {
            case 'update_settings':
                if (hasPermission('settings.update')) {
                    handleUpdateSettings();
                } else {
                    setFlashMessage('error', 'Keine Berechtigung für diese Aktion.');
                }
                break;
            case 'add_rule':
                if (hasPermission('rules.create')) {
                    handleAddRule();
                } else {
                    setFlashMessage('error', 'Keine Berechtigung für diese Aktion.');
                }
                break;
            case 'update_rule':
                if (hasPermission('rules.update')) {
                    handleUpdateRule();
                } else {
                    setFlashMessage('error', 'Keine Berechtigung für diese Aktion.');
                }
                break;
            case 'delete_rule':
                if (hasPermission('rules.delete')) {
                    handleDeleteRule();
                } else {
                    setFlashMessage('error', 'Keine Berechtigung für diese Aktion.');
                }
                break;
            case 'add_news':
                if (hasPermission('news.create')) {
                    handleAddNews();
                } else {
                    setFlashMessage('error', 'Keine Berechtigung für diese Aktion.');
                }
                break;
            case 'update_news':
                if (hasPermission('news.update')) {
                    handleUpdateNews();
                } else {
                    setFlashMessage('error', 'Keine Berechtigung für diese Aktion.');
                }
                break;
            case 'delete_news':
                if (hasPermission('news.delete')) {
                    handleDeleteNews();
                } else {
                    setFlashMessage('error', 'Keine Berechtigung für diese Aktion.');
                }
                break;
            case 'add_whitelist_question':
                if (hasPermission('whitelist.questions.manage')) {
                    handleAddWhitelistQuestion();
                } else {
                    setFlashMessage('error', 'Keine Berechtigung für diese Aktion.');
                }
                break;
            case 'update_whitelist_question':
                if (hasPermission('whitelist.questions.manage')) {
                    handleUpdateWhitelistQuestion();
                } else {
                    setFlashMessage('error', 'Keine Berechtigung für diese Aktion.');
                }
                break;
            case 'delete_whitelist_question':
                if (hasPermission('whitelist.questions.manage')) {
                    handleDeleteWhitelistQuestion();
                } else {
                    setFlashMessage('error', 'Keine Berechtigung für diese Aktion.');
                }
                break;
            case 'update_application_status':
                if (hasPermission('whitelist.update')) {
                    handleUpdateApplicationStatus();
                } else {
                    setFlashMessage('error', 'Keine Berechtigung für diese Aktion.');
                }
                break;
            case 'update_manual_score':
                if (hasPermission('whitelist.update')) {
                    handleUpdateManualScore();
                } else {
                    setFlashMessage('error', 'Keine Berechtigung für diese Aktion.');
                }
                break;
        }
        
        redirect('dashboard.php?page=' . $page);
    }
}

// Action Handler Funktionen (bestehende Handler beibehalten...)
function handleUpdateSettings() {
    $settings = [
        'server_name' => sanitizeInput($_POST['server_name'] ?? ''),
        'max_players' => (int)($_POST['max_players'] ?? 64),
        'current_players' => (int)($_POST['current_players'] ?? 0),
        'server_ip' => sanitizeInput($_POST['server_ip'] ?? ''),
        'discord_link' => sanitizeInput($_POST['discord_link'] ?? ''),
        'is_online' => isset($_POST['is_online']) ? '1' : '0',
        'min_age' => (int)($_POST['min_age'] ?? 18),
        'whitelist_active' => isset($_POST['whitelist_active']) ? '1' : '0',
        'whitelist_enabled' => isset($_POST['whitelist_enabled']) ? '1' : '0',
        'discord_client_id' => sanitizeInput($_POST['discord_client_id'] ?? ''),
        'discord_client_secret' => sanitizeInput($_POST['discord_client_secret'] ?? ''),
        'discord_redirect_uri' => sanitizeInput($_POST['discord_redirect_uri'] ?? ''),
        'whitelist_questions_count' => (int)($_POST['whitelist_questions_count'] ?? 5),
        'whitelist_passing_score' => (int)($_POST['whitelist_passing_score'] ?? 70),
        'whitelist_auto_approve' => isset($_POST['whitelist_auto_approve']) ? '1' : '0',
        // Twitch Settings hinzugefügt
        'twitch_display_enabled' => isset($_POST['twitch_display_enabled']) ? '1' : '0',
        'twitch_max_display' => (int)($_POST['twitch_max_display'] ?? 3),
        'twitch_update_interval' => (int)($_POST['twitch_update_interval'] ?? 300),
        'twitch_auto_update' => isset($_POST['twitch_auto_update']) ? '1' : '0'
    ];
    
    $success = true;
    $changedSettings = [];
    
    foreach ($settings as $key => $value) {
        $oldValue = getServerSetting($key);
        if ($oldValue !== $value) {
            $changedSettings[$key] = ['old' => $oldValue, 'new' => $value];
            if (!setServerSetting($key, $value)) {
                $success = false;
            }
        }
    }
    
    if ($success && !empty($changedSettings)) {
        logAdminActivity(
            getCurrentUser()['id'],
            'settings_updated',
            'Server-Einstellungen aktualisiert',
            'settings',
            null,
            null,
            $changedSettings
        );
        
        setFlashMessage('success', 'Server-Einstellungen wurden erfolgreich aktualisiert.');
    } elseif ($success) {
        setFlashMessage('info', 'Keine Änderungen an den Einstellungen vorgenommen.');
    } else {
        setFlashMessage('error', 'Fehler beim Aktualisieren der Einstellungen.');
    }
}

// Weitere Action Handler bleiben unverändert...
function handleAddRule() {
    $title = sanitizeInput($_POST['rule_title'] ?? '');
    $content = sanitizeInput($_POST['rule_content'] ?? '');
    $order = (int)($_POST['rule_order'] ?? 0);
    
    if (empty($title) || empty($content)) {
        setFlashMessage('error', 'Titel und Inhalt sind erforderlich.');
        return;
    }
    
    $result = insertData('server_rules', [
        'rule_title' => $title,
        'rule_content' => $content,
        'rule_order' => $order
    ]);
    
    if ($result) {
        logAdminActivity(
            getCurrentUser()['id'],
            'rule_created',
            "Regel '{$title}' erstellt",
            'rule',
            $result,
            null,
            ['title' => $title, 'content' => $content, 'order' => $order]
        );
        
        setFlashMessage('success', 'Regel wurde erfolgreich hinzugefügt.');
    } else {
        setFlashMessage('error', 'Fehler beim Hinzufügen der Regel.');
    }
}

function handleUpdateRule() {
    $id = (int)($_POST['rule_id'] ?? 0);
    $title = sanitizeInput($_POST['rule_title'] ?? '');
    $content = sanitizeInput($_POST['rule_content'] ?? '');
    $order = (int)($_POST['rule_order'] ?? 0);
    $active = isset($_POST['is_active']) ? 1 : 0;
    
    if ($id <= 0 || empty($title) || empty($content)) {
        setFlashMessage('error', 'Ungültige Regel-Daten.');
        return;
    }
    
    $oldRule = fetchOne("SELECT * FROM server_rules WHERE id = :id", ['id' => $id]);
    
    $result = updateData('server_rules', [
        'rule_title' => $title,
        'rule_content' => $content,
        'rule_order' => $order,
        'is_active' => $active
    ], 'id = :id', ['id' => $id]);
    
    if ($result !== false) {
        logAdminActivity(
            getCurrentUser()['id'],
            'rule_updated',
            "Regel '{$title}' bearbeitet",
            'rule',
            $id,
            $oldRule,
            ['title' => $title, 'content' => $content, 'order' => $order, 'active' => $active]
        );
        
        setFlashMessage('success', 'Regel wurde erfolgreich aktualisiert.');
    } else {
        setFlashMessage('error', 'Fehler beim Aktualisieren der Regel.');
    }
}

function handleDeleteRule() {
    $id = (int)($_POST['rule_id'] ?? 0);
    
    if ($id <= 0) {
        setFlashMessage('error', 'Ungültige Regel-ID.');
        return;
    }
    
    $rule = fetchOne("SELECT * FROM server_rules WHERE id = :id", ['id' => $id]);
    
    $result = executeQuery("DELETE FROM server_rules WHERE id = :id", ['id' => $id]);
    
    if ($result) {
        if ($rule) {
            logAdminActivity(
                getCurrentUser()['id'],
                'rule_deleted',
                "Regel '{$rule['rule_title']}' gelöscht",
                'rule',
                $id,
                $rule,
                null
            );
        }
        
        setFlashMessage('success', 'Regel wurde erfolgreich gelöscht.');
    } else {
        setFlashMessage('error', 'Fehler beim Löschen der Regel.');
    }
}

function handleAddNews() {
    $title = sanitizeInput($_POST['news_title'] ?? '');
    $content = $_POST['news_content'] ?? '';
    $published = isset($_POST['is_published']) ? 1 : 0;
    
    if (empty($title) || empty($content)) {
        setFlashMessage('error', 'Titel und Inhalt sind erforderlich.');
        return;
    }
    
    $result = insertData('news', [
        'title' => $title,
        'content' => $content,
        'author_id' => getCurrentUser()['id'],
        'is_published' => $published
    ]);
    
    if ($result) {
        logAdminActivity(
            getCurrentUser()['id'],
            'news_created',
            "News-Artikel '{$title}' erstellt",
            'news',
            $result,
            null,
            ['title' => $title, 'published' => $published]
        );
        
        setFlashMessage('success', 'News-Artikel wurde erfolgreich erstellt.');
    } else {
        setFlashMessage('error', 'Fehler beim Erstellen des Artikels.');
    }
}

function handleUpdateNews() {
    $id = (int)($_POST['news_id'] ?? 0);
    $title = sanitizeInput($_POST['news_title'] ?? '');
    $content = $_POST['news_content'] ?? '';
    $published = isset($_POST['is_published']) ? 1 : 0;
    
    if ($id <= 0 || empty($title) || empty($content)) {
        setFlashMessage('error', 'Ungültige News-Daten.');
        return;
    }
    
    $oldNews = fetchOne("SELECT * FROM news WHERE id = :id", ['id' => $id]);
    
    $result = updateData('news', [
        'title' => $title,
        'content' => $content,
        'is_published' => $published
    ], 'id = :id', ['id' => $id]);
    
    if ($result !== false) {
        logAdminActivity(
            getCurrentUser()['id'],
            'news_updated',
            "News-Artikel '{$title}' bearbeitet",
            'news',
            $id,
            $oldNews,
            ['title' => $title, 'published' => $published]
        );
        
        setFlashMessage('success', 'News-Artikel wurde erfolgreich aktualisiert.');
    } else {
        setFlashMessage('error', 'Fehler beim Aktualisieren des Artikels.');
    }
}

function handleDeleteNews() {
    $id = (int)($_POST['news_id'] ?? 0);
    
    if ($id <= 0) {
        setFlashMessage('error', 'Ungültige News-ID.');
        return;
    }
    
    $news = fetchOne("SELECT * FROM news WHERE id = :id", ['id' => $id]);
    
    $result = executeQuery("DELETE FROM news WHERE id = :id", ['id' => $id]);
    
    if ($result) {
        if ($news) {
            logAdminActivity(
                getCurrentUser()['id'],
                'news_deleted',
                "News-Artikel '{$news['title']}' gelöscht",
                'news',
                $id,
                $news,
                null
            );
        }
        
        setFlashMessage('success', 'News-Artikel wurde erfolgreich gelöscht.');
    } else {
        setFlashMessage('error', 'Fehler beim Löschen des Artikels.');
    }
}

function handleAddWhitelistQuestion() {
    $question = trim($_POST['question'] ?? '');
    $questionType = $_POST['question_type'] ?? 'text';
    $correctAnswer = trim($_POST['correct_answer'] ?? '');
    $order = (int)($_POST['question_order'] ?? 0);
    $required = isset($_POST['is_required']) ? 1 : 0;
    $options = null;
    
    if (empty($question)) {
        setFlashMessage('error', 'Frage ist erforderlich.');
        return;
    }
    
    if ($questionType === 'multiple_choice') {
        $optionsArray = array_filter(array_map('trim', $_POST['options'] ?? []));
        if (count($optionsArray) < 2) {
            setFlashMessage('error', 'Multiple Choice Fragen benötigen mindestens 2 Antwortmöglichkeiten.');
            return;
        }
        if (count($optionsArray) > 3) {
            setFlashMessage('error', 'Maximal 3 Antwortmöglichkeiten sind erlaubt.');
            return;
        }
        
        if (!empty($correctAnswer) && !in_array($correctAnswer, $optionsArray)) {
            setFlashMessage('error', 'Die richtige Antwort muss eine der verfügbaren Optionen sein.');
            return;
        }
        
        $options = json_encode($optionsArray);
    }
    
    $result = insertData('whitelist_questions', [
        'question' => $question,
        'question_type' => $questionType,
        'options' => $options,
        'correct_answer' => $correctAnswer,
        'question_order' => $order,
        'is_required' => $required
    ]);
    
    if ($result) {
        logAdminActivity(
            getCurrentUser()['id'],
            'whitelist_question_created',
            "Whitelist-Frage erstellt",
            'whitelist_question',
            $result,
            null,
            ['question' => $question, 'type' => $questionType, 'order' => $order]
        );
        
        setFlashMessage('success', 'Whitelist-Frage wurde erfolgreich hinzugefügt.');
    } else {
        setFlashMessage('error', 'Fehler beim Hinzufügen der Frage.');
    }
}

function handleUpdateWhitelistQuestion() {
    $id = (int)($_POST['question_id'] ?? 0);
    $question = trim($_POST['question'] ?? '');
    $questionType = $_POST['question_type'] ?? 'text';
    $correctAnswer = trim($_POST['correct_answer'] ?? '');
    $order = (int)($_POST['question_order'] ?? 0);
    $required = isset($_POST['is_required']) ? 1 : 0;
    $active = isset($_POST['is_active']) ? 1 : 0;
    $options = null;
    
    if ($id <= 0 || empty($question)) {
        setFlashMessage('error', 'Ungültige Frage-Daten.');
        return;
    }
    
    if ($questionType === 'multiple_choice') {
        $optionsArray = array_filter(array_map('trim', $_POST['options'] ?? []));
        if (count($optionsArray) < 2) {
            setFlashMessage('error', 'Multiple Choice Fragen benötigen mindestens 2 Antwortmöglichkeiten.');
            return;
        }
        if (count($optionsArray) > 3) {
            setFlashMessage('error', 'Maximal 3 Antwortmöglichkeiten sind erlaubt.');
            return;
        }
        
        if (!empty($correctAnswer) && !in_array($correctAnswer, $optionsArray)) {
            setFlashMessage('error', 'Die richtige Antwort muss eine der verfügbaren Optionen sein.');
            return;
        }
        
        $options = json_encode($optionsArray);
    }
    
    $oldQuestion = fetchOne("SELECT * FROM whitelist_questions WHERE id = :id", ['id' => $id]);
    
    $result = updateData('whitelist_questions', [
        'question' => $question,
        'question_type' => $questionType,
        'options' => $options,
        'correct_answer' => $correctAnswer,
        'question_order' => $order,
        'is_required' => $required,
        'is_active' => $active
    ], 'id = :id', ['id' => $id]);
    
    if ($result !== false) {
        logAdminActivity(
            getCurrentUser()['id'],
            'whitelist_question_updated',
            "Whitelist-Frage bearbeitet",
            'whitelist_question',
            $id,
            $oldQuestion,
            ['question' => $question, 'type' => $questionType, 'active' => $active]
        );
        
        setFlashMessage('success', 'Whitelist-Frage wurde erfolgreich aktualisiert.');
    } else {
        setFlashMessage('error', 'Fehler beim Aktualisieren der Frage.');
    }
}

function handleDeleteWhitelistQuestion() {
    $id = (int)($_POST['question_id'] ?? 0);
    
    if ($id <= 0) {
        setFlashMessage('error', 'Ungültige Frage-ID.');
        return;
    }
    
    $question = fetchOne("SELECT * FROM whitelist_questions WHERE id = :id", ['id' => $id]);
    
    $result = executeQuery("DELETE FROM whitelist_questions WHERE id = :id", ['id' => $id]);
    
    if ($result) {
        if ($question) {
            logAdminActivity(
                getCurrentUser()['id'],
                'whitelist_question_deleted',
                "Whitelist-Frage gelöscht",
                'whitelist_question',
                $id,
                $question,
                null
            );
        }
        
        setFlashMessage('success', 'Whitelist-Frage wurde erfolgreich gelöscht.');
    } else {
        setFlashMessage('error', 'Fehler beim Löschen der Frage.');
    }
}

function handleUpdateApplicationStatus() {
    $applicationId = (int)($_POST['application_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $notes = trim($_POST['notes'] ?? '');
    
    if ($applicationId <= 0 || !in_array($status, ['pending', 'approved', 'rejected', 'closed'])) {
        setFlashMessage('error', 'Ungültige Daten.');
        return;
    }
    
    $oldApp = fetchOne("SELECT * FROM whitelist_applications WHERE id = :id", ['id' => $applicationId]);
    
    $result = updateData('whitelist_applications', [
        'status' => $status,
        'notes' => $notes,
        'reviewed_by' => getCurrentUser()['id'],
        'reviewed_at' => date('Y-m-d H:i:s')
    ], 'id = :id', ['id' => $applicationId]);
    
    if ($result !== false) {
        logAdminActivity(
            getCurrentUser()['id'],
            'whitelist_status_updated',
            "Whitelist-Bewerbung Status geändert: {$status}",
            'whitelist_application',
            $applicationId,
            ['status' => $oldApp['status'] ?? null],
            ['status' => $status, 'notes' => $notes]
        );
        
        setFlashMessage('success', 'Bewerbungsstatus wurde aktualisiert.');
    } else {
        setFlashMessage('error', 'Fehler beim Aktualisieren des Status.');
    }
}

function handleUpdateManualScore() {
    $applicationId = (int)($_POST['application_id'] ?? 0);
    $answerEvaluations = $_POST['answer_evaluations'] ?? [];
    $notes = trim($_POST['notes'] ?? '');
    
    if ($applicationId <= 0) {
        setFlashMessage('error', 'Ungültige Bewerbungs-ID.');
        return;
    }
    
    $totalAnswers = 0;
    $correctAnswers = 0;
    
    foreach ($answerEvaluations as $answerId => $isCorrect) {
        $answerId = (int)$answerId;
        $isCorrect = (int)$isCorrect;
        
        if ($answerId > 0) {
            updateData('whitelist_answers', [
                'is_correct' => $isCorrect,
                'auto_evaluated' => 0
            ], 'id = :id', ['id' => $answerId]);
            
            $totalAnswers++;
            if ($isCorrect) {
                $correctAnswers++;
            }
        }
    }
    
    $scorePercentage = $totalAnswers > 0 ? ($correctAnswers / $totalAnswers) * 100 : 0;
    
    $updateData = [
        'total_questions' => $totalAnswers,
        'correct_answers' => $correctAnswers,
        'score_percentage' => round($scorePercentage, 2),
        'reviewed_by' => getCurrentUser()['id'],
        'reviewed_at' => date('Y-m-d H:i:s')
    ];
    
    if (!empty($notes)) {
        $updateData['notes'] = $notes;
    }
    
    $autoApprove = getServerSetting('whitelist_auto_approve', '0');
    $passingScore = getServerSetting('whitelist_passing_score', '70');
    
    if ($autoApprove && $scorePercentage >= $passingScore) {
        $updateData['status'] = 'approved';
        $updateData['notes'] = ($notes ? $notes . "\n\n" : '') . 
                               "Automatisch genehmigt aufgrund hoher Punktzahl (" . round($scorePercentage, 1) . "%)";
    }
    
    $result = updateData('whitelist_applications', $updateData, 'id = :id', ['id' => $applicationId]);
    
    if ($result !== false) {
        logAdminActivity(
            getCurrentUser()['id'],
            'whitelist_manual_score',
            "Whitelist-Bewerbung manuell bewertet: {$scorePercentage}%",
            'whitelist_application',
            $applicationId,
            null,
            ['score' => $scorePercentage, 'correct' => $correctAnswers, 'total' => $totalAnswers]
        );
        
        setFlashMessage('success', 'Bewertung wurde erfolgreich aktualisiert.');
    } else {
        setFlashMessage('error', 'Fehler beim Aktualisieren der Bewertung.');
    }
}

// Benutzer-Berechtigungen abrufen
$userPermissions = [];
if ($currentUser['permissions']) {
    $userPermissions = json_decode($currentUser['permissions'], true) ?: [];
}

$role = getRoleByName($currentUser['role']);
if ($role && $role['permissions']) {
    $rolePermissions = json_decode($role['permissions'], true) ?: [];
    $userPermissions = array_merge($userPermissions, $rolePermissions);
}

if ($currentUser['role'] === 'super_admin') {
    $userPermissions = array_keys(AVAILABLE_PERMISSIONS);
}

// Twitch-Daten für Dashboard laden
$twitchEnabled = getServerSetting('twitch_display_enabled', '1');
$twitchStreamers = [];
$twitchLiveCount = 0;
$twitchApiStatus = 'not_configured';

if ($twitchEnabled && function_exists('getAllStreamers')) {
    try {
        $twitchStreamers = getAllStreamers();
        $liveStreamers = getLiveStreamers();
        $twitchLiveCount = count($liveStreamers);
        
        $twitchAPI = getTwitchAPI();
        if ($twitchAPI) {
            $twitchApiStatus = 'connected';
        } else {
            $twitchApiStatus = 'not_configured';
        }
    } catch (Exception $e) {
        $twitchApiStatus = 'error';
        error_log('Twitch Dashboard Error: ' . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&display=swap" rel="stylesheet">
    
    <!-- Styles -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    
    <!-- Meta Tags -->
    <meta name="robots" content="noindex,nofollow">
    <meta name="description" content="<?php echo SITE_NAME; ?> Admin Dashboard">
</head>
<body>
    <!-- Background Effects -->
    <div class="bg-video"></div>
    <div class="bg-overlay"></div>
    
    <!-- Dashboard Header -->
    <header class="dashboard-header">
        <div class="dashboard-nav">
            <div class="dashboard-title">🧟 <?php echo SITE_NAME; ?> Control Panel</div>
            <div class="user-info">
                <span>👋 Willkommen, <strong><?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?></strong></span>
                <span style="color: var(--gray); font-size: 0.8rem; margin-left: 1rem;">
                    Rolle: <?php echo ucfirst($currentUser['role']); ?>
                </span>
                <a href="logout.php" class="logout-btn">🚪 Abmelden</a>
            </div>
        </div>
    </header>
    
    <!-- Main Content Container -->
    <div class="admin-container">
        
        <!-- Flash Messages Container -->
        <?php if (!empty($flashMessages)): ?>
        <div class="flash-messages">
            <?php foreach ($flashMessages as $message): ?>
            <div class="flash-message <?php echo $message['type']; ?>">
                <?php echo htmlspecialchars($message['message']); ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <!-- Modern Navigation Grid -->
        <div class="admin-nav">
            <a href="?page=overview" class="nav-button <?php echo $page === 'overview' ? 'active' : ''; ?>">
                <span class="icon">📊</span>
                <div class="text">
                    <span class="title">Dashboard</span>
                    <span class="subtitle">Übersicht & Statistiken</span>
                </div>
            </a>
            
            <?php if (hasPermission('users.read')): ?>
            <a href="users.php" class="nav-button">
                <span class="icon">👥</span>
                <div class="text">
                    <span class="title">Benutzer</span>
                    <span class="subtitle">Benutzer-Management</span>
                </div>
            </a>
            <?php endif; ?>
            
            <?php if (hasPermission('settings.read')): ?>
            <a href="?page=settings" class="nav-button <?php echo $page === 'settings' ? 'active' : ''; ?>">
                <span class="icon">⚙️</span>
                <div class="text">
                    <span class="title">Einstellungen</span>
                    <span class="subtitle">Server-Konfiguration</span>
                </div>
            </a>
            <?php endif; ?>
            
            <!-- Twitch Streamers Navigation Button -->
            <?php if (hasPermission('settings.update')): ?>
            <a href="streamers.php" class="nav-button">
                <span class="icon">📺</span>
                <div class="text">
                    <span class="title">Twitch Streams</span>
                    <span class="subtitle"><?php echo count($twitchStreamers); ?> Streamer, <?php echo $twitchLiveCount; ?> live</span>
                </div>
                <?php if ($twitchLiveCount > 0): ?>
                <div class="nav-badge live-badge">🔴</div>
                <?php endif; ?>
            </a>
            <?php endif; ?>
            
            <?php if (hasPermission('rules.read')): ?>
            <a href="?page=rules" class="nav-button <?php echo $page === 'rules' ? 'active' : ''; ?>">
                <span class="icon">📋</span>
                <div class="text">
                    <span class="title">Regeln</span>
                    <span class="subtitle">Server-Regeln verwalten</span>
                </div>
            </a>
            <?php endif; ?>
            
            <?php if (hasPermission('news.read')): ?>
            <a href="?page=news" class="nav-button <?php echo $page === 'news' ? 'active' : ''; ?>">
                <span class="icon">📰</span>
                <div class="text">
                    <span class="title">News</span>
                    <span class="subtitle">Artikel & Updates</span>
                </div>
            </a>
            <?php endif; ?>
            
            <?php if (hasPermission('whitelist.read')): ?>
            <a href="?page=whitelist" class="nav-button <?php echo $page === 'whitelist' ? 'active' : ''; ?>">
                <span class="icon">📝</span>
                <div class="text">
                    <span class="title">Whitelist</span>
                    <span class="subtitle">Bewerbungen verwalten</span>
                </div>
            </a>
            <?php endif; ?>
            
            <?php if (hasPermission('whitelist.questions.manage')): ?>
            <a href="?page=whitelist_questions" class="nav-button <?php echo $page === 'whitelist_questions' ? 'active' : ''; ?>">
                <span class="icon">❓</span>
                <div class="text">
                    <span class="title">WL-Fragen</span>
                    <span class="subtitle">Fragebogen verwalten</span>
                </div>
            </a>
            <?php endif; ?>
            
            <?php if (hasPermission('activity.read')): ?>
            <a href="?page=activity" class="nav-button <?php echo $page === 'activity' ? 'active' : ''; ?>">
                <span class="icon">📋</span>
                <div class="text">
                    <span class="title">Aktivitäten</span>
                    <span class="subtitle">Admin-Aktivitätslog</span>
                </div>
            </a>
            <?php endif; ?>
            
            <?php if (hasPermission('logs.read')): ?>
            <a href="?page=logs" class="nav-button <?php echo $page === 'logs' ? 'active' : ''; ?>">
                <span class="icon">📜</span>
                <div class="text">
                    <span class="title">Protokolle</span>
                    <span class="subtitle">Login-Überwachung</span>
                </div>
            </a>
            <?php endif; ?>
            
            <a href="../index.php" target="_blank" class="nav-button external">
                <span class="icon">🌐</span>
                <div class="text">
                    <span class="title">Website</span>
                    <span class="subtitle">Live-Ansicht öffnen</span>
                </div>
            </a>
        </div>
        
        <!-- Dashboard Overview Section -->
        <div id="overview" class="content-section <?php echo $page === 'overview' ? 'active' : ''; ?>">
            <div class="admin-card">
                <h2>🎯 Dashboard Übersicht</h2>
                <div class="stats-grid">
                    <?php
                    $currentPlayers = getServerSetting('current_players', '0');
                    $maxPlayers = getServerSetting('max_players', '64');
                    $totalRules = fetchOne("SELECT COUNT(*) as count FROM server_rules WHERE is_active = 1")['count'] ?? 0;
                    $totalNews = fetchOne("SELECT COUNT(*) as count FROM news WHERE is_published = 1")['count'] ?? 0;
                    $totalAdmins = fetchOne("SELECT COUNT(*) as count FROM admins WHERE is_active = 1")['count'] ?? 0;
                    $activeAdmins = fetchOne("SELECT COUNT(DISTINCT admin_id) as count FROM admin_sessions WHERE last_activity > DATE_SUB(NOW(), INTERVAL 1 HOUR)")['count'] ?? 0;
                    $recentLogins = fetchOne("SELECT COUNT(*) as count FROM login_attempts WHERE success = 1 AND attempted_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)")['count'] ?? 0;
                    $serverOnline = getServerSetting('is_online', '1');
                    $pendingApplications = fetchOne("SELECT COUNT(*) as count FROM whitelist_applications WHERE status = 'pending'")['count'] ?? 0;
                    $totalQuestions = fetchOne("SELECT COUNT(*) as count FROM whitelist_questions WHERE is_active = 1")['count'] ?? 0;
                    $avgScore = fetchOne("SELECT AVG(score_percentage) as avg_score FROM whitelist_applications WHERE score_percentage > 0")['avg_score'] ?? 0;
                    $highScoreApps = fetchOne("SELECT COUNT(*) as count FROM whitelist_applications WHERE score_percentage >= 70")['count'] ?? 0;
                    $recentActivities = fetchOne("SELECT COUNT(*) as count FROM admin_activity_log WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)")['count'] ?? 0;
                    ?>
                    
                    <div class="stat-card">
                        <div class="stat-icon">👥</div>
                        <h3><?php echo $currentPlayers; ?>/<?php echo $maxPlayers; ?></h3>
                        <p>Aktuelle Spieler</p>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">👨‍💼</div>
                        <h3><?php echo $activeAdmins; ?>/<?php echo $totalAdmins; ?></h3>
                        <p>Aktive Admins</p>
                    </div>
                    
                    <!-- Twitch Stats Card -->
                    <?php if ($twitchEnabled && hasPermission('settings.update')): ?>
                    <div class="stat-card <?php echo $twitchLiveCount > 0 ? 'live-highlight' : ''; ?>">
                        <div class="stat-icon"><?php echo $twitchLiveCount > 0 ? '🔴' : '📺'; ?></div>
                        <h3><?php echo $twitchLiveCount; ?>/<?php echo count($twitchStreamers); ?></h3>
                        <p>Live Streamer</p>
                        <?php if ($twitchApiStatus !== 'connected'): ?>
                        <small style="color: var(--warning); font-size: 0.7rem;">⚠️ API nicht konfiguriert</small>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="stat-card">
                        <div class="stat-icon">📜</div>
                        <h3><?php echo $totalRules; ?></h3>
                        <p>Aktive Regeln</p>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">📰</div>
                        <h3><?php echo $totalNews; ?></h3>
                        <p>Veröffentlichte News</p>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">📝</div>
                        <h3><?php echo $pendingApplications; ?></h3>
                        <p>Offene Bewerbungen</p>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">❓</div>
                        <h3><?php echo $totalQuestions; ?></h3>
                        <p>WL-Fragen</p>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">🎯</div>
                        <h3><?php echo round($avgScore, 1); ?>%</h3>
                        <p>Durchschnittlicher Score</p>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">⭐</div>
                        <h3><?php echo $highScoreApps; ?></h3>
                        <p>High-Score Bewerbungen</p>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">📊</div>
                        <h3><?php echo $recentActivities; ?></h3>
                        <p>Aktivitäten (24h)</p>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon"><?php echo $serverOnline ? '🟢' : '🔴'; ?></div>
                        <h3><?php echo $serverOnline ? 'Online' : 'Offline'; ?></h3>
                        <p>Server Status</p>
                    </div>
                </div>
                
                <!-- Twitch Stream Overview (nur wenn aktiviert und berechtigt) -->
                <?php if ($twitchEnabled && hasPermission('settings.update') && !empty($twitchStreamers)): ?>
                <div style="margin-top: 2rem;">
                    <h3 style="color: var(--primary); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                        📺 Live Streams Übersicht
                        <?php if ($twitchLiveCount > 0): ?>
                        <span class="badge badge-danger" style="font-size: 0.7rem;">🔴 <?php echo $twitchLiveCount; ?> LIVE</span>
                        <?php endif; ?>
                    </h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem;">
                        <?php 
                        $displayStreamers = array_slice($twitchStreamers, 0, 4); // Nur erste 4 anzeigen
                        foreach ($displayStreamers as $streamer): 
                        ?>
                        <div class="feature-card <?php echo $streamer['is_currently_live'] ? 'live-streamer' : 'offline-streamer'; ?>">
                            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem;">
                                <?php if ($streamer['profile_image_url']): ?>
                                    <img src="<?php echo htmlspecialchars($streamer['profile_image_url']); ?>" 
                                         style="width: 40px; height: 40px; border-radius: 50%; border: 2px solid <?php echo $streamer['is_currently_live'] ? '#ff4444' : '#9146ff'; ?>;" 
                                         alt="Avatar">
                                <?php else: ?>
                                    <div style="width: 40px; height: 40px; border-radius: 50%; background: #9146ff; display: flex; align-items: center; justify-content: center; color: white; font-size: 0.8rem; font-weight: bold;">
                                        <?php echo strtoupper(substr($streamer['display_name'], 0, 2)); ?>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <h4 style="margin: 0; color: var(--primary);"><?php echo htmlspecialchars($streamer['display_name']); ?></h4>
                                    <small style="color: var(--gray);">@<?php echo htmlspecialchars($streamer['streamer_name']); ?></small>
                                </div>
                            </div>
                            
                            <?php if ($streamer['is_currently_live']): ?>
                                <div style="color: var(--success); font-size: 0.9rem; margin-bottom: 0.5rem;">
                                    🔴 <strong>LIVE</strong> - <?php echo number_format($streamer['viewer_count']); ?> Zuschauer
                                </div>
                                <?php if ($streamer['last_stream_title']): ?>
                                <p style="font-size: 0.85rem; margin-bottom: 0.5rem;">
                                    <?php echo htmlspecialchars(substr($streamer['last_stream_title'], 0, 40)); ?>
                                    <?php echo strlen($streamer['last_stream_title']) > 40 ? '...' : ''; ?>
                                </p>
                                <?php endif; ?>
                                <?php if ($streamer['last_stream_game']): ?>
                                <small style="color: var(--text-secondary);">
                                    Spielt: <?php echo htmlspecialchars($streamer['last_stream_game']); ?>
                                </small>
                                <?php endif; ?>
                            <?php else: ?>
                                <div style="color: var(--gray); font-size: 0.9rem;">
                                    ⚫ Offline
                                    <?php if ($streamer['last_live_check']): ?>
                                        <br><small>Letzter Check: <?php echo date('H:i', strtotime($streamer['last_live_check'])); ?></small>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <div style="margin-top: 1rem; display: flex; gap: 0.5rem;">
                                <a href="https://twitch.tv/<?php echo htmlspecialchars($streamer['streamer_name']); ?>" 
                                   target="_blank" 
                                   class="btn btn-small btn-secondary">
                                    📺 Twitch
                                </a>
                                <?php if ($streamer['is_currently_live']): ?>
                                <span class="btn btn-small btn-success" style="cursor: default;">
                                    👁️ Live
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div style="text-align: center; margin-top: 1rem;">
                        <a href="streamers.php" class="btn btn-primary">
                            📺 Alle Streamer verwalten (<?php echo count($twitchStreamers); ?>)
                        </a>
                        <?php if ($twitchApiStatus === 'connected'): ?>
                        <button onclick="updateStreamStatus()" class="btn btn-secondary" id="updateStreamBtn">
                            🔄 Status aktualisieren
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Benutzer-Berechtigungen anzeigen -->
                <div style="margin-top: 2rem;">
                    <h3 style="color: var(--primary); margin-bottom: 1rem;">🔑 Ihre Berechtigungen</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                        <?php
                        $permissionCategories = [
                            'Benutzer' => ['users.create', 'users.read', 'users.update', 'users.delete', 'users.activate', 'users.reset_password'],
                            'System' => ['settings.read', 'settings.update', 'settings.backup', 'settings.restore'],
                            'Whitelist' => ['whitelist.read', 'whitelist.update', 'whitelist.approve', 'whitelist.reject', 'whitelist.questions.manage'],
                            'Content' => ['news.create', 'news.read', 'news.update', 'news.delete', 'rules.create', 'rules.read', 'rules.update', 'rules.delete'],
                            'Logs' => ['logs.read', 'activity.read', 'api.access']
                        ];
                        
                        foreach ($permissionCategories as $category => $permissions): ?>
                        <div style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px; padding: 1rem;">
                            <h4 style="color: var(--secondary); margin-bottom: 0.5rem;"><?php echo $category; ?></h4>
                            <?php
                            $hasPermissions = false;
                            foreach ($permissions as $permission) {
                                if (in_array($permission, $userPermissions)) {
                                    $hasPermissions = true;
                                    $permissionName = AVAILABLE_PERMISSIONS[$permission] ?? $permission;
                                    echo '<div style="color: var(--success); font-size: 0.9rem; margin-bottom: 0.25rem;">✅ ' . htmlspecialchars($permissionName) . '</div>';
                                }
                            }
                            if (!$hasPermissions) {
                                echo '<div style="color: var(--gray); font-size: 0.9rem;">❌ Keine Berechtigungen</div>';
                            }
                            ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div style="margin-top: 2rem;">
                    <h3 style="color: var(--primary); margin-bottom: 1rem;">⚡ Schnellzugriff</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                        <?php if (hasPermission('news.create')): ?>
                        <button onclick="openModal('addNewsModal')" class="btn btn-primary" style="padding: 1rem;">
                            📰 News erstellen
                        </button>
                        <?php endif; ?>
                        
                        <?php if (hasPermission('rules.create')): ?>
                        <button onclick="openModal('addRuleModal')" class="btn btn-primary" style="padding: 1rem;">
                            📋 Regel hinzufügen
                        </button>
                        <?php endif; ?>
                        
                        <!-- Twitch Streamer Quick Add -->
                        <?php if (hasPermission('settings.update') && $twitchEnabled): ?>
                        <a href="streamers.php" class="btn btn-primary" style="padding: 1rem; text-align: center;">
                            📺 Streamer hinzufügen
                        </a>
                        <?php endif; ?>
                        
                        <?php if (hasPermission('users.read')): ?>
                        <a href="users.php" class="btn btn-secondary" style="padding: 1rem; text-align: center;">
                            👥 Benutzer verwalten (<?php echo $totalAdmins; ?>)
                        </a>
                        <?php endif; ?>
                        
                        <?php if (hasPermission('whitelist.read')): ?>
                        <a href="?page=whitelist" class="btn btn-secondary" style="padding: 1rem; text-align: center;">
                            📝 Bewerbungen (<?php echo $pendingApplications; ?>)
                        </a>
                        <?php endif; ?>
                        
                        <?php if (hasPermission('whitelist.questions.manage')): ?>
                        <a href="?page=whitelist_questions" class="btn btn-secondary" style="padding: 1rem; text-align: center;">
                            ❓ WL-Fragen (<?php echo $totalQuestions; ?>)
                        </a>
                        <?php endif; ?>
                        
                        <?php if (hasPermission('activity.read')): ?>
                        <a href="?page=activity" class="btn btn-secondary" style="padding: 1rem; text-align: center;">
                            📋 Aktivitäten (<?php echo $recentActivities; ?>)
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Letzte Aktivitäten -->
                <?php if (hasPermission('activity.read')): ?>
                <div style="margin-top: 2rem;">
                    <h3 style="color: var(--primary); margin-bottom: 1rem;">📋 Letzte Aktivitäten</h3>
                    <?php
                    $recentActivity = fetchAll("
                        SELECT al.*, a.username, a.first_name, a.last_name 
                        FROM admin_activity_log al 
                        LEFT JOIN admins a ON al.admin_id = a.id 
                        ORDER BY al.created_at DESC 
                        LIMIT 10
                    ");
                    ?>
                    
                    <?php if (!empty($recentActivity)): ?>
                    <div class="data-table">
                        <table class="table" style="margin: 0;">
                            <thead>
                                <tr>
                                    <th>👤 Benutzer</th>
                                    <th>⚡ Aktion</th>
                                    <th>📝 Beschreibung</th>
                                    <th>⏰ Zeitpunkt</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentActivity as $activity): ?>
                                <tr>
                                    <td>
                                        <?php echo htmlspecialchars(($activity['first_name'] ?? '') . ' ' . ($activity['last_name'] ?? '')); ?>
                                        <small style="display: block; color: var(--gray);">
                                            @<?php echo htmlspecialchars($activity['username'] ?? 'Unbekannt'); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php
                                        // Twitch-spezifische Actions mit speziellen Badges
                                        $badgeClass = 'badge-info';
                                        if (strpos($activity['action'], 'stream') !== false) {
                                            $badgeClass = 'badge-twitch';
                                        } elseif (strpos($activity['action'], 'streamer') !== false) {
                                            $badgeClass = 'badge-twitch';
                                        }
                                        ?>
                                        <span class="badge <?php echo $badgeClass; ?>">
                                            <?php echo htmlspecialchars($activity['action']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($activity['description'] ?? '-'); ?></td>
                                    <td>
                                        <?php echo date('d.m.Y H:i', strtotime($activity['created_at'])); ?>
                                        <small style="display: block; color: var(--gray);">
                                            IP: <?php echo htmlspecialchars($activity['ip_address']); ?>
                                        </small>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div style="text-align: center; padding: 2rem; color: var(--gray);">
                        <p>📋 Noch keine Aktivitäten protokolliert.</p>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Server Settings Section (erweitert mit Twitch-Einstellungen) -->
        <?php if (hasPermission('settings.read') && $page === 'settings'): ?>
        <div id="settings" class="content-section active">
            <div class="admin-card">
                <h2>⚙️ Server-Einstellungen</h2>
                
                <?php if (!hasPermission('settings.update')): ?>
                <div class="alert alert-warning">
                    <strong>⚠️ Hinweis:</strong> Sie haben nur Lesezugriff auf die Einstellungen.
                </div>
                <?php endif; ?>
                
                <form method="POST" action="" id="settingsForm">
                    <input type="hidden" name="action" value="update_settings">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <h3 style="color: var(--primary); margin-bottom: 1rem;">🎮 Server-Grundeinstellungen</h3>
                    
                    <div class="form-group">
                        <label for="server_name">🏷️ Server Name</label>
                        <input type="text" 
                               id="server_name" 
                               name="server_name" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars(getServerSetting('server_name', 'Zombie RP Server')); ?>" 
                               <?php echo hasPermission('settings.update') ? 'required' : 'readonly'; ?>>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label for="max_players">👥 Maximale Spieler</label>
                            <input type="number" 
                                   id="max_players" 
                                   name="max_players" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars(getServerSetting('max_players', '64')); ?>" 
                                   min="1" max="128" 
                                   <?php echo hasPermission('settings.update') ? 'required' : 'readonly'; ?>>
                        </div>
                        
                        <div class="form-group">
                            <label for="current_players">🎮 Aktuelle Spieler</label>
                            <input type="number" 
                                   id="current_players" 
                                   name="current_players" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars(getServerSetting('current_players', '0')); ?>" 
                                   min="0" 
                                   <?php echo hasPermission('settings.update') ? 'required' : 'readonly'; ?>>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="server_ip">🌐 Server IP/Domain</label>
                        <input type="text" 
                               id="server_ip" 
                               name="server_ip" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars(getServerSetting('server_ip', 'localhost')); ?>" 
                               <?php echo hasPermission('settings.update') ? 'required' : 'readonly'; ?>>
                    </div>
                    
                    <div class="form-group">
                        <label for="discord_link">💬 Discord Link</label>
                        <input type="url" 
                               id="discord_link" 
                               name="discord_link" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars(getServerSetting('discord_link', '#')); ?>"
                               <?php echo hasPermission('settings.update') ? '' : 'readonly'; ?>>
                    </div>
                    
                    <!-- Twitch Integration Settings -->
                    <?php if (hasPermission('settings.update')): ?>
                    <h3 style="color: var(--primary); margin: 2rem 0 1rem;">📺 Twitch Integration</h3>
                    
                    <div class="alert alert-info" style="margin-bottom: 1rem;">
                        <strong>💡 Hinweis:</strong> Die detaillierten Twitch API-Einstellungen finden Sie unter 
                        <a href="streamers.php" style="color: var(--primary);">Twitch Streamers</a>.
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label for="twitch_max_display">📺 Max. gleichzeitige Streams</label>
                            <input type="number" 
                                   id="twitch_max_display" 
                                   name="twitch_max_display" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars(getServerSetting('twitch_max_display', '3')); ?>" 
                                   min="1" max="10">
                        </div>
                        
                        <div class="form-group">
                            <label for="twitch_update_interval">⏱️ Update-Intervall (Sekunden)</label>
                            <input type="number" 
                                   id="twitch_update_interval" 
                                   name="twitch_update_interval" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars(getServerSetting('twitch_update_interval', '300')); ?>" 
                                   min="60" max="3600">
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 2rem; margin: 1rem 0; flex-wrap: wrap;">
                        <label style="display: flex; align-items: center; gap: 0.5rem;">
                            <input type="checkbox" name="twitch_display_enabled" 
                                   <?php echo getServerSetting('twitch_display_enabled', '1') ? 'checked' : ''; ?>>
                            <span>📺 Twitch Stream-Anzeige aktiviert</span>
                        </label>
                        
                        <label style="display: flex; align-items: center; gap: 0.5rem;">
                            <input type="checkbox" name="twitch_auto_update" 
                                   <?php echo getServerSetting('twitch_auto_update', '1') ? 'checked' : ''; ?>>
                            <span>🔄 Automatische Stream-Updates</span>
                        </label>
                    </div>
                    <?php endif; ?>
                    
                    <h3 style="color: var(--primary); margin: 2rem 0 1rem;">📝 Discord OAuth2 Einstellungen</h3>
                    
                    <div class="form-group">
                        <label for="discord_client_id">🆔 Discord Client ID</label>
                        <input type="text" 
                               id="discord_client_id" 
                               name="discord_client_id" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars(getServerSetting('discord_client_id', '')); ?>"
                               <?php echo hasPermission('settings.update') ? '' : 'readonly'; ?>>
                    </div>
                    
                    <div class="form-group">
                        <label for="discord_client_secret">🔐 Discord Client Secret</label>
                        <input type="password" 
                               id="discord_client_secret" 
                               name="discord_client_secret" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars(getServerSetting('discord_client_secret', '')); ?>"
                               <?php echo hasPermission('settings.update') ? '' : 'readonly'; ?>>
                    </div>
                    
                    <div class="form-group">
                        <label for="discord_redirect_uri">🔄 Discord Redirect URI</label>
                        <input type="url" 
                               id="discord_redirect_uri" 
                               name="discord_redirect_uri" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars(getServerSetting('discord_redirect_uri', SITE_URL . '/whitelist/discord-callback.php')); ?>"
                               <?php echo hasPermission('settings.update') ? '' : 'readonly'; ?>>
                    </div>
                    
                    <h3 style="color: var(--primary); margin: 2rem 0 1rem;">🎯 Whitelist-Einstellungen</h3>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label for="min_age">🔞 Mindestalter</label>
                            <input type="number" 
                                   id="min_age" 
                                   name="min_age" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars(getServerSetting('min_age', '18')); ?>" 
                                   min="12" max="21" 
                                   <?php echo hasPermission('settings.update') ? 'required' : 'readonly'; ?>>
                        </div>
                        
                        <div class="form-group">
                            <label for="whitelist_questions_count">❓ Anzahl Fragen</label>
                            <input type="number" 
                                   id="whitelist_questions_count" 
                                   name="whitelist_questions_count" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars(getServerSetting('whitelist_questions_count', '5')); ?>" 
                                   min="1" max="10" 
                                   <?php echo hasPermission('settings.update') ? 'required' : 'readonly'; ?>>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="whitelist_passing_score">🎯 Mindestpunktzahl (%)</label>
                        <input type="number" 
                               id="whitelist_passing_score" 
                               name="whitelist_passing_score" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars(getServerSetting('whitelist_passing_score', '70')); ?>" 
                               min="0" max="100" 
                               <?php echo hasPermission('settings.update') ? 'required' : 'readonly'; ?>>
                    </div>
                    
                    <?php if (hasPermission('settings.update')): ?>
                    <div style="display: flex; gap: 2rem; margin: 2rem 0;">
                        <label style="display: flex; align-items: center; gap: 0.5rem;">
                            <input type="checkbox" name="is_online" <?php echo getServerSetting('is_online', '1') ? 'checked' : ''; ?>>
                            <span>🟢 Server ist online</span>
                        </label>
                        
                        <label style="display: flex; align-items: center; gap: 0.5rem;">
                            <input type="checkbox" name="whitelist_active" <?php echo getServerSetting('whitelist_active', '1') ? 'checked' : ''; ?>>
                            <span>🔒 Whitelist aktiv</span>
                        </label>
                        
                        <label style="display: flex; align-items: center; gap: 0.5rem;">
                            <input type="checkbox" name="whitelist_enabled" <?php echo getServerSetting('whitelist_enabled', '1') ? 'checked' : ''; ?>>
                            <span>📝 Whitelist-System aktiviert</span>
                        </label>
                        
                        <label style="display: flex; align-items: center; gap: 0.5rem;">
                            <input type="checkbox" name="whitelist_auto_approve" <?php echo getServerSetting('whitelist_auto_approve', '0') ? 'checked' : ''; ?>>
                            <span>🤖 Automatische Genehmigung</span>
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">💾 Einstellungen speichern</button>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- Bestehende Sections bleiben unverändert... -->
        <!-- Rules Management Section -->
        <?php if (hasPermission('rules.read') && $page === 'rules'): ?>
        <div id="rules" class="content-section active">
            <div class="admin-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <h2>📋 Server-Regeln verwalten</h2>
                    <?php if (hasPermission('rules.create')): ?>
                    <button onclick="openModal('addRuleModal')" class="btn btn-primary">➕ Neue Regel hinzufügen</button>
                    <?php endif; ?>
                </div>
                
                <?php $rules = fetchAll("SELECT * FROM server_rules ORDER BY rule_order ASC, id ASC"); ?>
                <?php if (!empty($rules)): ?>
                <div class="data-table">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>📈 Reihenfolge</th>
                                <th>📋 Titel</th>
                                <th>📝 Inhalt</th>
                                <th>📄 Status</th>
                                <th>⚡ Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rules as $rule): ?>
                            <tr>
                                <td><?php echo $rule['rule_order']; ?></td>
                                <td><strong><?php echo htmlspecialchars($rule['rule_title']); ?></strong></td>
                                <td><?php echo htmlspecialchars(substr($rule['rule_content'], 0, 80)) . '...'; ?></td>
                                <td>
                                    <span class="badge <?php echo $rule['is_active'] ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo $rule['is_active'] ? '✅ Aktiv' : '❌ Inaktiv'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 0.5rem;">
                                        <?php if (hasPermission('rules.update')): ?>
                                        <button onclick="editRule(<?php echo htmlspecialchars(json_encode($rule)); ?>)" 
                                                class="btn btn-small btn-edit">✏️ Bearbeiten</button>
                                        <?php endif; ?>
                                        
                                        <?php if (hasPermission('rules.delete')): ?>
                                        <button onclick="deleteRule(<?php echo $rule['id']; ?>)" 
                                                class="btn btn-small btn-delete">🗑️ Löschen</button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div style="text-align: center; padding: 3rem; color: var(--gray);">
                    <p>📝 Noch keine Regeln erstellt.</p>
                    <?php if (hasPermission('rules.create')): ?>
                    <button onclick="openModal('addRuleModal')" class="btn btn-primary" style="margin-top: 1rem;">
                        ➕ Erste Regel hinzufügen
                    </button>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- News Management Section -->
        <?php if (hasPermission('news.read') && $page === 'news'): ?>
        <div id="news" class="content-section active">
            <div class="admin-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <h2>📰 News verwalten</h2>
                    <?php if (hasPermission('news.create')): ?>
                    <button onclick="openModal('addNewsModal')" class="btn btn-primary">➕ Neuen Artikel erstellen</button>
                    <?php endif; ?>
                </div>
                
                <?php $news = fetchAll("SELECT n.*, a.username as author_name FROM news n LEFT JOIN admins a ON n.author_id = a.id ORDER BY n.created_at DESC"); ?>
                <?php if (!empty($news)): ?>
                <div class="data-table">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>📰 Titel</th>
                                <th>👤 Autor</th>
                                <th>📄 Status</th>
                                <th>📅 Erstellt</th>
                                <th>⚡ Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($news as $article): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($article['title']); ?></strong></td>
                                <td><?php echo htmlspecialchars($article['author_name'] ?? 'Unbekannt'); ?></td>
                                <td>
                                    <span class="badge <?php echo $article['is_published'] ? 'badge-success' : 'badge-warning'; ?>">
                                        <?php echo $article['is_published'] ? '✅ Veröffentlicht' : '📝 Entwurf'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('d.m.Y H:i', strtotime($article['created_at'])); ?></td>
                                <td>
                                    <div style="display: flex; gap: 0.5rem;">
                                        <?php if (hasPermission('news.update')): ?>
                                        <button onclick="editNews(<?php echo htmlspecialchars(json_encode($article)); ?>)" 
                                                class="btn btn-small btn-edit">✏️ Bearbeiten</button>
                                        <?php endif; ?>
                                        
                                        <?php if (hasPermission('news.delete')): ?>
                                        <button onclick="deleteNews(<?php echo $article['id']; ?>)" 
                                                class="btn btn-small btn-delete">🗑️ Löschen</button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div style="text-align: center; padding: 3rem; color: var(--gray);">
                    <p>📰 Noch keine News-Artikel erstellt.</p>
                    <?php if (hasPermission('news.create')): ?>
                    <button onclick="openModal('addNewsModal')" class="btn btn-primary" style="margin-top: 1rem;">
                        ➕ Ersten Artikel erstellen
                    </button>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Weitere Sections bleiben unverändert... -->
        <!-- Whitelist Applications Section -->
        <?php if (hasPermission('whitelist.read') && $page === 'whitelist'): ?>
        <div id="whitelist" class="content-section active">
            <div class="admin-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <h2>📝 Whitelist Bewerbungen</h2>
                    <div style="display: flex; gap: 1rem; align-items: center;">
                        <select id="statusFilter" onchange="filterApplications()" class="form-control" style="width: auto;">
                            <option value="">Alle Status</option>
                            <option value="pending">Noch offen</option>
                            <option value="closed">Geschlossen</option>
                            <option value="approved">Genehmigt</option>
                            <option value="rejected">Abgelehnt</option>
                        </select>
                        <select id="scoreFilter" onchange="filterApplications()" class="form-control" style="width: auto;">
                            <option value="">Alle Scores</option>
                            <option value="high">≥70% (Hoch)</option>
                            <option value="medium">50-69% (Mittel)</option>
                            <option value="low"><50% (Niedrig)</option>
                            <option value="unscored">Nicht bewertet</option>
                        </select>
                    </div>
                </div>
                
                <?php $applications = fetchAll("
                    SELECT wa.*, a.username as reviewed_by_name 
                    FROM whitelist_applications wa 
                    LEFT JOIN admins a ON wa.reviewed_by = a.id 
                    ORDER BY 
                        CASE wa.status 
                            WHEN 'pending' THEN 1 
                            WHEN 'closed' THEN 2 
                            WHEN 'approved' THEN 3 
                            WHEN 'rejected' THEN 4 
                        END, 
                        wa.score_percentage DESC,
                        wa.created_at DESC
                "); ?>
                
                <?php if (!empty($applications)): ?>
                <div class="data-table">
                    <table class="table" id="applicationsTable">
                        <thead>
                            <tr>
                                <th>👤 Discord User</th>
                                <th>🎯 Score</th>
                                <th>📊 Status</th>
                                <th>📅 Eingereicht</th>
                                <th>👨‍💼 Bearbeiter</th>
                                <th>⚡ Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($applications as $app): ?>
                            <?php 
                            $scoreClass = 'unscored';
                            if ($app['score_percentage'] > 0) {
                                if ($app['score_percentage'] >= 70) $scoreClass = 'high';
                                elseif ($app['score_percentage'] >= 50) $scoreClass = 'medium';
                                else $scoreClass = 'low';
                            }
                            ?>
                            <tr data-status="<?php echo $app['status']; ?>" data-score="<?php echo $scoreClass; ?>">
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <?php if ($app['discord_avatar']): ?>
                                            <img src="<?php echo htmlspecialchars($app['discord_avatar']); ?>" 
                                                 style="width: 32px; height: 32px; border-radius: 50%; border: 2px solid #5865f2;" 
                                                 alt="Avatar">
                                        <?php else: ?>
                                            <div style="width: 32px; height: 32px; border-radius: 50%; background: #5865f2; display: flex; align-items: center; justify-content: center; color: white; font-size: 0.8rem; font-weight: bold;">
                                                <?php echo strtoupper(substr($app['discord_username'], 0, 2)); ?>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <strong><?php echo htmlspecialchars($app['discord_username']); ?></strong>
                                            <br><small style="color: var(--gray);">ID: <?php echo htmlspecialchars($app['discord_id']); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($app['score_percentage'] > 0): ?>
                                        <div style="text-align: center;">
                                            <div style="font-size: 1.2rem; font-weight: bold; color: <?php 
                                                echo $app['score_percentage'] >= 70 ? 'var(--success)' : 
                                                    ($app['score_percentage'] >= 50 ? 'var(--warning)' : 'var(--danger)'); 
                                            ?>;">
                                                <?php echo round($app['score_percentage'], 1); ?>%
                                            </div>
                                            <small style="color: var(--gray);">
                                                <?php echo $app['correct_answers']; ?>/<?php echo $app['total_questions']; ?> richtig
                                            </small>
                                        </div>
                                    <?php else: ?>
                                        <div style="text-align: center; color: var(--gray);">
                                            <div>-</div>
                                            <small>Nicht bewertet</small>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $statusColors = [
                                        'pending' => '#f59e0b',
                                        'closed' => '#6b7280', 
                                        'approved' => '#10b981',
                                        'rejected' => '#ef4444'
                                    ];
                                    $statusLabels = [
                                        'pending' => '🟡 Noch offen',
                                        'closed' => '⚫ Geschlossen',
                                        'approved' => '✅ Genehmigt', 
                                        'rejected' => '❌ Abgelehnt'
                                    ];
                                    ?>
                                    <span style="color: <?php echo $statusColors[$app['status']] ?? '#6b7280'; ?>">
                                        <?php echo $statusLabels[$app['status']] ?? $app['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo date('d.m.Y H:i', strtotime($app['created_at'])); ?>
                                </td>
                                <td>
                                    <?php if ($app['reviewed_by_name']): ?>
                                        <?php echo htmlspecialchars($app['reviewed_by_name']); ?>
                                        <br><small style="color: var(--gray);">
                                            <?php echo date('d.m.Y H:i', strtotime($app['reviewed_at'])); ?>
                                        </small>
                                    <?php else: ?>
                                        <span style="color: var(--gray);">Nicht bearbeitet</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 0.25rem; flex-wrap: wrap;">
                                        <button onclick="viewApplicationDetails(<?php echo $app['id']; ?>)" 
                                                class="btn btn-small btn-edit">👁️ Details</button>
                                        
                                        <?php if ($app['status'] === 'pending' && hasPermission('whitelist.update')): ?>
                                            <button onclick="quickApprove(<?php echo $app['id']; ?>)" 
                                                    class="btn btn-small btn-success">✅</button>
                                            <button onclick="quickReject(<?php echo $app['id']; ?>)" 
                                                    class="btn btn-small btn-delete">❌</button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div style="text-align: center; padding: 3rem; color: var(--gray);">
                    <p>📝 Noch keine Whitelist-Bewerbungen vorhanden.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Weitere bestehende Sections... -->
        
    </div>
    
    <!-- Modals -->
    <?php include '../modals/rule-modals.php'; ?>
    <?php include '../modals/news-modals.php'; ?>
    <?php include '../modals/whitelist-modals.php'; ?>
    
    <!-- Activity Details Modal -->
    <div id="activityDetailsModal" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h3 class="modal-title">📊 Aktivitäts-Details</h3>
                <button class="close-modal" onclick="closeModal('activityDetailsModal')">&times;</button>
            </div>
            
            <div class="modal-body">
                <div id="activityDetailsContent">
                    <!-- Content wird per JavaScript gefüllt -->
                </div>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="../assets/js/admin.js"></script>
    <script>
        // Pass PHP data to JavaScript
        window.adminData = {
            csrfToken: '<?php echo generateCSRFToken(); ?>',
            currentPage: '<?php echo $page; ?>',
            username: '<?php echo htmlspecialchars($currentUser['username']); ?>',
            permissions: <?php echo json_encode($userPermissions ?? []); ?>,
            twitchEnabled: <?php echo $twitchEnabled ? 'true' : 'false'; ?>,
            twitchApiStatus: '<?php echo $twitchApiStatus; ?>'
        };
        
        // Twitch-specific functions
        function updateStreamStatus() {
            const btn = document.getElementById('updateStreamBtn');
            if (btn) {
                btn.disabled = true;
                btn.textContent = '🔄 Aktualisiere...';
                
                fetch('ajax/update-stream-status.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message, 'success');
                        if (data.updated) {
                            setTimeout(() => location.reload(), 2000);
                        }
                    } else {
                        showNotification('Fehler: ' + (data.error || 'Unbekannter Fehler'), 'error');
                    }
                })
                .catch(error => {
                    console.error('Stream status update error:', error);
                    showNotification('Netzwerkfehler beim Update', 'error');
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.textContent = '🔄 Status aktualisieren';
                });
            }
        }
        
        // Enhanced application filtering with score support
        function filterApplications() {
            const statusFilter = document.getElementById('statusFilter').value;
            const scoreFilter = document.getElementById('scoreFilter').value;
            const rows = document.querySelectorAll('#applicationsTable tbody tr');
            let visibleCount = 0;
            
            rows.forEach(row => {
                const status = row.getAttribute('data-status');
                const scoreClass = row.getAttribute('data-score');
                
                let showRow = true;
                
                if (statusFilter && status !== statusFilter) {
                    showRow = false;
                }
                
                if (scoreFilter && scoreClass !== scoreFilter) {
                    showRow = false;
                }
                
                if (showRow) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
        }
        
        // Auto-refresh für Dashboard-Statistiken und Live-Streams
        if (window.adminData.currentPage === 'overview' && window.adminData.twitchEnabled) {
            setInterval(function() {
                // Nur aktualisieren wenn Twitch aktiviert und API verbunden
                if (window.adminData.twitchApiStatus === 'connected') {
                    fetch('ajax/update-stream-status.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.updated) {
                            // Seite neu laden um aktualisierte Stream-Daten anzuzeigen
                            location.reload();
                        }
                    })
                    .catch(error => console.log('Auto-refresh error:', error));
                }
            }, 300000); // Alle 5 Minuten
        }
        
        // Existing functions remain unchanged...
        function viewApplicationDetails(id) {
            window.open('view-application.php?id=' + id, '_blank', 'width=900,height=700,scrollbars=yes');
        }
        
        function quickApprove(id) {
            showConfirmDialog(
                '✅ Bewerbung genehmigen',
                'Sind Sie sicher, dass Sie diese Bewerbung genehmigen möchten?',
                () => {
                    updateApplicationStatus(id, 'approved', 'Schnellgenehmigung durch Admin');
                }
            );
        }
        
        function quickReject(id) {
            showConfirmDialog(
                '❌ Bewerbung ablehnen',
                'Sind Sie sicher, dass Sie diese Bewerbung ablehnen möchten?',
                () => {
                    const reason = prompt('Grund für die Ablehnung (optional):');
                    updateApplicationStatus(id, 'rejected', reason || 'Abgelehnt durch Admin');
                }
            );
        }
        
        function updateApplicationStatus(id, status, notes) {
            submitForm('update_application_status', {
                application_id: id,
                status: status,
                notes: notes
            });
        }
        
        // Rule management functions
        function editRule(rule) {
            document.getElementById('edit_rule_id').value = rule.id;
            document.getElementById('edit_rule_title').value = rule.rule_title;
            document.getElementById('edit_rule_content').value = rule.rule_content;
            document.getElementById('edit_rule_order').value = rule.rule_order;
            document.getElementById('edit_is_active').checked = rule.is_active == 1;
            openModal('editRuleModal');
        }

        function deleteRule(id) {
            showConfirmDialog(
                '🗑️ Regel löschen',
                'Sind Sie sicher, dass Sie diese Regel löschen möchten? Diese Aktion kann nicht rückgängig gemacht werden.',
                () => {
                    submitForm('delete_rule', { rule_id: id });
                }
            );
        }

        // News management functions
        function editNews(article) {
            document.getElementById('edit_news_id').value = article.id;
            document.getElementById('edit_news_title').value = article.title;
            document.getElementById('edit_news_content').value = article.content;
            document.getElementById('edit_news_published').checked = article.is_published == 1;
            openModal('editNewsModal');
        }

        function deleteNews(id) {
            showConfirmDialog(
                '🗑️ Artikel löschen',
                'Sind Sie sicher, dass Sie diesen Artikel löschen möchten? Diese Aktion kann nicht rückgängig gemacht werden.',
                () => {
                    submitForm('delete_news', { news_id: id });
                }
            );
        }
        
        // Modal functions
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('active');
                
                // Focus first input
                const firstInput = modal.querySelector('input:not([type="hidden"]):not([disabled]), textarea:not([disabled]), select:not([disabled])');
                if (firstInput) {
                    setTimeout(() => firstInput.focus(), 100);
                }
            }
        }
        
        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('active');
            }
        }
        
        // Universal form submission function
        function submitForm(action, data) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '';
            
            // CSRF Token hinzufügen
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = 'csrf_token';
            csrfInput.value = window.adminData.csrfToken;
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
        
        // Confirm dialog function
        function showConfirmDialog(title, message, onConfirm) {
            if (confirm(`${title}\n\n${message}`)) {
                onConfirm();
            }
        }
        
        // Notification system for real-time updates
        function showNotification(message, type = 'info', duration = 5000) {
            const notification = document.createElement('div');
            notification.className = `flash-message ${type}`;
            notification.textContent = message;
            notification.style.position = 'fixed';
            notification.style.top = '20px';
            notification.style.right = '20px';
            notification.style.zIndex = '10000';
            notification.style.animation = 'slideIn 0.3s ease-out';
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease-in';
                setTimeout(() => {
                    if (document.body.contains(notification)) {
                        document.body.removeChild(notification);
                    }
                }, 300);
            }, duration);
        }
        
        // Modal außerhalb klicken zum Schließen
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                }
            });
        });
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Escape zum Schließen von Modals
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal.active').forEach(modal => {
                    modal.classList.remove('active');
                });
            }
            
            // Ctrl/Cmd + Alt + N für neue News (wenn berechtigt)
            if ((e.ctrlKey || e.metaKey) && e.altKey && e.key === 'n') {
                e.preventDefault();
                if (window.adminData.permissions.includes('news.create')) {
                    openModal('addNewsModal');
                }
            }
            
            // Ctrl/Cmd + Alt + R für neue Regel (wenn berechtigt)
            if ((e.ctrlKey || e.metaKey) && e.altKey && e.key === 'r') {
                e.preventDefault();
                if (window.adminData.permissions.includes('rules.create')) {
                    openModal('addRuleModal');
                }
            }
            
            // Ctrl/Cmd + Alt + S für Twitch Streamer Management
            if ((e.ctrlKey || e.metaKey) && e.altKey && e.key === 's') {
                e.preventDefault();
                if (window.adminData.permissions.includes('settings.update') && window.adminData.twitchEnabled) {
                    window.location.href = 'streamers.php';
                }
            }
        });
        
        // Form validation enhancement
        document.addEventListener('DOMContentLoaded', function() {
            // Enhance all forms with validation
            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    const requiredFields = this.querySelectorAll('[required]');
                    let isValid = true;
                    
                    requiredFields.forEach(field => {
                        if (!field.value.trim()) {
                            isValid = false;
                            field.style.borderColor = 'var(--danger)';
                            
                            // Remove error styling after user starts typing
                            field.addEventListener('input', function() {
                                this.style.borderColor = '';
                            }, { once: true });
                        }
                    });
                    
                    if (!isValid) {
                        e.preventDefault();
                        showNotification('Bitte füllen Sie alle Pflichtfelder aus.', 'error');
                    }
                });
            });
            
            // Auto-save form data for disaster recovery
            const forms = document.querySelectorAll('form:not([data-no-autosave])');
            forms.forEach(form => {
                const formId = form.id || 'form_' + Math.random().toString(36).substr(2, 9);
                
                // Load saved data
                const savedData = localStorage.getItem('form_backup_' + formId);
                if (savedData) {
                    try {
                        const data = JSON.parse(savedData);
                        Object.keys(data).forEach(name => {
                            const field = form.querySelector(`[name="${name}"]`);
                            if (field && field.type !== 'password') {
                                if (field.type === 'checkbox') {
                                    field.checked = data[name];
                                } else {
                                    field.value = data[name];
                                }
                            }
                        });
                    } catch (e) {
                        console.log('Could not restore form data');
                    }
                }
                
                // Save data on input
                form.addEventListener('input', function() {
                    const formData = new FormData(this);
                    const data = {};
                    for (let [key, value] of formData.entries()) {
                        if (key !== 'csrf_token' && key !== 'password') {
                            data[key] = value;
                        }
                    }
                    localStorage.setItem('form_backup_' + formId, JSON.stringify(data));
                });
                
                // Clear backup on successful submit
                form.addEventListener('submit', function() {
                    localStorage.removeItem('form_backup_' + formId);
                });
            });
            
            // Initialize filters
            if (typeof filterApplications === 'function') {
                filterApplications();
            }
            
            // Initialize tooltips for buttons
            document.querySelectorAll('[data-tooltip]').forEach(element => {
                element.addEventListener('mouseenter', function() {
                    this.setAttribute('title', this.getAttribute('data-tooltip'));
                });
            });
        });
        
        // Performance monitoring
        if (window.performance) {
            window.addEventListener('load', function() {
                const loadTime = window.performance.timing.loadEventEnd - window.performance.timing.navigationStart;
                if (loadTime > 3000) {
                    console.warn('Dashboard loaded slowly:', loadTime + 'ms');
                }
            });
        }
        
        // Session timeout warning
        let sessionTimeout = <?php echo SESSION_TIMEOUT; ?> * 1000;
        let warningShown = false;
        
        setTimeout(function() {
            if (!warningShown) {
                warningShown = true;
                if (confirm('Ihre Session läuft in 5 Minuten ab. Möchten Sie angemeldet bleiben?')) {
                    // Reload page to refresh session
                    location.reload();
                } else {
                    window.location.href = 'logout.php';
                }
            }
        }, sessionTimeout - 300000); // 5 Minuten vor Ablauf warnen
        
        // Add slide out animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from {
                    opacity: 0;
                    transform: translateX(100%);
                }
                to {
                    opacity: 1;
                    transform: translateX(0);
                }
            }
            
            @keyframes slideOut {
                from {
                    opacity: 1;
                    transform: translateX(0);
                }
                to {
                    opacity: 0;
                    transform: translateX(100%);
                }
            }
            
            /* Twitch-specific styling */
            .live-highlight {
                border: 2px solid var(--danger) !important;
                box-shadow: 0 0 20px rgba(255, 68, 68, 0.3);
                animation: pulse 2s infinite;
            }
            
            .live-streamer {
                border-left: 4px solid var(--danger) !important;
            }
            
            .offline-streamer {
                opacity: 0.7;
            }
            
            .badge-twitch {
                background: var(--twitch) !important;
                color: white !important;
            }
            
            .nav-badge {
                position: absolute;
                top: -8px;
                right: -8px;
                background: var(--danger);
                color: white;
                border-radius: 50%;
                width: 20px;
                height: 20px;
                font-size: 0.7rem;
                display: flex;
                align-items: center;
                justify-content: center;
                animation: pulse 2s infinite;
            }
            
            .live-badge {
                animation: pulse 1s infinite;
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>