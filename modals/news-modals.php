<!-- News Management Modals -->

<!-- Add News Modal -->
<div id="addNewsModal" class="modal">
    <div class="modal-content">
        <button class="close-btn" onclick="closeModal('addNewsModal')" aria-label="Modal schließen">&times;</button>
        <div class="modal-header">
            <h3 class="modal-title">➕ Neuen Artikel erstellen</h3>
        </div>
        <form method="POST" action="" id="addNewsForm">
            <input type="hidden" name="action" value="add_news">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div class="form-group">
                <label for="news_title">📰 Artikel-Titel *</label>
                <input type="text" 
                       id="news_title" 
                       name="news_title" 
                       class="form-control" 
                       placeholder="z.B. Server Update v2.1 - Neue Features!"
                       required 
                       maxlength="255">
                <small style="color: var(--gray); font-size: 0.8rem;">
                    Aussagekräftiger Titel, der Aufmerksamkeit erzeugt
                </small>
            </div>
            
            <div class="form-group">
                <label for="news_content">📝 Artikel-Inhalt *</label>
                <textarea id="news_content" 
                          name="news_content" 
                          class="form-control" 
                          rows="8" 
                          placeholder="Schreiben Sie hier den vollständigen Artikel-Inhalt..."
                          required></textarea>
                <small style="color: var(--gray); font-size: 0.8rem;">
                    HTML-Tags sind erlaubt für Formatierung (z.B. &lt;strong&gt;, &lt;em&gt;, &lt;br&gt;)
                </small>
            </div>
            
            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 0.5rem;">
                    <input type="checkbox" 
                           name="is_published" 
                           checked 
                           style="margin: 0;">
                    <span>✅ Sofort veröffentlichen</span>
                </label>
                <small style="color: var(--gray); font-size: 0.8rem; margin-top: 0.25rem; display: block;">
                    Wenn deaktiviert, wird der Artikel als Entwurf gespeichert
                </small>
            </div>
            
            <!-- Preview Section -->
            <div class="form-group">
                <button type="button" 
                        onclick="togglePreview('add')" 
                        class="btn btn-secondary" 
                        style="width: 100%;">
                    👁️ Vorschau anzeigen/ausblenden
                </button>
                <div id="addNewsPreview" class="news-preview" style="display: none;">
                    <h4>📰 Artikel-Vorschau:</h4>
                    <div class="preview-content"></div>
                </div>
            </div>
            
            <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                <button type="button" 
                        onclick="closeModal('addNewsModal')" 
                        class="btn btn-secondary">
                    ❌ Abbrechen
                </button>
                <button type="submit" class="btn btn-primary">
                    📰 Artikel erstellen
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit News Modal -->
<div id="editNewsModal" class="modal">
    <div class="modal-content">
        <button class="close-btn" onclick="closeModal('editNewsModal')" aria-label="Modal schließen">&times;</button>
        <div class="modal-header">
            <h3 class="modal-title">✏️ Artikel bearbeiten</h3>
        </div>
        <form method="POST" action="" id="editNewsForm">
            <input type="hidden" name="action" value="update_news">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" id="edit_news_id" name="news_id">
            
            <div class="form-group">
                <label for="edit_news_title">📰 Artikel-Titel *</label>
                <input type="text" 
                       id="edit_news_title" 
                       name="news_title" 
                       class="form-control" 
                       required 
                       maxlength="255">
            </div>
            
            <div class="form-group">
                <label for="edit_news_content">📝 Artikel-Inhalt *</label>
                <textarea id="edit_news_content" 
                          name="news_content" 
                          class="form-control" 
                          rows="8" 
                          required></textarea>
                <small style="color: var(--gray); font-size: 0.8rem;">
                    HTML-Tags sind erlaubt für Formatierung
                </small>
            </div>
            
            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 0.5rem;">
                    <input type="checkbox" 
                           id="edit_news_published" 
                           name="is_published" 
                           style="margin: 0;">
                    <span>✅ Artikel ist veröffentlicht</span>
                </label>
                <small style="color: var(--gray); font-size: 0.8rem; margin-top: 0.25rem; display: block;">
                    Nur veröffentlichte Artikel sind auf der Website sichtbar
                </small>
            </div>
            
            <!-- Preview Section -->
            <div class="form-group">
                <button type="button" 
                        onclick="togglePreview('edit')" 
                        class="btn btn-secondary" 
                        style="width: 100%;">
                    👁️ Vorschau anzeigen/ausblenden
                </button>
                <div id="editNewsPreview" class="news-preview" style="display: none;">
                    <h4>📰 Artikel-Vorschau:</h4>
                    <div class="preview-content"></div>
                </div>
            </div>
            
            <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                <button type="button" 
                        onclick="closeModal('editNewsModal')" 
                        class="btn btn-secondary">
                    ❌ Abbrechen
                </button>
                <button type="submit" class="btn btn-primary">
                    💾 Artikel aktualisieren
                </button>
            </div>
        </form>
    </div>
</div>

<style>
/* News Modal Specific Styles */
.news-preview {
    margin-top: 1rem;
    padding: 1rem;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    animation: slideDown 0.3s ease;
}

.news-preview h4 {
    color: var(--primary);
    margin-bottom: 1rem;
    font-size: 1rem;
}

.preview-content {
    background: rgba(0, 0, 0, 0.2);
    padding: 1rem;
    border-radius: 6px;
    border-left: 3px solid var(--primary);
    color: var(--text);
    line-height: 1.6;
    max-height: 200px;
    overflow-y: auto;
}

.preview-content h1,
.preview-content h2,
.preview-content h3 {
    color: var(--primary);
    margin-top: 0;
}

.preview-content p {
    margin-bottom: 0.75rem;
}

.preview-content strong {
    color: var(--secondary);
}

.preview-content em {
    color: var(--gray);
    font-style: italic;
}

/* Enhanced textarea for news content */
#news_content,
#edit_news_content {
    font-family: 'Courier New', monospace;
    line-height: 1.6;
    tab-size: 2;
}

/* Formatting helpers */
.formatting-help {
    background: rgba(59, 130, 246, 0.1);
    border: 1px solid rgba(59, 130, 246, 0.3);
    border-radius: 6px;
    padding: 0.75rem;
    margin-top: 0.5rem;
    font-size: 0.8rem;
    color: #93c5fd;
}

.formatting-help summary {
    cursor: pointer;
    font-weight: 500;
    margin-bottom: 0.5rem;
}

.formatting-help code {
    background: rgba(0, 0, 0, 0.3);
    padding: 0.2rem 0.4rem;
    border-radius: 3px;
    font-family: 'Courier New', monospace;
}

/* Word count indicator */
.word-count {
    text-align: right;
    font-size: 0.8rem;
    color: var(--gray);
    margin-top: 0.25rem;
}

.word-count.good {
    color: var(--success);
}

.word-count.warning {
    color: var(--warning);
}

/* Auto-save indicator */
.auto-save-indicator {
    position: absolute;
    top: 1rem;
    left: 1rem;
    background: rgba(16, 185, 129, 0.2);
    border: 1px solid var(--success);
    color: var(--success);
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.auto-save-indicator.show {
    opacity: 1;
}

@keyframes slideDown {
    from {
        transform: translateY(-10px);
        opacity: 0;
        max-height: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
        max-height: 300px;
    }
}
</style>

<script>
// News Modal JavaScript Functions
document.addEventListener('DOMContentLoaded', function() {
    setupNewsModals();
});

function setupNewsModals() {
    // Setup preview functionality
    const contentFields = ['news_content', 'edit_news_content'];
    contentFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('input', debounce(() => {
                updatePreview(fieldId);
                updateWordCount(fieldId);
            }, 500));
        }
    });
    
    // Setup formatting help
    addFormattingHelp();
    
    // Setup auto-save (if needed)
    setupAutoSave();
}

function togglePreview(type) {
    const previewId = type === 'add' ? 'addNewsPreview' : 'editNewsPreview';
    const contentId = type === 'add' ? 'news_content' : 'edit_news_content';
    const titleId = type === 'add' ? 'news_title' : 'edit_news_title';
    
    const preview = document.getElementById(previewId);
    
    if (preview.style.display === 'none') {
        preview.style.display = 'block';
        updatePreview(contentId, titleId, previewId);
    } else {
        preview.style.display = 'none';
    }
}

function updatePreview(contentId, titleId = null, previewId = null) {
    if (!previewId) {
        previewId = contentId.includes('edit') ? 'editNewsPreview' : 'addNewsPreview';
    }
    if (!titleId) {
        titleId = contentId.includes('edit') ? 'edit_news_title' : 'news_title';
    }
    
    const content = document.getElementById(contentId)?.value || '';
    const title = document.getElementById(titleId)?.value || 'Artikel-Titel';
    const preview = document.getElementById(previewId);
    
    if (preview && preview.style.display !== 'none') {
        const previewContent = preview.querySelector('.preview-content');
        if (previewContent) {
            previewContent.innerHTML = `
                <h3>${escapeHtml(title)}</h3>
                <div>${content || '<em>Noch kein Inhalt verfügbar...</em>'}</div>
            `;
        }
    }
}

function updateWordCount(fieldId) {
    const field = document.getElementById(fieldId);
    if (!field) return;
    
    let countElement = field.parentNode.querySelector('.word-count');
    if (!countElement) {
        countElement = document.createElement('div');
        countElement.className = 'word-count';
        field.parentNode.appendChild(countElement);
    }
    
    const text = field.value.trim();
    const wordCount = text ? text.split(/\s+/).length : 0;
    const charCount = text.length;
    
    countElement.textContent = `${wordCount} Wörter, ${charCount} Zeichen`;
    
    // Color coding based on length
    countElement.classList.remove('good', 'warning');
    if (wordCount > 50) {
        countElement.classList.add('good');
    } else if (wordCount > 20) {
        countElement.classList.add('warning');
    }
}

function addFormattingHelp() {
    const textareas = document.querySelectorAll('#news_content, #edit_news_content');
    
    textareas.forEach(textarea => {
        if (!textarea.parentNode.querySelector('.formatting-help')) {
            const helpDiv = document.createElement('details');
            helpDiv.className = 'formatting-help';
            helpDiv.innerHTML = `
                <summary>💡 Formatierungs-Hilfe</summary>
                <strong>Verfügbare HTML-Tags:</strong><br>
                <code>&lt;strong&gt;Fett&lt;/strong&gt;</code> - <strong>Fett</strong><br>
                <code>&lt;em&gt;Kursiv&lt;/em&gt;</code> - <em>Kursiv</em><br>
                <code>&lt;br&gt;</code> - Zeilenumbruch<br>
                <code>&lt;p&gt;Absatz&lt;/p&gt;</code> - Neuer Absatz<br>
                <code>&lt;h3&gt;Überschrift&lt;/h3&gt;</code> - Zwischenüberschrift
            `;
            textarea.parentNode.appendChild(helpDiv);
        }
    });
}

function setupAutoSave() {
    // Auto-save functionality (saves to localStorage as draft)
    const forms = document.querySelectorAll('#addNewsForm, #editNewsForm');
    
    forms.forEach(form => {
        const inputs = form.querySelectorAll('input[type="text"], textarea');
        inputs.forEach(input => {
            input.addEventListener('input', debounce(() => {
                saveFormDraft(form);
            }, 2000));
        });
    });
}

function saveFormDraft(form) {
    try {
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        const formId = form.id;
        
        localStorage.setItem(`admin_draft_${formId}`, JSON.stringify({
            data: data,
            timestamp: Date.now()
        }));
        
        showAutoSaveIndicator(form);
    } catch (error) {
        console.warn('Auto-save failed:', error);
    }
}

function loadFormDraft(formId) {
    try {
        const saved = localStorage.getItem(`admin_draft_${formId}`);
        if (saved) {
            const { data, timestamp } = JSON.parse(saved);
            // Only load if less than 24 hours old
            if (Date.now() - timestamp < 24 * 60 * 60 * 1000) {
                const form = document.getElementById(formId);
                if (form) {
                    Object.entries(data).forEach(([name, value]) => {
                        const field = form.querySelector(`[name="${name}"]`);
                        if (field && field.type !== 'hidden') {
                            if (field.type === 'checkbox') {
                                field.checked = value === 'on';
                            } else {
                                field.value = value;
                            }
                        }
                    });
                    
                    // Show notification about loaded draft
                    if (window.showNotification) {
                        showNotification('Entwurf aus Local Storage geladen', 'info', 3000);
                    }
                }
            }
        }
    } catch (error) {
        console.warn('Draft loading failed:', error);
    }
}

function clearFormDraft(formId) {
    localStorage.removeItem(`admin_draft_${formId}`);
}

function showAutoSaveIndicator(form) {
    let indicator = form.querySelector('.auto-save-indicator');
    if (!indicator) {
        indicator = document.createElement('div');
        indicator.className = 'auto-save-indicator';
        indicator.textContent = '💾 Entwurf gespeichert';
        form.appendChild(indicator);
    }
    
    indicator.classList.add('show');
    setTimeout(() => {
        indicator.classList.remove('show');
    }, 2000);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Load drafts when modals open
document.addEventListener('click', function(e) {
    if (e.target.getAttribute('onclick')?.includes('addNewsModal')) {
        setTimeout(() => loadFormDraft('addNewsForm'), 100);
    }
});

// Clear drafts when forms are successfully submitted
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('#addNewsForm, #editNewsForm');
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            clearFormDraft(this.id);
        });
    });
});
</script>