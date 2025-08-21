<?php
require_once '../config/config.php';

// Überprüfen ob Benutzer eingeloggt ist
if (isLoggedIn()) {
    // Logout-Zeit in Datenbank protokollieren
    $adminId = $_SESSION['admin_id'] ?? null;
    if ($adminId) {
        updateData('admins', 
            ['last_login' => date('Y-m-d H:i:s')], 
            'id = :id', 
            ['id' => $adminId]
        );
        
        // Login-Protokoll für erfolgreichen Logout
        logLoginAttempt(getUserIP(), $_SESSION['admin_username'] ?? 'Unknown', true);
    }
    
    // Flash Message für erfolgreichen Logout setzen
    setFlashMessage('info', 'Sie wurden erfolgreich abgemeldet.');
}

// Session vollständig zerstören
session_unset();
session_destroy();

// Neue Session für Flash Messages starten
session_start();
setFlashMessage('info', 'Sie wurden erfolgreich abgemeldet.');

// Zur Login-Seite weiterleiten
redirect('login.php');
?>