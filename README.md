# OUTBREAK RP - FiveM Zombie Roleplay Server Website

Eine vollständige PHP-Website für FiveM Zombie-Apokalypse Roleplay Server mit sicherem Admin-Panel.

## ✨ Features

### Frontend
- **Responsive Design** - Funktioniert auf allen Geräten
- **Moderne UI** - Ansprechendes Design mit Animationen
- **Live Server Status** - Echtzeit Spieleranzahl
- **Dynamic Content** - News, Regeln und Server-Infos aus der Datenbank
- **SEO-optimiert** - Suchmaschinenfreundlich

### Admin Panel
- **Sicheres Login** - Passwort-Hashing, CSRF-Schutz, Rate-Limiting
- **Server-Verwaltung** - Einstellungen, Spieleranzahl, Status
- **News-System** - Artikel erstellen, bearbeiten, veröffentlichen
- **Regel-Management** - Server-Regeln verwalten und sortieren
- **Login-Protokoll** - Überwachung aller Anmeldeversuche
- **Dashboard** - Übersicht über alle wichtigen Statistiken

### Sicherheit
- **SQL Injection Schutz** - Prepared Statements
- **XSS Protection** - Input Sanitization
- **CSRF Tokens** - Schutz vor Cross-Site Request Forgery
- **Session Security** - Sichere Session-Verwaltung
- **Rate Limiting** - Schutz vor Brute-Force Angriffen
- **Security Headers** - via .htaccess

## 🚀 Installation

### Voraussetzungen
- **PHP 7.4+** mit folgenden Extensions:
  - PDO
  - PDO MySQL
  - OpenSSL
  - JSON
  - Sessions
- **MySQL/MariaDB 5.7+**
- **Apache/Nginx** Webserver
- **mod_rewrite** (für Apache)

### Automatische Installation

1. **Dateien hochladen**
   ```bash
   # Alle Dateien in Ihr Webverzeichnis hochladen
   # z.B. /var/www/html/ oder /public_html/
   ```

2. **Browser öffnen**
   ```
   Navigieren Sie zu: http://ihre-domain.de/setup.php
   ```

3. **Setup-Wizard befolgen**
   - Schritt 1: Systemanforderungen prüfen
   - Schritt 2: Datenbank konfigurieren
   - Schritt 3: Admin-Account erstellen
   - Schritt 4: Installation abschließen

4. **setup.php löschen**
   ```bash
   rm setup.php  # Aus Sicherheitsgründen!
   ```

### Manuelle Installation

1. **Datenbank erstellen**
   ```sql
   CREATE DATABASE outbreak_rp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

2. **SQL-Datei importieren**
   ```bash
   mysql -u username -p outbreak_rp < database.sql
   ```

3. **Konfiguration anpassen**
   ```php
   // config/database.php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'outbreak_rp');
   define('DB_USER', 'your_username');
   define('DB_PASS', 'your_password');
   ```

4. **Verzeichnisberechtigungen setzen**
   ```bash
   chmod 755 assets/
   chmod 755 config/
   chmod 644 config/*.php
   ```

5. **Admin-Account erstellen**
   ```sql
   INSERT INTO admins (username, password, email, role) VALUES 
   ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@example.com', 'admin');
   -- Passwort: admin123 (bitte ändern!)
   ```

## 📁 Dateistruktur

```
outbreak-rp-website/
├── index.php                 # Hauptseite
├── setup.php                 # Installations-Wizard
├── .htaccess                  # Apache-Konfiguration
├── README.md                  # Diese Datei
├── database.sql               # Datenbankstruktur
│
├── config/
│   ├── config.php            # Hauptkonfiguration
│   └── database.php          # Datenbankverbindung
│
├── admin/
│   ├── login.php             # Admin Login
│   ├── dashboard.php         # Admin Dashboard
│   ├── logout.php            # Admin Logout
│   └── ajax/
│       └── update_players.php # AJAX Player Update
│
└── assets/
    └── css/
        └── style.css         # Haupt-Stylesheet
```

## ⚙️ Konfiguration

### Server-Einstellungen
Alle wichtigen Einstellungen können über das Admin-Panel verwaltet werden:

- **Server Name** - Name Ihres RP Servers
- **IP/Domain** - Connect-Adresse für FiveM
- **Spieleranzahl** - Aktuelle und maximale Slots
- **Discord Link** - Community Discord Server
- **Whitelist** - Ein/Ausschalten der Bewerbungspflicht
- **Mindestalter** - Altersbeschränkung

### Sicherheitseinstellungen
```php
// config/config.php
define('SESSION_TIMEOUT', 3600);        // Session-Dauer (Sekunden)
define('MAX_LOGIN_ATTEMPTS', 5);        // Max. Login-Versuche
define('LOGIN_LOCKOUT_TIME', 900);      // Sperrzeit (Sekunden)
define('MIN_PASSWORD_LENGTH', 8);       // Min. Passwort-Länge
```

## 🔧 Wartung & Updates

### Backup
```bash
# Datenbank-Backup
mysqldump -u username -p outbreak_rp > backup_$(date +%Y%m%d).sql

# Datei-Backup
tar -czf website_backup_$(date +%Y%m%d).tar.gz /path/to/website/
```

### Log-Dateien überwachen
- Login-Versuche: Admin Panel → Logs
- Server-Logs: `/var/log/apache2/` oder `/var/log/nginx/`
- PHP-Errors: `/var/log/php_errors.log`

### Updates einspielen
1. Backup erstellen
2. Neue Dateien hochladen
3. Datenbankänderungen anwenden (falls nötig)
4. Cache leeren

## 🛡️ Sicherheitstipps

### Produktionsumgebung
1. **Debug-Modus deaktivieren**
   ```php
   define('DEBUG_MODE', false);
   ```

2. **HTTPS aktivieren**
   ```apache
   # .htaccess uncomment:
   RewriteCond %{HTTPS} off
   RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
   ```

3. **Sichere Passwörter verwenden**
   - Mindestens 12 Zeichen
   - Groß-/Kleinbuchstaben, Zahlen, Sonderzeichen
   - Regelmäßig ändern

4. **Firewall konfigurieren**
   ```bash
   # Nur notwendige Ports öffnen
   ufw allow 80/tcp    # HTTP
   ufw allow 443/tcp   # HTTPS
   ufw allow 22/tcp    # SSH
   ```

### Überwachung
- Login-Protokolle regelmäßig prüfen
- Ungewöhnliche IP-Adressen blockieren
- Automatische Backups einrichten
- Updates zeitnah einspielen

## 🎨 Anpassungen

### Design ändern
```css
/* assets/css/style.css */
:root {
    --primary: #ff6b35;     /* Hauptfarbe */
    --secondary: #f7931e;   /* Sekundärfarbe */
    --dark: #1a1a1a;        /* Dunkler Hintergrund */
}
```

### Inhalte bearbeiten
- **Texte**: Über Admin Panel → Einstellungen
- **Regeln**: Admin Panel → Regeln
- **News**: Admin Panel → News
- **Features**: Direkt in `index.php` (Zeile ~180)

### Neue Funktionen hinzufügen
1. Datenbankstruktur erweitern (`database.sql`)
2. Admin-Panel Funktionen ergänzen (`admin/dashboard.php`)
3. Frontend anpassen (`index.php`)

## 📞 Support

### Häufige Probleme

**Setup-Fehler "Requirements not met"**
- PHP-Version prüfen: `php -v`
- Extensions installieren: `apt install php-pdo php-mysql`
- Verzeichnisrechte setzen: `chmod 755 config/ assets/`

**Admin-Login funktioniert nicht**
- Datenbank prüfen: Ist `admins` Tabelle vorhanden?
- Passwort zurücksetzen:
  ```sql
  UPDATE admins SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE username = 'admin';
  -- Passwort ist dann: admin123
  ```

**500 Internal Server Error**
- PHP Error-Log prüfen
- `.htaccess` temporär umbenennen
- Debug-Modus aktivieren

**Player Count Update funktioniert nicht**
- JavaScript-Console prüfen
- AJAX-Pfad korrekt: `/admin/ajax/update_players.php`
- Session-Timeout prüfen

### Kontakt
- **GitHub Issues**: Für Bugs und Feature-Requests
- **Discord**: Community-Support
- **E-Mail**: Für private Anfragen

## 📜 Lizenz

Dieses Projekt steht unter der MIT-Lizenz. Sie können es frei verwenden, ändern und verteilen.

## 🙏 Credits

- **Design**: Inspiriert von modernen Gaming-Websites
- **Icons**: Unicode Emojis
- **Fonts**: Google Fonts (Orbitron, Rajdhani)
- **Framework**: Vanilla PHP für maximale Kompatibilität

---

**⚠️ Wichtiger Hinweis**: Diese Website ist für FiveM Roleplay-Server gedacht und nicht offiziell mit Rockstar Games oder Take-Two Interactive verbunden.

## 🚀 Deployment-Checkliste

- [ ] Alle Dateien hochgeladen
- [ ] Datenbank erstellt und konfiguriert
- [ ] Admin-Account eingerichtet
- [ ] `setup.php` gelöscht
- [ ] `.htaccess` aktiviert
- [ ] HTTPS konfiguriert (Produktion)
- [ ] Debug-Modus deaktiviert
- [ ] Backup-System eingerichtet
- [ ] Firewall konfiguriert
- [ ] Domain auf Server zeigend

**Viel Erfolg mit Ihrem OUTBREAK RP Server! 🧟‍♂️⚔️**