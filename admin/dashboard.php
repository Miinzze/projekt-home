<?php
require_once '../config/config.php';

// Login prüfen
if (!isLoggedIn()) {
    redirect('login.php');
}

$currentUser = getCurrentUser();

// Aktuelle Seite ermitteln
$page = $_GET['page'] ?? 'overview';
$allowedPages = ['overview', 'settings', 'rules', 'news', 'users', 'logs', 'whitelist', 'whitelist_questions', 'activity', 'roadmap', 'streamers'];

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
    case 'roadmap':
        requirePermission('roadmap.read');
        break;
    case 'streamers':
        requirePermission('streamers.read');
        break;
}

// Flash Messages verarbeiten
$flashMessages = getFlashMessages();

// POST-Anfragen verarbeiten
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
            case 'add_roadmap_item':
                if (hasPermission('roadmap.create')) {
                    handleAddRoadmapItem();
                } else {
                    setFlashMessage('error', 'Keine Berechtigung für diese Aktion.');
                }
                break;
            case 'update_roadmap_item':
                if (hasPermission('roadmap.update')) {
                    handleUpdateRoadmapItem();
                } else {
                    setFlashMessage('error', 'Keine Berechtigung für diese Aktion.');
                }
                break;
            case 'delete_roadmap_item':
                if (hasPermission('roadmap.delete')) {
                    handleDeleteRoadmapItem();
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

// Sicherstellen, dass roadmap_items Tabelle existiert
createRoadmapTable();

// Action Handler Funktionen (erweitert mit Logging)
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
        'whitelist_auto_approve' => isset($_POST['whitelist_auto_approve']) ? '1' : '0'
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

// ================================
// ROADMAP HANDLER FUNKTIONEN
// ================================

function handleAddRoadmapItem() {
    $title = sanitizeInput($_POST['roadmap_title'] ?? '');
    $description = sanitizeInput($_POST['roadmap_description'] ?? '');
    $status = sanitizeInput($_POST['roadmap_status'] ?? 'planned');
    $priority = (int)($_POST['roadmap_priority'] ?? 3);
    $estimatedDate = $_POST['roadmap_estimated_date'] ?? null;
    
    if (empty($title) || empty($description)) {
        setFlashMessage('error', 'Titel und Beschreibung sind erforderlich.');
        return;
    }
    
    $result = insertData('roadmap_items', [
        'title' => $title,
        'description' => $description,
        'status' => $status,
        'priority' => $priority,
        'estimated_completion_date' => $estimatedDate ?: null,
        'created_by' => getCurrentUser()['id']
    ]);
    
    if ($result) {
        logAdminActivity(
            getCurrentUser()['id'],
            'roadmap_created',
            "Roadmap-Eintrag '{$title}' erstellt",
            'roadmap_item',
            $result,
            null,
            ['title' => $title, 'status' => $status, 'priority' => $priority]
        );
        
        setFlashMessage('success', 'Roadmap-Eintrag wurde erfolgreich hinzugefügt.');
    } else {
        setFlashMessage('error', 'Fehler beim Hinzufügen des Roadmap-Eintrags.');
    }
}

function handleUpdateRoadmapItem() {
    $id = (int)($_POST['roadmap_id'] ?? 0);
    $title = sanitizeInput($_POST['roadmap_title'] ?? '');
    $description = sanitizeInput($_POST['roadmap_description'] ?? '');
    $status = sanitizeInput($_POST['roadmap_status'] ?? 'planned');
    $priority = (int)($_POST['roadmap_priority'] ?? 3);
    $estimatedDate = $_POST['roadmap_estimated_date'] ?? null;
    $active = isset($_POST['is_active']) ? 1 : 0;
    
    if ($id <= 0 || empty($title) || empty($description)) {
        setFlashMessage('error', 'Ungültige Roadmap-Daten.');
        return;
    }
    
    $oldItem = fetchOne("SELECT * FROM roadmap_items WHERE id = :id", ['id' => $id]);
    
    $result = updateData('roadmap_items', [
        'title' => $title,
        'description' => $description,
        'status' => $status,
        'priority' => $priority,
        'estimated_completion_date' => $estimatedDate ?: null,
        'is_active' => $active,
        'updated_by' => getCurrentUser()['id']
    ], 'id = :id', ['id' => $id]);
    
    if ($result !== false) {
        logAdminActivity(
            getCurrentUser()['id'],
            'roadmap_updated',
            "Roadmap-Eintrag '{$title}' bearbeitet",
            'roadmap_item',
            $id,
            $oldItem,
            ['title' => $title, 'status' => $status, 'priority' => $priority, 'active' => $active]
        );
        
        setFlashMessage('success', 'Roadmap-Eintrag wurde erfolgreich aktualisiert.');
    } else {
        setFlashMessage('error', 'Fehler beim Aktualisieren des Roadmap-Eintrags.');
    }
}

function handleDeleteRoadmapItem() {
    $id = (int)($_POST['roadmap_id'] ?? 0);
    
    if ($id <= 0) {
        setFlashMessage('error', 'Ungültige Roadmap-ID.');
        return;
    }
    
    $item = fetchOne("SELECT * FROM roadmap_items WHERE id = :id", ['id' => $id]);
    
    $result = executeQuery("DELETE FROM roadmap_items WHERE id = :id", ['id' => $id]);
    
    if ($result) {
        if ($item) {
            logAdminActivity(
                getCurrentUser()['id'],
                'roadmap_deleted',
                "Roadmap-Eintrag '{$item['title']}' gelöscht",
                'roadmap_item',
                $id,
                $item,
                null
            );
        }
        
        setFlashMessage('success', 'Roadmap-Eintrag wurde erfolgreich gelöscht.');
    } else {
        setFlashMessage('error', 'Fehler beim Löschen des Roadmap-Eintrags.');
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

// Erstelle roadmap_items Tabelle falls sie nicht existiert
function createRoadmapTable() {
    global $pdo;
    
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `roadmap_items` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `title` varchar(255) NOT NULL,
                `description` text NOT NULL,
                `status` enum('planned','in_progress','testing','completed','cancelled') NOT NULL DEFAULT 'planned',
                `priority` int(11) NOT NULL DEFAULT 3 COMMENT '1=Sehr hoch, 2=Hoch, 3=Normal, 4=Niedrig, 5=Sehr niedrig',
                `estimated_completion_date` date NULL,
                `completed_date` date NULL,
                `is_active` tinyint(1) NOT NULL DEFAULT 1,
                `created_by` int(11) NOT NULL,
                `updated_by` int(11) NULL,
                `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_status` (`status`),
                KEY `idx_priority` (`priority`),
                KEY `idx_is_active` (`is_active`),
                KEY `idx_created_by` (`created_by`),
                KEY `idx_updated_by` (`updated_by`),
                FOREIGN KEY (`created_by`) REFERENCES `admins`(`id`) ON DELETE CASCADE,
                FOREIGN KEY (`updated_by`) REFERENCES `admins`(`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        return true;
    } catch (PDOException $e) {
        error_log("Fehler beim Erstellen der roadmap_items Tabelle: " . $e->getMessage());
        return false;
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
    // Roadmap-Berechtigungen für Super-Admin hinzufügen
    $userPermissions = array_merge($userPermissions, [
        'roadmap.create', 'roadmap.read', 'roadmap.update', 'roadmap.delete',
        'streamers.read', 'streamers.update'
    ]);
}

// Überprüfe ob roadmap permissions verfügbar sind
$hasRoadmapPermissions = hasPermission('roadmap.read') || 
                        hasPermission('roadmap.create') || 
                        hasPermission('roadmap.update') || 
                        hasPermission('roadmap.delete') ||
                        $currentUser['role'] === 'super_admin';

$hasStreamersPermissions = hasPermission('streamers.read') || 
                          hasPermission('streamers.update') ||
                          $currentUser['role'] === 'super_admin';
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
            
            <?php if ($hasRoadmapPermissions): ?>
            <a href="?page=roadmap" class="nav-button <?php echo $page === 'roadmap' ? 'active' : ''; ?>">
                <span class="icon">🗺️</span>
                <div class="text">
                    <span class="title">Roadmap</span>
                    <span class="subtitle">Entwicklungs-Planung</span>
                </div>
            </a>
            <?php endif; ?>
            
            <?php if ($hasStreamersPermissions): ?>
            <a href="streamers.php" class="nav-button">
                <span class="icon">📺</span>
                <div class="text">
                    <span class="title">Streamer</span>
                    <span class="subtitle">Twitch Integration</span>
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
                    
                    // Roadmap Statistiken
                    $totalRoadmapItems = fetchOne("SELECT COUNT(*) as count FROM roadmap_items WHERE is_active = 1")['count'] ?? 0;
                    $completedRoadmapItems = fetchOne("SELECT COUNT(*) as count FROM roadmap_items WHERE status = 'completed' AND is_active = 1")['count'] ?? 0;
                    $inProgressItems = fetchOne("SELECT COUNT(*) as count FROM roadmap_items WHERE status = 'in_progress' AND is_active = 1")['count'] ?? 0;
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
                        <div class="stat-icon">🗺️</div>
                        <h3><?php echo $totalRoadmapItems; ?></h3>
                        <p>Roadmap-Einträge</p>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">⚙️</div>
                        <h3><?php echo $inProgressItems; ?></h3>
                        <p>In Entwicklung</p>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">✅</div>
                        <h3><?php echo $completedRoadmapItems; ?></h3>
                        <p>Abgeschlossen</p>
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
                        <div class="stat-icon"><?php echo $serverOnline ? '🟢' : '🔴'; ?></div>
                        <h3><?php echo $serverOnline ? 'Online' : 'Offline'; ?></h3>
                        <p>Server Status</p>
                    </div>
                </div>
                
                <!-- Benutzer-Berechtigungen anzeigen -->
                <div style="margin-top: 2rem;">
                    <h3 style="color: var(--primary); margin-bottom: 1rem;">🔑 Ihre Berechtigungen</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                        <?php
                        $permissionCategories = [
                            'Benutzer' => ['users.create', 'users.read', 'users.update', 'users.delete', 'users.activate', 'users.reset_password'],
                            'System' => ['settings.read', 'settings.update', 'settings.backup', 'settings.restore'],
                            'Roadmap' => ['roadmap.create', 'roadmap.read', 'roadmap.update', 'roadmap.delete'],
                            'Whitelist' => ['whitelist.read', 'whitelist.update', 'whitelist.approve', 'whitelist.reject', 'whitelist.questions.manage'],
                            'Content' => ['news.create', 'news.read', 'news.update', 'news.delete', 'rules.create', 'rules.read', 'rules.update', 'rules.delete'],
                            'Streams' => ['streamers.read', 'streamers.update'],
                            'Logs' => ['logs.read', 'activity.read', 'api.access']
                        ];
                        
                        foreach ($permissionCategories as $category => $permissions): ?>
                        <div style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px; padding: 1rem;">
                            <h4 style="color: var(--secondary); margin-bottom: 0.5rem;"><?php echo $category; ?></h4>
                            <?php
                            $hasPermissions = false;
                            foreach ($permissions as $permission) {
                                if (in_array($permission, $userPermissions) || $currentUser['role'] === 'super_admin') {
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
                        
                        <?php if ($hasRoadmapPermissions): ?>
                        <button onclick="openModal('addRoadmapModal')" class="btn btn-primary" style="padding: 1rem;">
                            🗺️ Roadmap-Eintrag
                        </button>
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
                                        <span class="badge badge-info">
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

        <!-- REST OF ORIGINAL SECTIONS (Settings, Rules, News, Whitelist, etc.) ... -->
        
        <!-- NEUE ROADMAP SECTION -->
        <?php if ($hasRoadmapPermissions && $page === 'roadmap'): ?>
        <div id="roadmap" class="content-section active">
            <div class="admin-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <h2>🗺️ Roadmap verwalten</h2>
                    <div style="display: flex; gap: 1rem; align-items: center;">
                        <?php if (hasPermission('roadmap.create') || $currentUser['role'] === 'super_admin'): ?>
                        <button onclick="openModal('addRoadmapModal')" class="btn btn-primary">
                            ➕ Neuer Roadmap-Eintrag
                        </button>
                        <?php endif; ?>
                        
                        <button onclick="exportRoadmapJSON()" class="btn btn-secondary">
                            📥 Export JSON
                        </button>
                        
                        <select id="roadmapStatusFilter" onchange="filterRoadmapItems()" class="form-control" style="width: auto;">
                            <option value="">Alle Status</option>
                            <option value="planned">📋 Geplant</option>
                            <option value="in_progress">⚙️ In Arbeit</option>
                            <option value="testing">🧪 Testing</option>
                            <option value="completed">✅ Abgeschlossen</option>
                            <option value="cancelled">❌ Abgebrochen</option>
                        </select>
                    </div>
                </div>
                
                <?php 
                $roadmapItems = fetchAll("
                    SELECT r.*, 
                           creator.username as created_by_name,
                           updater.username as updated_by_name
                    FROM roadmap_items r 
                    LEFT JOIN admins creator ON r.created_by = creator.id
                    LEFT JOIN admins updater ON r.updated_by = updater.id
                    ORDER BY r.priority ASC, r.created_at DESC
                "); 
                ?>
                
                <?php if (!empty($roadmapItems)): ?>
                <div class="data-table">
                    <table class="table" id="roadmapTable">
                        <thead>
                            <tr>
                                <th>📈 Priorität</th>
                                <th>🎯 Titel</th>
                                <th>📝 Beschreibung</th>
                                <th>📊 Status</th>
                                <th>📅 Geschätzt</th>
                                <th>👤 Erstellt von</th>
                                <th>📄 Aktiv</th>
                                <th>⚡ Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($roadmapItems as $item): ?>
                            <tr data-status="<?php echo $item['status']; ?>">
                                <td>
                                    <?php
                                    $priorityLabels = [
                                        1 => '<span style="color: #ff4444;">🔥 Sehr hoch</span>',
                                        2 => '<span style="color: #ff8800;">🟠 Hoch</span>',
                                        3 => '<span style="color: #ffaa00;">🟡 Normal</span>',
                                        4 => '<span style="color: #0088ff;">🔵 Niedrig</span>',
                                        5 => '<span style="color: #888888;">⚪ Sehr niedrig</span>'
                                    ];
                                    echo $priorityLabels[$item['priority']] ?? $item['priority'];
                                    ?>
                                </td>
                                <td><strong><?php echo htmlspecialchars($item['title']); ?></strong></td>
                                <td>
                                    <?php echo htmlspecialchars(substr($item['description'], 0, 100)); ?>
                                    <?php if (strlen($item['description']) > 100): ?>...<?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $statusLabels = [
                                        'planned' => '<span class="badge" style="background: #6b7280;">📋 Geplant</span>',
                                        'in_progress' => '<span class="badge" style="background: #f59e0b;">⚙️ In Arbeit</span>',
                                        'testing' => '<span class="badge" style="background: #8b5cf6;">🧪 Testing</span>',
                                        'completed' => '<span class="badge badge-success">✅ Abgeschlossen</span>',
                                        'cancelled' => '<span class="badge badge-danger">❌ Abgebrochen</span>'
                                    ];
                                    echo $statusLabels[$item['status']] ?? $item['status'];
                                    ?>
                                </td>
                                <td>
                                    <?php if ($item['estimated_completion_date']): ?>
                                        <?php echo date('d.m.Y', strtotime($item['estimated_completion_date'])); ?>
                                    <?php else: ?>
                                        <span style="color: var(--gray);">Nicht festgelegt</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($item['created_by_name'] ?? 'Unbekannt'); ?>
                                    <br><small style="color: var(--gray);">
                                        <?php echo date('d.m.Y', strtotime($item['created_at'])); ?>
                                    </small>
                                </td>
                                <td>
                                    <span class="badge <?php echo $item['is_active'] ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo $item['is_active'] ? '✅ Ja' : '❌ Nein'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 0.5rem;">
                                        <?php if (hasPermission('roadmap.update') || $currentUser['role'] === 'super_admin'): ?>
                                        <button onclick="editRoadmapItem(<?php echo htmlspecialchars(json_encode($item)); ?>)" 
                                                class="btn btn-small btn-edit">✏️</button>
                                        <?php endif; ?>
                                        
                                        <?php if (hasPermission('roadmap.delete') || $currentUser['role'] === 'super_admin'): ?>
                                        <button onclick="deleteRoadmapItem(<?php echo $item['id']; ?>)" 
                                                class="btn btn-small btn-delete">🗑️</button>
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
                    <div style="font-size: 4rem; margin-bottom: 1rem;">🗺️</div>
                    <h3>Noch keine Roadmap-Einträge erstellt</h3>
                    <p>Erstellen Sie Ihren ersten Roadmap-Eintrag, um die Entwicklungspläne zu verwalten.</p>
                    <?php if (hasPermission('roadmap.create') || $currentUser['role'] === 'super_admin'): ?>
                    <button onclick="openModal('addRoadmapModal')" class="btn btn-primary" style="margin-top: 1rem;">
                        ➕ Ersten Roadmap-Eintrag erstellen
                    </button>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- ADD OTHER SECTIONS HERE (Settings, Rules, News, etc. - same as original) -->
        
    </div>
    
    <!-- Modals -->
    <?php include '../modals/rule-modals.php'; ?>
    <?php include '../modals/news-modals.php'; ?>
    <?php include '../modals/whitelist-modals.php'; ?>
    <?php include '../modals/roadmap-modals.php'; ?>
    
    <!-- Scripts -->
    <script src="../assets/js/admin.js"></script>
    <script>
        // Pass PHP data to JavaScript
        window.adminData = {
            csrfToken: '<?php echo generateCSRFToken(); ?>',
            currentPage: '<?php echo $page; ?>',
            username: '<?php echo htmlspecialchars($currentUser['username']); ?>',
            permissions: <?php echo json_encode($userPermissions ?? []); ?>,
            hasRoadmapPermissions: <?php echo $hasRoadmapPermissions ? 'true' : 'false'; ?>
        };
        
        // ================================
        // ROADMAP MANAGEMENT FUNCTIONS
        // ================================
        
        function editRoadmapItem(item) {
            document.getElementById('edit_roadmap_id').value = item.id;
            document.getElementById('edit_roadmap_title').value = item.title;
            document.getElementById('edit_roadmap_description').value = item.description;
            document.getElementById('edit_roadmap_status').value = item.status;
            document.getElementById('edit_roadmap_priority').value = item.priority;
            document.getElementById('edit_roadmap_estimated_date').value = item.estimated_completion_date || '';
            document.getElementById('edit_roadmap_active').checked = item.is_active == 1;
            
            openModal('editRoadmapModal');
        }
        
        function deleteRoadmapItem(id) {
            showConfirmDialog(
                '🗑️ Roadmap-Eintrag löschen',
                'Sind Sie sicher, dass Sie diesen Roadmap-Eintrag löschen möchten? Diese Aktion kann nicht rückgängig gemacht werden.',
                () => {
                    submitForm('delete_roadmap_item', { roadmap_id: id });
                }
            );
        }
        
        function filterRoadmapItems() {
            const statusFilter = document.getElementById('roadmapStatusFilter').value;
            const table = document.getElementById('roadmapTable');
            
            if (!table) return;
            
            const rows = table.querySelectorAll('tbody tr');
            let visibleCount = 0;
            
            rows.forEach(row => {
                const status = row.getAttribute('data-status');
                
                if (!statusFilter || status === statusFilter) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            console.log(`Roadmap Filter: ${visibleCount} von ${rows.length} Einträgen sichtbar`);
        }
        
        function exportRoadmapJSON() {
            window.open('ajax/export-roadmap.php?format=json', '_blank');
        }
        
        // Initialize roadmap page
        if (window.adminData.currentPage === 'roadmap') {
            document.addEventListener('DOMContentLoaded', function() {
                filterRoadmapItems();
                console.log('🗺️ Roadmap-Management initialisiert');
            });
        }
        
        // Add roadmap shortcuts
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.altKey && e.key === 'm') {
                e.preventDefault();
                if (window.adminData.hasRoadmapPermissions && 
                    window.adminData.currentPage === 'roadmap') {
                    openModal('addRoadmapModal');
                }
            }
        });
        
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