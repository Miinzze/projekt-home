<?php
/**
 * Vollständige AJAX-Endpoints für die Benutzerverwaltung
 * Diese Dateien in admin/ajax/ speichern
 */

// ================================================================
// 1. admin/ajax/get-active-users.php
// ================================================================
?>
<?php
require_once '../../config/config.php';
require_once '../../config/user_management.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !hasPermission('users.view')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Keine Berechtigung']);
    exit;
}

// Aktive Benutzer mit Sessions laden
$activeUsers = fetchAll("
    SELECT DISTINCT 
        a.id, a.username, a.role, a.last_login,
        COUNT(s.id) as active_sessions,
        MAX(s.last_activity) as latest_activity,
        GROUP_CONCAT(DISTINCT s.ip_address) as ip_addresses
    FROM admins a
    INNER JOIN user_sessions s ON a.id = s.user_id
    WHERE s.is_active = 1 AND s.expires_at > NOW()
    GROUP BY a.id, a.username, a.role, a.last_login
    ORDER BY latest_activity DESC
");

ob_start();
?>

<div style="max-height: 500px; overflow-y: auto;">
    <?php if (!empty($activeUsers)): ?>
    <div style="display: grid; gap: 1rem;">
        <?php foreach ($activeUsers as $user): ?>
        <div style="background: rgba(255, 255, 255, 0.05); border-radius: 8px; padding: 1rem; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <div class="user-avatar" style="width: 32px; height: 32px; font-size: 0.8rem;">
                        <?php echo strtoupper(substr($user['username'], 0, 2)); ?>
                    </div>
                    <div>
                        <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                        <span class="role-badge role-<?php echo $user['role']; ?>" style="margin-left: 0.5rem; font-size: 0.7rem;">
                            <?php echo htmlspecialchars($user['role']); ?>
                        </span>
                    </div>
                </div>
                <div style="font-size: 0.8rem; color: var(--gray); margin-top: 0.25rem;">
                    🔗 <?php echo $user['active_sessions']; ?> Session(s) | 
                    ⏰ <?php echo date('H:i:s', strtotime($user['latest_activity'])); ?> |
                    🌐 <?php echo htmlspecialchars(substr($user['ip_addresses'], 0, 50)); ?>
                </div>
            </div>
            <div>
                <button onclick="viewUserDetails(<?php echo $user['id']; ?>)" class="btn btn-small btn-secondary">
                    👁️ Details
                </button>
                <?php if (hasPermission('users.edit') && $user['id'] != $_SESSION['admin_id']): ?>
                <button onclick="terminateUserSessions(<?php echo $user['id']; ?>)" class="btn btn-small btn-warning">
                    🚫 Sessions beenden
                </button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div style="text-align: center; padding: 3rem; color: var(--gray);">
        <p>🔗 Keine aktiven Benutzer-Sessions gefunden</p>
    </div>
    <?php endif; ?>
</div>

<script>
function terminateUserSessions(userId) {
    showConfirmDialog(
        '🚫 Sessions beenden',
        'Möchten Sie alle Sessions dieses Benutzers beenden?',
        async () => {
            try {
                const response = await fetch('ajax/terminate-sessions.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ user_id: userId })
                });
                
                const result = await response.json();
                if (result.success) {
                    showNotification('success', 'Sessions beendet');
                    // Modal neu laden
                    showActiveUsers();
                } else {
                    alert('Fehler: ' + result.error);
                }
            } catch (error) {
                alert('Fehler beim Beenden der Sessions');
            }
        }
    );
}
</script>

<?php
$html = ob_get_clean();
echo json_encode(['success' => true, 'html' => $html]);
?>

<?php