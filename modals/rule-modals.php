<!-- Rule Management Modals -->

<!-- Add Rule Modal -->
<div id="addRuleModal" class="modal">
    <div class="modal-content">
        <button class="close-btn" onclick="closeModal('addRuleModal')" aria-label="Modal schließen">&times;</button>
        <div class="modal-header">
            <h3 class="modal-title">➕ Neue Regel hinzufügen</h3>
        </div>
        <form method="POST" action="" id="addRuleForm">
            <input type="hidden" name="action" value="add_rule">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div class="form-group">
                <label for="rule_title">📋 Regel-Titel *</label>
                <input type="text" 
                       id="rule_title" 
                       name="rule_title" 
                       class="form-control" 
                       placeholder="z.B. ROLEPLAY FIRST"
                       required 
                       maxlength="255">
                <small style="color: var(--gray); font-size: 0.8rem;">
                    Kurzer, prägnanter Titel für die Regel
                </small>
            </div>
            
            <div class="form-group">
                <label for="rule_content">📝 Regel-Inhalt *</label>
                <textarea id="rule_content" 
                          name="rule_content" 
                          class="form-control" 
                          rows="4" 
                          placeholder="Detaillierte Beschreibung der Regel..."
                          required
                          maxlength="1000"></textarea>
                <small style="color: var(--gray); font-size: 0.8rem;">
                    Ausführliche Erklärung der Regel (max. 1000 Zeichen)
                </small>
            </div>
            
            <div class="form-group">
                <label for="rule_order">📈 Reihenfolge</label>
                <input type="number" 
                       id="rule_order" 
                       name="rule_order" 
                       class="form-control" 
                       value="0" 
                       min="0" 
                       max="999"
                       placeholder="0">
                <small style="color: var(--gray); font-size: 0.8rem;">
                    Niedrigere Zahlen werden zuerst angezeigt (0 = höchste Priorität)
                </small>
            </div>
            
            <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                <button type="button" 
                        onclick="closeModal('addRuleModal')" 
                        class="btn btn-secondary">
                    ❌ Abbrechen
                </button>
                <button type="submit" class="btn btn-primary">
                    ✅ Regel hinzufügen
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Rule Modal -->
<div id="editRuleModal" class="modal">
    <div class="modal-content">
        <button class="close-btn" onclick="closeModal('editRuleModal')" aria-label="Modal schließen">&times;</button>
        <div class="modal-header">
            <h3 class="modal-title">✏️ Regel bearbeiten</h3>
        </div>
        <form method="POST" action="" id="editRuleForm">
            <input type="hidden" name="action" value="update_rule">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" id="edit_rule_id" name="rule_id">
            
            <div class="form-group">
                <label for="edit_rule_title">📋 Regel-Titel *</label>
                <input type="text" 
                       id="edit_rule_title" 
                       name="rule_title" 
                       class="form-control" 
                       required 
                       maxlength="255">
            </div>
            
            <div class="form-group">
                <label for="edit_rule_content">📝 Regel-Inhalt *</label>
                <textarea id="edit_rule_content" 
                          name="rule_content" 
                          class="form-control" 
                          rows="4" 
                          required
                          maxlength="1000"></textarea>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label for="edit_rule_order">📈 Reihenfolge</label>
                    <input type="number" 
                           id="edit_rule_order" 
                           name="rule_order" 
                           class="form-control" 
                           min="0" 
                           max="999">
                </div>
                
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 0.5rem; margin-top: 1.5rem;">
                        <input type="checkbox" 
                               id="edit_is_active" 
                               name="is_active" 
                               style="margin: 0;">
                        <span>✅ Regel ist aktiv</span>
                    </label>
                    <small style="color: var(--gray); font-size: 0.8rem; margin-top: 0.25rem; display: block;">
                        Nur aktive Regeln werden auf der Website angezeigt
                    </small>
                </div>
            </div>
            
            <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                <button type="button" 
                        onclick="closeModal('editRuleModal')" 
                        class="btn btn-secondary">
                    ❌ Abbrechen
                </button>
                <button type="submit" class="btn btn-primary">
                    💾 Regel aktualisieren
                </button>
            </div>
        </form>
    </div>
</div>

<style>
/* Modal-spezifische Styles */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    z-index: 1000;
    backdrop-filter: blur(5px);
    animation: fadeIn 0.3s ease;
}

.modal-content {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.05));
    border: 1px solid rgba(255, 107, 53, 0.3);
    border-radius: 16px;
    padding: 2rem;
    max-width: 600px;
    width: 90%;
    max-height: 85vh;
    overflow-y: auto;
    backdrop-filter: blur(20px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
    animation: scaleIn 0.3s ease;
}

.modal-header {
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.modal-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--primary);
    text-shadow: 0 0 10px rgba(255, 107, 53, 0.3);
    margin: 0;
}

.close-btn {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: none;
    border: none;
    color: var(--gray);
    font-size: 1.5rem;
    cursor: pointer;
    transition: all 0.3s ease;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
}

.close-btn:hover {
    color: var(--danger);
    background: rgba(239, 68, 68, 0.1);
    transform: scale(1.1);
}

/* Form Styling in Modals */
.modal .form-group {
    margin-bottom: 1.5rem;
}

.modal .form-group label {
    display: block;
    margin-bottom: 0.5rem;
    color: var(--text);
    font-weight: 500;
    font-size: 0.9rem;
}

.modal .form-control {
    width: 100%;
    padding: 0.75rem 1rem;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 8px;
    color: var(--text);
    font-size: 1rem;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
    box-sizing: border-box;
}

.modal .form-control:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
    background: rgba(255, 255, 255, 0.08);
}

.modal .form-control::placeholder {
    color: rgba(255, 255, 255, 0.5);
}

.modal textarea.form-control {
    resize: vertical;
    min-height: 100px;
    font-family: inherit;
}

/* Character Counter für Textareas */
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

/* Responsive Modal Design */
@media (max-width: 768px) {
    .modal-content {
        padding: 1.5rem;
        margin: 1rem;
        width: calc(100% - 2rem);
        max-height: 90vh;
    }
    
    .modal-title {
        font-size: 1.1rem;
    }
    
    .close-btn {
        top: 0.75rem;
        right: 0.75rem;
    }
    
    .modal .form-control {
        padding: 0.6rem 0.8rem;
        font-size: 0.9rem;
    }
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes scaleIn {
    from {
        transform: translate(-50%, -50%) scale(0.9);
        opacity: 0;
    }
    to {
        transform: translate(-50%, -50%) scale(1);
        opacity: 1;
    }
}
</style>

<script>
// Character counter for textareas
document.addEventListener('DOMContentLoaded', function() {
    const textareas = document.querySelectorAll('textarea[maxlength]');
    
    textareas.forEach(textarea => {
        const maxLength = parseInt(textarea.getAttribute('maxlength'));
        
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
    });
});
</script>