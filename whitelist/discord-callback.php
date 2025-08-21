<?php
/**
 * Discord Callback - Überarbeitete Version zur Behebung von Session-Problemen
 */

require_once '../config/config.php';

// Session-Konfiguration verstärken
ini_set('session.cookie_lifetime', 7200); // 2 Stunden
ini_set('session.gc_maxlifetime', 7200);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Lax'); // Weniger restriktiv als 'Strict'

// Session starten oder fortsetzen
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Extended Debug-Funktion
function logDebug($message) {
    $logMessage = "[" . date('Y-m-d H:i:s') . "] " . $message;
    error_log($logMessage);
    
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        echo "<!-- DEBUG: " . htmlspecialchars($message) . " -->\n";
    }
}

function redirectWithError($message) {
    logDebug("REDIRECT_ERROR: " . $message);
    setFlashMessage('error', $message);
    
    // Session explizit speichern
    session_write_close();
    
    header('Location: apply.php');
    exit;
}

logDebug("=== DISCORD CALLBACK START ===");
logDebug("Session ID: " . session_id());
logDebug("Session Status: " . session_status());
logDebug("Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'unknown'));

// 1. OAuth Error Check
if (isset($_GET['error'])) {
    $error = 'Discord-Authentifizierung abgebrochen: ' . htmlspecialchars($_GET['error_description'] ?? $_GET['error']);
    logDebug("OAuth error: " . $error);
    redirectWithError($error);
}

// 2. Parameter Validierung
$code = $_GET['code'] ?? '';
$receivedState = $_GET['state'] ?? '';

if (empty($code)) {
    logDebug("No authorization code received");
    redirectWithError('Kein Authorization Code von Discord erhalten.');
}

if (empty($receivedState)) {
    logDebug("No state parameter received");
    redirectWithError('Kein State-Parameter erhalten.');
}

logDebug("Code: " . substr($code, 0, 20) . "...");
logDebug("State received: " . $receivedState);

// 3. State Validation mit mehreren Fallbacks
$sessionState = $_SESSION['discord_state'] ?? '';
$cookieState = $_COOKIE['discord_state_backup'] ?? '';

logDebug("Session state: " . $sessionState);
logDebug("Cookie state: " . $cookieState);

$validState = false;

if (!empty($sessionState) && hash_equals($sessionState, $receivedState)) {
    $validState = true;
    logDebug("State validation: SUCCESS (session)");
} elseif (!empty($cookieState) && hash_equals($cookieState, $receivedState)) {
    $validState = true;
    logDebug("State validation: SUCCESS (cookie fallback)");
    $_SESSION['discord_state'] = $cookieState;
} else {
    logDebug("State validation: FAILED");
    
    // In Debug-Modus erlauben wir Bypass
    if (defined('DEBUG_MODE') && DEBUG_MODE && isset($_GET['bypass_state'])) {
        logDebug("BYPASSING state validation for debug purposes");
        $validState = true;
    } else {
        redirectWithError('Session-Fehler bei Discord-Authentifizierung. Bitte versuchen Sie es erneut.');
    }
}

// 4. Discord Configuration
$discordConfig = getDiscordConfig();
if (empty($discordConfig['client_id']) || empty($discordConfig['client_secret'])) {
    logDebug("Discord config incomplete");
    redirectWithError('Discord-Integration ist nicht vollständig konfiguriert.');
}

// 5. Token Exchange
logDebug("Starting token exchange...");

$tokenData = [
    'client_id' => $discordConfig['client_id'],
    'client_secret' => $discordConfig['client_secret'],
    'grant_type' => 'authorization_code',
    'code' => $code,
    'redirect_uri' => $discordConfig['redirect_uri']
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'https://discord.com/api/oauth2/token',
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query($tokenData),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
    CURLOPT_TIMEOUT => 30,
    CURLOPT_USERAGENT => 'WhitelistBot/1.0',
    CURLOPT_SSL_VERIFYPEER => true
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

logDebug("Token exchange: HTTP $httpCode");

if ($httpCode !== 200 || !$response) {
    logDebug("Token exchange failed: HTTP $httpCode, Response: $response, CURL Error: $curlError");
    redirectWithError('Discord-Token konnte nicht ausgetauscht werden.');
}

$tokenResponse = json_decode($response, true);
if (!$tokenResponse || !isset($tokenResponse['access_token'])) {
    logDebug("Invalid token response: $response");
    redirectWithError('Ungültige Token-Antwort von Discord.');
}

logDebug("Token exchange successful");

// 6. User Info Request
logDebug("Fetching Discord user info...");

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'https://discord.com/api/v10/users/@me',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $tokenResponse['access_token'],
        'User-Agent: WhitelistBot/1.0'
    ],
    CURLOPT_TIMEOUT => 30
]);

$userResponse = curl_exec($ch);
$userHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

logDebug("User info request: HTTP $userHttpCode");

if ($userHttpCode !== 200 || !$userResponse) {
    logDebug("User info failed: HTTP $userHttpCode, Response: $userResponse");
    redirectWithError('Discord-Benutzerdaten konnten nicht abgerufen werden.');
}

$discordUser = json_decode($userResponse, true);
if (!$discordUser || !isset($discordUser['id'])) {
    logDebug("Invalid user response: $userResponse");
    redirectWithError('Ungültige Benutzerdaten von Discord erhalten.');
}

// Avatar URL aufbauen
if (!empty($discordUser['avatar'])) {
    $discordUser['avatar'] = 'https://cdn.discordapp.com/avatars/' . $discordUser['id'] . '/' . $discordUser['avatar'] . '.png';
}

logDebug("Discord user loaded: " . $discordUser['username'] . " (ID: " . $discordUser['id'] . ")");

// 7. Bestehende Bewerbungen prüfen
try {
    $existingApp = fetchOne(
        "SELECT id, status, score_percentage, created_at FROM whitelist_applications WHERE discord_id = :discord_id ORDER BY created_at DESC LIMIT 1",
        ['discord_id' => $discordUser['id']]
    );
    
    if ($existingApp) {
        logDebug("Existing application found: " . json_encode($existingApp));
        
        if ($existingApp['status'] === 'pending') {
            setFlashMessage('info', 'Sie haben bereits eine offene Whitelist-Bewerbung vom ' . date('d.m.Y', strtotime($existingApp['created_at'])) . '. Bitte warten Sie auf die Bearbeitung.');
            header('Location: ../index.php#whitelist');
            exit;
        } elseif ($existingApp['status'] === 'approved') {
            setFlashMessage('success', 'Sie sind bereits für den Server genehmigt! Sie können sofort spielen.');
            header('Location: ../index.php#whitelist');
            exit;
        }
    }
} catch (Exception $e) {
    logDebug("Error checking existing applications: " . $e->getMessage());
}

// 8. Session kritisch speichern - MEHRFACH-SICHERUNG
logDebug("Storing user in session...");

// Alte Session aufräumen aber kritische Daten erhalten
$_SESSION['discord_user'] = $discordUser;
$_SESSION['discord_auth_time'] = time();
$_SESSION['discord_auth_ip'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

// Zusätzliche Verifikations-Token für Session-Wiederherstellung
$sessionToken = bin2hex(random_bytes(16));
$_SESSION['discord_session_token'] = $sessionToken;

// States aufräumen
unset($_SESSION['discord_state']);

// Cookie-Backup als zusätzliche Sicherheit
setcookie('discord_user_backup', base64_encode(json_encode([
    'user' => $discordUser,
    'token' => $sessionToken,
    'time' => time()
])), time() + 3600, '/whitelist/', $_SERVER['HTTP_HOST'] ?? '', isset($_SERVER['HTTPS']), true);

// State-Cookie löschen
if (isset($_COOKIE['discord_state_backup'])) {
    setcookie('discord_state_backup', '', time() - 3600, '/whitelist/', $_SERVER['HTTP_HOST'] ?? '');
}

// KRITISCH: Session mehrfach schreiben
session_write_close();
session_start();

// Session-Verifikation
$verifyUser = $_SESSION['discord_user'] ?? null;
logDebug("Session verification: " . ($verifyUser ? $verifyUser['username'] : 'FAILED'));

// 9. Whitelist System Check
$whitelistEnabled = getServerSetting('whitelist_enabled', '1');
$whitelistActive = getServerSetting('whitelist_active', '1');

if (!$whitelistEnabled || !$whitelistActive) {
    logDebug("Whitelist system disabled");
    setFlashMessage('error', 'Whitelist-Bewerbungen sind momentan deaktiviert.');
    header('Location: ../index.php#whitelist');
    exit;
}

// 10. Questions Check
$questions = getWhitelistQuestions();
if (empty($questions)) {
    logDebug("No active questions found");
    setFlashMessage('error', 'Keine aktiven Whitelist-Fragen konfiguriert. Kontaktieren Sie den Administrator.');
    header('Location: ../index.php#whitelist');
    exit;
}

logDebug("Found " . count($questions) . " active questions");

// 11. Success - Final Session Write und Redirect
logDebug("=== Discord Authentication Successful ===");
logDebug("User: " . $discordUser['username'] . " (" . $discordUser['id'] . ")");
logDebug("Session ID: " . session_id());

setFlashMessage('success', 'Discord-Anmeldung erfolgreich! Füllen Sie nun das Bewerbungsformular aus.');

// Session final schließen
session_write_close();

// Debug-Seite für Entwicklung
if (defined('DEBUG_MODE') && DEBUG_MODE) {
    echo "<!DOCTYPE html><html><head><title>Discord Auth Debug</title>";
    echo "<style>body{font-family:Arial;background:#222;color:#fff;padding:20px;}</style></head><body>";
    echo "<h1>✅ Discord Authentication Successful!</h1>";
    echo "<p><strong>User:</strong> " . htmlspecialchars($discordUser['username']) . "</p>";
    echo "<p><strong>ID:</strong> " . htmlspecialchars($discordUser['id']) . "</p>";
    echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
    echo "<p><strong>Questions Available:</strong> " . count($questions) . "</p>";
    echo "<p><strong>Cookie Set:</strong> " . (isset($_COOKIE['discord_user_backup']) ? 'Yes' : 'No') . "</p>";
    echo "<div style='margin:20px 0;'>";
    echo "<p>Weiterleitung in <span id='countdown'>5</span> Sekunden...</p>";
    echo "<a href='apply.php' style='color:#fff;background:#5865f2;padding:15px 25px;text-decoration:none;border-radius:5px;'>▶️ Sofort zur Bewerbung</a>";
    echo "</div>";
    echo "<script>
        let count = 5;
        const countdown = setInterval(() => {
            count--;
            document.getElementById('countdown').textContent = count;
            if (count <= 0) {
                clearInterval(countdown);
                window.location.href = 'apply.php';
            }
        }, 1000);
    </script>";
    echo "</body></html>";
    exit;
}

// Production Redirect
header('Location: apply.php');
exit;
?>