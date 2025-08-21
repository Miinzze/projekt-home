<!-- Erweiterte Whitelist Management Modals mit Scoring System -->

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
}
</style>

<script>
// Erweiterte Whitelist-Funktionen mit Scoring
document.addEventListener('DOMContentLoaded', function() {
    setupEnhancedWhitelistModals();
});

function setupEnhancedWhitelistModals() {
    // Setup character counters für alle relevanten Felder
    const textareas = document.querySelectorAll('#question, #edit_question, #correct_answer, #edit_correct_answer');
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
                optionsInputs[2].required = false; // Third option is optional
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

// Erweiterte editQuestion Funktion mit correct_answer Support
function editQuestion(question) {
    document.getElementById('edit_question_id').value = question.id;
    document.getElementById('edit_question').value = question.question;
    document.getElementById('edit_question_type').value = question.question_type;
    document.getElementById('edit_question_order').value = question.question_order;
    document.getElementById('edit_question_required').checked = question.is_required == 1;
    document.getElementById('edit_question_active').checked = question.is_active == 1;
    
    // Set correct answer
    if (document.getElementById('edit_correct_answer')) {
        document.getElementById('edit_correct_answer').value = question.correct_answer || '';
    }
    
    // Handle options for multiple choice
    const optionsContainer = document.getElementById('edit_options_container');
    const optionsInputs = optionsContainer.querySelectorAll('input[name="options[]"]');
    
    // Clear existing options
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
    
    toggleQuestionType('edit');
    openModal('editQuestionModal');
}

// Neue Funktion für Application Details
function viewApplicationDetails(applicationId) {
    openModal('applicationDetailsModal');
    
    // Loading state
    document.getElementById('applicationDetailsContent').innerHTML = `
        <div style="text-align: center; padding: 2rem;">
            <div style="font-size: 3rem; margin-bottom: 1rem;">⏳</div>
            <p>Lade Bewerbungsdetails...</p>
        </div>
    `;
    
    // Fetch application details
    fetch(`get-application-details.php?id=${applicationId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayApplicationDetails(data.application, data.answers);
            } else {
                document.getElementById('applicationDetailsContent').innerHTML = `
                    <div style="text-align: center; padding: 2rem; color: var(--danger);">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">❌</div>
                        <p>Fehler beim Laden der Details: ${data.error}</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error fetching application details:', error);
            document.getElementById('applicationDetailsContent').innerHTML = `
                <div style="text-align: center; padding: 2rem; color: var(--danger);">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">❌</div>
                    <p>Fehler beim Laden der Details.</p>
                </div>
            `;
        });
}

function displayApplicationDetails(application, answers) {
    const scoreClass = getScoreClass(application.score_percentage);
    const scoreColor = getScoreColor(application.score_percentage);
    
    let html = `
        <div class="application-header">
            <div>
                ${application.discord_avatar ? 
                    `<img src="${application.discord_avatar}" class="application-avatar" alt="Avatar">` :
                    `<div class="application-avatar" style="background: #5865f2; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 1.2rem;">
                        ${application.discord_username.substring(0, 2).toUpperCase()}
                    </div>`
                }
            </div>
            <div class="application-info">
                <h4>${application.discord_username}</h4>
                <div class="application-meta">
                    Discord ID: ${application.discord_id}<br>
                    Eingereicht: ${new Date(application.created_at).toLocaleString('de-DE')}<br>
                    Status: ${getStatusLabel(application.status)}
                </div>
            </div>
            <div style="margin-left: auto;">
                <div class="score-display ${scoreClass}">
                    <div class="score-number ${scoreClass}">${application.score_percentage}%</div>
                    <div style="font-size: 0.9rem; margin-top: 0.5rem;">
                        ${application.correct_answers}/${application.total_questions} richtig
                    </div>
                </div>
            </div>
        </div>
        
        <div class="answers-grid">
    `;
    
    answers.forEach((answer, index) => {
        const isCorrect = answer.is_correct == 1;
        const autoEvaluated = answer.auto_evaluated == 1;
        const questionClass = isCorrect ? 'correct' : (autoEvaluated ? 'incorrect' : 'manual');
        
        html += `
            <div class="question-scoring ${questionClass}">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                    <div style="flex: 1;">
                        <h5 style="color: var(--primary); margin-bottom: 0.5rem;">
                            Frage ${index + 1}: ${answer.question}
                        </h5>
                        <div style="color: var(--text); margin-bottom: 0.5rem;">
                            <strong>Antwort:</strong> ${answer.answer}
                        </div>
                        ${answer.correct_answer ? `
                            <div class="correct-answer-preview">
                                <strong>Erwartete Antwort:</strong> ${answer.correct_answer}
                            </div>
                        ` : ''}
                    </div>
                    <div class="answer-evaluation">
                        ${isCorrect ? 
                            '<span style="color: var(--success); font-weight: bold;">✅ Richtig</span>' :
                            (autoEvaluated ? 
                                '<span style="color: var(--danger); font-weight: bold;">❌ Falsch</span>' :
                                '<span style="color: var(--warning); font-weight: bold;">❓ Nicht bewertet</span>'
                            )
                        }
                        ${autoEvaluated ? 
                            '<span style="color: var(--gray); font-size: 0.8rem;">(Auto)</span>' :
                            '<span style="color: var(--gray); font-size: 0.8rem;">(Manuell)</span>'
                        }
                    </div>
                </div>
            </div>
        `;
    });
    
    html += `
        </div>
        
        <div style="margin-top: 2rem; text-align: center;">
            <button onclick="openManualScoring(${application.id})" class="btn btn-primary">
                🎯 Manuelle Bewertung
            </button>
            <button onclick="updateApplicationStatus(${application.id})" class="btn btn-secondary">
                📝 Status ändern
            </button>
        </div>
    `;
    
    document.getElementById('applicationDetailsContent').innerHTML = html;
}

function getScoreClass(percentage) {
    if (percentage >= 70) return '';
    if (percentage >= 50) return 'medium';
    return 'low';
}

function getScoreColor(percentage) {
    if (percentage >= 70) return 'var(--success)';
    if (percentage >= 50) return 'var(--warning)';
    return 'var(--danger)';
}

function getStatusLabel(status) {
    const labels = {
        'pending': '🟡 Noch offen',
        'approved': '✅ Genehmigt',
        'rejected': '❌ Abgelehnt',
        'closed': '⚫ Geschlossen'
    };
    return labels[status] || status;
}

function openManualScoring(applicationId) {
    closeModal('applicationDetailsModal');
    openModal('manualScoringModal');
    
    document.getElementById('scoring_application_id').value = applicationId;
    
    // Load questions for manual scoring
    fetch(`get-scoring-questions.php?app_id=${applicationId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayScoringQuestions(data.questions);
            }
        })
        .catch(error => {
            console.error('Error loading scoring questions:', error);
        });
}

function displayScoringQuestions(questions) {
    let html = '';
    
    questions.forEach((question, index) => {
        const isCorrect = question.is_correct == 1;
        
        html += `
            <div class="question-scoring ${isCorrect ? 'correct' : 'incorrect'}" style="margin-bottom: 1rem;">
                <h5 style="color: var(--primary); margin-bottom: 0.5rem;">
                    Frage ${index + 1}: ${question.question}
                </h5>
                <div style="margin-bottom: 0.5rem;">
                    <strong>Antwort:</strong> ${question.answer}
                </div>
                ${question.correct_answer ? `
                    <div class="correct-answer-preview">
                        <strong>Erwartete Antwort:</strong> ${question.correct_answer}
                    </div>
                ` : ''}
                <div class="evaluation-toggle" style="margin-top: 1rem;">
                    <button type="button" 
                            class="evaluation-btn ${isCorrect ? 'correct' : ''}"
                            onclick="toggleAnswerEvaluation(${question.answer_id}, true, this)">
                        ✅ Richtig
                    </button>
                    <button type="button" 
                            class="evaluation-btn ${!isCorrect ? 'incorrect' : ''}"
                            onclick="toggleAnswerEvaluation(${question.answer_id}, false, this)">
                        ❌ Falsch
                    </button>
                </div>
                <input type="hidden" name="answer_evaluations[${question.answer_id}]" value="${isCorrect ? '1' : '0'}">
            </div>
        `;
    });
    
    document.getElementById('scoringQuestionsContainer').innerHTML = html;
}

function toggleAnswerEvaluation(answerId, isCorrect, buttonElement) {
    const container = buttonElement.closest('.question-scoring');
    const hiddenInput = container.querySelector(`input[name="answer_evaluations[${answerId}]"]`);
    const buttons = container.querySelectorAll('.evaluation-btn');
    
    // Remove active classes
    buttons.forEach(btn => {
        btn.classList.remove('correct', 'incorrect');
    });
    
    // Add active class to clicked button
    buttonElement.classList.add(isCorrect ? 'correct' : 'incorrect');
    
    // Update hidden input
    hiddenInput.value = isCorrect ? '1' : '0';
    
    // Update container class
    container.classList.remove('correct', 'incorrect');
    container.classList.add(isCorrect ? 'correct' : 'incorrect');
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
});
</script>