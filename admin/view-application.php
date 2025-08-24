<?php
require_once '../config/config.php';

// Login prüfen
if (!isLoggedIn()) {
    redirect('login.php');
}

$applicationId = (int)($_GET['id'] ?? 0);

if ($applicationId <= 0) {
    die('Ungültige Bewerbungs-ID');
}

// Bewerbung mit Antworten laden
$application = fetchOne("
    SELECT wa.*, a.username as reviewed_by_name 
    FROM whitelist_applications wa 
    LEFT JOIN admins a ON wa.reviewed_by = a.id 
    WHERE wa.id = :id
", ['id' => $applicationId]);

if (!$application) {
    die('Bewerbung nicht gefunden');
}

// Antworten laden
$answers = fetchAll("
    SELECT wans.*, wq.question, wq.question_type, wq.options 
    FROM whitelist_answers wans 
    JOIN whitelist_questions wq ON wans.question_id = wq.id 
    WHERE wans.application_id = :id 
    ORDER BY wq.question_order ASC, wq.id ASC
", ['id' => $applicationId]);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bewerbung #<?php echo $applicationId; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #0a0a0a;
            color: white;
            margin: 0;
            padding: 2rem;
            line-height: 1.6;
        }
        
        .application-container {
            max-width: 800px;
            margin: 0 auto;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.08), rgba(255, 255, 255, 0.03));
            border: 1px solid rgba(255, 68, 68, 0.3);
            border-radius: 16px;
            padding: 2rem;
            backdrop-filter: blur(20px);
        }
        
        .header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .user-avatar {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            border: 3px solid #5865f2;
        }
        
        .user-info h1 {
            margin: 0;
            color: #ff4444;
            font-size: 1.5rem;
        }
        
        .user-info .meta {
            color: #ccc;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }
        
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
            margin-left: auto;
        }
        
        .status-pending { background: rgba(245, 158, 11, 0.2); color: #f59e0b; border: 1px solid #f59e0b; }
        .status-closed { background: rgba(107, 114, 128, 0.2); color: #6b7280; border: 1px solid #6b7280; }
        .status-approved { background: rgba(16, 185, 129, 0.2); color: #10b981; border: 1px solid #10b981; }
        .status-rejected { background: rgba(239, 68, 68, 0.2); color: #ef4444; border: 1px solid #ef4444; }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .info-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 1rem;
        }
        
        .info-card h3 {
            margin: 0 0 0.5rem 0;
            color: #ff4444;
            font-size: 1rem;
        }
        
        .info-card p {
            margin: 0;
            color: #ccc;
        }
        
        .answers-section {
            margin-top: 2rem;
        }
        
        .answer-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .question-title {
            color: #ff4444;
            font-weight: 600;
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }
        
        .answer-text {
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 6px;
            padding: 1rem;
            color: white;
            line-height: 1.6;
            min-height: 50px;
        }
        
        .answer-type {
            font-size: 0.8rem;
            color: #ccc;
            margin-bottom: 0.5rem;
        }
        
        .actions {
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            gap: 1rem;
            justify-content: center;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.9rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #ff4444, #cc0000);
            color: white;
        }
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 68, 68, 0.3);
        }
        
        .appointment-info {
            background: rgba(255, 68, 68, 0.1);
            border: 1px solid rgba(255, 68, 68, 0.3);
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
        }
        
        .appointment-info h4 {
            color: #ff4444;
            margin: 0 0 1rem 0;
        }
        
        .appointment-message {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 6px;
            padding: 1rem;
            font-family: monospace;
            white-space: pre-wrap;
            line-height: 1.5;
        }
        
        .notes-section {
            margin-top: 1rem;
        }
        
        .notes-text {
            background: rgba(255, 193, 7, 0.1);
            border: 1px solid rgba(255, 193, 7, 0.3);
            border-radius: 6px;
            padding: 1rem;
            color: #ffc107;
            font-style: italic;
        }
        
        /* Modal Styles - HINZUGEFÜGT */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.05));
            border: 1px solid rgba(255, 68, 68, 0.3);
            border-radius: 16px;
            backdrop-filter: blur(20px);
            margin: 5% auto;
            padding: 2rem;
            width: 90%;
            max-width: 600px;
            color: white;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .modal-header h2 {
            color: #ff4444;
            margin: 0;
            font-size: 1.5rem;
        }

        .close {
            color: #ccc;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
            background: none;
            border: none;
            padding: 0;
            transition: color 0.3s ease;
        }

        .close:hover {
            color: #ff4444;
            transform: scale(1.1);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #ff4444;
            font-weight: 600;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            background: rgba(0, 0, 0, 0.3);
            color: white;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #ff4444 !important;
            box-shadow: 0 0 0 3px rgba(255, 68, 68, 0.1) !important;
            background: rgba(0, 0, 0, 0.4) !important;
        }

        .form-row {
            display: flex;
            gap: 1rem;
        }

        .form-col {
            flex: 1;
        }

        .user-info {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-avatar-small {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            border: 2px solid #5865f2;
        }

        .quick-time {
            background: rgba(255, 68, 68, 0.1);
            border: 1px solid rgba(255, 68, 68, 0.3);
            color: #ff4444;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-right: 0.5rem;
            margin-top: 0.5rem;
            display: inline-block;
        }

        .quick-time:hover {
            background: rgba(255, 68, 68, 0.2);
            transform: scale(1.05);
        }

        .quick-time.selected {
            background: rgba(255, 68, 68, 0.3);
            border-color: #ff4444;
            transform: scale(1.05);
        }

        .message-preview {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 1rem;
            white-space: pre-wrap;
            font-family: monospace;
            font-size: 0.9rem;
            line-height: 1.4;
            max-height: 200px;
            overflow-y: auto;
            color: #ccc;
        }

        .alert {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border-left: 4px solid;
        }

        .alert-info {
            background: rgba(59, 130, 246, 0.1);
            border-color: #3b82f6;
            color: #93c5fd;
        }

        .alert-warning {
            background: rgba(245, 158, 11, 0.1);
            border-color: #f59e0b;
            color: #fcd34d;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .btn.loading {
            position: relative;
            color: transparent;
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

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }
            
            .application-container {
                padding: 1.5rem;
            }
            
            .header {
                flex-direction: column;
                text-align: center;
            }
            
            .status-badge {
                margin-left: 0;
                margin-top: 1rem;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .actions {
                flex-direction: column;
            }
            
            .form-row {
                flex-direction: column;
            }
            
            .modal-footer {
                flex-direction: column;
            }
        }
        
        @media print {
            body {
                background: white;
                color: black;
            }
            
            .application-container {
                border: 1px solid #ccc;
                background: white;
                box-shadow: none;
            }
            
            .btn {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="application-container">
        <!-- Header -->
        <div class="header">
            <?php if ($application['discord_avatar']): ?>
                <img src="<?php echo htmlspecialchars($application['discord_avatar']); ?>" 
                     alt="Avatar" class="user-avatar">
            <?php else: ?>
                <div class="user-avatar" style="background: #5865f2; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 1.2rem;">
                    <?php echo strtoupper(substr($application['discord_username'], 0, 2)); ?>
                </div>
            <?php endif; ?>
            
            <div class="user-info">
                <h1><?php echo htmlspecialchars($application['discord_username']); ?></h1>
                <div class="meta">
                    <strong>Discord ID:</strong> <?php echo htmlspecialchars($application['discord_id']); ?><br>
                    <strong>E-Mail:</strong> <?php echo htmlspecialchars($application['discord_email'] ?? 'Nicht verfügbar'); ?>
                </div>
            </div>
            
            <div class="status-badge status-<?php echo $application['status']; ?>">
                <?php
                $statusLabels = [
                    'pending' => '🟡 Noch offen',
                    'closed' => '⚫ Geschlossen',
                    'approved' => '✅ Genehmigt',
                    'rejected' => '❌ Abgelehnt'
                ];
                echo $statusLabels[$application['status']] ?? $application['status'];
                ?>
            </div>
        </div>
        
        <!-- Bewerbungsinfo -->
        <div class="info-grid">
            <div class="info-card">
                <h3>📅 Eingereicht am</h3>
                <p><?php echo date('d.m.Y H:i:s', strtotime($application['created_at'])); ?></p>
            </div>
            
            <div class="info-card">
                <h3>🌐 IP-Adresse</h3>
                <p><?php echo htmlspecialchars($application['ip_address']); ?></p>
            </div>
            
            <?php if ($application['reviewed_by_name']): ?>
            <div class="info-card">
                <h3>👨‍💼 Bearbeitet von</h3>
                <p>
                    <?php echo htmlspecialchars($application['reviewed_by_name']); ?><br>
                    <small style="color: #999;">
                        <?php echo date('d.m.Y H:i:s', strtotime($application['reviewed_at'])); ?>
                    </small>
                </p>
            </div>
            <?php endif; ?>
            
            <?php if ($application['appointment_date']): ?>
            <div class="info-card">
                <h3>📅 Termin</h3>
                <p><?php echo date('d.m.Y H:i', strtotime($application['appointment_date'])); ?></p>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Antworten -->
        <div class="answers-section">
            <h2 style="color: #ff4444; margin-bottom: 1.5rem;">📝 Bewerbungsantworten</h2>
            
            <?php if (!empty($answers)): ?>
                <?php foreach ($answers as $index => $answer): ?>
                <div class="answer-card">
                    <div class="answer-type">
                        Frage <?php echo $index + 1; ?> • 
                        <?php echo $answer['question_type'] === 'multiple_choice' ? '📋 Multiple Choice' : '✏️ Textfeld'; ?>
                    </div>
                    <div class="question-title">
                        <?php echo htmlspecialchars($answer['question']); ?>
                    </div>
                    <div class="answer-text">
                        <?php 
                        $answerText = htmlspecialchars($answer['answer']);
                        echo nl2br($answerText);
                        ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 2rem; color: #ccc;">
                    <p>Keine Antworten verfügbar.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Termin-Nachricht (falls vorhanden) -->
        <?php if ($application['appointment_message']): ?>
        <div class="appointment-info">
            <h4>📧 Gesendete Termin-Nachricht</h4>
            <div class="appointment-message">
                <?php echo htmlspecialchars($application['appointment_message']); ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Admin-Notizen (falls vorhanden) -->
        <?php if ($application['notes']): ?>
        <div class="notes-section">
            <h4 style="color: #ffc107;">📝 Admin-Notizen</h4>
            <div class="notes-text">
                <?php echo nl2br(htmlspecialchars($application['notes'])); ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Aktionen -->
        <div class="actions">
            <button onclick="window.print()" class="btn btn-secondary">
                🖨️ Drucken
            </button>
            <button onclick="window.close()" class="btn btn-secondary">
                ❌ Schließen
            </button>
            <?php if ($application['status'] === 'pending'): ?>
            <button onclick="sendAppointment(<?php echo $applicationId; ?>)" class="btn btn-primary">
                📧 Termin senden
            </button>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- TERMIN MODAL - HINZUGEFÜGT -->
    <div id="appointmentModal" class="modal">
        <div class="modal-content" style="max-width: 650px;">
            <div class="modal-header">
                <h2>📅 Termin senden</h2>
                <button class="close" onclick="closeModal('appointmentModal')">&times;</button>
            </div>

            <div class="alert alert-info">
                <strong>📧 Termin-Nachricht:</strong> Der Benutzer erhält eine Discord-Direktnachricht mit dem ausgewählten Termin.
            </div>

            <div id="discord_bot_status" style="margin-bottom: 1rem;">
                <!-- Wird dynamisch gefüllt -->
            </div>

            <div class="user-info" id="appointment_user_info">
                <!-- Wird dynamisch gefüllt -->
            </div>

            <form id="appointmentForm">
                <input type="hidden" id="appointment_application_id" name="application_id">

                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="appointment_date">📅 Datum</label>
                            <input type="date" 
                                   id="appointment_date" 
                                   name="appointment_date" 
                                   class="form-control" 
                                   required>
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label for="appointment_time">🕐 Uhrzeit</label>
                            <input type="time" 
                                   id="appointment_time" 
                                   name="appointment_time" 
                                   class="form-control" 
                                   required>
                            <div>
                                <span class="quick-time" onclick="setTime('18:00')">18:00</span>
                                <span class="quick-time" onclick="setTime('19:00')">19:00</span>
                                <span class="quick-time" onclick="setTime('20:00')">20:00</span>
                                <span class="quick-time" onclick="setTime('21:00')">21:00</span>
                                <span class="quick-time" onclick="setTime('22:00')">22:00</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>📋 Nachrichten-Vorschau</label>
                    <div class="alert alert-warning">
                        <strong>Hinweis:</strong> Die finale Nachricht wird automatisch generiert und die Platzhalter ersetzt.
                    </div>
                    <div class="message-preview" id="message_preview">
                        Wählen Sie Datum und Uhrzeit aus, um eine Vorschau zu sehen...
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('appointmentModal')">
                        ❌ Abbrechen
                    </button>
                    <button type="submit" id="sendAppointmentBtn" class="btn btn-primary">
                        📧 Termin senden
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Global variables
        let currentApplicationData = null;

        /**
         * Termin-Modal öffnen - NEUE VERSION
         */
        function sendAppointment(applicationId) {
            console.log('sendAppointment called with ID:', applicationId);
            
            // Bewerbungsdaten laden
            fetch(`ajax/get-application-details.php?id=${applicationId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Application data loaded:', data);
                    if (data.success && data.application) {
                        currentApplicationData = data.application;
                        setupAppointmentModal(data.application);
                        openModal('appointmentModal');
                    } else {
                        throw new Error(data.error || 'Bewerbungsdaten konnten nicht geladen werden');
                    }
                })
                .catch(error => {
                    console.error('Error loading application details:', error);
                    alert(`Fehler beim Laden der Bewerbungsdaten: ${error.message}`);
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
                            `<img src="${application.discord_avatar}" class="user-avatar-small" alt="Avatar">` :
                            `<div style="width: 48px; height: 48px; border-radius: 50%; background: #5865f2; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 1.2rem;">
                                ${application.discord_username.substring(0, 2).toUpperCase()}
                            </div>`
                        }
                        <div style="flex: 1;">
                            <h4 style="margin: 0; color: white; font-size: 1.1rem;">${escapeHtml(application.discord_username)}</h4>
                            <p style="margin: 0.25rem 0 0 0; color: #ccc; font-size: 0.9rem;">Discord ID: ${application.discord_id}</p>
                            <p style="margin: 0.25rem 0 0 0; color: #ccc; font-size: 0.9rem;">Bewerbung vom: ${formatDateTime(application.created_at)}</p>
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
            
            checkDiscordBotStatus();
            updateMessagePreview();
            
            // Event Listeners für Live-Preview
            if (dateInput) dateInput.addEventListener('change', updateMessagePreview);
            if (timeInput) timeInput.addEventListener('change', updateMessagePreview);
        }

        /**
         * Discord Bot Status prüfen
         */
        function checkDiscordBotStatus() {
            const statusElement = document.getElementById('discord_bot_status');
            if (!statusElement) return;
            
            statusElement.innerHTML = `<div style="color: #3b82f6; font-size: 0.9rem;">🔍 Discord Bot Status wird geprüft...</div>`;
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
         * Modal öffnen/schließen
         */
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (!modal) return;
            
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
            
            const firstInput = modal.querySelector('input:not([type="hidden"])');
            if (firstInput) {
                setTimeout(() => firstInput.focus(), 100);
            }
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (!modal) return;
            
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
        }

        /**
         * Form Handler
         */
        document.getElementById('appointmentForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('sendAppointmentBtn');
            const originalText = submitBtn.textContent;
            
            const date = document.getElementById('appointment_date').value;
            const time = document.getElementById('appointment_time').value;
            const applicationId = document.getElementById('appointment_application_id').value;
            
            if (!date || !time || !applicationId) {
                alert('Bitte füllen Sie alle erforderlichen Felder aus.');
                return;
            }
            
            const selectedDateTime = new Date(date + 'T' + time);
            if (selectedDateTime < new Date()) {
                alert('Der Termin kann nicht in der Vergangenheit liegen.');
                return;
            }
            
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Wird gesendet...';
            
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
                    alert('✅ Termin-Nachricht erfolgreich gesendet!');
                    closeModal('appointmentModal');
                    window.location.reload();
                } else {
                    throw new Error(result.error || 'Unbekannter Fehler beim Senden');
                }
            })
            .catch(error => {
                console.error('Error sending appointment:', error);
                alert(`Fehler beim Senden: ${error.message}`);
            })
            .finally(() => {
                submitBtn.classList.remove('loading');
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            });
        });

        /**
         * Hilfsfunktionen
         */
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

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + P: Print
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                window.print();
            }
            
            // Escape: Close window or modal
            if (e.key === 'Escape') {
                const modal = document.getElementById('appointmentModal');
                if (modal && modal.style.display === 'block') {
                    closeModal('appointmentModal');
                } else {
                    window.close();
                }
            }
            
            // Ctrl/Cmd + T: Send appointment (if pending)
            if ((e.ctrlKey || e.metaKey) && e.key === 't' && '<?php echo $application['status']; ?>' === 'pending') {
                e.preventDefault();
                sendAppointment(<?php echo $applicationId; ?>);
            }
        });

        // Modal außerhalb klicken zum Schließen
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('appointmentModal');
            if (event.target === modal) {
                closeModal('appointmentModal');
            }
        });

        console.log('🎯 View Application mit Termin-System geladen!');
    </script>
</body>
</html>