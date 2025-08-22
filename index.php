<?php
require_once 'config/config.php';

// Server-Daten aus der Datenbank laden
$serverName = getServerSetting('server_name', 'OUTBREAK RP');
$maxPlayers = getServerSetting('max_players', '64');
$currentPlayers = getServerSetting('current_players', '0'); // Korrigiert: zeigt echte Spielerzahl
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

// Roadmap laden (nur aktive Einträge)
$roadmapItems = fetchAll("SELECT * FROM roadmap_items WHERE is_active = 1 ORDER BY priority ASC, created_at DESC");
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
                <?php if (!empty($roadmapItems)): ?>
                <li><a href="#roadmap">Roadmap</a></li>
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

    <!-- Roadmap Section - Timeline Version (3 Items) -->
    <?php if (!empty($roadmapItems)): ?>
    <section id="roadmap" class="section">
        <div class="roadmap-section-header">
            <h2>🗺️ Entwicklungs-Roadmap</h2>
            <p class="roadmap-section-subtitle">
                Hier siehst du unsere wichtigsten geplanten Features und den aktuellen Entwicklungsstand. 
                Von neuen Gameplay-Mechaniken bis hin zu technischen Verbesserungen.
            </p>
        </div>
        
        <div class="roadmap-timeline">
            <?php 
            // Roadmap Items nach Priorität und Status sortieren
            usort($roadmapItems, function($a, $b) {
                // Erst nach Status-Priorität (in_progress > planned > testing > completed > cancelled)
                $statusPriority = [
                    'in_progress' => 1,
                    'planned' => 2,
                    'testing' => 3,
                    'completed' => 4,
                    'cancelled' => 5
                ];
                
                $aStatusPrio = $statusPriority[$a['status']] ?? 6;
                $bStatusPrio = $statusPriority[$b['status']] ?? 6;
                
                if ($aStatusPrio !== $bStatusPrio) {
                    return $aStatusPrio - $bStatusPrio;
                }
                
                // Dann nach Priorität
                if ($a['priority'] !== $b['priority']) {
                    return $a['priority'] - $b['priority'];
                }
                
                // Zuletzt nach Erstellungsdatum
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });
            
            // NUR 3 Items für die Timeline anzeigen
            $timelineItems = array_slice($roadmapItems, 0, 3);
            ?>
            
            <?php foreach ($timelineItems as $index => $item): ?>
            <div class="timeline-item <?php echo $item['status']; ?>" data-priority="<?php echo $item['priority']; ?>">
                <!-- Priority Indicator -->
                <?php if ($item['priority'] <= 2): ?>
                <div class="timeline-priority priority-<?php echo $item['priority']; ?>">
                    <?php echo $item['priority']; ?>
                </div>
                <?php endif; ?>
                
                <div class="timeline-content">
                    <h3 class="timeline-title"><?php echo htmlspecialchars($item['title']); ?></h3>
                    <p class="timeline-description">
                        <?php 
                        $description = htmlspecialchars($item['description']);
                        echo strlen($description) > 120 ? substr($description, 0, 120) . '...' : $description;
                        ?>
                    </p>
                    
                    <div class="timeline-meta">
                        <span class="timeline-status">
                            <?php
                            $statusIcons = [
                                'planned' => '📋',
                                'in_progress' => '⚙️',
                                'testing' => '🧪',
                                'completed' => '✅',
                                'cancelled' => '❌'
                            ];
                            
                            $statusTexts = [
                                'planned' => 'Geplant',
                                'in_progress' => 'In Arbeit',
                                'testing' => 'Testing',
                                'completed' => 'Fertig',
                                'cancelled' => 'Abgebrochen'
                            ];
                            
                            echo ($statusIcons[$item['status']] ?? '📋') . ' ' . ($statusTexts[$item['status']] ?? 'Unbekannt');
                            ?>
                        </span>
                        
                        <?php if ($item['estimated_date']): ?>
                        <span class="timeline-date">
                            📅 <?php echo date('M Y', strtotime($item['estimated_date'])); ?>
                        </span>
                        <?php elseif ($item['completion_date']): ?>
                        <span class="timeline-date">
                            ✅ <?php echo date('M Y', strtotime($item['completion_date'])); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Roadmap Progress Summary -->
        <?php
        $totalItems = count($roadmapItems);
        $completedItems = count(array_filter($roadmapItems, function($item) { return $item['status'] === 'completed'; }));
        $inProgressItems = count(array_filter($roadmapItems, function($item) { return $item['status'] === 'in_progress'; }));
        $plannedItems = count(array_filter($roadmapItems, function($item) { return $item['status'] === 'planned'; }));
        $testingItems = count(array_filter($roadmapItems, function($item) { return $item['status'] === 'testing'; }));
        
        $progressPercentage = $totalItems > 0 ? round(($completedItems / $totalItems) * 100, 1) : 0;
        ?>
        
        <div class="roadmap-progress">
            <div class="progress-item">
                <div class="progress-dot completed"></div>
                <span><?php echo $completedItems; ?> Abgeschlossen</span>
            </div>
            <div class="progress-item">
                <div class="progress-dot in_progress"></div>
                <span><?php echo $inProgressItems; ?> In Arbeit</span>
            </div>
            <div class="progress-item">
                <div class="progress-dot testing"></div>
                <span><?php echo $testingItems; ?> Testing</span>
            </div>
            <div class="progress-item">
                <div class="progress-dot planned"></div>
                <span><?php echo $plannedItems; ?> Geplant</span>
            </div>
            <div class="progress-item" style="margin-left: 1rem; font-weight: 600; color: #ff4444;">
                📊 <?php echo $progressPercentage; ?>% Fortschritt
            </div>
        </div>
        
        <!-- Link zur vollständigen Roadmap -->
        <?php if (count($roadmapItems) > 3): ?>
        <div style="text-align: center; margin-top: 2rem;">
            <p style="color: #ccc; font-size: 0.9rem;">
                <?php echo count($roadmapItems) - 3; ?> weitere Einträge verfügbar
            </p>
            <button onclick="showFullRoadmap()" class="btn btn-secondary" style="padding: 0.75rem 2rem;">
                🗺️ Vollständige Roadmap anzeigen
            </button>
        </div>
        <?php endif; ?>
    </section>
    
    <!-- Full Roadmap Modal -->
    <div id="fullRoadmapModal" class="roadmap-modal">
        <div class="roadmap-modal-content">
            <div class="roadmap-modal-header">
                <h2 class="roadmap-modal-title">🗺️ Vollständige Roadmap</h2>
                <button class="roadmap-close" onclick="closeFullRoadmap()">&times;</button>
            </div>
            
            <div class="roadmap-grid">
                <?php foreach ($roadmapItems as $item): ?>
                <div class="roadmap-card">
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
                        <span style="font-size: 1.5rem;">
                            <?php
                            $statusIcons = [
                                'planned' => '📋',
                                'in_progress' => '⚙️',
                                'testing' => '🧪',
                                'completed' => '✅',
                                'cancelled' => '❌'
                            ];
                            echo $statusIcons[$item['status']] ?? '📋';
                            ?>
                        </span>
                        <h3 style="color: #ff4444; margin: 0; flex: 1;"><?php echo htmlspecialchars($item['title']); ?></h3>
                        <?php if ($item['priority'] <= 2): ?>
                        <div class="timeline-priority priority-<?php echo $item['priority']; ?>" style="position: static; margin: 0;">
                            <?php echo $item['priority']; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <p style="color: #ccc; line-height: 1.5; margin-bottom: 1rem;">
                        <?php echo htmlspecialchars($item['description']); ?>
                    </p>
                    
                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.8rem; color: #999;">
                        <span class="timeline-status <?php echo $item['status']; ?>">
                            <?php
                            $statusTexts = [
                                'planned' => 'Geplant',
                                'in_progress' => 'In Arbeit',
                                'testing' => 'Testing',
                                'completed' => 'Abgeschlossen',
                                'cancelled' => 'Abgebrochen'
                            ];
                            echo $statusTexts[$item['status']] ?? 'Unbekannt';
                            ?>
                        </span>
                        
                        <?php if ($item['estimated_date']): ?>
                        <span>📅 <?php echo date('M Y', strtotime($item['estimated_date'])); ?></span>
                        <?php elseif ($item['completion_date']): ?>
                        <span>✅ <?php echo date('M Y', strtotime($item['completion_date'])); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Modal Footer mit Statistiken -->
            <div style="margin-top: 2rem; padding-top: 1rem; border-top: 1px solid rgba(255, 255, 255, 0.1);">
                <div class="roadmap-progress">
                    <div class="progress-item">
                        <div class="progress-dot completed"></div>
                        <span><?php echo $completedItems; ?> Abgeschlossen</span>
                    </div>
                    <div class="progress-item">
                        <div class="progress-dot in_progress"></div>
                        <span><?php echo $inProgressItems; ?> In Arbeit</span>
                    </div>
                    <div class="progress-item">
                        <div class="progress-dot testing"></div>
                        <span><?php echo $testingItems; ?> Testing</span>
                    </div>
                    <div class="progress-item">
                        <div class="progress-dot planned"></div>
                        <span><?php echo $plannedItems; ?> Geplant</span>
                    </div>
                    <div class="progress-item" style="margin-left: 1rem; font-weight: 600; color: #ff4444;">
                        📊 <?php echo $progressPercentage; ?>% Fortschritt (<?php echo $totalItems; ?> Items insgesamt)
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

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

        // Timeline Animation beim Scrollen
        document.addEventListener('DOMContentLoaded', function() {
            const timelineItems = document.querySelectorAll('.timeline-item');
            
            const timelineObserverOptions = {
                threshold: 0.2,
                rootMargin: '0px 0px -50px 0px'
            };
            
            const timelineObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animate-in');
                    }
                });
            }, timelineObserverOptions);
            
            timelineItems.forEach((item, index) => {
                // Gestaffeltes Erscheinen
                item.style.transitionDelay = `${index * 0.2}s`;
                timelineObserver.observe(item);
            });
            
            // Smooth scroll zu Timeline-Items
            timelineItems.forEach(item => {
                item.addEventListener('click', function() {
                    const rect = this.getBoundingClientRect();
                    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                    const targetY = scrollTop + rect.top - 100;
                    
                    window.scrollTo({
                        top: targetY,
                        behavior: 'smooth'
                    });
                });
            });
        });

        // Vollständige Roadmap anzeigen
        function showFullRoadmap() {
            const modal = document.getElementById('fullRoadmapModal');
            if (modal) {
                modal.classList.add('active');
                document.body.style.overflow = 'hidden'; // Verhindert Hintergrund-Scrolling
                
                // Escape-Key zum Schließen
                document.addEventListener('keydown', closeOnEscape);
            }
        }

        // Vollständige Roadmap schließen
        function closeFullRoadmap() {
            const modal = document.getElementById('fullRoadmapModal');
            if (modal) {
                modal.classList.remove('active');
                document.body.style.overflow = ''; // Scrolling wieder aktivieren
                
                // Event Listener entfernen
                document.removeEventListener('keydown', closeOnEscape);
            }
        }

        // Escape-Key Handler
        function closeOnEscape(e) {
            if (e.key === 'Escape') {
                closeFullRoadmap();
            }
        }

        // Modal bei Klick außerhalb schließen
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('fullRoadmapModal');
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        closeFullRoadmap();
                    }
                });
            }
        });

        // Parallax-Effekt für Timeline-Linie (reduziert)
        window.addEventListener('scroll', function() {
            const timeline = document.querySelector('.roadmap-timeline');
            if (timeline) {
                const scrolled = window.pageYOffset;
                const parallax = scrolled * 0.02; // Sehr subtiler Effekt
                
                timeline.style.transform = `translateY(${parallax}px)`;
            }
        });

        // Automatische Spielerzahl-Updates alle 30 Sekunden
        function updatePlayerCount() {
            fetch("admin/ajax/get-server-status.php")
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Spielerzahl in der Anzeige aktualisieren
                        const statusElements = document.querySelectorAll(".status span:last-child");
                        statusElements.forEach(element => {
                            element.textContent = `Server ${data.online ? "Online" : "Offline"} - ${data.current_players}/${data.max_players} Spieler`;
                        });
                        
                        // Status-Punkt aktualisieren
                        const statusDots = document.querySelectorAll(".status-dot");
                        statusDots.forEach(dot => {
                            dot.style.backgroundColor = data.online ? "#00ff00" : "#ff4444";
                        });
                    } else {
                        console.log("Server Status Update Failed:", data.error);
                    }
                })
                .catch(error => {
                    console.log("Server Status Error:", error);
                });
        }

        // Erste Aktualisierung nach 5 Sekunden
        setTimeout(updatePlayerCount, 5000);

        // Dann alle 30 Sekunden
        setInterval(updatePlayerCount, 30000);

        // Easter Egg: Klick auf Timeline-Linie
        document.addEventListener('DOMContentLoaded', function() {
            const timeline = document.querySelector('.roadmap-timeline');
            let clickCount = 0;
            
            if (timeline) {
                timeline.addEventListener('click', function(e) {
                    // Nur wenn auf die Timeline-Linie geklickt wird (nicht auf Items)
                    if (e.target === this) {
                        clickCount++;
                        if (clickCount === 3) { // 3 Klicks für Easter Egg
                            // Easter Egg
                            const confetti = document.createElement('div');
                            confetti.innerHTML = '🎉✨🚀';
                            confetti.style.cssText = `
                                position: fixed;
                                top: 50%;
                                left: 50%;
                                font-size: 2rem;
                                z-index: 10000;
                                animation: confettiPop 1.5s ease-out forwards;
                                pointer-events: none;
                                text-align: center;
                            `;
                            document.body.appendChild(confetti);
                            
                            setTimeout(() => confetti.remove(), 1500);
                            clickCount = 0;
                            
                            // Konfetti-Animation
                            const style = document.createElement('style');
                            style.textContent = `
                                @keyframes confettiPop {
                                    0% { transform: translate(-50%, -50%) scale(0) rotate(0deg); opacity: 0; }
                                    50% { transform: translate(-50%, -50%) scale(1.5) rotate(180deg); opacity: 1; }
                                    100% { transform: translate(-50%, -50%) scale(0) rotate(360deg); opacity: 0; }
                                }
                            `;
                            document.head.appendChild(style);
                            setTimeout(() => style.remove(), 1500);
                            
                            // Zeige kurz alle Roadmap Items
                            showFullRoadmap();
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>