-- Datenbankstruktur für OUTBREAK RP Website (Erweitert mit Whitelist-System)
-- Erstelle die Datenbank
CREATE DATABASE IF NOT EXISTS outbreak_rp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE outbreak_rp;

-- Admins Tabelle für sichere Anmeldung
CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('admin','moderator') DEFAULT 'admin',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Server Settings Tabelle
CREATE TABLE `server_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL UNIQUE,
  `setting_value` text,
  `setting_type` enum('text','number','boolean','json') DEFAULT 'text',
  `description` varchar(255),
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- News/Updates Tabelle
CREATE TABLE `news` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `author_id` int(11) NOT NULL,
  `is_published` tinyint(1) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`author_id`) REFERENCES `admins`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Server Rules Tabelle
CREATE TABLE `server_rules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rule_title` varchar(255) NOT NULL,
  `rule_content` text NOT NULL,
  `rule_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Login Attempts Tabelle für Sicherheit
CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `username` varchar(50),
  `success` tinyint(1) DEFAULT 0,
  `attempted_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_ip_time` (`ip_address`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- NEU: Whitelist Questions Tabelle
CREATE TABLE `whitelist_questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question` text NOT NULL,
  `question_type` enum('text','multiple_choice') DEFAULT 'text',
  `options` json NULL, -- JSON Array für Multiple Choice Optionen
  `question_order` int(11) DEFAULT 0,
  `is_required` tinyint(1) DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- NEU: Whitelist Applications Tabelle
CREATE TABLE `whitelist_applications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `discord_id` varchar(100) NOT NULL,
  `discord_username` varchar(100) NOT NULL,
  `discord_avatar` varchar(255),
  `discord_email` varchar(255),
  `status` enum('pending','approved','rejected','closed') DEFAULT 'pending',
  `reviewed_by` int(11) NULL,
  `reviewed_at` timestamp NULL,
  `appointment_message` text NULL,
  `appointment_date` datetime NULL,
  `notes` text NULL,
  `ip_address` varchar(45),
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`reviewed_by`) REFERENCES `admins`(`id`) ON DELETE SET NULL,
  INDEX `idx_discord_id` (`discord_id`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- NEU: Whitelist Answers Tabelle
CREATE TABLE `whitelist_answers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `answer` text NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`application_id`) REFERENCES `whitelist_applications`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`question_id`) REFERENCES `whitelist_questions`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_app_question` (`application_id`, `question_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Standard Admin Account erstellen (Passwort: admin123)
INSERT INTO `admins` (`username`, `password`, `email`, `role`) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@outbreak-rp.de', 'admin');

-- Standard Server Settings (erweitert)
INSERT INTO `server_settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES 
('server_name', 'OUTBREAK RP', 'text', 'Name des Servers'),
('max_players', '64', 'number', 'Maximale Spieleranzahl'),
('current_players', '47', 'number', 'Aktuelle Spieleranzahl'),
('server_ip', 'outbreak-rp.de', 'text', 'Server IP/Domain'),
('discord_link', 'https://discord.gg/outbreak-rp', 'text', 'Discord Server Link'),
('is_online', '1', 'boolean', 'Server Online Status'),
('min_age', '18', 'number', 'Mindestalter'),
('whitelist_active', '1', 'boolean', 'Whitelist aktiviert'),
('discord_client_id', '', 'text', 'Discord OAuth2 Client ID'),
('discord_client_secret', '', 'text', 'Discord OAuth2 Client Secret'),
('discord_redirect_uri', '', 'text', 'Discord OAuth2 Redirect URI'),
('whitelist_questions_count', '5', 'number', 'Anzahl der Whitelist-Fragen pro Bewerbung'),
('whitelist_message_template', 'Hallo {username},\n\ndeine Whitelist-Bewerbung wurde geprüft. Wir möchten dich zu einem Gespräch einladen.\n\nTermin: {appointment_date}\nDiscord: {discord_link}\n\nBitte sei pünktlich!\n\nViele Grüße,\nDas OUTBREAK RP Team', 'text', 'Standard-Nachricht für Whitelist-Termine'),
('whitelist_enabled', '1', 'boolean', 'Whitelist-System aktiviert');

-- Standard Regeln
INSERT INTO `server_rules` (`rule_title`, `rule_content`, `rule_order`) VALUES 
('ROLEPLAY FIRST', 'Bleibe immer im Charakter. Meta-Gaming ist strengstens verboten.', 1),
('KEIN RDM/VDM', 'Töte oder verletze andere Spieler nur mit angemessenem RP-Grund.', 2),
('REALISMUS', 'Deine Aktionen müssen realistisch und nachvollziehbar sein.', 3),
('RESPEKT', 'Behandle alle Spieler mit Respekt, sowohl IC als auch OOC.', 4),
('BUG EXPLOITING', 'Das Ausnutzen von Bugs oder Glitches führt zum permanenten Ban.', 5),
('COMBAT LOGGING', 'Das Verlassen während eines Kampfes ist verboten.', 6),
('POWERGAMING', 'Erzwinge keine Roleplay-Situationen ohne Rücksicht auf andere.', 7),
('MIKROFON PFLICHT', 'Ein funktionierendes Mikrofon ist erforderlich.', 8);

-- Standard Whitelist-Fragen
INSERT INTO `whitelist_questions` (`question`, `question_type`, `options`, `question_order`, `is_required`) VALUES 
('Wie alt bist du?', 'text', NULL, 1, 1),
('Hast du bereits Erfahrung mit Roleplay-Servern?', 'multiple_choice', '["Ja, sehr viel", "Ja, etwas", "Nein, bin Anfänger"]', 2, 1),
('Beschreibe deinen geplanten Charakter (Name, Hintergrundgeschichte, Persönlichkeit)', 'text', NULL, 3, 1),
('Wie würdest du in einer Zombie-Apokalypse überleben?', 'multiple_choice', '["Alleine verstecken", "Gruppe suchen", "Kämpfen und Ressourcen sammeln"]', 4, 1),
('Warum möchtest du auf unserem Server spielen?', 'text', NULL, 5, 1),
('Hast du unsere Serverregeln gelesen und verstanden?', 'multiple_choice', '["Ja, vollständig", "Teilweise", "Nein, noch nicht"]', 6, 1);

-- Database Migration für Whitelist Scoring System
-- Führe diese SQL-Befehle in deiner Datenbank aus

-- 1. Erweitere whitelist_questions Tabelle um correct_answer Spalte
ALTER TABLE whitelist_questions 
ADD COLUMN correct_answer TEXT DEFAULT NULL COMMENT 'Richtige Antwort für Multiple Choice oder Schlüsselwörter für Textfragen';

-- 2. Erweitere whitelist_applications Tabelle um Score-Tracking
ALTER TABLE whitelist_applications 
ADD COLUMN total_questions INT DEFAULT 0 COMMENT 'Anzahl beantworteter Fragen',
ADD COLUMN correct_answers INT DEFAULT 0 COMMENT 'Anzahl richtiger Antworten',
ADD COLUMN score_percentage DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Prozentsatz richtiger Antworten';

-- 3. Entferne IP und Email Tracking (Optional - nur wenn du die Spalten komplett entfernen willst)
-- ALTER TABLE whitelist_applications DROP COLUMN ip_address;
-- ALTER TABLE whitelist_applications DROP COLUMN discord_email;

-- 4. Erweitere whitelist_answers um Bewertung
ALTER TABLE whitelist_answers 
ADD COLUMN is_correct TINYINT(1) DEFAULT 0 COMMENT 'Ist die Antwort korrekt?',
ADD COLUMN auto_evaluated TINYINT(1) DEFAULT 0 COMMENT 'Wurde automatisch bewertet?';

-- 5. Index für bessere Performance
CREATE INDEX idx_applications_score ON whitelist_applications(score_percentage, total_questions);
CREATE INDEX idx_answers_correct ON whitelist_answers(is_correct, auto_evaluated);