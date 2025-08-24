<!-- Erweiterte Whitelist Management Modals mit Termin-System -->

<!-- Add Question Modal -->
<div id="addQuestionModal" class="modal">
    <div class="modal-content">
        <button class="close-btn" onclick="closeModal('addQuestionModal')" aria-label="Modal schließen">&times;</button>
        <div class="modal-header">
            <h3 class="modal-title">➕ Neue Whitelist-Frage hinzufügen</h3>
        </div>
        <form method="POST" action="" id="addQuestionForm">
            <input type="hidden" name="action" value="add_whitelist_question">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div class="form-group">
                <label for="question">❓ Frage *</label>
                <textarea id="question" 
                          name="question" 
                          class="form-control" 
                          rows="3" 
                          placeholder="z.B. Wie alt bist du?"
                          required
                          maxlength="500"></textarea>
                <small style="color: var(--gray); font-size: 0.8rem;">
                    Die Frage, die dem Bewerber gestellt wird (max. 500 Zeichen)
                </small>
            </div>
            
            <div class="form-group">
                <label for="question_type">📝 Fragetyp</label>
                <select id="question_type" name="question_type" class="form-control" onchange="toggleQuestionType()">
                    <option value="text">✏️ Textfeld (freie Antwort)</option>
                    <option value="multiple_choice">📋 Multiple Choice (vorgegebene Optionen)</option>
                </select>
            </div>
            
            <div id="options_container" class="form-group" style="display: none;">
                <label>📋 Antwortmöglichkeiten (2-3 Optionen)</label>
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <input type="text" name="options[]" class="form-control" placeholder="Option 1" maxlength="100">
                    <input type="text" name="options[]" class="form-control" placeholder="Option 2" maxlength="100">
                    <input type="text" name="options[]" class="form-control" placeholder="Option 3 (optional)" maxlength="100">
                </div>
                <small style="color: var(--gray); font-size: 0.8rem;">
                    Mindestens 2, maximal 3 Antwortmöglichkeiten (je max. 100 Zeichen)
                </small>
            </div>
            
            <!-- Neue Sektion für richtige Antwort -->
            <div class="form-group">
                <label for="correct_answer">✅ Richtige Antwort / Bewertungskriterien</label>
                <textarea id="correct_answer" 
                          name="correct_answer" 
                          class="form-control" 
                          rows="2" 
                          placeholder="Für Multiple Choice: Exakte Antwort. Für Textfragen: Schlüsselwörter (kommagetrennt)"
                          maxlength="500"></textarea>
                <div id="correct_answer_help" style="color: var(--gray); font-size: 0.8rem; margin-top: 0.5rem;">
                    <div id="help_text_mode">
                        <strong>Textfragen:</strong> Geben Sie Schlüsselwörter ein, getrennt durch Kommas (z.B. "18,achtzehn,volljährig")
                    </div>
                    <div id="help_multiple_mode" style="display: none;">
                        <strong>Multiple Choice:</strong> Geben Sie die exakte richtige Antwort ein
                    </div>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label for="question_order">📈 Reihenfolge</label>
                    <input type="number" 
                           id="question_order" 
                           name="question_order" 
                           class="form-control" 
                           value="0" 
                           min="0" 
                           max="999"
                           placeholder="0">
                    <small style="color: var(--gray); font-size: 0.8rem;">
                        Niedrigere Zahlen werden zuerst angezeigt
                    </small>
                </div>
                
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 0.5rem; margin-top: 1.5rem;">
                        <input type="checkbox" 
                               name="is_required" 
                               checked
                               style="margin: 0;">
                        <span>🔒 Pflichtfrage</span>
                    </label>
                    <small style="color: var(--gray); font-size: 0.8rem; margin-top: 0.25rem; display: block;">
                        Muss vom Bewerber beantwortet werden
                    </small>
                </div>
            </div>
            
            <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                <button type="button" 
                        onclick="closeModal('addQuestionModal')" 
                        class="btn btn-secondary">
                    ❌ Abbrechen
                </button>
                <button type="submit" class="btn btn-primary">
                    ✅ Frage hinzufügen
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Question Modal -->
<div id="editQuestionModal" class="modal">
    <div class="modal-content">
        <button class="close-btn" onclick="closeModal('editQuestionModal')" aria-label="Modal schließen">&times;</button>
        <div class="modal-header">
            <h3 class="modal-title">✏️ Whitelist-Frage bearbeiten</h3>
        </div>
        <form method="POST" action="" id="editQuestionForm">
            <input type="hidden" name="action" value="update_whitelist_question">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" id="edit_question_id" name="question_id">
            
            <div class="form-group">
                <label for="edit_question">❓ Frage *</label>
                <textarea id="edit_question" 
                          name="question" 
                          class="form-control" 
                          rows="3" 
                          required
                          maxlength="500"></textarea>
            </div>
            
            <div class="form-group">
                <label for="edit_question_type">📝 Fragetyp</label>
                <select id="edit_question_type" name="question_type" class="form-control" onchange="toggleQuestionType('edit')">
                    <option value="text">✏️ Textfeld (freie Antwort)</option>
                    <option value="multiple_choice">📋 Multiple Choice (vorgegebene Optionen)</option>
                </select>
            </div>
            
            <div id="edit_options_container" class="form-group" style="display: none;">
                <label>📋 Antwortmöglichkeiten (2-3 Optionen)</label>
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <input type="text" name="options[]" class="form-control" placeholder="Option 1" maxlength="100">
                    <input type="text" name="options[]" class="form-control" placeholder="Option 2" maxlength="100">
                    <input type="text" name="options[]" class="form-control" placeholder="Option 3 (optional)" maxlength="100">
                </div>
            </div>
            
            <!-- Richtige Antwort für Edit Modal -->
            <div class="form-group">
                <label for="edit_correct_answer">✅ Richtige Antwort / Bewertungskriterien</label>
                <textarea id="edit_correct_answer" 
                          name="correct_answer" 
                          class="form-control" 
                          rows="2" 
                          placeholder="Für Multiple Choice: Exakte Antwort. Für Textfragen: Schlüsselwörter (kommagetrennt)"
                          maxlength="500"></textarea>
                <div id="edit_correct_answer_help" style="color: var(--gray); font-size: 0.8rem; margin-top: 0.5rem;">
                    <div id="edit_help_text_mode">
                        <strong>Textfragen:</strong> Geben Sie Schlüsselwörter ein, getrennt durch Kommas (z.B. "18,achtzehn,volljährig")
                    </div>
                    <div id="edit_help_multiple_mode" style="display: none;">
                        <strong>Multiple Choice:</strong> Geben Sie die exakte richtige Antwort ein
                    </div>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label for="edit_question_order">📈 Reihenfolge</label>
                    <input type="number" 
                           id="edit_question_order" 
                           name="question_order" 
                           class="form-control" 
                           min="0" 
                           max="999">
                </div>
                
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 0.5rem; margin-top: 1.5rem;">
                        <input type="checkbox" 
                               id="edit_question_required"
                               name="is_required" 
                               style="margin: 0;">
                        <span>🔒 Pflichtfrage</span>
                    </label>
                </div>
                
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 0.5rem; margin-top: 1.5rem;">
                        <input type="checkbox" 
                               id="edit_question_active"
                               name="is_active" 
                               style="margin: 0;">
                        <span>✅ Frage ist aktiv</span>
                    </label>
                </div>
            </div>
            
            <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                <button type="button" 
                        onclick="closeModal('editQuestionModal')" 
                        class="btn btn-secondary">
                    ❌ Abbrechen
                </button>
                <button type="submit" class="btn btn-primary">
                    💾 Frage aktualisieren
                </button>
            </div>
        </form>
    </div>
</div>

<!-- NEUES MODAL: Termin-Nachricht senden -->
<div id="appointmentModal" class="modal">
    <div class="modal-content" style="max-width: 600px;">
        <button class="close-btn" onclick="closeModal('appointmentModal')" aria-label="Modal schließen">&times;</button>
        <div class="modal-header">
            <h3 class="modal-title">📧 Termin-Nachricht senden</h3>
            <p style="color: var(--gray); margin: 0.5rem 0 0 0; font-size: 0.9rem;">
                Sende eine Discord-Direktnachricht mit Termindetails an den Bewerber
            </p>
        </div>
        
        <!-- Benutzer-Info wird hier eingefügt -->
        <div id="appointment_user_info">
            <!-- Wird per JavaScript gefüllt -->
        </div>
        
        <form id="appointmentForm">
            <input type="hidden" id="appointment_application_id" name="application_id">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label for="appointment_date">📅 Termin-Datum *</label>
                    <input type="date" 
                           id="appointment_date" 
                           name="appointment_date" 
                           class="form-control" 
                           required
                           min="<?php echo date('Y-m-d'); ?>">
                    <small style="color: var(--gray); font-size: 0.8rem;">
                        Datum für das Whitelist-Gespräch
                    </small>
                </div>
                
                <div class="form-group">
                    <label for="appointment_time">🕐 Uhrzeit *</label>
                    <input type="time" 
                           id="appointment_time" 
                           name="appointment_time" 
                           class="form-control" 
                           required
                           value="20:00">
                    <small style="color: var(--gray); font-size: 0.8rem;">
                        Uhrzeit für das Whitelist-Gespräch
                    </small>
                </div>
            </div>
            
            <div class="form-group">
                <label for="custom_message">✉️ Nachricht</label>
                <textarea id="custom_message" 
                          name="custom_message" 
                          class="form-control" 
                          rows="8" 
                          placeholder="Angepasste Nachricht (optional)..."
                          maxlength="2000"></textarea>
                <small style="color: var(--gray); font-size: 0.8rem;">
                    Leer lassen für Standard-Nachricht. Platzhalter: {appointment_date}, {appointment_time}, {username}, {server_name}
                </small>
            </div>
            
            <!-- Discord Bot Status -->
            <div id="discord_bot_status" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; padding: 1rem; margin: 1rem 0;">
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                    <span style="font-size: 1.2rem;">🤖</span>
                    <strong style="color: var(--primary);">Discord Bot Status</strong>
                </div>
                <div id="bot_status_content">
                    <div style="color: var(--gray); font-size: 0.9rem;">
                        Status wird geprüft...
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
                    📧 Termin senden
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Application Details Modal -->
<div id="applicationDetailsModal" class="modal">
    <div class="modal-content" style="max-width: 900px;">
        <button class="close-btn" onclick="closeModal('applicationDetailsModal')" aria-label="Modal schließen">&times;</button>
        <div class="modal-header">
            <h3 class="modal-title">👀 Bewerbungsdetails</h3>
        </div>
        <div id="applicationDetailsContent">
            <!-- Content wird per JavaScript geladen -->
        </div>
    </div>
</div>

<!-- Manual Scoring Modal -->
<div id="manualScoringModal" class="modal">
    <div class="modal-content">
        <button class="close-btn" onclick="closeModal('manualScoringModal')" aria-label="Modal schließen">&times;</button>
        <div class="modal-header">
            <h3 class="modal-title">🎯 Manuelle Bewertung</h3>
        </div>
        <form method="POST" action="" id="manualScoringForm">
            <input type="hidden" name="action" value="update_manual_score">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" id="scoring_application_id" name="application_id">
            
            <div id="scoringQuestionsContainer">
                <!-- Questions werden per JavaScript geladen -->
            </div>
            
            <div class="form-group">
                <label for="manual_notes">📝 Bewertungsnotizen</label>
                <textarea id="manual_notes" 
                          name="notes" 
                          class="form-control" 
                          rows="4" 
                          placeholder="Zusätzliche Notizen zur Bewertung..."></textarea>
            </div>
            
            <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                <button type="button" 
                        onclick="closeModal('manualScoringModal')" 
                        class="btn btn-secondary">
                    ❌ Abbrechen
                </button>
                <button type="submit" class="btn btn-primary">
                    💾 Bewertung speichern
                </button>
            </div>
        </form>
    </div>
</div>

<style>
/* Erweiterte Whitelist Modal Styles */
.modal .form-control select {
    appearance: none;
    background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6,9 12,15 18,9'%3e%3c/polyline%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 0.7rem center;
    background-size: 1rem;
    padding-right: 2.5rem;
}

.form-control select {
    appearance: none;
    background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6,9 12,15 18,9'%3e%3c/polyline%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 0.7rem center;
    background-size: 1rem;
    padding-right: 2.5rem;
    cursor: pointer;
}

/* Termin-Modal spezifische Styles */
.appointment-user-info {
    background: linear-gradient(135deg, rgba(88, 101, 242, 0.2), rgba(88, 101, 242, 0.1));
    border: 1px solid rgba(88, 101, 242, 0.3);
    border-radius: 12px;
    padding: 1rem;
    margin-bottom: 1.5rem;
}

.appointment-user-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    border: 2px solid #5865f2;
}

.appointment-preview {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    padding: 1rem;
    margin-top: 1rem;
    font-family: monospace;
    font-size: 0.9rem;
    line-height: 1.5;
    white-space: pre-wrap;
}

.discord-status {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    font-size: 0.9rem;
    font-weight: 500;
}

.discord-status.online {
    background: rgba(16, 185, 129, 0.1);
    border: 1px solid rgba(16, 185, 129, 0.3);
    color: var(--success);
}

.discord-status.offline {
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.3);
    color: var(--danger);
}

.discord-status.warning {
    background: rgba(245, 158, 11, 0.1);
    border: 1px solid rgba(245, 158, 11, 0.3);
    color: var(--warning);
}

/* Loading Button Style */
.btn.loading {
    position: relative;
    pointer-events: none;
}

.btn.loading::before {
    content: '';
    position: absolute;
    left: 50%;
    top: 50%;
    width: 16px;
    height: 16px;
    margin: -8px 0 0 -8px;
    border: 2px solid rgba(255,255,255,0.3);
    border-top: 2px solid rgba(255,255,255,0.8);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Scoring Specific Styles */
.score-display {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.2), rgba(16, 185, 129, 0.1));
    border: 1px solid rgba(16, 185, 129, 0.3);
    border-radius: 8px;
    padding: 1rem;
    margin: 1rem 0;
    text-align: center;
}

.score-display.low {
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.2), rgba(239, 68, 68, 0.1));
    border-color: rgba(239, 68, 68, 0.3);
}

.score-display.medium {
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.2), rgba(245, 158, 11, 0.1));
    border-color: rgba(245, 158, 11, 0.3);
}

.score-number {
    font-size: 2rem;
    font-weight: bold;
    color: var(--success);
    font-family: 'Orbitron', monospace;
}

.score-number.low {
    color: var(--danger);
}

.score-number.medium {
    color: var(--warning);
}

.question-scoring {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
}

.question-scoring.correct {
    border-left: 4px solid var(--success);
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(16, 185, 129, 0.05));
}

.question-scoring.incorrect {
    border-left: 4px solid var(--danger);
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(239, 68, 68, 0.05));
}

.question-scoring.manual {
    border-left: 4px solid var(--warning);
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(245, 158, 11, 0.05));
}

.answer-evaluation {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 0.5rem;
}

.evaluation-toggle {
    display: flex;
    gap: 0.5rem;
}

.evaluation-btn {
    padding: 0.25rem 0.75rem;
    border: 1px solid rgba(255, 255, 255, 0.2);
    background: rgba(255, 255, 255, 0.05);
    color: white;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 0.8rem;
}

.evaluation-btn.correct {
    background: var(--success);
    border-color: var(--success);
}

.evaluation-btn.incorrect {
    background: var(--danger);
    border-color: var(--danger);
}

.evaluation-btn:hover {
    background: rgba(255, 255, 255, 0.1);
}

.correct-answer-preview {
    background: rgba(0, 0, 0, 0.2);
    padding: 0.5rem;
    border-radius: 4px;
    margin-top: 0.5rem;
    font-size: 0.85rem;
    border-left: 3px solid var(--primary);
}

/* Help text transitions */
#correct_answer_help div,
#edit_correct_answer_help div {
    transition: opacity 0.3s ease;
}

/* Character counter for correct answer */
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

/* Application details specific styles */
.application-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.application-avatar {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    border: 3px solid #5865f2;
}

.application-info h4 {
    margin: 0;
    color: var(--primary);
    font-size: 1.2rem;
}

.application-meta {
    color: var(--gray);
    font-size: 0.9rem;
    margin-top: 0.5rem;
}

.answers-grid {
    display: grid;
    gap: 1rem;
    margin-top: 1rem;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .modal-content {
        padding: 1.5rem;
        margin: 1rem;
        width: calc(100% - 2rem);
    }
    
    .evaluation-toggle {
        flex-direction: column;
    }
    
    .score-display {
        padding: 0.75rem;
    }
    
    .score-number {
        font-size: 1.5rem;
    }
    
    .appointment-user-info {
        padding: 0.75rem;
    }
    
    .appointment-user-avatar {
        width: 40px;
        height: 40px;
    }
}

/* Dark theme enhancements */
.modal {
    backdrop-filter: blur(10px);
}

.modal-content {
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
    border: 1px solid rgba(255, 107, 53, 0.2);
}

.form-control:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
    outline: none;
}

.form-control[type="date"]::-webkit-calendar-picker-indicator,
.form-control[type="time"]::-webkit-calendar-picker-indicator {
    filter: invert(1);
    cursor: pointer;
}

/* Notification enhancements */
.notification {
    position: relative;
    overflow: hidden;
}

.notification::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: rgba(255, 255, 255, 0.3);
}

/* Custom scrollbar für Modal */
.modal-content::-webkit-scrollbar {
    width: 8px;
}

.modal-content::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 4px;
}

.modal-content::-webkit-scrollbar-thumb {
    background: rgba(255, 107, 53, 0.5);
    border-radius: 4px;
}

.modal-content::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 107, 53, 0.7);
}
</style>

<script>
// Erweiterte Whitelist-Funktionen mit Termin-System
document.addEventListener('DOMContentLoaded', function() {
    setupEnhancedWhitelistModals();
    setupAppointmentSystem();
});

function setupEnhancedWhitelistModals() {
    // Setup character counters für alle relevanten Felder
    const textareas = document.querySelectorAll('#question, #edit_question, #correct_answer, #edit_correct_answer, #custom_message');
    textareas.forEach(textarea => {
        setupCharacterCounter(textarea);
    });
    
    // Setup question type change handlers
    const questionTypeSelects = document.querySelectorAll('[id*="question_type"]');
    questionTypeSelects.forEach(select => {
        select.addEventListener('change', function() {
            const prefix = this.id.includes('edit_') ? 'edit' : '';
            toggleQuestionType(prefix);
            updateCorrectAnswerHelp(prefix);
        });
    });
    
    // Setup correct answer help updates
    updateCorrectAnswerHelp('');
    updateCorrectAnswerHelp('edit');
}

function setupAppointmentSystem() {
    console.log('📧 Setup Termin-System...');
    
    // Termin-Modal Form Handler
    const appointmentForm = document.getElementById('appointmentForm');
    if (appointmentForm) {
        appointmentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            handleAppointmentSubmission(this);
        });
        console.log('✅ Appointment Form Handler registriert');
    } else {
        console.warn('⚠️ Appointment Form nicht gefunden');
    }
    
    // Standard-Datum setzen (morgen)
    const appointmentDate = document.getElementById('appointment_date');
    if (appointmentDate && !appointmentDate.value) {
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        appointmentDate.value = tomorrow.toISOString().split('T')[0];
    }
}

function setupCharacterCounter(textarea) {
    const maxLength = parseInt(textarea.getAttribute('maxlength'));
    
    if (!maxLength) return;
    
    // Create counter element
    const counter = document.createElement('div');
    counter.className = 'char-counter';
    textarea.parentNode.appendChild(counter);
    
    // Update counter function
    function updateCounter() {
        const currentLength = textarea.value.length;
        const remaining = maxLength - currentLength;
        counter.textContent = `${currentLength}/${maxLength} Zeichen`;
        
        // Add warning classes
        counter.classList.remove('warning', 'danger');
        if (remaining < 50) {
            counter.classList.add('warning');
        }
        if (remaining < 20) {
            counter.classList.add('danger');
        }
    }
    
    // Initial update and event listeners
    updateCounter();
    textarea.addEventListener('input', updateCounter);
    textarea.addEventListener('paste', () => setTimeout(updateCounter, 10));
}

function updateCorrectAnswerHelp(prefix = '') {
    const typeSelect = document.getElementById(prefix + (prefix ? '_' : '') + 'question_type');
    const helpTextMode = document.getElementById(prefix + (prefix ? '_' : '') + 'help_text_mode');
    const helpMultipleMode = document.getElementById(prefix + (prefix ? '_' : '') + 'help_multiple_mode');
    
    if (typeSelect && helpTextMode && helpMultipleMode) {
        if (typeSelect.value === 'multiple_choice') {
            helpTextMode.style.display = 'none';
            helpMultipleMode.style.display = 'block';
        } else {
            helpTextMode.style.display = 'block';
            helpMultipleMode.style.display = 'none';
        }
    }
}

function toggleQuestionType(prefix = '') {
    const typeSelect = document.getElementById(prefix + (prefix ? '_' : '') + 'question_type');
    const optionsContainer = document.getElementById(prefix + (prefix ? '_' : '') + 'options_container');
    const optionsInputs = optionsContainer ? optionsContainer.querySelectorAll('input[name="options[]"]') : [];
    
    if (typeSelect && optionsContainer) {
        if (typeSelect.value === 'multiple_choice') {
            optionsContainer.style.display = 'block';
            optionsContainer.classList.remove('hide');
            optionsContainer.classList.add('show');
            
            // Make first two options required
            if (optionsInputs.length >= 2) {
                optionsInputs[0].required = true;
                optionsInputs[1].required = true;
                if (optionsInputs[2]) optionsInputs[2].required = false; // Third option is optional
            }
        } else {
            optionsContainer.style.display = 'none';
            optionsContainer.classList.remove('show');
            optionsContainer.classList.add('hide');
            
            // Remove required attribute from all options
            optionsInputs.forEach(input => {
                input.required = false;
                input.value = ''; // Clear values when switching to text
            });
        }
    }
    
    // Update help text
    updateCorrectAnswerHelp(prefix);
}

/**
 * NEUE TERMIN-FUNKTIONEN
 */

// Termin-Formular Submission Handler
function handleAppointmentSubmission(form) {
    const submitBtn = document.getElementById('sendAppointmentBtn');
    const originalText = submitBtn ? submitBtn.textContent : '';
    
    console.log('📤 Sende Termin-Formular...');
    
    // Button in Loading-Zustand setzen
    if (submitBtn) {
        submitBtn.classList.add('loading');
        submitBtn.disabled = true;
        submitBtn.textContent = '📤 Wird gesendet...';
    }
    
    // Form-Daten sammeln
    const formData = new FormData(form);
    const data = {};
    
    // FormData zu Object konvertieren
    for (let [key, value] of formData.entries()) {
        data[key] = value;
    }
    
    console.log('📤 Sende Daten:', data);
    
    // AJAX-Request senden
    fetch('ajax/send-appointment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        console.log('📥 Response Status:', response.status);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        return response.json();
    })
    .then(result => {
        console.log('📥 Response Data:', result);
        
        if (result.success) {
            showNotification('✅ Termin-Nachricht erfolgreich gesendet!', 'success');
            closeModal('appointmentModal');
            
            // Formular zurücksetzen
            form.reset();
            
            // Seite nach kurzer Verzögerung neu laden
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            throw new Error(result.error || 'Unbekannter Fehler beim Senden der Nachricht');
        }
    })
    .catch(error => {
        console.error('❌ Fehler beim Senden der Nachricht:', error);
        showNotification(`❌ Fehler beim Senden der Nachricht: ${error.message}`, 'error');
    })
    .finally(() => {
        // Button zurücksetzen
        if (submitBtn) {
            submitBtn.classList.remove('loading');
            submitBtn.disabled = false;
            submitBtn.textContent = originalText || '📧 Termin senden';
        }
    });
}

// Discord Bot Status prüfen
function checkDiscordBotStatus() {
    const statusContent = document.getElementById('bot_status_content');
    if (!statusContent) return;
    
    statusContent.innerHTML = '<div style="color: var(--gray);">🔄 Status wird geprüft...</div>';
    
    fetch('ajax/check-discord-bot.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.bot_enabled && data.bot_configured) {
                    statusContent.innerHTML = `
                        <div class="discord-status online">
                            <span>✅</span>
                            <span>Bot ist bereit - Nachrichten können gesendet werden</span>
                        </div>
                    `;
                } else if (!data.bot_enabled) {
                    statusContent.innerHTML = `
                        <div class="discord-status offline">
                            <span>❌</span>
                            <span>Discord Bot ist deaktiviert</span>
                        </div>
                    `;
                } else {
                    statusContent.innerHTML = `
                        <div class="discord-status warning">
                            <span>⚠️</span>
                            <span>Bot-Konfiguration unvollständig</span>
                        </div>
                    `;
                }
            } else {
                statusContent.innerHTML = `
                    <div class="discord-status offline">
                        <span>❌</span>
                        <span>Status konnte nicht geprüft werden</span>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Discord bot status check failed:', error);
            statusContent.innerHTML = `
                <div class="discord-status offline">
                    <span>❌</span>
                    <span>Verbindungsfehler</span>
                </div>
            `;
        });
}

// Enhanced form validation für question forms mit correct_answer
document.addEventListener('DOMContentLoaded', function() {
    const questionForms = document.querySelectorAll('#addQuestionForm, #editQuestionForm');
    
    questionForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const questionType = form.querySelector('[name="question_type"]').value;
            const correctAnswer = form.querySelector('[name="correct_answer"]').value.trim();
            
            if (questionType === 'multiple_choice') {
                const options = Array.from(form.querySelectorAll('[name="options[]"]'))
                    .map(input => input.value.trim())
                    .filter(value => value.length > 0);
                
                if (options.length < 2) {
                    e.preventDefault();
                    alert('Multiple Choice Fragen benötigen mindestens 2 Antwortmöglichkeiten.');
                    return false;
                }
                
                if (options.length > 3) {
                    e.preventDefault();
                    alert('Maximal 3 Antwortmöglichkeiten sind erlaubt.');
                    return false;
                }
                
                // Check for duplicate options
                const uniqueOptions = [...new Set(options)];
                if (uniqueOptions.length !== options.length) {
                    e.preventDefault();
                    alert('Antwortmöglichkeiten müssen eindeutig sein.');
                    return false;
                }
                
                // Validate correct answer for multiple choice
                if (correctAnswer && !options.includes(correctAnswer)) {
                    e.preventDefault();
                    alert('Die richtige Antwort muss eine der verfügbaren Optionen sein.');
                    return false;
                }
            }
        });
    });
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + Alt + S: Quick open scoring
    if ((e.ctrlKey || e.metaKey) && e.altKey && e.key === 's') {
        e.preventDefault();
        const firstPendingRow = document.querySelector('#applicationsTable tbody tr[data-status="pending"]');
        if (firstPendingRow) {
            const detailsBtn = firstPendingRow.querySelector('button[onclick*="viewApplication"]');
            if (detailsBtn) {
                detailsBtn.click();
            }
        }
    }
    
    // Ctrl/Cmd + Alt + T: Quick appointment for first pending
    if ((e.ctrlKey || e.metaKey) && e.altKey && e.key === 't') {
        e.preventDefault();
        const firstPendingRow = document.querySelector('#applicationsTable tbody tr[data-status="pending"]');
        if (firstPendingRow) {
            // Extract application ID from the row
            const detailsBtn = firstPendingRow.querySelector('button[onclick*="viewApplication"]');
            if (detailsBtn) {
                const onclickAttr = detailsBtn.getAttribute('onclick');
                const matches = onclickAttr.match(/viewApplication\((\d+)\)/);
                if (matches) {
                    const appId = matches[1];
                    sendAppointment(appId);
                }
            }
        }
    }
});
</script>