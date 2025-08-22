/**
 * Admin Dashboard JavaScript (Erweitert mit Whitelist-System)
 * Interaktive Funktionen für das Admin-Panel
 */

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
}

/**
 * Navigation Button Effekte
 */
function setupNavigationEffects() {
    const navButtons = document.querySelectorAll('.nav-button');
    
    navButtons.forEach((button, index) => {
        // Staggered animation on load
        setTimeout(() => {
            button.style.animation = 'buttonFadeIn 0.6s ease forwards';
        }, index * 100);
        
        // Enhanced hover effects
        button.addEventListener('mouseenter', function() {
            if (!this.classList.contains('active')) {
                this.style.transform = 'translateY(-3px) scale(1.02)';
            }
        });
        
        button.addEventListener('mouseleave', function() {
            if (!this.classList.contains('active')) {
                this.style.transform = 'translateY(0) scale(1)';
            }
        });
        
        // Ripple effect on click
        button.addEventListener('click', function(e) {
            createRippleEffect(e, this);
        });
    });
}

/**
 * Stat Card Animationen
 */
function setupStatCardAnimations() {
    const statCards = document.querySelectorAll('.stat-card');
    
    // Intersection Observer für scroll-basierte Animationen
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry, index) => {
            if (entry.isIntersecting) {
                setTimeout(() => {
                    entry.target.style.animation = 'fadeInUp 0.6s ease forwards';
                    animateStatNumber(entry.target);
                }, index * 150);
            }
        });
    }, { threshold: 0.1 });
    
    statCards.forEach(card => {
        card.style.opacity = '0';
        observer.observe(card);
    });
}

/**
 * Stat Nummer Animation
 */
function animateStatNumber(card) {
    const numberElement = card.querySelector('h3');
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
            
            // Ersetze die erste Zahl im Text
            numberElement.textContent = text.replace(/\d+/, currentNumber);
        }, 50);
    }
}

/**
 * Form Validation
 */
function setupFormValidation() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
                showNotification('Bitte füllen Sie alle erforderlichen Felder aus.', 'error');
            }
        });
        
        // Real-time validation
        const inputs = form.querySelectorAll('input[required], textarea[required]');
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

/**
 * Form Validierung
 */
function validateForm(form) {
    const requiredFields = form.querySelectorAll('input[required], textarea[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!validateField(field)) {
            isValid = false;
        }
    });
    
    return isValid;
}

/**
 * Einzelnes Feld validieren
 */
function validateField(field) {
    const value = field.value.trim();
    let isValid = true;
    
    // Required validation
    if (field.hasAttribute('required') && !value) {
        showFieldError(field, 'Dieses Feld ist erforderlich.');
        isValid = false;
    }
    
    // Email validation
    if (field.type === 'email' && value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            showFieldError(field, 'Bitte geben Sie eine gültige E-Mail-Adresse ein.');
            isValid = false;
        }
    }
    
    // URL validation
    if (field.type === 'url' && value) {
        try {
            new URL(value);
        } catch {
            showFieldError(field, 'Bitte geben Sie eine gültige URL ein.');
            isValid = false;
        }
    }
    
    // Number validation
    if (field.type === 'number' && value) {
        const min = field.getAttribute('min');
        const max = field.getAttribute('max');
        const num = parseInt(value);
        
        if (min && num < parseInt(min)) {
            showFieldError(field, `Wert muss mindestens ${min} sein.`);
            isValid = false;
        }
        
        if (max && num > parseInt(max)) {
            showFieldError(field, `Wert darf maximal ${max} sein.`);
            isValid = false;
        }
    }
    
    if (isValid) {
        clearFieldError(field);
    }
    
    return isValid;
}

/**
 * Feld Fehler anzeigen
 */
function showFieldError(field, message) {
    clearFieldError(field);
    
    field.style.borderColor = 'var(--danger)';
    field.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.1)';
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error';
    errorDiv.style.cssText = `
        color: var(--danger);
        font-size: 0.8rem;
        margin-top: 0.25rem;
        animation: slideDown 0.3s ease;
    `;
    errorDiv.textContent = message;
    
    field.parentNode.appendChild(errorDiv);
}

/**
 * Feld Fehler entfernen
 */
function clearFieldError(field) {
    field.style.borderColor = '';
    field.style.boxShadow = '';
    
    const errorDiv = field.parentNode.querySelector('.field-error');
    if (errorDiv) {
        errorDiv.remove();
    }
}

/**
 * Auto-Hide Flash Messages
 */
function setupAutoHideFlashMessages() {
    setTimeout(() => {
        const flashMessages = document.querySelector('.flash-messages');
        if (flashMessages) {
            flashMessages.style.opacity = '0';
            flashMessages.style.transition = 'opacity 0.5s ease';
            setTimeout(() => {
                flashMessages.remove();
            }, 500);
        }
    }, 5000);
}

/**
 * Modal Events Setup
 */
function setupModalEvents() {
    // Close modals when clicking outside
    window.addEventListener('click', function(event) {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            if (event.target === modal) {
                closeModal(modal.id);
            }
        });
    });
    
    // Close modals with ESC key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            const openModal = document.querySelector('.modal[style*="block"]');
            if (openModal) {
                closeModal(openModal.id);
            }
        }
    });
}

/**
 * Table Interactions
 */
function setupTableInteractions() {
    const tableRows = document.querySelectorAll('.table tbody tr');
    
    tableRows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.01)';
            this.style.transition = 'all 0.3s ease';
        });
        
        row.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    });
}

/**
 * Whitelist-spezifische Funktionen
 */
function setupWhitelistFunctions() {
    // Application filtering
    if (document.getElementById('statusFilter')) {
        filterApplications();
    }
    
    // Question type toggles
    const questionTypeSelects = document.querySelectorAll('[id*="question_type"]');
    questionTypeSelects.forEach(select => {
        select.addEventListener('change', function() {
            const prefix = this.id.includes('edit_') ? 'edit' : '';
            toggleQuestionType(prefix);
        });
    });
}

/**
 * Modal Funktionen
 */
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
        modal.style.animation = 'fadeIn 0.3s ease';
        
        // Focus first input
        const firstInput = modal.querySelector('input, textarea');
        if (firstInput) {
            setTimeout(() => firstInput.focus(), 100);
        }
        
        // Prevent background scrolling
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.animation = 'fadeOut 0.3s ease';
        setTimeout(() => {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }, 300);
    }
}

/**
 * Rule Management Functions
 */
function editRule(rule) {
    document.getElementById('edit_rule_id').value = rule.id;
    document.getElementById('edit_rule_title').value = rule.rule_title;
    document.getElementById('edit_rule_content').value = rule.rule_content;
    document.getElementById('edit_rule_order').value = rule.rule_order;
    document.getElementById('edit_is_active').checked = rule.is_active == 1;
    openModal('editRuleModal');
}

function deleteRule(id) {
    showConfirmDialog(
        '🗑️ Regel löschen',
        'Sind Sie sicher, dass Sie diese Regel löschen möchten? Diese Aktion kann nicht rückgängig gemacht werden.',
        () => {
            submitForm('delete_rule', { rule_id: id });
        }
    );
}

/**
 * News Management Functions
 */
function editNews(article) {
    document.getElementById('edit_news_id').value = article.id;
    document.getElementById('edit_news_title').value = article.title;
    document.getElementById('edit_news_content').value = article.content;
    document.getElementById('edit_news_published').checked = article.is_published == 1;
    openModal('editNewsModal');
}

function deleteNews(id) {
    showConfirmDialog(
        '🗑️ Artikel löschen',
        'Sind Sie sicher, dass Sie diesen Artikel löschen möchten? Diese Aktion kann nicht rückgängig gemacht werden.',
        () => {
            submitForm('delete_news', { news_id: id });
        }
    );
}

/**
 * Whitelist Questions Management
 */
function editQuestion(question) {
    document.getElementById('edit_question_id').value = question.id;
    document.getElementById('edit_question').value = question.question;
    document.getElementById('edit_question_type').value = question.question_type;
    document.getElementById('edit_question_order').value = question.question_order;
    document.getElementById('edit_question_required').checked = question.is_required == 1;
    document.getElementById('edit_question_active').checked = question.is_active == 1;
    
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

function deleteQuestion(id) {
    showConfirmDialog(
        '🗑️ Frage löschen',
        'Sind Sie sicher, dass Sie diese Frage löschen möchten? Alle damit verbundenen Antworten gehen verloren!',
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
            optionsContainer.classList.add('show');
            optionsContainer.classList.remove('hide');
            
            // Make first two options required
            const optionsInputs = optionsContainer.querySelectorAll('input[name="options[]"]');
            if (optionsInputs.length >= 2) {
                optionsInputs[0].required = true;
                optionsInputs[1].required = true;
                if (optionsInputs[2]) optionsInputs[2].required = false;
            }
        } else {
            optionsContainer.style.display = 'none';
            optionsContainer.classList.add('hide');
            optionsContainer.classList.remove('show');
            
            // Remove required attribute and clear values
            const optionsInputs = optionsContainer.querySelectorAll('input[name="options[]"]');
            optionsInputs.forEach(input => {
                input.required = false;
                input.value = '';
            });
        }
    }
}

/**
 * Whitelist Applications Management
 */
function filterApplications() {
    const filter = document.getElementById('statusFilter');
    const table = document.getElementById('applicationsTable');
    const totalCountElement = document.getElementById('totalCount');
    
    if (!filter || !table) return;
    
    const filterValue = filter.value;
    const rows = table.querySelectorAll('tbody tr');
    let visibleCount = 0;
    
    rows.forEach(row => {
        const status = row.getAttribute('data-status');
        if (!filterValue || status === filterValue) {
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

function viewApplication(id) {
    const url = `view-application.php?id=${id}`;
    const windowFeatures = 'width=900,height=700,scrollbars=yes,resizable=yes,location=no,menubar=no,toolbar=no';
    window.open(url, 'ApplicationDetails', windowFeatures);
}

function sendAppointment(id) {
    openModal('appointmentModal');
    document.getElementById('appointment_application_id').value = id;
    
    // Set default appointment date to tomorrow 10:00 AM
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    tomorrow.setHours(10, 0, 0, 0);
    
    const year = tomorrow.getFullYear();
    const month = String(tomorrow.getMonth() + 1).padStart(2, '0');
    const day = String(tomorrow.getDate()).padStart(2, '0');
    const hours = String(tomorrow.getHours()).padStart(2, '0');
    const minutes = String(tomorrow.getMinutes()).padStart(2, '0');
    
    const appointmentInput = document.getElementById('appointment_date');
    if (appointmentInput) {
        appointmentInput.value = `${year}-${month}-${day}T${hours}:${minutes}`;
    }
}

/**
 * Confirm Dialog
 */
function showConfirmDialog(title, message, onConfirm) {
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
        animation: fadeIn 0.3s ease;
    `;
    
    const dialog = document.createElement('div');
    dialog.style.cssText = `
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.05));
        border: 1px solid rgba(255, 107, 53, 0.3);
        border-radius: 16px;
        padding: 2rem;
        max-width: 400px;
        width: 90%;
        backdrop-filter: blur(20px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        text-align: center;
        animation: scaleIn 0.3s ease;
    `;
    
    dialog.innerHTML = `
        <h3 style="color: var(--primary); margin-bottom: 1rem; font-size: 1.2rem;">${title}</h3>
        <p style="color: var(--text); margin-bottom: 2rem; line-height: 1.5;">${message}</p>
        <div style="display: flex; gap: 1rem; justify-content: center;">
            <button class="btn btn-secondary" onclick="this.closest('.confirm-overlay').remove()">
                ❌ Abbrechen
            </button>
            <button class="btn btn-delete" id="confirmButton">
                ✅ Bestätigen
            </button>
        </div>
    `;
    
    overlay.className = 'confirm-overlay';
    overlay.appendChild(dialog);
    document.body.appendChild(overlay);
    
    // Bind confirm function to button
    const confirmBtn = dialog.querySelector('#confirmButton');
    confirmBtn.onclick = () => {
        overlay.remove();
        onConfirm();
    };
    
    // Focus confirm button
    setTimeout(() => confirmBtn.focus(), 100);
}

/**
 * Form Submission Helper
 */
function submitForm(action, data) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.style.display = 'none';
    
    // Add CSRF token
    const csrfToken = document.querySelector('input[name="csrf_token"]')?.value || 
                     (window.adminData && window.adminData.csrfToken);
    
    form.innerHTML = `
        <input type="hidden" name="action" value="${action}">
        <input type="hidden" name="csrf_token" value="${csrfToken}">
    `;
    
    // Add data fields
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

/**
 * Notification System
 */
function showNotification(message, type = 'info', duration = 5000) {
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 120px;
        right: 20px;
        max-width: 400px;
        padding: 1rem;
        border-radius: 8px;
        color: white;
        font-weight: 500;
        z-index: 1001;
        backdrop-filter: blur(10px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        animation: slideInRight 0.5s ease;
        cursor: pointer;
    `;
    
    // Set background based on type
    switch (type) {
        case 'success':
            notification.style.background = 'linear-gradient(135deg, rgba(16, 185, 129, 0.9), rgba(16, 185, 129, 0.7))';
            notification.style.border = '1px solid rgba(16, 185, 129, 0.5)';
            break;
        case 'error':
            notification.style.background = 'linear-gradient(135deg, rgba(239, 68, 68, 0.9), rgba(239, 68, 68, 0.7))';
            notification.style.border = '1px solid rgba(239, 68, 68, 0.5)';
            break;
        case 'warning':
            notification.style.background = 'linear-gradient(135deg, rgba(245, 158, 11, 0.9), rgba(245, 158, 11, 0.7))';
            notification.style.border = '1px solid rgba(245, 158, 11, 0.5)';
            break;
        default:
            notification.style.background = 'linear-gradient(135deg, rgba(59, 130, 246, 0.9), rgba(59, 130, 246, 0.7))';
            notification.style.border = '1px solid rgba(59, 130, 246, 0.5)';
    }
    
    notification.textContent = message;
    notification.onclick = () => removeNotification(notification);
    
    document.body.appendChild(notification);
    
    // Auto remove
    setTimeout(() => {
        removeNotification(notification);
    }, duration);
}

function removeNotification(notification) {
    notification.style.animation = 'slideOutRight 0.5s ease';
    setTimeout(() => {
        if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
        }
    }, 500);
}

/**
 * Ripple Effect für Buttons
 */
function createRippleEffect(event, element) {
    const ripple = document.createElement('span');
    const rect = element.getBoundingClientRect();
    const size = Math.max(rect.width, rect.height);
    const x = event.clientX - rect.left - size / 2;
    const y = event.clientY - rect.top - size / 2;
    
    ripple.style.cssText = `
        position: absolute;
        width: ${size}px;
        height: ${size}px;
        left: ${x}px;
        top: ${y}px;
        background: rgba(255, 255, 255, 0.3);
        border-radius: 50%;
        transform: scale(0);
        animation: ripple 0.6s ease-out;
        pointer-events: none;
    `;
    
    element.style.position = 'relative';
    element.style.overflow = 'hidden';
    element.appendChild(ripple);
    
    setTimeout(() => {
        if (ripple.parentNode) {
            ripple.parentNode.removeChild(ripple);
        }
    }, 600);
}

/**
 * Real-time Player Count Update
 */
function updatePlayerCount() {
    const statusElement = document.querySelector('.stat-card h3');
    if (statusElement && statusElement.textContent.includes('/')) {
        const current = parseInt(statusElement.textContent.split('/')[0]);
        const max = parseInt(statusElement.textContent.split('/')[1]);
        const variation = Math.floor(Math.random() * 6) - 3; // -3 to +3
        const newCount = Math.max(0, Math.min(max, current + variation));
        
        if (newCount !== current) {
            // Animate number change
            animateNumberChange(statusElement, `${newCount}/${max}`);
            
            // Update in background via AJAX
            fetch('../admin/ajax/update_players.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ current_players: newCount })
            }).catch(err => console.log('Player count update failed:', err));
        }
    }
}

/**
 * Animate Number Change
 */
function animateNumberChange(element, newValue) {
    element.style.transform = 'scale(1.1)';
    element.style.color = 'var(--secondary)';
    
    setTimeout(() => {
        element.textContent = newValue;
        element.style.transform = 'scale(1)';
        element.style.color = 'var(--primary)';
    }, 150);
}

/**
 * Copy to Clipboard Function
 */
function copyToClipboard(text, successMessage = 'In Zwischenablage kopiert!') {
    navigator.clipboard.writeText(text).then(() => {
        showNotification(successMessage, 'success', 3000);
    }).catch(err => {
        console.error('Clipboard copy failed:', err);
        showNotification('Kopieren fehlgeschlagen', 'error');
    });
}

/**
 * Keyboard Shortcuts
 */
function setupKeyboardShortcuts() {
    document.addEventListener('keydown', function(event) {
        // Ctrl/Cmd + S: Save form
        if ((event.ctrlKey || event.metaKey) && event.key === 's') {
            event.preventDefault();
            const form = document.querySelector('form:not([style*="display: none"])');
            if (form) {
                form.dispatchEvent(new Event('submit'));
            }
        }
        
        // Ctrl/Cmd + N: New item (open first modal)
        if ((event.ctrlKey || event.metaKey) && event.key === 'n') {
            event.preventDefault();
            const addButton = document.querySelector('button[onclick*="Modal"]');
            if (addButton) {
                addButton.click();
            }
        }
        
        // Ctrl/Cmd + /: Focus search (if exists)
        if ((event.ctrlKey || event.metaKey) && event.key === '/') {
            event.preventDefault();
            const searchField = document.querySelector('input[type="search"], input[placeholder*="such"], #statusFilter');
            if (searchField) {
                searchField.focus();
            }
        }
        
        // Ctrl/Cmd + Alt + W: Switch to whitelist page
        if ((event.ctrlKey || event.metaKey) && event.altKey && event.key === 'w') {
            event.preventDefault();
            window.location.href = '?page=whitelist';
        }
        
        // Ctrl/Cmd + Alt + Q: Switch to questions page
        if ((event.ctrlKey || event.metaKey) && event.altKey && event.key === 'q') {
            event.preventDefault();
            window.location.href = '?page=whitelist_questions';
        }
    });
}

/**
 * ERWEITERTE ADMIN.JS FUNKTIONEN
 * Fügen Sie diese Funktionen zu Ihrer admin.js hinzu
 */

// ========================================
// TERMIN-NACHRICHT FUNKTIONEN
// ========================================

/**
 * Termin-Modal öffnen für eine Bewerbung
 */
function sendAppointmentMessage(applicationId) {
    // Bewerbungsdaten laden
    fetch(`ajax/get-application-details.php?id=${applicationId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const application = data.application;
                
                // Modal-Felder füllen
                document.getElementById('appointment_application_id').value = applicationId;
                
                // Benutzer-Info anzeigen
                const userInfo = document.getElementById('appointment_user_info');
                userInfo.innerHTML = `
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        ${application.discord_avatar ? 
                            `<img src="${application.discord_avatar}" style="width: 32px; height: 32px; border-radius: 50%;" alt="Avatar">` :
                            `<div style="width: 32px; height: 32px; border-radius: 50%; background: #5865f2; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 0.8rem;">${application.discord_username.substring(0, 2).toUpperCase()}</div>`
                        }
                        <div>
                            <strong>${application.discord_username}</strong>
                            <br><small style="color: var(--gray);">ID: ${application.discord_id}</small>
                        </div>
                    </div>
                `;
                
                // Standard-Datum und Zeit setzen (morgen um 20:00)
                const tomorrow = new Date();
                tomorrow.setDate(tomorrow.getDate() + 1);
                document.getElementById('appointment_date').value = tomorrow.toISOString().split('T')[0];
                document.getElementById('appointment_time').value = '20:00';
                
                // Discord Bot Status prüfen
                checkDiscordBotStatus();
                
                // Modal öffnen
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

/**
 * Termin-Formular absenden
 */
document.addEventListener('DOMContentLoaded', function() {
    const appointmentForm = document.getElementById('appointmentForm');
    if (appointmentForm) {
        appointmentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('sendAppointmentBtn');
            const originalText = submitBtn.textContent;
            
            // Button in Loading-Zustand setzen
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Wird gesendet...';
            
            // Form-Daten sammeln
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());
            
            // AJAX-Request senden
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
                    
                    // Seite neu laden um aktualisierte Bewerbung zu zeigen
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                    
                } else {
                    alert('Fehler beim Senden der Nachricht:\n' + result.error);
                }
            })
            .catch(error => {
                console.error('Error sending appointment:', error);
                alert('Fehler beim Senden der Nachricht');
            })
            .finally(() => {
                // Button zurücksetzen
                submitBtn.classList.remove('loading');
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            });
        });
    }
});

/**
 * Notification System
 */
function showNotification(message, type = 'info', duration = 5000) {
    // Prüfen ob bereits ein Notification-Container existiert
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
        `;
        document.body.appendChild(container);
    }
    
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.style.cssText = `
        background: ${type === 'success' ? 'linear-gradient(135deg, #10b981, #059669)' : 
                     type === 'error' ? 'linear-gradient(135deg, #ef4444, #dc2626)' : 
                     type === 'warning' ? 'linear-gradient(135deg, #f59e0b, #d97706)' : 
                     'linear-gradient(135deg, #3b82f6, #2563eb)'};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        margin-bottom: 0.5rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        animation: slideInRight 0.3s ease-out;
        cursor: pointer;
        font-weight: 500;
        line-height: 1.4;
        position: relative;
        overflow: hidden;
    `;
    
    notification.innerHTML = `
        <div style="display: flex; align-items: center; gap: 0.5rem;">
            <span>${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" 
                    style="background: none; border: none; color: white; font-size: 1.2rem; cursor: pointer; margin-left: auto;">×</button>
        </div>
    `;
    
    // Progress bar für Auto-Close
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
    
    // Auto-remove nach duration
    if (duration > 0) {
        setTimeout(() => {
            if (notification.parentNode) {
                notification.style.animation = 'slideOutRight 0.3s ease-in';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.remove();
                    }
                }, 300);
            }
        }, duration);
    }
    
    // Click to remove
    notification.addEventListener('click', () => {
        notification.style.animation = 'slideOutRight 0.3s ease-in';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 300);
    });
}

// CSS Animations für Notifications hinzufügen
if (!document.getElementById('notification-styles')) {
    const style = document.createElement('style');
    style.id = 'notification-styles';
    style.textContent = `
        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(100%);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes slideOutRight {
            from {
                opacity: 1;
                transform: translateX(0);
            }
            to {
                opacity: 0;
                transform: translateX(100%);
            }
        }
        
        @keyframes progressBar {
            from {
                width: 100%;
            }
            to {
                width: 0%;
            }
        }
    `;
    document.head.appendChild(style);
}

// ========================================
// ERWEITERTE WHITELIST-FUNKTIONEN
// ========================================

/**
 * Erweiterte Bewerbungsdetails anzeigen
 */
function viewApplicationDetails(id) {
    // Neues Fenster öffnen
    const detailWindow = window.open(
        `view-application.php?id=${id}`, 
        '_blank', 
        'width=900,height=700,scrollbars=yes,resizable=yes'
    );
    
    // Callback für Termin-Senden aus dem Detail-Fenster
    detailWindow.sendAppointmentMessage = function(applicationId) {
        // Modal im Hauptfenster öffnen
        sendAppointmentMessage(applicationId);
        detailWindow.close();
    };
}

/**
 * Bulk-Aktionen für Bewerbungen
 */
function initBulkActions() {
    // Checkbox für "Alle auswählen"
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
    
    // Individual checkboxes
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
            bulkActions.querySelector('.selected-count').textContent = selectedCount;
        } else {
            bulkActions.style.display = 'none';
        }
    }
}

function bulkApproveApplications() {
    const selectedIds = Array.from(document.querySelectorAll('.application-checkbox:checked'))
                            .map(cb => cb.value);
    
    if (selectedIds.length === 0) {
        alert('Keine Bewerbungen ausgewählt');
        return;
    }
    
    showConfirmDialog(
        `✅ ${selectedIds.length} Bewerbungen genehmigen`,
        `Sind Sie sicher, dass Sie ${selectedIds.length} Bewerbungen genehmigen möchten?`,
        () => {
            bulkUpdateApplications(selectedIds, 'approved', 'Bulk-Genehmigung durch Admin');
        }
    );
}

function bulkRejectApplications() {
    const selectedIds = Array.from(document.querySelectorAll('.application-checkbox:checked'))
                            .map(cb => cb.value);
    
    if (selectedIds.length === 0) {
        alert('Keine Bewerbungen ausgewählt');
        return;
    }
    
    const reason = prompt(`Grund für die Ablehnung von ${selectedIds.length} Bewerbungen:`);
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
                application_id: id,
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
                showNotification(`✅ ${successCount} Bewerbungen erfolgreich aktualisiert`, 'success');
            }
            
            if (errorCount > 0) {
                showNotification(`❌ ${errorCount} Bewerbungen konnten nicht aktualisiert werden`, 'error');
            }
            
            // Seite neu laden
            setTimeout(() => location.reload(), 1500);
        })
        .catch(error => {
            console.error('Bulk update error:', error);
            showNotification('❌ Fehler bei der Bulk-Aktualisierung', 'error');
        });
}

// ========================================
// DASHBOARD STATISTIKEN LIVE-UPDATE
// ========================================

/**
 * Dashboard-Statistiken automatisch aktualisieren
 */
function initDashboardAutoRefresh() {
    if (window.location.search.includes('page=overview') || !window.location.search.includes('page=')) {
        // Alle 2 Minuten aktualisieren
        setInterval(refreshDashboardStats, 120000);
    }
}

function refreshDashboardStats() {
    fetch('ajax/get-dashboard-stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Statistiken aktualisieren
                Object.keys(data.stats).forEach(key => {
                    const element = document.querySelector(`[data-stat="${key}"]`);
                    if (element) {
                        element.textContent = data.stats[key];
                    }
                });
                
                // Letzte Aktualisierung anzeigen
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

// ========================================
// EXPORT-FUNKTIONEN
// ========================================

/**
 * Bewerbungen als CSV exportieren
 */
function exportApplicationsCSV() {
    const statusFilter = document.getElementById('statusFilter')?.value || '';
    const scoreFilter = document.getElementById('scoreFilter')?.value || '';
    
    const params = new URLSearchParams({
        export: 'csv',
        status: statusFilter,
        score: scoreFilter
    });
    
    window.open(`ajax/export-applications.php?${params.toString()}`, '_blank');
}

/**
 * Roadmap als JSON exportieren
 */
function exportRoadmapJSON() {
    fetch('ajax/export-roadmap.php?format=json')
        .then(response => response.blob())
        .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `roadmap-${new Date().toISOString().split('T')[0]}.json`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        })
        .catch(error => {
            console.error('Export error:', error);
            showNotification('❌ Fehler beim Export', 'error');
        });
}

// ========================================
// KEYBOARD SHORTCUTS
// ========================================

document.addEventListener('keydown', function(e) {
    // Globale Shortcuts
    if (e.ctrlKey || e.metaKey) {
        switch (e.key.toLowerCase()) {
            case 's':
                e.preventDefault();
                // Aktuelles Formular speichern
                const activeForm = document.querySelector('.modal.active form, form:focus-within');
                if (activeForm) {
                    activeForm.dispatchEvent(new Event('submit'));
                }
                break;
                
            case 'n':
                if (e.altKey) {
                    e.preventDefault();
                    // Je nach aktueller Seite entsprechendes Modal öffnen
                    if (window.location.search.includes('page=news')) {
                        openModal('addNewsModal');
                    } else if (window.location.search.includes('page=rules')) {
                        openModal('addRuleModal');
                    } else if (window.location.search.includes('page=roadmap')) {
                        openModal('addRoadmapModal');
                    } else if (window.location.search.includes('page=whitelist_questions')) {
                        openModal('addQuestionModal');
                    }
                }
                break;
                
            case 'f':
                if (e.altKey) {
                    e.preventDefault();
                    // Focus auf ersten Filter
                    const firstFilter = document.querySelector('select[id*="Filter"], input[id*="Filter"]');
                    if (firstFilter) {
                        firstFilter.focus();
                    }
                }
                break;
                
            case 'r':
                if (e.altKey) {
                    e.preventDefault();
                    // Statistiken aktualisieren
                    refreshDashboardStats();
                    showNotification('🔄 Statistiken aktualisiert', 'info', 2000);
                }
                break;
        }
    }
    
    // Escape für Modal schließen
    if (e.key === 'Escape') {
        const activeModal = document.querySelector('.modal.active');
        if (activeModal) {
            activeModal.classList.remove('active');
        }
    }
});

// ========================================
// INITIALIZATION
// ========================================

document.addEventListener('DOMContentLoaded', function() {
    // Alle Initialisierungsfunktionen aufrufen
    initBulkActions();
    initDashboardAutoRefresh();
    
    // Tooltips initialisieren
    initTooltips();
    
    // Auto-save für Formulare
    initAutoSave();
    
    console.log('🚀 Admin Dashboard JavaScript geladen');
});

/**
 * Tooltips für Buttons und Elemente
 */
function initTooltips() {
    document.querySelectorAll('[data-tooltip]').forEach(element => {
        element.addEventListener('mouseenter', function() {
            this.setAttribute('title', this.getAttribute('data-tooltip'));
        });
    });
}

/**
 * Auto-Save für Formulare
 */
function initAutoSave() {
    document.querySelectorAll('form:not([data-no-autosave])').forEach(form => {
        const formId = form.id || 'form_' + Math.random().toString(36).substr(2, 9);
        
        // Gespeicherte Daten laden
        const savedData = localStorage.getItem('admin_form_backup_' + formId);
        if (savedData) {
            try {
                const data = JSON.parse(savedData);
                Object.keys(data).forEach(name => {
                    const field = form.querySelector(`[name="${name}"]`);
                    if (field && field.type !== 'password' && field.name !== 'csrf_token') {
                        if (field.type === 'checkbox') {
                            field.checked = data[name];
                        } else {
                            field.value = data[name];
                        }
                    }
                });
            } catch (e) {
                console.log('Could not restore form data');
            }
        }
        
        // Daten bei Eingabe speichern
        form.addEventListener('input', debounce(function() {
            const formData = new FormData(this);
            const data = {};
            for (let [key, value] of formData.entries()) {
                if (key !== 'csrf_token' && key !== 'password') {
                    data[key] = value;
                }
            }
            localStorage.setItem('admin_form_backup_' + formId, JSON.stringify(data));
        }, 1000));
        
        // Backup bei erfolgreichem Submit löschen
        form.addEventListener('submit', function() {
            localStorage.removeItem('admin_form_backup_' + formId);
        });
    });
}

/**
 * Debounce Utility Function
 */
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

/**
 * Initialize all features
 */
document.addEventListener('DOMContentLoaded', function() {
    // Core initialization
    initializeAdminDashboard();
    
    // Optional features
    setupKeyboardShortcuts();
    
    // Update player count every 30 seconds
    setInterval(updatePlayerCount, 30000);
    
    console.log('🎯 Admin Dashboard (mit Whitelist-System) erfolgreich initialisiert!');
});

/**
 * Add custom CSS animations
 */
const adminStyles = document.createElement('style');
adminStyles.textContent = `
    @keyframes ripple {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
    
    @keyframes scaleIn {
        from {
            transform: scale(0.9);
            opacity: 0;
        }
        to {
            transform: scale(1);
            opacity: 1;
        }
    }
    
    @keyframes slideDown {
        from {
            transform: translateY(-10px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }
    
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    
    @keyframes fadeOut {
        to {
            opacity: 0;
        }
    }
    
    @keyframes buttonFadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .field-error {
        animation: slideDown 0.3s ease;
    }
    
    .table tbody tr {
        transition: all 0.3s ease;
    }
    
    .nav-button:nth-child(1) { animation-delay: 0.1s; }
    .nav-button:nth-child(2) { animation-delay: 0.2s; }
    .nav-button:nth-child(3) { animation-delay: 0.3s; }
    .nav-button:nth-child(4) { animation-delay: 0.4s; }
    .nav-button:nth-child(5) { animation-delay: 0.5s; }
    .nav-button:nth-child(6) { animation-delay: 0.6s; }
    .nav-button:nth-child(7) { animation-delay: 0.7s; }
    .nav-button:nth-child(8) { animation-delay: 0.8s; }
`;

document.head.appendChild(adminStyles);