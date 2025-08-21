<?php
/**
 * AJAX-Endpoint für Benutzer-Details
 * Pfad: admin/ajax/get-user-details.php
 */

require_once '../../config/config.php';
require_once '../../config/user_management.php';

// JSON Response Header
header('Content-Type: application/json');

// Login und Berechtigung prüfen
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Nicht angemeldet']);
    exit;
}

if (!hasPermission('users.view')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Keine Berechtigung']);
    exit;
}

// Benutzer-ID aus GET-Parameter
$userId = (int)($_GET['id'] ?? 0);

if ($userId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Ungültige Benutzer-ID']);
    exit;
}

// Benutzer-Details laden
$user = getUserDetails($userId);

if (!$user) {
    echo json_encode(['success' => false, 'error' => 'Benutzer nicht gefunden']);
    exit;
}

// Aktive Sessions laden
$sessions = getUserActiveSessions($userId);

// Audit-Log für diesen Benutzer laden (letzte 10 Einträge)
$auditLog = getAuditLog(10, ['user_id' => $userId]);

// HTML für Details generieren
ob_start();
?>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
    <!-- Benutzer-Informationen -->
    <div>
        <h4 style="color: var(--primary); margin-bottom: 1rem;">👤 Benutzer-Informationen</h4>
        
        <div style="background: rgba(255, 255, 255, 0.05); border-radius: 8px; padding: 1.5rem;">
            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
                <div class="user-avatar" style="width: 60px; height: 60px; font-size: 1.5rem;">
                    <?php echo strtoupper(substr($user['username'], 0, 2)); ?>
                </div>
                <div>
                    <h3 style="margin: 0; color: white;">
                        <?php echo htmlspecialchars($user['username']); ?>
                    </h3>
                    <?php if ($user['first_name'] || $user['last_name']): ?>
                    <p style="margin: 0; color: var(--gray);">
                        <?php echo htmlspecialchars(trim($user['first_name'] . ' ' . $user['last_name'])); ?>
                    </p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div style="display: grid; gap: 0.75rem;">
                <div>
                    <strong>📧 E-Mail:</strong> 
                    <?php echo htmlspecialchars($user['email']); ?>
                    <?php if ($user['email_verified']): ?>
                        <span style="color: var(--success);">✅ Verifiziert</span>
                    <?php else: ?>
                        <span style="color: var(--warning);">⚠️ Nicht verifiziert</span>
                    <?php endif; ?>
                </div>
                
                <?php if ($user['phone']): ?>
                <div>
                    <strong>📞 Telefon:</strong> 
                    <?php echo htmlspecialchars($user['phone']); ?>
                </div>
                <?php endif; ?>
                
                <div>
                    <strong>🛡️ Rolle:</strong> 
                    <span class="role-badge role-<?php echo $user['role']; ?>">
                        <?php echo htmlspecialchars($user['role_display_name'] ?? $user['role']); ?>
                    </span>
                </div>
                
                <div>
                    <strong>📊 Status:</strong> 
                    <span style="color: <?php echo $user['is_active'] ? 'var(--success)' : 'var(--danger)'; ?>">
                        <?php echo $user['is_active'] ? '✅ Aktiv' : '❌ Inaktiv'; ?>
                    </span>
                </div>
                
                <div>
                    <strong>🔐 Zwei-Faktor-Authentifizierung:</strong> 
                    <span style="color: <?php echo $user['two_factor_enabled'] ? 'var(--success)' : 'var(--gray)'; ?>">
                        <?php echo $user['two_factor_enabled'] ? '✅ Aktiviert' : '❌ Deaktiviert'; ?>
                    </span>
                </div>
                
                <div>
                    <strong>⏰ Letzter Login:</strong> 
                    <?php if ($user['last_login']): ?>
                        <?php echo date('d.m.Y H:i:s', strtotime($user['last_login'])); ?>
                    <?php else: ?>
                        <span style="color: var(--gray);">Noch nie angemeldet</span>
                    <?php endif; ?>
                </div>
                
                <div>
                    <strong>🔑 Letztes Passwort-Update:</strong> 
                    <?php echo date('d.m.Y H:i:s', strtotime($user['last_password_change'])); ?>
                </div>
                
                <div>
                    <strong>📅 Erstellt:</strong> 
                    <?php echo date('d.m.Y H:i:s', strtotime($user['created_at'])); ?>
                    <?php if ($user['created_by_username']): ?>
                        <br><small style="color: var(--gray);">
                            von <?php echo htmlspecialchars($user['created_by_username']); ?>
                        </small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Berechtigungen -->
        <h4 style="color: var(--primary); margin: 2rem 0 1rem;">🔧 Berechtigungen</h4>
        <div style="background: rgba(255, 255, 255, 0.05); border-radius: 8px; padding: 1.5rem;">
            <?php
            $userPermissions = explode(',', $user['permissions'] ?? '');
            $allPermissions = getAllPermissions();
            
            if (in_array('all', $userPermissions) || $user['role'] === 'super_admin') {
                echo '<div style="color: var(--success); font-weight: bold;">🌟 Vollzugriff (Alle Berechtigungen)</div>';
            } elseif (!empty($userPermissions) && $userPermissions[0] !== '') {
                echo '<div style="display: grid; gap: 0.5rem;">';
                foreach ($userPermissions as $permission) {
                    $permissionData = array_filter($allPermissions, function($p) use ($permission) {
                        return $p['permission_key'] === trim($permission);
                    });
                    
                    if (!empty($permissionData)) {
                        $permissionData = array_values($permissionData)[0];
                        echo '<div style="display: flex; align-items: center; gap: 0.5rem;">';
                        echo '<span style="color: var(--success);">✅</span>';
                        echo '<span>' . htmlspecialchars($permissionData['permission_name']) . '</span>';
                        echo '</div>';
                    } else {
                        echo '<div style="display: flex; align-items: center; gap: 0.5rem;">';
                        echo '<span style="color: var(--warning);">⚠️</span>';
                        echo '<span style="color: var(--warning);">' . htmlspecialchars($permission) . ' (Unbekannt)</span>';
                        echo '</div>';
                    }
                }
                echo '</div>';
            } else {
                echo '<div style="color: var(--gray);">Keine spezifischen Berechtigungen definiert</div>';
            }
            ?>
        </div>
    </div>
    
    <!-- Aktivitäten und Sessions -->
    <div>
        <!-- Aktive Sessions -->
        <h4 style="color: var(--primary); margin-bottom: 1rem;">🔗 Aktive Sessions</h4>
        <div style="background: rgba(255, 255, 255, 0.05); border-radius: 8px; padding: 1.5rem; margin-bottom: 2rem;">
            <?php if (!empty($sessions)): ?>
            <div class="user-sessions">
                <?php foreach ($sessions as $session): ?>
                <div class="session-item">
                    <div>
                        <div style="font-weight: bold; color: white;">
                            🌐 <?php echo htmlspecialchars($session['ip_address']); ?>
                        </div>
                        <div style="font-size: 0.8rem; color: var(--gray);">
                            <?php echo htmlspecialchars(substr($session['user_agent'], 0, 60)); ?>
                            <?php echo strlen($session['user_agent']) > 60 ? '...' : ''; ?>
                        </div>
                        <div style="font-size: 0.8rem; color: var(--gray);">
                            Letzte Aktivität: <?php echo date('d.m.Y H:i:s', strtotime($session['last_activity'])); ?>
                        </div>
                    </div>
                    <div style="color: var(--success); font-size: 0.8rem;">
                        ⚡ Aktiv
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php if (hasPermission('users.edit') && count($sessions) > 1): ?>
            <div style="margin-top: 1rem; text-align: center;">
                <button onclick="terminateAllSessions(<?php echo $user['id']; ?>)" 
                        class="btn btn-small btn-delete">
                    🚫 Alle Sessions beenden
                </button>
            </div>
            <?php endif; ?>
            
            <?php else: ?>
            <div style="text-align: center; color: var(--gray); padding: 2rem;">
                🔗 Keine aktiven Sessions
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Letzte Aktivitäten -->
        <h4 style="color: var(--primary); margin-bottom: 1rem;">📜 Letzte Aktivitäten</h4>
        <div style="background: rgba(255, 255, 255, 0.05); border-radius: 8px; padding: 1.5rem;">
            <?php if (!empty($auditLog)): ?>
            <div style="max-height: 300px; overflow-y: auto;">
                <?php foreach ($auditLog as $entry): ?>
                <div style="padding: 0.75rem 0; border-bottom: 1px solid rgba(255, 255, 255, 0.1);">
                    <div style="display: flex; justify-content: space-between; align-items: start;">
                        <div>
                            <div style="font-weight: bold; color: white;">
                                <?php
                                $actionLabels = [
                                    'user_login' => '🔑 Anmeldung',
                                    'user_logout' => '🚪 Abmeldung',
                                    'user_created' => '👤 Benutzer erstellt',
                                    'user_updated' => '✏️ Benutzer bearbeitet',
                                    'user_deleted' => '🗑️ Benutzer gelöscht',
                                    'password_changed' => '🔐 Passwort geändert',
                                    'role_created' => '🛡️ Rolle erstellt',
                                    'role_updated' => '⚙️ Rolle bearbeitet',
                                    'whitelist_approved' => '✅ Whitelist genehmigt',
                                    'whitelist_rejected' => '❌ Whitelist abgelehnt'
                                ];
                                echo $actionLabels[$entry['action']] ?? htmlspecialchars($entry['action']);
                                ?>
                            </div>
                            <?php if ($entry['target_type'] && $entry['target_id']): ?>
                            <div style="font-size: 0.8rem; color: var(--gray);">
                                <?php echo htmlspecialchars($entry['target_type']) . ' ID: ' . $entry['target_id']; ?>
                            </div>
                            <?php endif; ?>
                            <div style="font-size: 0.8rem; color: var(--gray);">
                                🌐 <?php echo htmlspecialchars($entry['ip_address']); ?>
                            </div>
                        </div>
                        <div style="font-size: 0.8rem; color: var(--gray); text-align: right;">
                            <?php echo date('d.m.Y', strtotime($entry['created_at'])); ?>
                            <br>
                            <?php echo date('H:i:s', strtotime($entry['created_at'])); ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div style="text-align: center; color: var(--gray); padding: 2rem;">
                📜 Keine Aktivitäten gefunden
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Statistiken -->
        <h4 style="color: var(--primary); margin: 2rem 0 1rem;">📊 Benutzer-Statistiken</h4>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <?php
            $totalLogins = fetchOne("SELECT COUNT(*) as count FROM login_attempts WHERE username = :username AND success = 1", 
                                  ['username' => $user['username']])['count'] ?? 0;
            $failedLogins = fetchOne("SELECT COUNT(*) as count FROM login_attempts WHERE username = :username AND success = 0", 
                                   ['username' => $user['username']])['count'] ?? 0;
            $totalActions = fetchOne("SELECT COUNT(*) as count FROM admin_audit_log WHERE user_id = :user_id", 
                                   ['user_id' => $user['id']])['count'] ?? 0;
            $accountAgeDays = ceil((time() - strtotime($user['created_at'])) / (60 * 60 * 24));
            ?>
            
            <div class="stats-card">
                <div class="number"><?php echo $totalLogins; ?></div>
                <div class="label">Erfolgreiche Logins</div>
            </div>
            
            <div class="stats-card">
                <div class="number"><?php echo $failedLogins; ?></div>
                <div class="label">Fehlgeschlagene Logins</div>
            </div>
            
            <div class="stats-card">
                <div class="number"><?php echo $totalActions; ?></div>
                <div class="label">Aktionen insgesamt</div>
            </div>
            
            <div class="stats-card">
                <div class="number"><?php echo $accountAgeDays; ?></div>
                <div class="label">Tage seit Erstellung</div>
            </div>
        </div>
    </div>
</div>

<?php if (hasPermission('users.edit')): ?>
<div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid rgba(255, 255, 255, 0.1);">
    <div style="display: flex; justify-content: center; gap: 1rem;">
        <button onclick="editUserFromDetails(<?php echo htmlspecialchars(json_encode($user)); ?>)" 
                class="btn btn-primary">
            ✏️ Benutzer bearbeiten
        </button>
        
        <?php if ($user['id'] != $_SESSION['admin_id']): ?>
        <button onclick="resetUserPassword(<?php echo $user['id']; ?>)" 
                class="btn btn-warning">
            🔐 Passwort zurücksetzen
        </button>
        
        <?php if (hasPermission('users.delete')): ?>
        <button onclick="deleteUserFromDetails(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')" 
                class="btn btn-delete">
            🗑️ Benutzer löschen
        </button>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<script>
function editUserFromDetails(user) {
    closeModal('userDetailsModal');
    setTimeout(() => editUser(user), 100);
}

function deleteUserFromDetails(userId, username) {
    closeModal('userDetailsModal');
    setTimeout(() => deleteUser(userId, username), 100);
}

function resetUserPassword(userId) {
    showConfirmDialog(
        '🔐 Passwort zurücksetzen',
        'Möchten Sie ein neues temporäres Passwort für diesen Benutzer generieren?',
        () => {
            // Hier würde die Passwort-Reset-Funktion implementiert
            alert('Passwort-Reset würde hier implementiert werden');
        }
    );
}

function terminateAllSessions(userId) {
    showConfirmDialog(
        '🚫 Alle Sessions beenden',
        'Möchten Sie alle aktiven Sessions dieses Benutzers beenden? Der Benutzer wird sofort abgemeldet.',
        async () => {
            try {
                const response = await fetch('ajax/terminate-sessions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ user_id: userId })
                });
                
                const result = await response.json();
                if (result.success) {
                    // Details neu laden
                    viewUserDetails(userId);
                    showNotification('success', 'Alle Sessions wurden beendet');
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

echo json_encode([
    'success' => true,
    'html' => $html,
    'user' => $user
]);