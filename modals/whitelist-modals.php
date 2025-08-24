<!-- Termin-Modal (Dieses Modal fehlt komplett und muss hinzugefügt werden!) -->
<div id="appointmentModal" class="modal">
    <div class="modal-content" style="max-width: 600px;">
        <button class="close-btn" onclick="closeModal('appointmentModal')" aria-label="Modal schließen">&times;</button>
        <div class="modal-header">
            <h3 class="modal-title">📅 Termin-Nachricht senden</h3>
        </div>
        
        <form id="appointmentForm" onsubmit="return false;">
            <input type="hidden" id="appointment_application_id" name="application_id">
            
            <!-- Benutzer-Information -->
            <div id="appointment_user_info" style="background: rgba(88, 101, 242, 0.1); border: 1px solid rgba(88, 101, 242, 0.3); border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem;">
                <!-- Wird per JavaScript gefüllt -->
            </div>
            
            <!-- Termin-Details -->
            <h4 style="color: var(--primary); margin-bottom: 1rem;">📅 Termin-Details</h4>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div class="form-group">
                    <label for="appointment_date">📅 Datum</label>
                    <input type="date" 
                           id="appointment_date" 
                           name="appointment_date" 
                           class="form-control" 
                           required>
                </div>
                
                <div class="form-group">
                    <label for="appointment_time">⏰ Uhrzeit</label>
                    <input type="time" 
                           id="appointment_time" 
                           name="appointment_time" 
                           class="form-control" 
                           required>
                </div>
            </div>
            
            <!-- Nachricht -->
            <div class="form-group">
                <label for="appointment_custom_message">💬 Persönliche Nachricht (optional)</label>
                <textarea id="appointment_custom_message" 
                          name="custom_message" 
                          class="form-control" 
                          rows="4" 
                          placeholder="Geben Sie eine persönliche Nachricht ein oder lassen Sie das Feld leer für die Standard-Nachricht..."
                          maxlength="500"></textarea>
                <small style="color: var(--gray); font-size: 0.8rem;">
                    Leer lassen für Standard-Nachricht. Platzhalter: {username}, {server_name}, {appointment_date}, {appointment_time}
                </small>
            </div>
            
            <!-- Discord Bot Status -->
            <div id="discord_bot_status" style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 8px; padding: 1rem; margin: 1rem 0;">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <span id="bot_status_icon">✅</span>
                    <span id="bot_status_text">Discord Bot ist bereit</span>
                </div>
            </div>
            
            <!-- Vorschau der Standard-Nachricht -->
            <details style="margin-bottom: 1.5rem;">
                <summary style="cursor: pointer; color: var(--primary); font-weight: bold;">📋 Standard-Nachricht Vorschau</summary>
                <div style="background: rgba(255, 255, 255, 0.05); border-radius: 8px; padding: 1rem; margin-top: 0.5rem; white-space: pre-line; font-family: monospace; font-size: 0.9rem; color: var(--gray);">
Hallo {username}!

Deine Whitelist-Bewerbung wurde geprüft und du bist für ein Gespräch vorgesehen.

Termin: {appointment_date}
Uhrzeit: {appointment_time}

Bitte melde dich zur angegebenen Zeit im Discord-Channel #whitelist-gespräche.

Viel Erfolg!
Dein {server_name} Team
                </div>
            </details>
            
            <!-- Warnung -->
            <div style="background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem; color: #f59e0b;">
                <div style="display: flex; align-items: flex-start; gap: 0.5rem;">
                    <span style="font-size: 1.2rem;">⚠️</span>
                    <div>
                        <strong>Wichtige Hinweise:</strong>
                        <ul style="margin: 0.5rem 0 0 1rem; padding: 0;">
                            <li>Die Nachricht wird als Discord Direct Message gesendet</li>
                            <li>Der Bewerbungsstatus wird automatisch auf "Geschlossen" gesetzt</li>
                            <li>Diese Aktion kann nicht rückgängig gemacht werden</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                <button type="button" 
                        onclick="closeModal('appointmentModal')" 
                        class="btn btn-secondary">
                    ❌ Abbrechen
                </button>
                <button type="submit" 
                        id="sendAppointmentBtn" 
                        class="btn btn-primary">
                    📤 Termin-Nachricht senden
                </button>
            </div>
        </form>
    </div>
</div>

<style>
/* Zusätzliche Styles für das Appointment Modal */
#appointmentModal .form-control {
    margin-bottom: 0.5rem;
}

#appointment_user_info {
    animation: fadeIn 0.5s ease;
}

#sendAppointmentBtn.loading {
    opacity: 0.7;
    cursor: not-allowed;
}

#sendAppointmentBtn.loading::before {
    content: "⏳ ";
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Discord Bot Status Styles */
.bot-status-online {
    background: rgba(16, 185, 129, 0.1) !important;
    border-color: rgba(16, 185, 129, 0.3) !important;
}

.bot-status-offline {
    background: rgba(239, 68, 68, 0.1) !important;
    border-color: rgba(239, 68, 68, 0.3) !important;
    color: #ef4444 !important;
}

.bot-status-warning {
    background: rgba(245, 158, 11, 0.1) !important;
    border-color: rgba(245, 158, 11, 0.3) !important;
    color: #f59e0b !important;
}

/* Character counter for message */
.char-counter {
    text-align: right;
    font-size: 0.8rem;
    color: var(--gray);
    margin-top: 0.25rem;
}

.char-counter.warning {
    color: var(--warning);
}

.char-counter.danger {
    color: var(--danger);
}

/* Responsive adjustments for appointment modal */
@media (max-width: 768px) {
    #appointmentModal .modal-content {
        padding: 1.5rem;
        margin: 1rem;
        width: calc(100% - 2rem);
    }
    
    #appointmentModal .form-group {
        grid-column: 1 / -1;
    }
    
    #appointmentModal .form-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// Zusätzliche JavaScript-Funktionen für das Appointment Modal

/**
 * Discord Bot Status prüfen
 */
function checkDiscordBotStatus() {
    fetch('ajax/check-discord-bot.php')
        .then(response => response.json())
        .then(data => {
            const statusContainer = document.getElementById('discord_bot_status');
            const statusIcon = document.getElementById('bot_status_icon');
            const statusText = document.getElementById('bot_status_text');
            
            if (data.success && data.bot_available) {
                statusContainer.className = 'bot-status-online';
                statusIcon.textContent = '✅';
                statusText.textContent = 'Discord Bot ist bereit';
            } else {
                statusContainer.className = 'bot-status-offline';
                statusIcon.textContent = '❌';
                statusText.textContent = data.error || 'Discord Bot ist nicht verfügbar';
            }
        })
        .catch(error => {
            console.error('Error checking bot status:', error);
            const statusContainer = document.getElementById('discord_bot_status');
            const statusIcon = document.getElementById('bot_status_icon');
            const statusText = document.getElementById('bot_status_text');
            
            statusContainer.className = 'bot-status-warning';
            statusIcon.textContent = '⚠️';
            statusText.textContent = 'Bot-Status konnte nicht geprüft werden';
        });
}

/**
 * Character counter für Custom Message
 */
function setupAppointmentMessageCounter() {
    const textarea = document.getElementById('appointment_custom_message');
    const maxLength = parseInt(textarea.getAttribute('maxlength')) || 500;
    
    // Create counter element
    const counter = document.createElement('div');
    counter.className = 'char-counter';
    textarea.parentNode.appendChild(counter);
    
    function updateCounter() {
        const currentLength = textarea.value.length;
        const remaining = maxLength - currentLength;
        counter.textContent = `${currentLength}/${maxLength} Zeichen`;
        
        counter.classList.remove('warning', 'danger');
        if (remaining < 50) {
            counter.classList.add('warning');
        }
        if (remaining < 20) {
            counter.classList.add('danger');
        }
    }
    
    updateCounter();
    textarea.addEventListener('input', updateCounter);
}

/**
 * Termin-Modal initialisieren
 */
document.addEventListener('DOMContentLoaded', function() {
    // Character counter setup
    if (document.getElementById('appointment_custom_message')) {
        setupAppointmentMessageCounter();
    }
    
    // Form validation enhancement für Appointment
    const appointmentForm = document.getElementById('appointmentForm');
    if (appointmentForm) {
        appointmentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('sendAppointmentBtn');
            const originalText = submitBtn.textContent;
            
            // Validation
            const date = document.getElementById('appointment_date').value;
            const time = document.getElementById('appointment_time').value;
            
            if (!date || !time) {
                alert('Bitte wählen Sie Datum und Uhrzeit aus.');
                return false;
            }
            
            // Check if date is not in the past
            const selectedDateTime = new Date(date + 'T' + time);
            const now = new Date();
            
            if (selectedDateTime <= now) {
                if (!confirm('Der gewählte Termin liegt in der Vergangenheit oder ist sehr bald. Trotzdem fortfahren?')) {
                    return false;
                }
            }
            
            // Button in Loading state
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Wird gesendet...';
            
            // Collect form data
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());
            
            // Send AJAX request
            fetch('ajax/send-appointment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showNotification('✅ Termin-Nachricht erfolgreich gesendet!', 'success');
                    closeModal('appointmentModal');
                    
                    // Refresh page to show updated application status
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    alert('Fehler beim Senden der Nachricht:\n' + result.error);
                }
            })
            .catch(error => {
                console.error('Error sending appointment:', error);
                alert('Fehler beim Senden der Nachricht. Bitte versuchen Sie es erneut.');
            })
            .finally(() => {
                // Reset button
                submitBtn.classList.remove('loading');
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            });
        });
    }
});

/**
 * Enhanced sendAppointment function (ergänzt die bestehende)
 */
function sendAppointmentMessage(applicationId) {
    // Erst prüfen ob das Modal existiert
    const modal = document.getElementById('appointmentModal');
    if (!modal) {
        alert('Fehler: Appointment Modal nicht gefunden. Bitte prüfen Sie die whitelist-modals.php Datei.');
        return;
    }
    
    // Load application data
    fetch(`ajax/get-application-details.php?id=${applicationId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const application = data.application;
                
                // Fill modal fields
                document.getElementById('appointment_application_id').value = applicationId;
                
                // Show user info
                const userInfo = document.getElementById('appointment_user_info');
                userInfo.innerHTML = `
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        ${application.discord_avatar ? 
                            `<img src="${application.discord_avatar}" style="width: 48px; height: 48px; border-radius: 50%;" alt="Avatar">` :
                            `<div style="width: 48px; height: 48px; border-radius: 50%; background: #5865f2; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 1rem;">${application.discord_username.substring(0, 2).toUpperCase()}</div>`
                        }
                        <div>
                            <h4 style="margin: 0; color: var(--primary);">${application.discord_username}</h4>
                            <p style="margin: 0; color: var(--gray); font-size: 0.9rem;">Discord ID: ${application.discord_id}</p>
                            <p style="margin: 0; color: var(--gray); font-size: 0.9rem;">Score: ${application.score_percentage}% (${application.correct_answers}/${application.total_questions} richtig)</p>
                        </div>
                    </div>
                `;
                
                // Set default date and time (tomorrow at 8 PM)
                const tomorrow = new Date();
                tomorrow.setDate(tomorrow.getDate() + 1);
                document.getElementById('appointment_date').value = tomorrow.toISOString().split('T')[0];
                document.getElementById('appointment_time').value = '20:00';
                
                // Clear custom message
                document.getElementById('appointment_custom_message').value = '';
                
                // Check Discord bot status
                checkDiscordBotStatus();
                
                // Open modal
                openModal('appointmentModal');
                
            } else {
                alert('Fehler beim Laden der Bewerbungsdaten: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error loading application details:', error);
            alert('Fehler beim Laden der Bewerbungsdaten');
        });
}

// Fallback function falls sendAppointment noch verwendet wird
function sendAppointment(applicationId) {
    sendAppointmentMessage(applicationId);
}
</script>