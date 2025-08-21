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
            <button onclick="sendAppointment()" class="btn btn-primary">
                📧 Termin senden
            </button>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function sendAppointment() {
            if (window.opener && window.opener.sendAppointment) {
                window.opener.sendAppointment(<?php echo $applicationId; ?>);
                window.close();
            } else {
                alert('Funktion nur im Admin-Panel verfügbar.');
            }
        }
        
        // Auto-focus on load for accessibility
        document.addEventListener('DOMContentLoaded', function() {
            document.body.focus();
        });
    </script>
</body>
</html>
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + P: Print
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                window.print();
            }
            
            // Escape: Close window
            if (e.key === 'Escape') {
                window.close();
            }
            
            // Ctrl/Cmd + T: Send appointment (if pending)
            if ((e.ctrlKey || e.metaKey) && e.key === 't' && '<?php echo $application['status']; ?>' === 'pending') {
                e.preventDefault();
                sendAppointment();
            }
        });