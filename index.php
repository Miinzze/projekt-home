<?php
require_once 'config/config.php';

// Server-Daten aus der Datenbank laden
$serverName = getServerSetting('server_name', 'OUTBREAK RP');
$maxPlayers = getServerSetting('max_players', '64');
$currentPlayers = getServerSetting('current_players', '47');
$serverIP = getServerSetting('server_ip', 'outbreak-rp.de');
$discordLink = getServerSetting('discord_link', '#');
$isOnline = getServerSetting('is_online', '1');
$minAge = getServerSetting('min_age', '18');
$whitelistActive = getServerSetting('whitelist_active', '1');
$whitelistEnabled = getServerSetting('whitelist_enabled', '1');

// Server-Regeln laden
$rules = fetchAll("SELECT * FROM server_rules WHERE is_active = 1 ORDER BY rule_order ASC");

// Neueste News laden
$news = fetchAll("SELECT n.*, a.username as author_name FROM news n 
                  LEFT JOIN admins a ON n.author_id = a.id 
                  WHERE n.is_published = 1 
                  ORDER BY n.created_at DESC LIMIT 3");
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($serverName); ?> - Zombie Apokalypse Roleplay</title>
    <meta name="description" content="<?php echo htmlspecialchars($serverName); ?> - Die ultimative Zombie-Apokalypse Erfahrung in FiveM">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo"><?php echo htmlspecialchars($serverName); ?></div>
            <ul class="nav-links">
                <li><a href="#home">Home</a></li>
                <li><a href="#features">Features</a></li>
                <li><a href="#server">Server Info</a></li>
                <li><a href="#rules">Regeln</a></li>
                <?php if (!empty($news)): ?>
                <li><a href="#news">News</a></li>
                <?php endif; ?>
                <?php if ($whitelistEnabled): ?>
                <li><a href="#whitelist">Whitelist</a></li>
                <?php endif; ?>
                <li><a href="<?php echo htmlspecialchars($discordLink); ?>" target="_blank">Discord</a></li>
                <li><a href="admin/login.php" style="opacity: 0.7;">Admin</a></li>
            </ul>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero">
        <div class="hero-content">
            <h1><?php echo htmlspecialchars($serverName); ?></h1>
            <p class="hero-subtitle">Die ultimative Zombie-Apokalypse Erfahrung in FiveM</p>
            <div class="status">
                <span class="status-dot"></span>
                <span>Server <?php echo $isOnline ? 'Online' : 'Offline'; ?> - <?php echo htmlspecialchars($currentPlayers . '/' . $maxPlayers); ?> Spieler</span>
            </div>
            <div class="cta-buttons">
                <a href="#server" class="btn">Jetzt Spielen</a>
                <?php if ($whitelistEnabled && $whitelistActive): ?>
                <a href="#whitelist" class="btn btn-secondary">Whitelist Bewerbung</a>
                <?php endif; ?>
                <a href="<?php echo htmlspecialchars($discordLink); ?>" target="_blank" class="btn btn-secondary">Discord Beitreten</a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="section">
        <h2>Server Features</h2>
        <div class="features">
            <div class="feature-card">
                <div class="feature-icon">🧟</div>
                <h3>Realistische Zombies</h3>
                <p>Hunderte von KI-gesteuerten Zombies bevölkern die Welt mit intelligenter Pathfinding und Gruppenverhalten.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🏗️</div>
                <h3>Base Building</h3>
                <p>Errichte und verteidige deine eigene Basis mit unserem fortschrittlichen Bausystem. Teamwork ist der Schlüssel zum Überleben.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">⚔️</div>
                <h3>Militärische Ausrüstung</h3>
                <p>Über 50 realistische Waffen und militärische Fahrzeuge. Von Pistolen bis hin zu gepanzerten Fahrzeugen.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🩺</div>
                <h3>Medizinisches System</h3>
                <p>Realistische Verletzungen und Heilung. Verband Wunden, splitte Knochen und führe Operationen durch.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📻</div>
                <h3>Funkommunikation</h3>
                <p>Koordiniere dich mit anderen Überlebenden über Funk. Verschiedene Frequenzen und Reichweiten verfügbar.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🎯</div>
                <h3>Missionen & Events</h3>
                <p>Tägliche Events, Supply Drops und spezielle Missionen. Verdiene seltene Belohnungen und Erfahrung.</p>
            </div>
        </div>
    </section>

    <!-- Server Info -->
    <section id="server" class="section">
        <h2>Server Informationen</h2>
        <div class="server-info">
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-number"><?php echo htmlspecialchars($maxPlayers); ?></span>
                    <span class="info-label">Max Spieler</span>
                </div>
                <div class="info-item">
                    <span class="info-number"><?php echo $isOnline ? '24/7' : 'Offline'; ?></span>
                    <span class="info-label">Online Zeit</span>
                </div>
                <div class="info-item">
                    <span class="info-number"><?php echo htmlspecialchars($minAge); ?>+</span>
                    <span class="info-label">Mindestalter</span>
                </div>
                <div class="info-item">
                    <span class="info-number"><?php echo $whitelistActive ? 'Whitelist' : 'Open'; ?></span>
                    <span class="info-label"><?php echo $whitelistActive ? 'Bewerbung erforderlich' : 'Direkt beitreten'; ?></span>
                </div>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 2rem;">
            <h3 style="color: #ff4444; margin-bottom: 1rem;">Connect Info</h3>
            <p style="background: #1a1a1a; padding: 1rem; border-radius: 5px; display: inline-block; font-family: monospace; cursor: pointer;" onclick="copyConnect()">
                connect <?php echo htmlspecialchars($serverIP); ?>
            </p>
            <p style="color: #cccccc; font-size: 0.9rem; margin-top: 0.5rem;">Klicken zum Kopieren | Drücke F8 in FiveM</p>
        </div>
    </section>

    <!-- Whitelist Section -->
    <?php if ($whitelistEnabled): ?>
    <section id="whitelist" class="section">
        <h2>Whitelist Bewerbung</h2>
        <div class="whitelist-info">
            <div style="background: linear-gradient(135deg, #1a1a1a, #2a2a2a); padding: 3rem; border-radius: 15px; border: 1px solid #333; text-align: center;">
                <?php if ($whitelistActive): ?>
                    <div style="margin-bottom: 2rem;">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">📋</div>
                        <h3 style="color: #ff4444; margin-bottom: 1rem;">Jetzt für den Server bewerben!</h3>
                        <p style="color: #cccccc; line-height: 1.6; margin-bottom: 2rem;">
                            Unser Server verwendet ein Whitelist-System, um die Qualität des Roleplays sicherzustellen. 
                            Melde dich mit Discord an und beantworte einige Fragen zu deiner Roleplay-Erfahrung.
                        </p>
                        <div style="background: rgba(255, 68, 68, 0.1); border: 1px solid #ff4444; border-radius: 8px; padding: 1.5rem; margin: 2rem 0;">
                            <h4 style="color: #ff4444; margin-bottom: 1rem;">Bewerbungsprozess:</h4>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; text-align: left;">
                                <div>
                                    <strong style="color: #ff4444;">1. Discord Login</strong><br>
                                    <small style="color: #cccccc;">Authentifizierung über Discord</small>
                                </div>
                                <div>
                                    <strong style="color: #ff4444;">2. Fragebogen</strong><br>
                                    <small style="color: #cccccc;">Einige Fragen zu deiner RP-Erfahrung</small>
                                </div>
                                <div>
                                    <strong style="color: #ff4444;">3. Prüfung</strong><br>
                                    <small style="color: #cccccc;">Unser Team prüft deine Bewerbung</small>
                                </div>
                                <div>
                                    <strong style="color: #ff4444;">4. Gespräch</strong><br>
                                    <small style="color: #cccccc;">Kurzes Interview auf Discord</small>
                                </div>
                            </div>
                        </div>
                        <button onclick="startWhitelistApplication()" class="btn btn-primary" style="font-size: 1.1rem; padding: 1rem 2rem;">
                            📋 Whitelist-Bewerbung starten
                        </button>
                    </div>
                <?php else: ?>
                    <div style="margin-bottom: 2rem;">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">❌</div>
                        <h3 style="color: #ff4444; margin-bottom: 1rem;">Whitelist vorübergehend geschlossen</h3>
                        <p style="color: #cccccc; line-height: 1.6;">
                            Die Whitelist-Bewerbungen sind momentan geschlossen. Folge uns auf Discord für Updates.
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- News Section -->
    <?php if (!empty($news)): ?>
    <section id="news" class="section">
        <h2>Server News</h2>
        <div class="features">
            <?php foreach ($news as $article): ?>
            <div class="feature-card">
                <div class="feature-icon">📰</div>
                <h3><?php echo htmlspecialchars($article['title']); ?></h3>
                <p><?php echo htmlspecialchars(substr(strip_tags($article['content']), 0, 150)) . '...'; ?></p>
                <div style="margin-top: 1rem; color: #ff4444; font-size: 0.875rem;">
                    Von <?php echo htmlspecialchars($article['author_name']); ?> • 
                    <?php echo date('d.m.Y', strtotime($article['created_at'])); ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Rules Section -->
    <section id="rules" class="section">
        <h2>Server Regeln</h2>
        <?php if (!empty($rules)): ?>
        <ul class="rules-list">
            <?php foreach ($rules as $rule): ?>
            <li>
                <strong><?php echo htmlspecialchars($rule['rule_title']); ?>:</strong> 
                <?php echo htmlspecialchars($rule['rule_content']); ?>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php else: ?>
        <ul class="rules-list">
            <li><strong>Roleplay First:</strong> Bleibe immer im Charakter. Meta-Gaming ist strengstens verboten.</li>
            <li><strong>Kein RDM/VDM:</strong> Töte oder verletze andere Spieler nur mit angemessenem RP-Grund.</li>
            <li><strong>Realismus:</strong> Deine Aktionen müssen realistisch und nachvollziehbar sein.</li>
            <li><strong>Respekt:</strong> Behandle alle Spieler mit Respekt, sowohl IC als auch OOC.</li>
            <li><strong>Bug Exploiting:</strong> Das Ausnutzen von Bugs oder Glitches führt zum permanenten Ban.</li>
            <li><strong>Combat Logging:</strong> Das Verlassen während eines Kampfes ist verboten.</li>
            <li><strong>Powergaming:</strong> Erzwinge keine Roleplay-Situationen ohne Rücksicht auf andere.</li>
            <li><strong>Mikrofon Pflicht:</strong> Ein funktionierendes Mikrofon ist erforderlich.</li>
        </ul>
        <?php endif; ?>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="social-links">
                <a href="<?php echo htmlspecialchars($discordLink); ?>" target="_blank">📱 Discord</a>
                <a href="#">📋 Forum</a>
                <a href="#">📺 Twitch</a>
                <a href="#">🎥 YouTube</a>
            </div>
            <p>&copy; 2025 <?php echo htmlspecialchars($serverName); ?>. Alle Rechte vorbehalten.</p>
            <p style="color: #666; font-size: 0.9rem; margin-top: 0.5rem;">
                Nicht offiziell mit Rockstar Games oder Take-Two Interactive verbunden.
            </p>
            <p style="color: #666; font-size: 0.8rem; margin-top: 1rem;">
                Server Status: <span style="color: <?php echo $isOnline ? '#00ff00' : '#ff4444'; ?>">
                    <?php echo $isOnline ? 'Online' : 'Offline'; ?>
                </span>
            </p>
        </div>
    </footer>

    <script>
        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Navbar scroll effect
        window.addEventListener('scroll', () => {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 100) {
                navbar.style.background = 'rgba(0, 0, 0, 0.95)';
            } else {
                navbar.style.background = 'rgba(0, 0, 0, 0.9)';
            }
        });

        // Player count animation
        function updatePlayerCount() {
            const statusText = document.querySelector('.status span:last-child');
            if (statusText) {
                const current = parseInt(statusText.textContent.match(/(\d+)\/\d+ Spieler/)[1]);
                const variation = Math.floor(Math.random() * 6) - 3; // -3 to +3
                const newCount = Math.max(0, Math.min(<?php echo $maxPlayers; ?>, current + variation));
                
                if (newCount !== current) {
                    statusText.textContent = `Server <?php echo $isOnline ? 'Online' : 'Offline'; ?> - ${newCount}/<?php echo $maxPlayers; ?> Spieler`;
                    
                    // Update in background
                    fetch('admin/ajax/update_players.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ current_players: newCount })
                    }).catch(err => console.log('Player count update failed'));
                }
            }
        }

        // Copy connect command
        function copyConnect() {
            const connectText = 'connect <?php echo htmlspecialchars($serverIP); ?>';
            navigator.clipboard.writeText(connectText).then(() => {
                const element = event.target;
                const originalText = element.textContent;
                element.textContent = 'In Zwischenablage kopiert!';
                element.style.color = '#00ff00';
                
                setTimeout(() => {
                    element.textContent = originalText;
                    element.style.color = '';
                }, 2000);
            }).catch(() => {
                console.log('Clipboard access failed');
            });
        }

        // Whitelist application function
        function startWhitelistApplication() {
            <?php if ($whitelistActive): ?>
            window.location.href = 'whitelist/apply.php';
            <?php else: ?>
            alert('Die Whitelist-Bewerbungen sind momentan geschlossen.');
            <?php endif; ?>
        }

        // Update player count every 30 seconds
        setInterval(updatePlayerCount, 30000);

        // Feature card animations on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observe feature cards
        document.querySelectorAll('.feature-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(card);
        });
    </script>
</body>
</html>