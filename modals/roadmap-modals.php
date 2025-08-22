<?php
/**
 * Roadmap Management Modals
 * Datei: modals/roadmap-modals.php
 */
?>

<!-- Add Roadmap Item Modal -->
<div id="addRoadmapModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">🗺️ Neuen Roadmap-Eintrag hinzufügen</h3>
            <button class="close-modal" onclick="closeModal('addRoadmapModal')">&times;</button>
        </div>
        
        <form method="POST" action="" id="addRoadmapForm">
            <input type="hidden" name="action" value="add_roadmap_item">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div class="modal-body">
                <div class="form-group">
                    <label for="roadmap_title">🎯 Titel *</label>
                    <input type="text" 
                           id="roadmap_title" 
                           name="roadmap_title" 
                           class="form-control" 
                           required 
                           maxlength="255"
                           placeholder="z.B. Erweiterte Base Building">
                </div>
                
                <div class="form-group">
                    <label for="roadmap_description">📝 Beschreibung *</label>
                    <textarea id="roadmap_description" 
                              name="roadmap_description" 
                              class="form-control" 
                              required 
                              rows="4"
                              placeholder="Detaillierte Beschreibung des Features oder Updates..."></textarea>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label for="roadmap_status">📊 Status</label>
                        <select id="roadmap_status" name="roadmap_status" class="form-control" required>
                            <option value="planned">📋 Geplant</option>
                            <option value="in_progress">⚙️ In Arbeit</option>
                            <option value="testing">🧪 Testing</option>
                            <option value="completed">✅ Abgeschlossen</option>
                            <option value="cancelled">❌ Abgebrochen</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="roadmap_priority">📈 Priorität</label>
                        <select id="roadmap_priority" name="roadmap_priority" class="form-control" required>
                            <option value="1">🔥 1 - Sehr hoch</option>
                            <option value="2">🟠 2 - Hoch</option>
                            <option value="3" selected>🟡 3 - Normal</option>
                            <option value="4">🔵 4 - Niedrig</option>
                            <option value="5">⚪ 5 - Sehr niedrig</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="roadmap_estimated_date">📅 Geschätztes Datum (optional)</label>
                    <input type="date" 
                           id="roadmap_estimated_date" 
                           name="roadmap_estimated_date" 
                           class="form-control"
                           min="<?php echo date('Y-m-d'); ?>">
                    <small style="color: var(--gray);">Lassen Sie das Feld leer, wenn kein Datum feststeht</small>
                </div>
                
                <div class="form-group">
                    <div class="priority-info" style="background: rgba(255, 68, 68, 0.1); border: 1px solid rgba(255, 68, 68, 0.3); border-radius: 8px; padding: 1rem; margin-top: 1rem;">
                        <h4 style="color: #ff4444; margin: 0 0 0.5rem 0;">💡 Prioritätsleitfaden</h4>
                        <ul style="margin: 0; padding-left: 1.5rem; color: #ccc;">
                            <li><strong>Priorität 1:</strong> Kritische Features, Bugfixes oder Updates</li>
                            <li><strong>Priorität 2:</strong> Wichtige Features für das Gameplay</li>
                            <li><strong>Priorität 3:</strong> Standard-Features und Verbesserungen</li>
                            <li><strong>Priorität 4:</strong> Nice-to-have Features</li>
                            <li><strong>Priorität 5:</strong> Zukunftsideen und Experimente</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" onclick="closeModal('addRoadmapModal')" class="btn btn-secondary">
                    ❌ Abbrechen
                </button>
                <button type="submit" class="btn btn-primary">
                    ➕ Roadmap-Eintrag hinzufügen
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Roadmap Item Modal -->
<div id="editRoadmapModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">✏️ Roadmap-Eintrag bearbeiten</h3>
            <button class="close-modal" onclick="closeModal('editRoadmapModal')">&times;</button>
        </div>
        
        <form method="POST" action="" id="editRoadmapForm">
            <input type="hidden" name="action" value="update_roadmap_item">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" id="edit_roadmap_id" name="roadmap_id">
            
            <div class="modal-body">
                <div class="form-group">
                    <label for="edit_roadmap_title">🎯 Titel *</label>
                    <input type="text" 
                           id="edit_roadmap_title" 
                           name="roadmap_title" 
                           class="form-control" 
                           required 
                           maxlength="255">
                </div>
                
                <div class="form-group">
                    <label for="edit_roadmap_description">📝 Beschreibung *</label>
                    <textarea id="edit_roadmap_description" 
                              name="roadmap_description" 
                              class="form-control" 
                              required 
                              rows="4"></textarea>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label for="edit_roadmap_status">📊 Status</label>
                        <select id="edit_roadmap_status" name="roadmap_status" class="form-control" required>
                            <option value="planned">📋 Geplant</option>
                            <option value="in_progress">⚙️ In Arbeit</option>
                            <option value="testing">🧪 Testing</option>
                            <option value="completed">✅ Abgeschlossen</option>
                            <option value="cancelled">❌ Abgebrochen</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_roadmap_priority">📈 Priorität</label>
                        <select id="edit_roadmap_priority" name="roadmap_priority" class="form-control" required>
                            <option value="1">🔥 1 - Sehr hoch</option>
                            <option value="2">🟠 2 - Hoch</option>
                            <option value="3">🟡 3 - Normal</option>
                            <option value="4">🔵 4 - Niedrig</option>
                            <option value="5">⚪ 5 - Sehr niedrig</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="edit_roadmap_estimated_date">📅 Geschätztes Datum (optional)</label>
                    <input type="date" 
                           id="edit_roadmap_estimated_date" 
                           name="roadmap_estimated_date" 
                           class="form-control">
                </div>
                
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" id="edit_roadmap_active" name="is_active" checked>
                        <span>✅ Roadmap-Eintrag ist aktiv und wird auf der Website angezeigt</span>
                    </label>
                </div>
                
                <div class="status-info" style="background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 8px; padding: 1rem; margin-top: 1rem;">
                    <h4 style="color: #3b82f6; margin: 0 0 0.5rem 0;">📊 Status-Erklärung</h4>
                    <ul style="margin: 0; padding-left: 1.5rem; color: #ccc; font-size: 0.9rem;">
                        <li><strong>Geplant:</strong> Feature ist für die Zukunft vorgesehen</li>
                        <li><strong>In Arbeit:</strong> Entwicklung läuft aktiv</li>
                        <li><strong>Testing:</strong> Feature wird getestet und optimiert</li>
                        <li><strong>Abgeschlossen:</strong> Feature ist live und verfügbar</li>
                        <li><strong>Abgebrochen:</strong> Feature wird nicht umgesetzt</li>
                    </ul>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" onclick="closeModal('editRoadmapModal')" class="btn btn-secondary">
                    ❌ Abbrechen
                </button>
                <button type="submit" class="btn btn-primary">
                    💾 Änderungen speichern
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Appointment Scheduler Modal -->
<div id="appointmentModal" class="modal">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h3 class="modal-title">📅 Termin senden</h3>
            <button class="close-modal" onclick="closeModal('appointmentModal')">&times;</button>
        </div>
        
        <form id="appointmentForm">
            <input type="hidden" id="appointment_application_id" name="application_id">
            
            <div class="modal-body">
                <div class="appointment-info" style="background: rgba(255, 68, 68, 0.1); border: 1px solid rgba(255, 68, 68, 0.3); border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem;">
                    <h4 style="color: #ff4444; margin: 0 0 0.5rem 0;">📋 Bewerbung</h4>
                    <div id="appointment_user_info">
                        <!-- Wird per JavaScript gefüllt -->
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div class="form-group">
                        <label for="appointment_date">📅 Termin-Datum</label>
                        <input type="date" 
                               id="appointment_date" 
                               name="appointment_date" 
                               class="form-control" 
                               min="<?php echo date('Y-m-d'); ?>"
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
                
                <div class="form-group">
                    <label for="appointment_custom_message">📧 Nachricht (optional - Standard-Vorlage wird verwendet wenn leer)</label>
                    <textarea id="appointment_custom_message" 
                              name="custom_message" 
                              class="form-control" 
                              rows="8"
                              placeholder="Hallo {username}!

Deine Whitelist-Bewerbung wurde geprüft und du bist für ein Gespräch vorgesehen.

Termin: {appointment_date}
Uhrzeit: {appointment_time}

Bitte melde dich zur angegebenen Zeit im Discord-Channel #whitelist-gespräche.

Viel Erfolg!
Dein {server_name} Team"></textarea>
                    <small style="color: var(--gray);">
                        Verfügbare Platzhalter: {username}, {server_name}, {appointment_date}, {appointment_time}, {appointment_datetime}
                    </small>
                </div>
                
                <div class="discord-status" style="background: rgba(88, 101, 242, 0.1); border: 1px solid rgba(88, 101, 242, 0.3); border-radius: 8px; padding: 1rem;">
                    <h4 style="color: #5865f2; margin: 0 0 0.5rem 0;">🤖 Discord Bot Status</h4>
                    <div id="discord_bot_status">
                        <span style="color: var(--gray);">⏳ Status wird überprüft...</span>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" onclick="closeModal('appointmentModal')" class="btn btn-secondary">
                    ❌ Abbrechen
                </button>
                <button type="submit" class="btn btn-primary" id="sendAppointmentBtn">
                    📧 Termin-Nachricht senden
                </button>
            </div>
        </form>
    </div>
</div>

<style>
/* Modal-spezifische Styles */
.modal .form-group {
    margin-bottom: 1rem;
}

.modal .form-control {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 6px;
    background: rgba(255, 255, 255, 0.05);
    color: white;
    font-size: 0.9rem;
}

.modal .form-control:focus {
    border-color: #ff4444;
    box-shadow: 0 0 0 2px rgba(255, 68, 68, 0.2);
    outline: none;
}

.modal label {
    display: block;
    margin-bottom: 0.5rem;
    color: #ff4444;
    font-weight: 600;
    font-size: 0.9rem;
}

.modal textarea {
    resize: vertical;
    min-height: 100px;
}

.modal select {
    cursor: pointer;
}

.modal input[type="date"],
.modal input[type="time"] {
    cursor: pointer;
}

.modal .priority-info,
.modal .status-info,
.modal .appointment-info,
.modal .discord-status {
    font-size: 0.9rem;
}

.modal .priority-info ul,
.modal .status-info ul {
    line-height: 1.5;
}

.modal .priority-info li,
.modal .status-info li {
    margin-bottom: 0.25rem;
}

/* Discord Bot Status Indicators */
.discord-status.online {
    border-color: rgba(16, 185, 129, 0.3);
    background: rgba(16, 185, 129, 0.1);
}

.discord-status.offline {
    border-color: rgba(239, 68, 68, 0.3);
    background: rgba(239, 68, 68, 0.1);
}

.discord-status.online h4 {
    color: #10b981;
}

.discord-status.offline h4 {
    color: #ef4444;
}

/* Loading animations */
.loading-spinner {
    display: inline-block;
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-radius: 50%;
    border-top-color: #ff4444;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

/* Form validation styles */
.form-control.error {
    border-color: #ef4444;
    box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.2);
}

.form-control.success {
    border-color: #10b981;
    box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2);
}

/* Responsive modal adjustments */
@media (max-width: 768px) {
    .modal .modal-content {
        margin: 1rem;
        max-width: calc(100vw - 2rem);
    }
    
    .modal .modal-body {
        padding: 1rem;
    }
    
    .modal div[style*="grid-template-columns"] {
        grid-template-columns: 1fr !important;
    }
}

/* Checkbox styling */
.modal input[type="checkbox"] {
    width: 18px;
    height: 18px;
    accent-color: #ff4444;
    cursor: pointer;
}

/* Button loading state */
.btn.loading {
    opacity: 0.7;
    cursor: not-allowed;
    position: relative;
}

.btn.loading::after {
    content: '';
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-radius: 50%;
    border-top-color: white;
    animation: spin 1s linear infinite;
}
</style>

<script>
// Roadmap Modal JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Auto-resize textareas
    document.querySelectorAll('textarea').forEach(textarea => {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    });
    
    // Form validation
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = this.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                field.classList.remove('error', 'success');
                
                if (!field.value.trim()) {
                    field.classList.add('error');
                    isValid = false;
                } else {
                    field.classList.add('success');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Bitte füllen Sie alle Pflichtfelder aus.');
            }
        });
    });
    
    // Date validation
    document.querySelectorAll('input[type="date"]').forEach(dateInput => {
        dateInput.addEventListener('change', function() {
            const selectedDate = new Date(this.value);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            if (selectedDate < today) {
                alert('Das Datum darf nicht in der Vergangenheit liegen.');
                this.value = '';
            }
        });
    });
});

// Check Discord Bot Status
function checkDiscordBotStatus() {
    const statusDiv = document.getElementById('discord_bot_status');
    const discordContainer = statusDiv.closest('.discord-status');
    
    statusDiv.innerHTML = '<span class="loading-spinner"></span> Status wird überprüft...';
    
    fetch('ajax/check-discord-bot.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                statusDiv.innerHTML = `
                    <div style="color: #10b981;">
                        ✅ Bot online: <strong>${data.bot_name}</strong>
                        <br><small>Nachrichten können gesendet werden</small>
                    </div>
                `;
                discordContainer.classList.add('online');
                discordContainer.classList.remove('offline');
            } else {
                statusDiv.innerHTML = `
                    <div style="color: #ef4444;">
                        ❌ Bot offline oder nicht konfiguriert
                        <br><small>${data.error}</small>
                    </div>
                `;
                discordContainer.classList.add('offline');
                discordContainer.classList.remove('online');
            }
        })
        .catch(error => {
            statusDiv.innerHTML = `
                <div style="color: #ef4444;">
                    ❌ Verbindungsfehler
                    <br><small>Bot-Status konnte nicht geprüft werden</small>
                </div>
            `;
            discordContainer.classList.add('offline');
            discordContainer.classList.remove('online');
        });
}

// Character counters for textareas
document.querySelectorAll('textarea').forEach(textarea => {
    const maxLength = textarea.getAttribute('maxlength');
    if (maxLength) {
        const counter = document.createElement('small');
        counter.style.color = 'var(--gray)';
        counter.style.display = 'block';
        counter.style.textAlign = 'right';
        counter.style.marginTop = '0.25rem';
        
        textarea.parentNode.appendChild(counter);
        
        function updateCounter() {
            const remaining = maxLength - textarea.value.length;
            counter.textContent = `${textarea.value.length}/${maxLength} Zeichen`;
            
            if (remaining < 50) {
                counter.style.color = '#f59e0b';
            } else if (remaining < 20) {
                counter.style.color = '#ef4444';
            } else {
                counter.style.color = 'var(--gray)';
            }
        }
        
        updateCounter();
        textarea.addEventListener('input', updateCounter);
    }
});
</script>