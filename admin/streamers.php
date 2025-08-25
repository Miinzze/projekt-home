<?php
require_once '../config/config.php';
require_once '../config/twitch_api.php';

// Login prüfen
if (!isLoggedIn()) {
    redirect('login.php');
}

// Berechtigung prüfen
requirePermission('settings.update'); // Streamers-Management benötigt Settings-Berechtigung

$currentUser = getCurrentUser();
$flashMessages = getFlashMessages();

// POST-Anfragen verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    if (!validateCSRFToken($csrfToken)) {
        setFlashMessage('error', 'Ungültiger Sicherheitstoken.');
        redirect('streamers.php');
    }
    
    switch ($action) {
        case 'add_streamer':
            handleAddStreamer();
            break;
        case 'update_streamer':
            handleUpdateStreamer();
            break;
        case 'delete_streamer':
            handleDeleteStreamer();
            break;
        case 'toggle_streamer':
            handleToggleStreamer();
            break;
        case 'update_stream_status':
            handleUpdateStreamStatus();
            break;
        case 'update_twitch_settings':
            handleUpdateTwitchSettings();
            break;
    }
    
    redirect('streamers.php');
}

// Action Handler Funktionen
function handleAddStreamer() {
    $streamerName = trim($_POST['streamer_name'] ?? '');
    $displayName = trim($_POST['display_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $priorityOrder = (int)($_POST['priority_order'] ?? 0);
    
    if (empty($streamerName)) {
        setFlashMessage('error', 'Twitch-Benutzername ist erforderlich.');
        return;
    }
    
    // Validierung über Twitch API
    $result = addStreamer($streamerName, $displayName, $description, $priorityOrder);
    
    if ($result['success']) {
        logAdminActivity(
            getCurrentUser()['id'],
            'streamer_added',
            "Twitch Streamer '{$streamerName}' hinzugefügt",
            'streamer',
            $result['streamer_id'],
            null,
            ['streamer_name' => $streamerName, 'display_name' => $displayName]
        );
        
        setFlashMessage('success', $result['message']);
    } else {
        setFlashMessage('error', $result['message']);
    }
}

function handleUpdateStreamer() {
    $id = (int)($_POST['streamer_id'] ?? 0);
    $displayName = trim($_POST['display_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $priorityOrder = (int)($_POST['priority_order'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    if ($id <= 0) {
        setFlashMessage('error', 'Ungültige Streamer-ID.');
        return;
    }
    
    $oldStreamer = getStreamerById($id);
    
    $updateData = [
        'display_name' => $displayName,
        'description' => $description,
        'priority_order' => $priorityOrder,
        'is_active' => $isActive
    ];
    
    $result = updateStreamer($id, $updateData);
    
    if ($result['success']) {
        logAdminActivity(
            getCurrentUser()['id'],
            'streamer_updated',
            "Twitch Streamer '{$displayName}' aktualisiert",
            'streamer',
            $id,
            $oldStreamer,
            $updateData
        );
        
        setFlashMessage('success', $result['message']);
    } else {
        setFlashMessage('error', $result['message']);
    }
}

function handleDeleteStreamer() {
    $id = (int)($_POST['streamer_id'] ?? 0);
    
    if ($id <= 0) {
        setFlashMessage('error', 'Ungültige Streamer-ID.');
        return;
    }
    
    $streamer = getStreamerById($id);
    $result = deleteStreamer($id);
    
    if ($result['success']) {
        if ($streamer) {
            logAdminActivity(
                getCurrentUser()['id'],
                'streamer_deleted',
                "Twitch Streamer '{$streamer['display_name']}' gelöscht",
                'streamer',
                $id,
                $streamer,
                null
            );
        }
        
        setFlashMessage('success', $result['message']);
    } else {
        setFlashMessage('error', $result['message']);
    }
}

function handleToggleStreamer() {
    $id = (int)($_POST['streamer_id'] ?? 0);
    
    if ($id <= 0) {
        setFlashMessage('error', 'Ungültige Streamer-ID.');
        return;
    }
    
    $streamer = getStreamerById($id);
    if (!$streamer) {
        setFlashMessage('error', 'Streamer nicht gefunden.');
        return;
    }
    
    $newStatus = $streamer['is_active'] ? 0 : 1;
    $result = updateStreamer($id, ['is_active' => $newStatus]);
    
    if ($result['success']) {
        logAdminActivity(
            getCurrentUser()['id'],
            'streamer_toggled',
            "Twitch Streamer '{$streamer['display_name']}' " . ($newStatus ? 'aktiviert' : 'deaktiviert'),
            'streamer',
            $id,
            ['is_active' => $streamer['is_active']],
            ['is_active' => $newStatus]
        );
        
        setFlashMessage('success', 'Streamer-Status wurde geändert.');
    } else {
        setFlashMessage('error', $result['message']);
    }
}

function handleUpdateStreamStatus() {
    $twitchAPI = getTwitchAPI();
    
    if (!$twitchAPI) {
        setFlashMessage('error', 'Twitch API nicht konfiguriert.');
        return;
    }
    
    $result = $twitchAPI->updateAllStreamersStatus();
    
    if ($result['success']) {
        logAdminActivity(
            getCurrentUser()['id'],
            'stream_status_updated',
            "Stream-Status aktualisiert: {$result['live_count']} von {$result['total_count']} Streamern live",
            'system',
            null,
            null,
            $result
        );
        
        setFlashMessage('success', $result['message']);
    } else {
        setFlashMessage('error', 'Fehler beim Aktualisieren: ' . ($result['error'] ?? 'Unbekannter Fehler'));
    }
}

function handleUpdateTwitchSettings() {
    $settings = [
        'twitch_client_id' => sanitizeInput($_POST['twitch_client_id'] ?? ''),
        'twitch_client_secret' => sanitizeInput($_POST['twitch_client_secret'] ?? ''),
        'twitch_display_enabled' => isset($_POST['twitch_display_enabled']) ? '1' : '0',
        'twitch_max_display' => (int)($_POST['twitch_max_display'] ?? 3),
        'twitch_update_interval' => (int)($_POST['twitch_update_interval'] ?? 300),
        'twitch_auto_update' => isset($_POST['twitch_auto_update']) ? '1' : '0',
        'twitch_show_offline_message' => isset($_POST['twitch_show_offline_message']) ? '1' : '0'
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
    
    // Access Token zurücksetzen wenn Client ID oder Secret geändert wurde
    if (isset($changedSettings['twitch_client_id']) || isset($changedSettings['twitch_client_secret'])) {
        setServerSetting('twitch_access_token', '');
        setServerSetting('twitch_token_expiry', '0');
    }
    
    if ($success && !empty($changedSettings)) {
        logAdminActivity(
            getCurrentUser()['id'],
            'twitch_settings_updated',
            'Twitch-Einstellungen aktualisiert',
            'settings',
            null,
            null,
            $changedSettings
        );
        
        setFlashMessage('success', 'Twitch-Einstellungen wurden erfolgreich aktualisiert.');
    } elseif ($success) {
        setFlashMessage('info', 'Keine Änderungen an den Twitch-Einstellungen vorgenommen.');
    } else {
        setFlashMessage('error', 'Fehler beim Aktualisieren der Twitch-Einstellungen.');
    }
}

// Daten laden
$streamers = getAllStreamers();
$liveStreamers = getLiveStreamers();
$twitchSettings = [
    'client_id' => getServerSetting('twitch_client_id', ''),
    'client_secret' => getServerSetting('twitch_client_secret', ''),
    'display_enabled' => getServerSetting('twitch_display_enabled', '1'),
    'max_display' => getServerSetting('twitch_max_display', '3'),
    'update_interval' => getServerSetting('twitch_update_interval', '300'),
    'auto_update' => getServerSetting('twitch_auto_update', '1'),
    'show_offline_message' => getServerSetting('twitch_show_offline_message', '1')
];

// API-Status prüfen
$apiStatus = null;
$lastUpdate = null;
$twitchAPI = getTwitchAPI();
if ($twitchAPI) {
    try {
        $apiStatus = 'connected';
        $lastUpdate = getServerSetting('twitch_last_update', null);
    } catch (Exception $e) {
        $apiStatus = 'error';
        $apiError = $e->getMessage();
    }
} else {
    $apiStatus = 'not_configured';
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Twitch Streamer Management - <?php echo SITE_NAME; ?></title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&display=swap" rel="stylesheet">
    
    <!-- Styles -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    
    <!-- Meta Tags -->
    <meta name="robots" content="noindex,nofollow">
</head>
<body>
    <!-- Background Effects -->
    <div class="bg-video"></div>
    <div class="bg-overlay"></div>
    
    <!-- Dashboard Header -->
    <header class="dashboard-header">
        <div class="dashboard-nav">
            <div class="dashboard-title">
                <a href="dashboard.php" style="color: inherit; text-decoration: none;">🧟 <?php echo SITE_NAME; ?> Control Panel</a>
            </div>
            <div class="user-info">
                <span>👋 <?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?></span>
                <a href="logout.php" class="logout-btn">🚪 Abmelden</a>
            </div>
        </div>
    </header>
    
    <!-- Main Content Container -->
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
        
        <!-- Navigation Breadcrumb -->
        <div class="breadcrumb" style="margin-bottom: 2rem;">
            <a href="dashboard.php" style="color: var(--text-secondary); text-decoration: none;">📊 Dashboard</a>
            <span style="color: var(--gray); margin: 0 0.5rem;">›</span>
            <span style="color: var(--primary);">📺 Twitch Streamers</span>
        </div>
        
        <!-- API Status Overview -->
        <div class="admin-card" style="margin-bottom: 2rem;">
            <h2 style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.5rem;">
                📺 Twitch API Status
                <?php if ($apiStatus === 'connected'): ?>
                    <span class="badge badge-success">✅ Verbunden</span>
                <?php elseif ($apiStatus === 'error'): ?>
                    <span class="badge badge-danger">❌ Fehler</span>
                <?php else: ?>
                    <span class="badge badge-warning">⚠️ Nicht konfiguriert</span>
                <?php endif; ?>
            </h2>
            
            <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <h3><?php echo count($streamers); ?></h3>
                    <p>Konfigurierte Streamer</p>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">🔴</div>
                    <h3><?php echo count($liveStreamers); ?></h3>
                    <p>Aktuell Live</p>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">⚙️</div>
                    <h3><?php echo $twitchSettings['display_enabled'] ? 'An' : 'Aus'; ?></h3>
                    <p>Stream-Anzeige</p>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">⏱️</div>
                    <h3><?php echo $lastUpdate ? date('H:i', strtotime($lastUpdate)) : '-'; ?></h3>
                    <p>Letztes Update</p>
                </div>
            </div>
            
            <?php if ($apiStatus === 'error'): ?>
            <div class="alert alert-danger" style="margin-top: 1rem;">
                <strong>❌ API-Fehler:</strong> <?php echo htmlspecialchars($apiError ?? 'Unbekannter Fehler'); ?>
            </div>
            <?php elseif ($apiStatus === 'not_configured'): ?>
            <div class="alert alert-warning" style="margin-top: 1rem;">
                <strong>⚠️ Hinweis:</strong> Die Twitch API ist noch nicht konfiguriert. Bitte geben Sie die API-Zugangsdaten ein.
            </div>
            <?php endif; ?>
            
            <div style="display: flex; gap: 1rem; margin-top: 1.5rem; flex-wrap: wrap;">
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="update_stream_status">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <button type="submit" class="btn btn-primary" <?php echo $apiStatus !== 'connected' ? 'disabled' : ''; ?>>
                        🔄 Stream-Status aktualisieren
                    </button>
                </form>
                
                <button onclick="openTwitchSettingsModal()" class="btn btn-secondary">
                    ⚙️ API-Einstellungen
                </button>
                
                <a href="../index.php#streams" target="_blank" class="btn btn-secondary">
                    🌐 Live-Vorschau
                </a>
            </div>
        </div>
        
        <!-- Streamer Management -->
        <div class="admin-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
                <h2 style="margin: 0;">📊 Streamer-Verwaltung</h2>
                <button onclick="openAddStreamerModal()" class="btn btn-primary">
                    ➕ Neuen Streamer hinzufügen
                </button>
            </div>
            
            <?php if (!empty($streamers)): ?>
            <div class="data-table">
                <table class="table">
                    <thead>
                        <tr>
                            <th>📈 Reihenfolge</th>
                            <th>👤 Streamer</th>
                            <th>📺 Twitch Name</th>
                            <th>🔴 Status</th>
                            <th>👥 Zuschauer</th>
                            <th>🎮 Spiel</th>
                            <th>⏰ Letzter Check</th>
                            <th>📄 Aktiv</th>
                            <th>⚡ Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($streamers as $streamer): ?>
                        <tr>
                            <td>
                                <span class="badge badge-secondary"><?php echo $streamer['priority_order']; ?></span>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <?php if ($streamer['profile_image_url']): ?>
                                        <img src="<?php echo htmlspecialchars($streamer['profile_image_url']); ?>" 
                                             style="width: 32px; height: 32px; border-radius: 50%; border: 2px solid #9146ff;" 
                                             alt="Avatar">
                                    <?php else: ?>
                                        <div style="width: 32px; height: 32px; border-radius: 50%; background: #9146ff; display: flex; align-items: center; justify-content: center; color: white; font-size: 0.8rem; font-weight: bold;">
                                            <?php echo strtoupper(substr($streamer['display_name'], 0, 2)); ?>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <strong><?php echo htmlspecialchars($streamer['display_name']); ?></strong>
                                        <?php if ($streamer['description']): ?>
                                            <br><small style="color: var(--gray);">
                                                <?php echo htmlspecialchars(substr($streamer['description'], 0, 30)); ?>
                                                <?php echo strlen($streamer['description']) > 30 ? '...' : ''; ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <a href="https://twitch.tv/<?php echo htmlspecialchars($streamer['streamer_name']); ?>" 
                                   target="_blank" 
                                   style="color: #9146ff; text-decoration: none;">
                                    <?php echo htmlspecialchars($streamer['streamer_name']); ?>
                                </a>
                            </td>
                            <td>
                                <?php if ($streamer['is_currently_live']): ?>
                                    <span class="badge badge-danger">🔴 LIVE</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">⚫ Offline</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($streamer['is_currently_live'] && $streamer['viewer_count'] > 0): ?>
                                    <strong style="color: var(--success);">
                                        <?php echo number_format($streamer['viewer_count']); ?>
                                    </strong>
                                <?php else: ?>
                                    <span style="color: var(--gray);">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($streamer['last_stream_game']): ?>
                                    <span style="font-size: 0.9rem;">
                                        <?php echo htmlspecialchars(substr($streamer['last_stream_game'], 0, 20)); ?>
                                        <?php echo strlen($streamer['last_stream_game']) > 20 ? '...' : ''; ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: var(--gray);">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($streamer['last_live_check']): ?>
                                    <?php echo date('d.m. H:i', strtotime($streamer['last_live_check'])); ?>
                                <?php else: ?>
                                    <span style="color: var(--gray);">Nie</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="toggle_streamer">
                                    <input type="hidden" name="streamer_id" value="<?php echo $streamer['id']; ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                    <button type="submit" class="badge <?php echo $streamer['is_active'] ? 'badge-success' : 'badge-danger'; ?>" 
                                            style="border: none; cursor: pointer;">
                                        <?php echo $streamer['is_active'] ? '✅ Aktiv' : '❌ Inaktiv'; ?>
                                    </button>
                                </form>
                            </td>
                            <td>
                                <div style="display: flex; gap: 0.25rem; flex-wrap: wrap;">
                                    <button onclick="editStreamer(<?php echo htmlspecialchars(json_encode($streamer)); ?>)" 
                                            class="btn btn-small btn-edit">✏️ Bearbeiten</button>
                                    <button onclick="deleteStreamer(<?php echo $streamer['id']; ?>)" 
                                            class="btn btn-small btn-delete">🗑️ Löschen</button>
                                    <?php if ($streamer['is_currently_live']): ?>
                                        <a href="https://twitch.tv/<?php echo htmlspecialchars($streamer['streamer_name']); ?>" 
                                           target="_blank" 
                                           class="btn btn-small btn-success">👁️ Live</a>
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
                <div style="font-size: 3rem; margin-bottom: 1rem;">📺</div>
                <h3>Noch keine Streamer konfiguriert</h3>
                <p>Fügen Sie Ihren ersten Twitch-Streamer hinzu, um loszulegen.</p>
                <button onclick="openModal('addStreamerModal')" class="btn btn-primary" style="margin-top: 1rem;">
                    ➕ Ersten Streamer hinzufügen
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Add Streamer Modal -->
    <div id="addStreamerModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">➕ Neuen Streamer hinzufügen</h3>
                <button class="close-modal" onclick="closeModal('addStreamerModal')">&times;</button>
            </div>
            
            <form method="POST" class="modal-body">
                <input type="hidden" name="action" value="add_streamer">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="form-group">
                    <label for="streamer_name">📺 Twitch Benutzername *</label>
                    <input type="text" id="streamer_name" name="streamer_name" class="form-control" required
                           placeholder="z.B. beispielstreamer" pattern="[a-zA-Z0-9_]+"
                           title="Nur Buchstaben, Zahlen und Unterstriche erlaubt">
                    <small style="color: var(--gray); margin-top: 0.5rem; display: block;">
                        Der exakte Twitch-Benutzername (ohne @-Zeichen)
                    </small>
                </div>
                
                <div class="form-group">
                    <label for="display_name">🏷️ Anzeigename</label>
                    <input type="text" id="display_name" name="display_name" class="form-control"
                           placeholder="Wird automatisch von Twitch geholt, falls leer">
                    <small style="color: var(--gray); margin-top: 0.5rem; display: block;">
                        Falls leer, wird der offizielle Twitch-Anzeigename verwendet
                    </small>
                </div>
                
                <div class="form-group">
                    <label for="description">📝 Beschreibung</label>
                    <textarea id="description" name="description" class="form-control" rows="3"
                              placeholder="Optionale Beschreibung des Streamers..."></textarea>
                </div>
                
                <div class="form-group">
                    <label for="priority_order">📈 Anzeigereihenfolge</label>
                    <input type="number" id="priority_order" name="priority_order" class="form-control" 
                           value="0" min="0" max="999">
                    <small style="color: var(--gray); margin-top: 0.5rem; display: block;">
                        Niedrigere Zahl = höhere Priorität (0 = höchste Priorität)
                    </small>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addStreamerModal')">
                        ❌ Abbrechen
                    </button>
                    <button type="submit" class="btn btn-primary">
                        ✅ Streamer hinzufügen
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Streamer Modal -->
    <div id="editStreamerModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">✏️ Streamer bearbeiten</h3>
                <button class="close-modal" onclick="closeModal('editStreamerModal')">&times;</button>
            </div>
            
            <form method="POST" class="modal-body">
                <input type="hidden" name="action" value="update_streamer">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" id="edit_streamer_id" name="streamer_id">
                
                <div class="form-group">
                    <label for="edit_display_name">🏷️ Anzeigename *</label>
                    <input type="text" id="edit_display_name" name="display_name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_description">📝 Beschreibung</label>
                    <textarea id="edit_description" name="description" class="form-control" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="edit_priority_order">📈 Anzeigereihenfolge</label>
                    <input type="number" id="edit_priority_order" name="priority_order" class="form-control" 
                           min="0" max="999" required>
                </div>
                
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 0.5rem;">
                        <input type="checkbox" id="edit_is_active" name="is_active">
                        <span>✅ Streamer ist aktiv</span>
                    </label>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editStreamerModal')">
                        ❌ Abbrechen
                    </button>
                    <button type="submit" class="btn btn-primary">
                        💾 Änderungen speichern
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Twitch Settings Modal -->
    <div id="twitchSettingsModal" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h3 class="modal-title">⚙️ Twitch API-Einstellungen</h3>
                <button class="close-modal" onclick="closeModal('twitchSettingsModal')">&times;</button>
            </div>
            
            <form method="POST" class="modal-body">
                <input type="hidden" name="action" value="update_twitch_settings">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="alert alert-info" style="margin-bottom: 2rem;">
                    <strong>🔗 Hinweis:</strong> Um die Twitch API zu nutzen, benötigen Sie eine Twitch-Anwendung. 
                    <a href="https://dev.twitch.tv/console/apps" target="_blank" style="color: var(--primary);">
                        Erstellen Sie hier eine neue App
                    </a> und kopieren Sie die Client ID und das Client Secret.
                </div>
                
                <h4 style="color: var(--primary); margin-bottom: 1rem;">🔑 API-Zugangsdaten</h4>
                
                <div class="form-group">
                    <label for="twitch_client_id">🆔 Twitch Client ID</label>
                    <input type="text" id="twitch_client_id" name="twitch_client_id" class="form-control"
                           value="<?php echo htmlspecialchars($twitchSettings['client_id']); ?>"
                           placeholder="Ihre Twitch App Client ID">
                </div>
                
                <div class="form-group">
                    <label for="twitch_client_secret">🔐 Twitch Client Secret</label>
                    <input type="password" id="twitch_client_secret" name="twitch_client_secret" class="form-control"
                           value="<?php echo htmlspecialchars($twitchSettings['client_secret']); ?>"
                           placeholder="Ihr Twitch App Client Secret">
                </div>
                
                <h4 style="color: var(--primary); margin: 2rem 0 1rem;">📺 Anzeige-Einstellungen</h4>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label for="twitch_max_display">👥 Max. gleichzeitige Streams</label>
                        <input type="number" id="twitch_max_display" name="twitch_max_display" class="form-control"
                               value="<?php echo htmlspecialchars($twitchSettings['max_display']); ?>"
                               min="1" max="10">
                    </div>
                    
                    <div class="form-group">
                        <label for="twitch_update_interval">⏱️ Update-Intervall (Sekunden)</label>
                        <input type="number" id="twitch_update_interval" name="twitch_update_interval" class="form-control"
                               value="<?php echo htmlspecialchars($twitchSettings['update_interval']); ?>"
                               min="60" max="3600">
                    </div>
                </div>
                
                <div style="display: flex; gap: 2rem; margin: 2rem 0; flex-wrap: wrap;">
                    <label style="display: flex; align-items: center; gap: 0.5rem;">
                        <input type="checkbox" name="twitch_display_enabled" 
                               <?php echo $twitchSettings['display_enabled'] ? 'checked' : ''; ?>>
                        <span>📺 Stream-Anzeige auf Website aktiviert</span>
                    </label>
                    
                    <label style="display: flex; align-items: center; gap: 0.5rem;">
                        <input type="checkbox" name="twitch_auto_update" 
                               <?php echo $twitchSettings['auto_update'] ? 'checked' : ''; ?>>
                        <span>🔄 Automatische Status-Updates</span>
                    </label>
                    
                    <label style="display: flex; align-items: center; gap: 0.5rem;">
                        <input type="checkbox" name="twitch_show_offline_message" 
                               <?php echo $twitchSettings['show_offline_message'] ? 'checked' : ''; ?>>
                        <span>💬 "Kein Streamer online" Nachricht anzeigen</span>
                    </label>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('twitchSettingsModal')">
                        ❌ Abbrechen
                    </button>
                    <button type="submit" class="btn btn-primary">
                        💾 Einstellungen speichern
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="../assets/js/admin.js"></script>
    <script>
        // Pass data to JavaScript
        window.streamersData = {
            csrfToken: '<?php echo generateCSRFToken(); ?>',
            apiStatus: '<?php echo $apiStatus; ?>'
        };
        
        // Twitch-specific modal functions
        window.openTwitchSettingsModal = function() {
            console.log('openTwitchSettingsModal aufgerufen');
            const modal = document.getElementById('twitchSettingsModal');
            if (modal) {
                console.log('Modal gefunden, öffne...');
                modal.classList.add('active');
                
                // Focus first input
                setTimeout(() => {
                    const firstInput = modal.querySelector('input:not([type="hidden"]):not([disabled])');
                    if (firstInput) {
                        firstInput.focus();
                    }
                }, 100);
            } else {
                console.error('twitchSettingsModal nicht gefunden!');
                alert('Modal nicht gefunden. Bitte laden Sie die Seite neu.');
            }
        };
        
        window.openAddStreamerModal = function() {
            console.log('openAddStreamerModal aufgerufen');
            const modal = document.getElementById('addStreamerModal');
            if (modal) {
                modal.classList.add('active');
                setTimeout(() => {
                    const firstInput = modal.querySelector('#streamer_name');
                    if (firstInput) {
                        firstInput.focus();
                    }
                }, 100);
            } else {
                console.error('addStreamerModal nicht gefunden!');
            }
        };

        window.editStreamer = function(streamer) {
            console.log('editStreamer aufgerufen für:', streamer);
            
            // Felder füllen
            const elements = {
                'edit_streamer_id': streamer.id,
                'edit_display_name': streamer.display_name,
                'edit_description': streamer.description || '',
                'edit_priority_order': streamer.priority_order
            };
            
            for (const [id, value] of Object.entries(elements)) {
                const element = document.getElementById(id);
                if (element) {
                    element.value = value;
                } else {
                    console.warn('Element nicht gefunden:', id);
                }
            }
            
            // Checkbox setzen
            const activeCheckbox = document.getElementById('edit_is_active');
            if (activeCheckbox) {
                activeCheckbox.checked = streamer.is_active == 1;
            }
            
            // Modal öffnen
            const modal = document.getElementById('editStreamerModal');
            if (modal) {
                modal.classList.add('active');
            } else {
                console.error('editStreamerModal nicht gefunden!');
            }
        };
        
window.deleteStreamer = function(id) {
    if (confirm('🗑️ Streamer löschen\n\nSind Sie sicher, dass Sie diesen Streamer löschen möchten?\nDiese Aktion kann nicht rückgängig gemacht werden.')) {
        submitForm('delete_streamer', { streamer_id: id });
    }
};

        
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('active');
                
                // Focus first input
                const firstInput = modal.querySelector('input:not([type="hidden"]):not([disabled]), textarea:not([disabled])');
                if (firstInput) {
                    setTimeout(() => firstInput.focus(), 100);
                }
            } else {
                console.error('Modal nicht gefunden:', modalId);
            }
        }
        
window.closeModal = function(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
    }
};
        
        // Close modal when clicking outside
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) {
                e.target.classList.remove('active');
            }
        });
        
        // Close modal with close button
        document.querySelectorAll('.close-modal').forEach(button => {
            button.addEventListener('click', function() {
                const modal = this.closest('.modal');
                if (modal) {
                    modal.classList.remove('active');
                }
            });
        });
        
window.submitForm = function(action, data) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '';
    
    // CSRF Token
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = 'csrf_token';
    csrfInput.value = window.streamersData?.csrfToken || '';
    form.appendChild(csrfInput);
    
    // Action
    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = action;
    form.appendChild(actionInput);
    
    // Data
    for (const [key, value] of Object.entries(data)) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = value;
        form.appendChild(input);
    }
    
    document.body.appendChild(form);
    form.submit();
};
        
        function deleteStreamer(id) {
            showConfirmDialog(
                '🗑️ Streamer löschen',
                'Sind Sie sicher, dass Sie diesen Streamer löschen möchten? Diese Aktion kann nicht rückgängig gemacht werden.',
                () => {
                    submitForm('delete_streamer', { streamer_id: id });
                }
            );
        }
        
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('active');
                
                // Focus first input
                const firstInput = modal.querySelector('input:not([type="hidden"]):not([disabled]), textarea:not([disabled])');
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
        
        function submitForm(action, data) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '';
            
            // CSRF Token
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = 'csrf_token';
            csrfInput.value = window.streamersData.csrfToken;
            form.appendChild(csrfInput);
            
            // Action
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = action;
            form.appendChild(actionInput);
            
            // Data
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
        
        function showConfirmDialog(title, message, onConfirm) {
            if (confirm(`${title}\n\n${message}`)) {
                onConfirm();
            }
        }
        
        // Auto-refresh page every 2 minutes to show live stream updates
        setInterval(() => {
            if (window.streamersData.apiStatus === 'connected') {
                location.reload();
            }
        }, 120000);
        
        // Modal click outside to close
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                }
            });
        });
        
        // Keyboard shortcuts
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.classList.remove('active');
    }
});

// Close buttons
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('close-modal')) {
        const modal = e.target.closest('.modal');
        if (modal) {
            modal.classList.remove('active');
        }
    }
});

// Escape key to close modals
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal.active').forEach(modal => {
            modal.classList.remove('active');
        });
    }
});
        
        // Form enhancements
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Streamers Modal Fix geladen');
            
            // Test ob Modals existieren
            const twitchModal = document.getElementById('twitchSettingsModal');
            const addModal = document.getElementById('addStreamerModal');
            const editModal = document.getElementById('editStreamerModal');
            
            console.log('Twitch Modal gefunden:', !!twitchModal);
            console.log('Add Modal gefunden:', !!addModal);
            console.log('Edit Modal gefunden:', !!editModal);
            
            // CSS für Modals hinzufügen (falls nicht vorhanden)
            const modalCSS = `
                <style id="modal-fix-styles">
                .modal {
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: rgba(0, 0, 0, 0.8);
                    backdrop-filter: blur(5px);
                    z-index: 10000;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    opacity: 0;
                    visibility: hidden;
                    transition: all 0.3s ease;
                }
                
                .modal.active {
                    opacity: 1 !important;
                    visibility: visible !important;
                }
                
                .modal-content {
                    background: var(--dark, #1a1a1a);
                    border: 1px solid var(--border, rgba(255, 255, 255, 0.1));
                    border-radius: 15px;
                    max-width: 800px;
                    width: 90%;
                    max-height: 90vh;
                    overflow-y: auto;
                    transform: scale(0.7);
                    transition: transform 0.3s ease;
                    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
                }
                
                .modal.active .modal-content {
                    transform: scale(1) !important;
                }
                
                .modal-header {
                    padding: 1.5rem;
                    border-bottom: 1px solid var(--border, rgba(255, 255, 255, 0.1));
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }
                
                .modal-title {
                    color: var(--primary, #ff4444);
                    font-size: 1.3rem;
                    margin: 0;
                }
                
                .close-modal {
                    background: none;
                    border: none;
                    color: var(--gray, #666);
                    font-size: 1.5rem;
                    cursor: pointer;
                    padding: 0.5rem;
                    border-radius: 4px;
                    transition: all 0.3s ease;
                }
                
                .close-modal:hover {
                    color: var(--primary, #ff4444);
                    background: rgba(255, 68, 68, 0.1);
                }
                
                .modal-body {
                    padding: 1.5rem;
                    color: white;
                }
                
                .modal-footer {
                    padding: 1.5rem;
                    border-top: 1px solid var(--border, rgba(255, 255, 255, 0.1));
                    display: flex;
                    gap: 1rem;
                    justify-content: flex-end;
                }
                </style>
            `;
            
            // CSS nur hinzufügen wenn noch nicht vorhanden
            if (!document.getElementById('modal-fix-styles')) {
                document.head.insertAdjacentHTML('beforeend', modalCSS);
            }
        });
    </script>
</body>
</html>