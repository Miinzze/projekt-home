/**
 * Admin Dashboard JavaScript mit Termin-System
 * Vollständige und saubere Version
 */

// Global variables
let currentApplicationData = null;

document.addEventListener('DOMContentLoaded', function() {
    initializeAdminDashboard();
});

/**
 * Initialisierung des Admin Dashboards
 */
function initializeAdminDashboard() {
    setupNavigationEffects();
    setupStatCardAnimations();
    setupFormValidation();
    setupAutoHideFlashMessages();
    setupModalEvents();
    setupTableInteractions();
    setupWhitelistFunctions();
    setupKeyboardShortcuts();
    initBulkActions();
    initDashboardAutoRefresh();
    addStyles();
    
    console.log('🚀 Admin Dashboard erfolgreich initialisiert!');
}

// ========================================
// TERMIN-SYSTEM
// ========================================

/**
 * Termin-Modal öffnen
 */
function sendAppointment(applicationId) {
    console.log('sendAppointment called with ID:', applicationId);
    
    // Fallback für einfache Bewerbungsdaten
    const fallbackApplicationData = {
        id: applicationId,
        discord_username: 'Benutzer',
        discord_id: '123456789',
        discord_avatar: null,
        created_at: new Date().toISOString(),
        score_percentage: 0
    };
    
    // Erst prüfen ob wir eine einfache Version brauchen
    fetch(`ajax/get-application-details-debug.php?id=${applicationId}`)
        .then(response => response.text())
        .then(debugText => {
            console.log('Debug response:', debugText);
            
            // Jetzt die echte Abfrage
            return fetch(`ajax/get-application-details.php?id=${applicationId}`);
        })
        .catch(debugError => {
            console.warn('Debug call failed, trying direct call:', debugError);
            // Falls Debug fehlschlägt, direkt probieren
            return fetch(`ajax/get-application-details.php?id=${applicationId}`);
        })
        .then(response => {
            console.log('Response status:', response.status);
            
            if (!response.ok) {
                // Bei HTTP Fehler, Fallback verwenden
                console.warn(`HTTP ${response.status}, using fallback data`);
                currentApplicationData = fallbackApplicationData;
                setupAppointmentModal(fallbackApplicationData);
                openModal('appointmentModal');
                return;
            }
            
            return response.text().then(text => {
                console.log('Raw response:', text);
                try {
                    return JSON.parse(text);
                } catch (parseError) {
                    console.error('JSON parse error:', parseError);
                    console.error('Response text:', text);
                    throw new Error('Server-Antwort konnte nicht gelesen werden');
                }
            });
        })
        .then(data => {
            if (!data) return; // Falls wir schon fallback verwendet haben
            
            console.log('Parsed data:', data);
            
            if (data.success && data.application) {
                currentApplicationData = data.application;
                setupAppointmentModal(data.application);
                openModal('appointmentModal');
            } else {
                console.error('API Error:', data.error);
                // Bei API-Fehler auch Fallback verwenden
                alert(`Bewerbungsdaten konnten nicht vollständig geladen werden: ${data.error || 'Unbekannter Fehler'}\n\nVerwende Standard-Daten für das Termin-Modal.`);
                currentApplicationData = fallbackApplicationData;
                setupAppointmentModal(fallbackApplicationData);
                openModal('appointmentModal');
            }
        })
        .catch(error => {
            console.error('Complete error:', error);
            
            // Als letzter Ausweg: Einfaches Modal ohne API-Call
            alert(`Fehler beim Laden der Bewerbungsdaten: ${error.message}\n\nÖffne Termin-Modal mit Standard-Daten.`);
            
            currentApplicationData = fallbackApplicationData;
            setupAppointmentModal(fallbackApplicationData);
            openModal('appointmentModal');
        });
}

/**
 * Modal mit Bewerbungsdaten füllen
 */
function setupAppointmentModal(application) {
    document.getElementById('appointment_application_id').value = application.id;
    
    const userInfo = document.getElementById('appointment_user_info');
    if (userInfo) {
        userInfo.innerHTML = `
            <div style="display: flex; align-items: center; gap: 1rem; width: 100%;">
                ${application.discord_avatar ? 
                    `<img src="${application.discord_avatar}" style="width: 48px; height: 48px; border-radius: 50%; border: 2px solid #5865f2;" alt="Avatar">` :
                    `<div style="width: 48px; height: 48px; border-radius: 50%; background: #5865f2; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 1.2rem;">
                        ${(application.discord_username || 'U').substring(0, 2).toUpperCase()}
                    </div>`
                }
                <div style="flex: 1;">
                    <h4 style="margin: 0; color: white; font-size: 1.1rem;">${escapeHtml(application.discord_username || 'Unbekannter Benutzer')}</h4>
                    <p style="margin: 0.25rem 0 0 0; color: #ccc; font-size: 0.9rem;">Discord ID: ${application.discord_id || 'Unbekannt'}</p>
                    <p style="margin: 0.25rem 0 0 0; color: #ccc; font-size: 0.9rem;">Bewerbung vom: ${formatDateTime(application.created_at || new Date().toISOString())}</p>
                </div>
                <div style="text-align: right;">
                    <span style="background: rgba(16, 185, 129, 0.2); color: #10b981; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem;">
                        ${application.score_percentage || 0}% Score
                    </span>
                </div>
            </div>
        `;
    }
    
    // Standard-Datum setzen (morgen)
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    const dateInput = document.getElementById('appointment_date');
    if (dateInput) {
        dateInput.value = tomorrow.toISOString().split('T')[0];
        dateInput.min = new Date().toISOString().split('T')[0];
    }
    
    // Standard-Zeit setzen (20:00)
    const timeInput = document.getElementById('appointment_time');
    if (timeInput) {
        timeInput.value = '20:00';
    }
    
    // Bot Status (vereinfacht)
    const statusElement = document.getElementById('discord_bot_status');
    if (statusElement) {
        statusElement.innerHTML = `
            <div style="color: #10b981; font-size: 0.9rem;">
                ✅ Bereit zum Senden
            </div>
        `;
    }
    
    updateMessagePreview();
    
    // Event Listeners für Live-Preview
    if (dateInput) {
        dateInput.removeEventListener('change', updateMessagePreview);
        dateInput.addEventListener('change', updateMessagePreview);
    }
    if (timeInput) {
        timeInput.removeEventListener('change', updateMessagePreview);
        timeInput.addEventListener('change', updateMessagePreview);
    }
}
/**
 * Discord Bot Status prüfen
 */
function checkDiscordBotStatus() {
    const statusElement = document.getElementById('discord_bot_status');
    if (!statusElement) return;
    
    statusElement.innerHTML = `
        <div style="color: #3b82f6; font-size: 0.9rem;">
            🔍 Discord Bot Status wird geprüft...
        </div>
    `;
    
    fetch('ajax/check-discord-bot.php')
        .then(response => response.json())
        .then(data => {
            if (data.enabled && data.configured) {
                statusElement.innerHTML = `
                    <div style="color: #10b981; font-size: 0.9rem;">
                        ✅ Discord Bot ist aktiv und bereit
                    </div>
                `;
            } else {
                statusElement.innerHTML = `
                    <div style="color: #ef4444; font-size: 0.9rem;">
                        ❌ Discord Bot ist nicht konfiguriert oder deaktiviert
                    </div>
                `;
            }
        })
        .catch(error => {
            statusElement.innerHTML = `
                <div style="color: #f59e0b; font-size: 0.9rem;">
                    ⚠️ Discord Bot Status konnte nicht geprüft werden
                </div>
            `;
        });
}

/**
 * Zeit-Button Handler
 */
function setTime(time) {
    const timeInput = document.getElementById('appointment_time');
    if (timeInput) {
        timeInput.value = time;
        updateMessagePreview();
        
        document.querySelectorAll('.quick-time').forEach(btn => btn.classList.remove('selected'));
        event.target.classList.add('selected');
    }
}

/**
 * Nachrichten-Vorschau aktualisieren
 */
function updateMessagePreview() {
    if (!currentApplicationData) return;
    
    const dateInput = document.getElementById('appointment_date');
    const timeInput = document.getElementById('appointment_time');
    const previewElement = document.getElementById('message_preview');
    
    if (!dateInput || !timeInput || !previewElement) return;
    
    const date = dateInput.value;
    const time = timeInput.value;
    
    if (date && time) {
        const formattedDate = new Date(date).toLocaleDateString('de-DE');
        const formattedTime = time;
        
        const template = `Hallo {username}!

Deine Whitelist-Bewerbung wurde geprüft und du bist für ein Gespräch vorgesehen.

📅 Termin: {appointment_date}
🕐 Uhrzeit: {appointment_time}

Bitte melde dich zur angegebenen Zeit im Discord-Channel #whitelist-gespräche.

Viel Erfolg!
Dein {server_name} Team`;
        
        const preview = template
            .replace('{username}', currentApplicationData.discord_username)
            .replace('{server_name}', 'Zombie RP Server')
            .replace('{appointment_date}', formattedDate)
            .replace('{appointment_time}', formattedTime);
        
        previewElement.textContent = preview;
        previewElement.style.color = 'white';
    } else {
        previewElement.textContent = 'Wählen Sie Datum und Uhrzeit aus, um eine Vorschau zu sehen...';
        previewElement.style.color = '#ccc';
    }
}

/**
 * Termin-Form Handler
 */
function setupAppointmentFormHandler() {
    const appointmentForm = document.getElementById('appointmentForm');
    if (!appointmentForm) return;
    
    appointmentForm.removeEventListener('submit', handleAppointmentSubmit);
    appointmentForm.addEventListener('submit', handleAppointmentSubmit);
}

function handleAppointmentSubmit(e) {
    e.preventDefault();
    
    const submitBtn = document.getElementById('sendAppointmentBtn');
    const originalText = submitBtn ? submitBtn.textContent : 'Senden';
    
    const date = document.getElementById('appointment_date')?.value;
    const time = document.getElementById('appointment_time')?.value;
    const applicationId = document.getElementById('appointment_application_id')?.value;
    
    if (!date || !time || !applicationId) {
        showNotification('❌ Bitte füllen Sie alle erforderlichen Felder aus.', 'error');
        return;
    }
    
    const selectedDateTime = new Date(date + 'T' + time);
    if (selectedDateTime < new Date()) {
        showNotification('❌ Der Termin kann nicht in der Vergangenheit liegen.', 'error');
        return;
    }
    
    if (submitBtn) {
        submitBtn.classList.add('loading');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Wird gesendet...';
    }
    
    const formData = {
        application_id: parseInt(applicationId),
        appointment_date: date,
        appointment_time: time
    };
    
    fetch('ajax/send-appointment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(formData)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(result => {
        if (result.success) {
            showNotification('✅ Termin-Nachricht erfolgreich gesendet!', 'success');
            closeModal('appointmentModal');
            
            updateApplicationRow(applicationId, {
                status: 'closed',
                appointment_date: result.formatted_date,
                appointment_sent: true
            });
            
            setTimeout(() => {
                location.reload();
            }, 2000);
            
        } else {
            throw new Error(result.error || 'Unbekannter Fehler beim Senden');
        }
    })
    .catch(error => {
        console.error('Error sending appointment:', error);
        showNotification(`❌ Fehler beim Senden: ${error.message}`, 'error');
    })
    .finally(() => {
        if (submitBtn) {
            submitBtn.classList.remove('loading');
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    });
}

/**
 * Bewerbungszeile aktualisieren
 */
function updateApplicationRow(applicationId, updates) {
    const row = document.querySelector(`tr[data-application-id="${applicationId}"]`);
    if (!row) return;
    
    if (updates.status) {
        const statusCell = row.querySelector('.status-badge');
        if (statusCell) {
            statusCell.className = `status-badge status-${updates.status}`;
            const statusLabels = {
                'pending': '🟡 Noch offen',
                'closed': '⚫ Geschlossen',
                'approved': '✅ Genehmigt',
                'rejected': '❌ Abgelehnt'
            };
            statusCell.textContent = statusLabels[updates.status] || updates.status;
        }
    }
    
    if (updates.appointment_date) {
        const actionsCell = row.querySelector('.actions-cell, td:last-child');
        if (actionsCell) {
            const appointmentInfo = document.createElement('small');
            appointmentInfo.style.display = 'block';
            appointmentInfo.style.color = '#10b981';
            appointmentInfo.textContent = `📅 Termin: ${updates.appointment_date}`;
            actionsCell.appendChild(appointmentInfo);
        }
    }
    
    const appointmentBtn = row.querySelector('button[onclick*="sendAppointment"]');
    if (appointmentBtn && updates.appointment_sent) {
        appointmentBtn.style.display = 'none';
    }
}

// ========================================
// MODAL FUNKTIONEN
// ========================================

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
    
    modal.style.opacity = '0';
    setTimeout(() => {
        modal.style.transition = 'opacity 0.3s ease';
        modal.style.opacity = '1';
    }, 10);
    
    const firstInput = modal.querySelector('input:not([type="hidden"]), textarea, select');
    if (firstInput) {
        setTimeout(() => firstInput.focus(), 100);
    }
    
    if (modalId === 'appointmentModal') {
        setupAppointmentFormHandler();
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    
    modal.style.transition = 'opacity 0.3s ease';
    modal.style.opacity = '0';
    
    setTimeout(() => {
        modal.style.display = 'none';
        document.body.style.overflow = '';
        
        const form = modal.querySelector('form');
        if (form) {
            form.reset();
        }
        
        if (modalId === 'appointmentModal') {
            const preview = document.getElementById('message_preview');
            if (preview) {
                preview.textContent = 'Wählen Sie Datum und Uhrzeit aus, um eine Vorschau zu sehen...';
                preview.style.color = '#ccc';
            }
            currentApplicationData = null;
        }
    }, 300);
}

// ========================================
// WHITELIST FUNKTIONEN
// ========================================

function viewApplication(id) {
    const detailWindow = window.open(
        `view-application.php?id=${id}`, 
        '_blank', 
        'width=1000,height=800,scrollbars=yes,resizable=yes,location=no,menubar=no,toolbar=no'
    );
    
    if (detailWindow) {
        detailWindow.addEventListener('load', function() {
            detailWindow.sendAppointmentFromParent = function(applicationId) {
                sendAppointment(applicationId);
            };
        });
    }
}

function quickApproveApplication(id, username) {
    showConfirmDialog(
        '✅ Bewerbung genehmigen',
        `Möchten Sie die Bewerbung von "${username}" wirklich genehmigen?`,
        () => {
            updateApplicationStatus(id, 'approved', 'Schnell-Genehmigung durch Admin');
        }
    );
}

function quickRejectApplication(id, username) {
    const reason = prompt(`Grund für die Ablehnung der Bewerbung von "${username}":`);
    if (reason !== null) {
        updateApplicationStatus(id, 'rejected', reason || 'Schnell-Ablehnung durch Admin');
    }
}

function updateApplicationStatus(applicationId, status, notes) {
    const data = {
        application_id: parseInt(applicationId),
        status: status,
        notes: notes || ''
    };
    
    fetch('ajax/update-application-status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            const statusText = status === 'approved' ? 'genehmigt' : 
                             status === 'rejected' ? 'abgelehnt' : 
                             status === 'closed' ? 'geschlossen' : status;
            
            showNotification(`✅ Bewerbung wurde ${statusText}`, 'success');
            updateApplicationRow(applicationId, { status: status });
            
        } else {
            throw new Error(result.error || 'Unbekannter Fehler');
        }
    })
    .catch(error => {
        console.error('Error updating application status:', error);
        showNotification(`❌ Fehler beim Aktualisieren: ${error.message}`, 'error');
    });
}

// ========================================
// NOTIFICATION SYSTEM
// ========================================

function showNotification(message, type = 'info', duration = 5000) {
    let container = document.getElementById('notification-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'notification-container';
        container.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            max-width: 400px;
            pointer-events: none;
        `;
        document.body.appendChild(container);
    }
    
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.style.cssText = `
        background: ${getNotificationBackground(type)};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        margin-bottom: 0.5rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        font-weight: 500;
        line-height: 1.4;
        position: relative;
        overflow: hidden;
        pointer-events: auto;
        transform: translateX(100%);
        transition: all 0.3s ease;
    `;
    
    notification.innerHTML = `
        <div style="display: flex; align-items: center; gap: 0.75rem; justify-content: space-between;">
            <span style="flex: 1;">${message}</span>
            <button onclick="removeNotification(this.parentElement.parentElement)" 
                    style="background: none; border: none; color: white; font-size: 1.2rem; cursor: pointer; opacity: 0.7; padding: 0; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center;">×</button>
        </div>
    `;
    
    if (duration > 0) {
        const progressBar = document.createElement('div');
        progressBar.style.cssText = `
            position: absolute;
            bottom: 0;
            left: 0;
            height: 3px;
            background: rgba(255, 255, 255, 0.3);
            width: 100%;
            animation: progressBar ${duration}ms linear;
        `;
        notification.appendChild(progressBar);
    }
    
    container.appendChild(notification);
    
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 10);
    
    if (duration > 0) {
        setTimeout(() => {
            removeNotification(notification);
        }, duration);
    }
}

function getNotificationBackground(type) {
    switch (type) {
        case 'success':
            return 'linear-gradient(135deg, #10b981, #059669)';
        case 'error':
            return 'linear-gradient(135deg, #ef4444, #dc2626)';
        case 'warning':
            return 'linear-gradient(135deg, #f59e0b, #d97706)';
        default:
            return 'linear-gradient(135deg, #3b82f6, #2563eb)';
    }
}

function removeNotification(notification) {
    if (!notification.parentNode) return;
    
    notification.style.transform = 'translateX(100%)';
    notification.style.opacity = '0';
    
    setTimeout(() => {
        if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
        }
    }, 300);
}

// ========================================
// BULK AKTIONEN
// ========================================

function initBulkActions() {
    const selectAllCheckbox = document.getElementById('selectAllApplications');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.application-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateBulkActionButtons();
        });
    }
    
    document.querySelectorAll('.application-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', updateBulkActionButtons);
    });
}

function updateBulkActionButtons() {
    const selectedCount = document.querySelectorAll('.application-checkbox:checked').length;
    const bulkActions = document.getElementById('bulkActions');
    
    if (bulkActions) {
        if (selectedCount > 0) {
            bulkActions.style.display = 'flex';
            const countElement = bulkActions.querySelector('.selected-count');
            if (countElement) {
                countElement.textContent = selectedCount;
            }
        } else {
            bulkActions.style.display = 'none';
        }
    }
}

function bulkApproveApplications() {
    const selectedIds = Array.from(document.querySelectorAll('.application-checkbox:checked')).map(cb => cb.value);
    
    if (selectedIds.length === 0) {
        showNotification('❌ Keine Bewerbungen ausgewählt', 'warning');
        return;
    }
    
    showConfirmDialog(
        `✅ ${selectedIds.length} Bewerbungen genehmigen`,
        `Sind Sie sicher, dass Sie ${selectedIds.length} Bewerbung(en) genehmigen möchten?`,
        () => {
            bulkUpdateApplications(selectedIds, 'approved', 'Bulk-Genehmigung durch Admin');
        }
    );
}

function bulkRejectApplications() {
    const selectedIds = Array.from(document.querySelectorAll('.application-checkbox:checked')).map(cb => cb.value);
    
    if (selectedIds.length === 0) {
        showNotification('❌ Keine Bewerbungen ausgewählt', 'warning');
        return;
    }
    
    const reason = prompt(`Grund für die Ablehnung von ${selectedIds.length} Bewerbung(en):`);
    if (reason !== null) {
        bulkUpdateApplications(selectedIds, 'rejected', reason || 'Bulk-Ablehnung durch Admin');
    }
}

function bulkUpdateApplications(applicationIds, status, notes) {
    const promises = applicationIds.map(id => 
        fetch('ajax/update-application-status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                application_id: parseInt(id),
                status: status,
                notes: notes
            })
        }).then(response => response.json())
    );
    
    Promise.all(promises)
        .then(results => {
            const successCount = results.filter(r => r.success).length;
            const errorCount = results.length - successCount;
            
            if (successCount > 0) {
                const statusText = status === 'approved' ? 'genehmigt' : 
                                 status === 'rejected' ? 'abgelehnt' : 'aktualisiert';
                showNotification(`✅ ${successCount} Bewerbung(en) erfolgreich ${statusText}`, 'success');
            }
            
            if (errorCount > 0) {
                showNotification(`❌ ${errorCount} Bewerbung(en) konnten nicht aktualisiert werden`, 'error');
            }
            
            applicationIds.forEach(id => {
                updateApplicationRow(id, { status: status });
            });
            
            document.querySelectorAll('.application-checkbox:checked').forEach(cb => {
                cb.checked = false;
            });
            updateBulkActionButtons();
            
        })
        .catch(error => {
            console.error('Bulk update error:', error);
            showNotification('❌ Fehler bei der Bulk-Aktualisierung', 'error');
        });
}

// ========================================
// DASHBOARD FUNKTIONEN
// ========================================

function initDashboardAutoRefresh() {
    if (window.location.search.includes('page=overview') || !window.location.search.includes('page=')) {
        setInterval(refreshDashboardStats, 120000);
    }
}

function refreshDashboardStats() {
    fetch('ajax/get-dashboard-stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.stats) {
                Object.keys(data.stats).forEach(key => {
                    const element = document.querySelector(`[data-stat="${key}"], .stat-${key}`);
                    if (element) {
                        const newValue = data.stats[key];
                        if (element.textContent !== newValue.toString()) {
                            animateStatUpdate(element, newValue);
                        }
                    }
                });
                
                const lastUpdate = document.getElementById('last-stats-update');
                if (lastUpdate) {
                    lastUpdate.textContent = 'Zuletzt aktualisiert: ' + new Date().toLocaleTimeString();
                }
            }
        })
        .catch(error => {
            console.error('Stats refresh error:', error);
        });
}

function animateStatUpdate(element, newValue) {
    element.style.transform = 'scale(1.1)';
    element.style.color = '#10b981';
    
    setTimeout(() => {
        element.textContent = newValue;
        element.style.transform = 'scale(1)';
        element.style.color = '';
    }, 200);
}

// ========================================
// HILFSFUNKTIONEN
// ========================================

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text || '';
    return div.innerHTML;
}

function formatDateTime(dateString) {
    if (!dateString) return 'Unbekannt';
    
    try {
        return new Date(dateString).toLocaleDateString('de-DE', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch (error) {
        return dateString;
    }
}

function showConfirmDialog(title, message, onConfirm, onCancel = null) {
    const overlay = document.createElement('div');
    overlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        z-index: 2000;
        display: flex;
        align-items: center;
        justify-content: center;
        backdrop-filter: blur(5px);
        opacity: 0;
        transition: opacity 0.3s ease;
    `;
    
    const dialog = document.createElement('div');
    dialog.style.cssText = `
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.05));
        border: 1px solid rgba(255, 68, 68, 0.3);
        border-radius: 16px;
        padding: 2rem;
        max-width: 450px;
        width: 90%;
        backdrop-filter: blur(20px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        text-align: center;
        transform: scale(0.9);
        transition: transform 0.3s ease;
    `;
    
    dialog.innerHTML = `
        <h3 style="color: #ff4444; margin-bottom: 1rem; font-size: 1.3rem;">${title}</h3>
        <p style="color: white; margin-bottom: 2rem; line-height: 1.5;">${message}</p>
        <div style="display: flex; gap: 1rem; justify-content: center;">
            <button class="btn btn-secondary" id="cancelButton" style="padding: 0.75rem 1.5rem; border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 8px; background: rgba(255, 255, 255, 0.1); color: white; font-weight: 600; cursor: pointer; transition: all 0.3s ease;">
                ❌ Abbrechen
            </button>
            <button class="btn btn-primary" id="confirmButton" style="padding: 0.75rem 1.5rem; border: none; border-radius: 8px; background: linear-gradient(135deg, #ff4444, #cc0000); color: white; font-weight: 600; cursor: pointer; transition: all 0.3s ease;">
                ✅ Bestätigen
            </button>
        </div>
    `;
    
    overlay.appendChild(dialog);
    document.body.appendChild(overlay);
    
    setTimeout(() => {
        overlay.style.opacity = '1';
        dialog.style.transform = 'scale(1)';
    }, 10);
    
    const confirmBtn = dialog.querySelector('#confirmButton');
    const cancelBtn = dialog.querySelector('#cancelButton');
    
    const closeDialog = () => {
        overlay.style.opacity = '0';
        dialog.style.transform = 'scale(0.9)';
        setTimeout(() => {
            if (overlay.parentNode) {
                overlay.parentNode.removeChild(overlay);
            }
        }, 300);
    };
    
    confirmBtn.addEventListener('click', () => {
        closeDialog();
        if (onConfirm) onConfirm();
    });
    
    cancelBtn.addEventListener('click', () => {
        closeDialog();
        if (onCancel) onCancel();
    });
    
    const escapeHandler = (e) => {
        if (e.key === 'Escape') {
            closeDialog();
            if (onCancel) onCancel();
            document.removeEventListener('keydown', escapeHandler);
        }
    };
    document.addEventListener('keydown', escapeHandler);
    
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) {
            closeDialog();
            if (onCancel) onCancel();
        }
    });
    
    setTimeout(() => confirmBtn.focus(), 100);
}

function exportApplicationsCSV() {
    const statusFilter = document.getElementById('statusFilter')?.value || '';
    const params = new URLSearchParams({
        format: 'csv',
        status: statusFilter
    });
    
    showNotification('📥 CSV-Export wird vorbereitet...', 'info', 3000);
    
    const link = document.createElement('a');
    link.href = `ajax/export-applications.php?${params.toString()}`;
    link.download = `bewerbungen-${new Date().toISOString().split('T')[0]}.csv`;
    link.style.display = 'none';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// ========================================
// KEYBOARD SHORTCUTS
// ========================================

function setupKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        if (document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA') {
            
            if (e.key.toLowerCase() === 'n' && !e.ctrlKey && !e.metaKey) {
                e.preventDefault();
                const addButton = document.querySelector('[onclick*="Modal"]:not([onclick*="edit"])');
                if (addButton) addButton.click();
            }
            
            if (e.key.toLowerCase() === 'r' && !e.ctrlKey && !e.metaKey) {
                e.preventDefault();
                refreshDashboardStats();
                showNotification('🔄 Daten aktualisiert', 'info', 2000);
            }
            
            if (e.key === '?') {
                e.preventDefault();
                showKeyboardShortcuts();
            }
        }
        
        if (e.ctrlKey || e.metaKey) {
            switch (e.key.toLowerCase()) {
                case 's':
                    e.preventDefault();
                    const activeForm = document.querySelector('.modal:not([style*="display: none"]) form, form:focus-within');
                    if (activeForm) {
                        activeForm.dispatchEvent(new Event('submit', { bubbles: true }));
                    }
                    break;
                    
                case 'e':
                    e.preventDefault();
                    exportApplicationsCSV();
                    break;
            }
        }
        
        if (e.key === 'Escape') {
            const openModal = document.querySelector('.modal:not([style*="display: none"])');
            if (openModal) {
                const modalId = openModal.id;
                if (modalId) {
                    closeModal(modalId);
                }
            }
        }
    });
}

function showKeyboardShortcuts() {
    const shortcuts = `
        <div style="text-align: left; line-height: 1.6;">
            <p><strong>N</strong> - Neues Element hinzufügen</p>
            <p><strong>R</strong> - Statistiken aktualisieren</p>
            <p><strong>Ctrl+S</strong> - Formular speichern</p>
            <p><strong>Ctrl+E</strong> - CSV exportieren</p>
            <p><strong>Esc</strong> - Modal schließen</p>
            <p><strong>?</strong> - Diese Hilfe anzeigen</p>
        </div>
    `;
    
    showConfirmDialog('⌨️ Tastaturkürzel', shortcuts, () => {}, () => {});
}

// ========================================
// ANIMATIONEN UND SETUP
// ========================================

function setupNavigationEffects() {
    const navButtons = document.querySelectorAll('.nav-button');
    
    navButtons.forEach((button, index) => {
        button.style.opacity = '0';
        button.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            button.style.transition = 'all 0.6s ease';
            button.style.opacity = '1';
            button.style.transform = 'translateY(0)';
        }, index * 100);
        
        button.addEventListener('mouseenter', function() {
            if (!this.classList.contains('active')) {
                this.style.transform = 'translateY(-3px) scale(1.02)';
                this.style.boxShadow = '0 8px 25px rgba(255, 68, 68, 0.2)';
            }
        });
        
        button.addEventListener('mouseleave', function() {
            if (!this.classList.contains('active')) {
                this.style.transform = 'translateY(0) scale(1)';
                this.style.boxShadow = '';
            }
        });
    });
}

function setupStatCardAnimations() {
    const statCards = document.querySelectorAll('.stat-card');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry, index) => {
            if (entry.isIntersecting) {
                setTimeout(() => {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                    animateStatNumber(entry.target);
                }, index * 150);
            }
        });
    }, { threshold: 0.1 });
    
    statCards.forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        card.style.transition = 'all 0.6s ease';
        observer.observe(card);
    });
}

function animateStatNumber(card) {
    const numberElement = card.querySelector('h3, .stat-number');
    if (!numberElement) return;
    
    const text = numberElement.textContent;
    const numbers = text.match(/\d+/g);
    
    if (numbers && numbers.length > 0) {
        const targetNumber = parseInt(numbers[0]);
        let currentNumber = 0;
        const increment = Math.ceil(targetNumber / 30);
        
        const timer = setInterval(() => {
            currentNumber += increment;
            if (currentNumber >= targetNumber) {
                currentNumber = targetNumber;
                clearInterval(timer);
            }
            
            numberElement.textContent = text.replace(/\d+/, currentNumber.toLocaleString());
        }, 50);
    }
}

function setupFormValidation() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
                showNotification('❌ Bitte füllen Sie alle erforderlichen Felder korrekt aus.', 'error');
            }
        });
        
        const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                validateField(this);
            });
            
            input.addEventListener('input', function() {
                clearFieldError(this);
            });
        });
    });
}

function validateForm(form) {
    const requiredFields = form.querySelectorAll('input[required], textarea[required], select[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!validateField(field)) {
            isValid = false;
        }
    });
    
    return isValid;
}

function validateField(field) {
    const value = field.value.trim();
    let isValid = true;
    
    if (field.hasAttribute('required') && !value) {
        showFieldError(field, 'Dieses Feld ist erforderlich.');
        isValid = false;
    }
    
    if (field.type === 'email' && value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            showFieldError(field, 'Bitte geben Sie eine gültige E-Mail-Adresse ein.');
            isValid = false;
        }
    }
    
    if (field.type === 'date' && value && field.id === 'appointment_date') {
        const selectedDate = new Date(value);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        if (selectedDate < today) {
            showFieldError(field, 'Das Datum kann nicht in der Vergangenheit liegen.');
            isValid = false;
        }
    }
    
    if (isValid) {
        clearFieldError(field);
    }
    
    return isValid;
}

function showFieldError(field, message) {
    clearFieldError(field);
    
    field.style.borderColor = '#ef4444';
    field.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.1)';
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error';
    errorDiv.style.cssText = `
        color: #ef4444;
        font-size: 0.8rem;
        margin-top: 0.25rem;
    `;
    errorDiv.textContent = message;
    
    field.parentNode.appendChild(errorDiv);
}

function clearFieldError(field) {
    field.style.borderColor = '';
    field.style.boxShadow = '';
    
    const errorDiv = field.parentNode.querySelector('.field-error');
    if (errorDiv) {
        errorDiv.remove();
    }
}

function setupAutoHideFlashMessages() {
    setTimeout(() => {
        const flashMessages = document.querySelector('.flash-messages, .alert');
        if (flashMessages) {
            flashMessages.style.transition = 'opacity 0.5s ease';
            flashMessages.style.opacity = '0';
            setTimeout(() => {
                if (flashMessages.parentNode) {
                    flashMessages.remove();
                }
            }, 500);
        }
    }, 5000);
}

function setupModalEvents() {
    window.addEventListener('click', function(event) {
        if (event.target.classList.contains('modal')) {
            const modalId = event.target.id;
            if (modalId) {
                closeModal(modalId);
            }
        }
    });
}

function setupTableInteractions() {
    const tableRows = document.querySelectorAll('.table tbody tr, table tbody tr');
    
    tableRows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.01)';
            this.style.transition = 'all 0.3s ease';
            this.style.backgroundColor = 'rgba(255, 68, 68, 0.05)';
        });
        
        row.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
            this.style.backgroundColor = '';
        });
    });
}

function setupWhitelistFunctions() {
    const statusFilter = document.getElementById('statusFilter');
    if (statusFilter) {
        statusFilter.addEventListener('change', filterApplications);
    }
    
    const questionTypeSelects = document.querySelectorAll('[id*="question_type"]');
    questionTypeSelects.forEach(select => {
        select.addEventListener('change', function() {
            const prefix = this.id.includes('edit_') ? 'edit' : '';
            toggleQuestionType(prefix);
        });
    });
}

function filterApplications() {
    const filter = document.getElementById('statusFilter');
    const table = document.getElementById('applicationsTable') || document.querySelector('.table');
    const totalCountElement = document.getElementById('totalCount');
    
    if (!filter || !table) return;
    
    const filterValue = filter.value;
    const rows = table.querySelectorAll('tbody tr');
    let visibleCount = 0;
    
    rows.forEach(row => {
        const status = row.getAttribute('data-status') || 
                      row.querySelector('.status-badge')?.textContent?.toLowerCase();
        
        if (!filterValue || status === filterValue || (status && status.includes(filterValue))) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    if (totalCountElement) {
        totalCountElement.textContent = visibleCount;
    }
}

// ========================================
// LEGACY FUNKTIONEN (für Kompatibilität)
// ========================================

function editRule(rule) {
    if (typeof rule === 'object') {
        document.getElementById('edit_rule_id').value = rule.id;
        document.getElementById('edit_rule_title').value = rule.rule_title;
        document.getElementById('edit_rule_content').value = rule.rule_content;
        document.getElementById('edit_rule_order').value = rule.rule_order;
        document.getElementById('edit_is_active').checked = rule.is_active == 1;
        openModal('editRuleModal');
    }
}

function deleteRule(id) {
    showConfirmDialog(
        '🗑️ Regel löschen',
        'Sind Sie sicher, dass Sie diese Regel löschen möchten?',
        () => {
            submitForm('delete_rule', { rule_id: id });
        }
    );
}

function editNews(article) {
    if (typeof article === 'object') {
        document.getElementById('edit_news_id').value = article.id;
        document.getElementById('edit_news_title').value = article.title;
        document.getElementById('edit_news_content').value = article.content;
        document.getElementById('edit_news_published').checked = article.is_published == 1;
        openModal('editNewsModal');
    }
}

function deleteNews(id) {
    showConfirmDialog(
        '🗑️ Artikel löschen',
        'Sind Sie sicher, dass Sie diesen Artikel löschen möchten?',
        () => {
            submitForm('delete_news', { news_id: id });
        }
    );
}

function editQuestion(question) {
    if (typeof question === 'object') {
        document.getElementById('edit_question_id').value = question.id;
        document.getElementById('edit_question').value = question.question;
        document.getElementById('edit_question_type').value = question.question_type;
        document.getElementById('edit_question_order').value = question.question_order;
        document.getElementById('edit_question_required').checked = question.is_required == 1;
        document.getElementById('edit_question_active').checked = question.is_active == 1;
        
        const optionsContainer = document.getElementById('edit_options_container');
        if (optionsContainer) {
            const optionsInputs = optionsContainer.querySelectorAll('input[name="options[]"]');
            
            optionsInputs.forEach(input => input.value = '');
            
            if (question.question_type === 'multiple_choice' && question.options) {
                try {
                    const options = JSON.parse(question.options);
                    options.forEach((option, index) => {
                        if (optionsInputs[index]) {
                            optionsInputs[index].value = option;
                        }
                    });
                } catch (e) {
                    console.error('Error parsing question options:', e);
                }
            }
        }
        
        toggleQuestionType('edit');
        openModal('editQuestionModal');
    }
}

function deleteQuestion(id) {
    showConfirmDialog(
        '🗑️ Frage löschen',
        'Sind Sie sicher, dass Sie diese Frage löschen möchten?',
        () => {
            submitForm('delete_whitelist_question', { question_id: id });
        }
    );
}

function toggleQuestionType(prefix = '') {
    const typeSelect = document.getElementById(prefix + (prefix ? '_' : '') + 'question_type');
    const optionsContainer = document.getElementById(prefix + (prefix ? '_' : '') + 'options_container');
    
    if (typeSelect && optionsContainer) {
        if (typeSelect.value === 'multiple_choice') {
            optionsContainer.style.display = 'block';
            
            const optionsInputs = optionsContainer.querySelectorAll('input[name="options[]"]');
            if (optionsInputs.length >= 2) {
                optionsInputs[0].required = true;
                optionsInputs[1].required = true;
            }
        } else {
            optionsContainer.style.display = 'none';
            
            const optionsInputs = optionsContainer.querySelectorAll('input[name="options[]"]');
            optionsInputs.forEach(input => {
                input.required = false;
                input.value = '';
            });
        }
    }
}

function submitForm(action, data) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.style.display = 'none';
    
    const csrfToken = document.querySelector('input[name="csrf_token"]')?.value;
    
    form.innerHTML = `
        <input type="hidden" name="action" value="${action}">
        <input type="hidden" name="csrf_token" value="${csrfToken || ''}">
    `;
    
    Object.entries(data).forEach(([key, value]) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = value;
        form.appendChild(input);
    });
    
    document.body.appendChild(form);
    form.submit();
}

function updatePlayerCount() {
    const statusElement = document.querySelector('.stat-card h3');
    if (statusElement && statusElement.textContent.includes('/')) {
        const current = parseInt(statusElement.textContent.split('/')[0]);
        const max = parseInt(statusElement.textContent.split('/')[1]);
        const variation = Math.floor(Math.random() * 6) - 3;
        const newCount = Math.max(0, Math.min(max, current + variation));
        
        if (newCount !== current) {
            statusElement.style.transform = 'scale(1.1)';
            statusElement.style.color = '#10b981';
            
            setTimeout(() => {
                statusElement.textContent = `${newCount}/${max}`;
                statusElement.style.transform = 'scale(1)';
                statusElement.style.color = '';
            }, 150);
        }
    }
}

function copyToClipboard(text, successMessage = 'In Zwischenablage kopiert!') {
    navigator.clipboard.writeText(text).then(() => {
        showNotification(successMessage, 'success', 3000);
    }).catch(err => {
        console.error('Clipboard copy failed:', err);
        showNotification('Kopieren fehlgeschlagen', 'error');
    });
}

// ========================================
// CSS STYLES
// ========================================

function addStyles() {
    if (document.getElementById('admin-enhanced-styles')) return;
    
    const adminStyles = document.createElement('style');
    adminStyles.id = 'admin-enhanced-styles';
    adminStyles.textContent = `
        @keyframes progressBar {
            from { width: 100%; }
            to { width: 0%; }
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .quick-time.selected {
            background: rgba(255, 68, 68, 0.3) !important;
            border-color: #ff4444 !important;
            transform: scale(1.05) !important;
        }
        
        .btn.loading::after {
            content: "";
            position: absolute;
            left: 50%;
            top: 50%;
            width: 16px;
            height: 16px;
            margin: -8px 0 0 -8px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        .notification {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            border-left: 4px solid rgba(255, 255, 255, 0.3);
        }
        
        .btn:hover:not(:disabled) {
            transform: translateY(-2px);
        }
        
        .btn-primary:hover:not(:disabled) {
            box-shadow: 0 8px 25px rgba(255, 68, 68, 0.3);
        }
        
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.2);
        }
    `;
    
    document.head.appendChild(adminStyles);
}

// Player count update alle 30 Sekunden
setInterval(updatePlayerCount, 30000);

console.log('🎯 Admin.js vollständig geladen!');