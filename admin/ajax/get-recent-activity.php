?>
<?php
require_once '../../config/config.php';
require_once '../../config/user_management.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !hasPermission('logs.view')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Keine Berechtigung']);
    exit;
}

// Letzte 50 Aktivitäten laden
$activities = getAuditLog(50);

ob_start();
?>

<div style="max-height: 500px; overflow-y: auto;">
    <?php if (!empty($activities)): ?>
    <div style="display: grid; gap: 0.5rem;">
        <?php foreach ($activities as $activity): ?>
        <div style="background: rgba(255, 255, 255, 0.05); border-radius: 6px; padding: 1rem; border-left: 3px solid var(--primary);">
            <div style="display: flex; justify-content: space-between; align-items: start;">
                <div style="flex: 1;">
                    <div style="font-weight: bold; color: white; margin-bottom: 0.25rem;">
                        <?php
                        $actionIcons = [
                            'user_login' => '🔑',
                            'user_logout' => '🚪',
                            'user_created' => '👤',
                            'user_updated' => '✏️',
                            'user_deleted' => '🗑️',
                            'password_changed' => '🔐',
                            'role_created' => '🛡️',
                            'role_updated' => '⚙️',
                            'whitelist_approved' => '✅',
                            'whitelist_rejected' => '❌',
                            'settings_updated' => '⚙️',
                            'news_created' => '📰',
                            'rule_created' => '📋'
                        ];
                        
                        $actionLabels = [
                            'user_login' => 'Benutzer-Anmeldung',
                            'user_logout' => 'Benutzer-Abmeldung', 
                            'user_created' => 'Benutzer erstellt',
                            'user_updated' => 'Benutzer bearbeitet',
                            'user_deleted' => 'Benutzer gelöscht',
                            'password_changed' => 'Passwort geändert',
                            'role_created' => 'Rolle erstellt',
                            'role_updated' => 'Rolle bearbeitet',
                            'whitelist_approved' => 'Whitelist genehmigt',
                            'whitelist_rejected' => 'Whitelist abgelehnt',
                            'settings_updated' => 'Einstellungen geändert',
                            'news_created' => 'News erstellt',
                            'rule_created' => 'Regel erstellt'
                        ];
                        
                        $icon = $actionIcons[$activity['action']] ?? '📋';
                        $label = $actionLabels[$activity['action']] ?? htmlspecialchars($activity['action']);
                        
                        echo $icon . ' ' . $label;
                        ?>
                    </div>
                    <div style="font-size: 0.85rem; color: var(--gray);">
                        👤 <?php echo htmlspecialchars($activity['user_username'] ?? 'System'); ?>
                        <?php if ($activity['target_type'] && $activity['target_id']): ?>
                        | 🎯 <?php echo htmlspecialchars($activity['target_type']) . ' #' . $activity['target_id']; ?>
                        <?php endif; ?>
                        | 🌐 <?php echo htmlspecialchars($activity['ip_address']); ?>
                    </div>
                </div>
                <div style="font-size: 0.8rem; color: var(--gray); text-align: right; min-width: 100px;">
                    <?php echo date('d.m.Y', strtotime($activity['created_at'])); ?>
                    <br>
                    <?php echo date('H:i:s', strtotime($activity['created_at'])); ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <div style="margin-top: 1rem; text-align: center;">
        <a href="audit-log.php" class="btn btn-secondary">
            📜 Vollständiges Audit-Log anzeigen
        </a>
    </div>
    
    <?php else: ?>
    <div style="text-align: center; padding: 3rem; color: var(--gray);">
        <p>📜 Keine Aktivitäten gefunden</p>
    </div>
    <?php endif; ?>
</div>

<?php
$html = ob_get_clean();
echo json_encode(['success' => true, 'html' => $html]);
?>

<?php