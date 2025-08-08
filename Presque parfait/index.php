<?php
require_once 'includes/auth.php';
$auth = new Auth();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Services de Marketing Digital</title>
    <meta name="description" content="Boostez votre présence sur les réseaux sociaux avec nos services SMM professionnels. Followers, likes, vues et plus encore.">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <div class="nav-content">
                <a href="index.php" class="logo">
                    <i class="fas fa-rocket"></i>
                    <?php echo SITE_NAME; ?>
                </a>

                <div class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>

                <ul class="nav-links" id="navLinks">
                    <li><a href="#accueil">Accueil</a></li>
                    <li><a href="#services">Services</a></li>
                    <li><a href="#apropos">À propos</a></li>
                    <li><a href="#contact">Contact</a></li>
                    <?php if ($auth->isUserLoggedIn()): ?>
                        <li><a href="dashboard.php">Dashboard</a></li>
                        <li><a href="logout.php" class="btn btn-secondary">Déconnexion</a></li>
                    <?php else: ?>
                        <li><a href="login.php" class="btn btn-secondary">Connexion</a></li>
                        <li><a href="register.php" class="btn btn-primary">S'inscrire</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="accueil" class="hero">
        <div class="container">
            <div class="hero-content fade-in-up">
                <h1>Boostez Votre Présence Digitale</h1>
                <p>Développez votre audience sur tous les réseaux sociaux avec nos services SMM professionnels et sécurisés. Résultats garantis, livraison rapide.</p>
                <div style="margin-top: 2rem; gap: 1rem; display: flex; justify-content: center; flex-wrap: wrap;">
                    <?php if ($auth->isUserLoggedIn()): ?>
                        <a href="dashboard.php" class="btn btn-primary">
                            <i class="fas fa-tachometer-alt"></i> Mon Dashboard
                        </a>
                        <a href="order.php" class="btn btn-secondary">
                            <i class="fas fa-shopping-cart"></i> Commander
                        </a>
                    <?php else: ?>
                        <a href="register.php" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> Commencer Maintenant
                        </a>
                        <a href="#services" class="btn btn-secondary">
                            <i class="fas fa-eye"></i> Voir les Services
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="section">
        <div class="container">
            <h2 class="section-title">Nos Services</h2>
            <div class="services-grid">
                <div class="card service-card fade-in-up">
                    <div class="service-icon">
                        <i class="fab fa-instagram"></i>
                    </div>
                    <h3>Instagram</h3>
                    <p>Followers, likes, vues stories, commentaires et plus encore pour votre compte Instagram professionnel.</p>
                    <ul style="text-align: left; margin: 1rem 0; color: var(--text-secondary);">
                        <li>✓ Followers de qualité</li>
                        <li>✓ Likes instantanés</li>
                        <li>✓ Vues stories</li>
                        <li>✓ Commentaires personnalisés</li>
                    </ul>
                </div>

                <div class="card service-card fade-in-up">
                    <div class="service-icon">
                        <i class="fab fa-facebook"></i>
                    </div>
                    <h3>Facebook</h3>
                    <p>Développez votre page Facebook avec nos services de likes, followers et partages authentiques.</p>
                    <ul style="text-align: left; margin: 1rem 0; color: var(--text-secondary);">
                        <li>✓ Likes de page</li>
                        <li>✓ Followers actifs</li>
                        <li>✓ Partages organiques</li>
                        <li>✓ Réactions diversifiées</li>
                    </ul>
                </div>

                <div class="card service-card fade-in-up">
                    <div class="service-icon">
                        <i class="fab fa-youtube"></i>
                    </div>
                    <h3>YouTube</h3>
                    <p>Boostez vos vidéos YouTube avec des vues, likes, abonnés et commentaires pour améliorer votre référencement.</p>
                    <ul style="text-align: left; margin: 1rem 0; color: var(--text-secondary);">
                        <li>✓ Vues haute rétention</li>
                        <li>✓ Abonnés actifs</li>
                        <li>✓ Likes et commentaires</li>
                        <li>✓ Temps de visionnage</li>
                    </ul>
                </div>

                <div class="card service-card fade-in-up">
                    <div class="service-icon">
                        <i class="fab fa-tiktok"></i>
                    </div>
                    <h3>TikTok</h3>
                    <p>Devenez viral sur TikTok avec nos services de followers, likes et vues pour vos vidéos créatives.</p>
                    <ul style="text-align: left; margin: 1rem 0; color: var(--text-secondary);">
                        <li>✓ Followers TikTok</li>
                        <li>✓ Likes vidéos</li>
                        <li>✓ Vues organiques</li>
                        <li>✓ Partages et saves</li>
                    </ul>
                </div>

                <div class="card service-card fade-in-up">
                    <div class="service-icon">
                        <i class="fab fa-twitter"></i>
                    </div>
                    <h3>Twitter</h3>
                    <p>Augmentez votre influence sur Twitter avec des followers, retweets et likes authentiques.</p>
                    <ul style="text-align: left; margin: 1rem 0; color: var(--text-secondary);">
                        <li>✓ Followers Twitter</li>
                        <li>✓ Retweets et likes</li>
                        <li>✓ Réponses engagées</li>
                        <li>✓ Impressions accrues</li>
                    </ul>
                </div>

                <div class="card service-card fade-in-up">
                    <div class="service-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>Services Premium</h3>
                    <p>Services avancés pour les entreprises et influenceurs : analytics, gestion de campagnes et stratégies personnalisées.</p>
                    <ul style="text-align: left; margin: 1rem 0; color: var(--text-secondary);">
                        <li>✓ Stratégies sur mesure</li>
                        <li>✓ Suivi analytique</li>
                        <li>✓ Gestion de campagnes</li>
                        <li>✓ Support prioritaire</li>
                    </ul>
                </div>
            </div>

            <div style="text-align: center; margin-top: 3rem;">
                <?php if (!$auth->isUserLoggedIn()): ?>
                    <a href="register.php" class="btn btn-primary">
                        <i class="fas fa-rocket"></i> Commencer Dès Maintenant
                    </a>
                <?php else: ?>
                    <a href="order.php" class="btn btn-primary">
                        <i class="fas fa-shopping-cart"></i> Passer une Commande
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- À propos Section -->
    <section id="apropos" class="section">
        <div class="container">
            <h2 class="section-title">Pourquoi Nous Choisir ?</h2>
            <div class="services-grid">
                <div class="card fade-in-up">
                    <div class="service-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>100% Sécurisé</h3>
                    <p>Tous nos services respectent les conditions d'utilisation des plateformes. Vos comptes sont en sécurité avec nous.</p>
                </div>

                <div class="card fade-in-up">
                    <div class="service-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3>Livraison Rapide</h3>
                    <p>La plupart de nos services commencent dans les 24h et sont livrés selon les délais annoncés.</p>
                </div>

                <div class="card fade-in-up">
                    <div class="service-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <h3>Support 24/7</h3>
                    <p>Notre équipe de support est disponible pour vous aider à tout moment. Satisfaction client garantie.</p>
                </div>

                <div class="card fade-in-up">
                    <div class="service-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <h3>Prix Compétitifs</h3>
                    <p>Les meilleurs prix du marché avec des services de qualité premium. Rapport qualité-prix imbattable.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistiques -->
    <section class="section" style="background: var(--card-bg);">
        <div class="container">
            <h2 class="section-title">Nos Statistiques</h2>
            <div class="dashboard-stats">
                <div class="stat-card">
                    <div class="stat-number pulse">15K+</div>
                    <div class="stat-label">Clients Satisfaits</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number pulse">150K+</div>
                    <div class="stat-label">Commandes Traitées</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number pulse">99%</div>
                    <div class="stat-label">Taux de Satisfaction</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number pulse">24/7</div>
                    <div class="stat-label">Support Client</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="section">
        <div class="container">
            <h2 class="section-title">Contactez-Nous</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 3rem;">
                <div class="card fade-in-up">
                    <h3 style="color: var(--primary-color); margin-bottom: 1.5rem;">
                        <i class="fas fa-envelope"></i> Informations de Contact
                    </h3>
                    <div style="space-y: 1rem;">
                        <p><i class="fas fa-envelope"></i> <strong>Email:</strong> support@smmwebsite.com</p>
                        <p><i class="fas fa-phone"></i> <strong>Téléphone:</strong> +226 XX XX XX XX</p>
                        <p><i class="fas fa-clock"></i> <strong>Horaires:</strong> 24h/24, 7j/7</p>
                        <p><i class="fas fa-map-marker-alt"></i> <strong>Lieu:</strong> Burkina Faso</p>
                    </div>

                    <div style="margin-top: 2rem;">
                        <h4 style="color: var(--primary-color); margin-bottom: 1rem;">Suivez-nous</h4>
                        <div style="display: flex; gap: 1rem;">
                            <a href="#" style="color: var(--primary-color); font-size: 1.5rem;"><i class="fab fa-facebook"></i></a>
                            <a href="#" style="color: var(--primary-color); font-size: 1.5rem;"><i class="fab fa-twitter"></i></a>
                            <a href="#" style="color: var(--primary-color); font-size: 1.5rem;"><i class="fab fa-instagram"></i></a>
                            <a href="#" style="color: var(--primary-color); font-size: 1.5rem;"><i class="fab fa-telegram"></i></a>
                        </div>
                    </div>
                </div>

                <div class="card fade-in-up">
                    <h3 style="color: var(--primary-color); margin-bottom: 1.5rem;">
                        <i class="fas fa-comment-dots"></i> Envoyez-nous un Message
                    </h3>
                    <form action="contact.php" method="POST">
                        <div class="form-group">
                            <label for="name">Nom complet</label>
                            <input type="text" id="name" name="name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="subject">Sujet</label>
                            <input type="text" id="subject" name="subject" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="message">Message</label>
                            <textarea id="message" name="message" class="form-control" rows="5" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            <i class="fas fa-paper-plane"></i> Envoyer le Message
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3><?php echo SITE_NAME; ?></h3>
                    <p>Votre partenaire de confiance pour tous vos besoins en marketing digital. Nous vous aidons à développer votre présence sur les réseaux sociaux de manière authentique et efficace.</p>
                </div>
                <div class="footer-section">
                    <h3>Services</h3>
                    <p><a href="#services">Instagram</a></p>
                    <p><a href="#services">Facebook</a></p>
                    <p><a href="#services">YouTube</a></p>
                    <p><a href="#services">TikTok</a></p>
                    <p><a href="#services">Twitter</a></p>
                </div>
                <div class="footer-section">
                    <h3>Support</h3>
                    <p><a href="#contact">Contact</a></p>
                    <p><a href="#">FAQ</a></p>
                    <p><a href="#">Conditions d'utilisation</a></p>
                    <p><a href="#">Politique de confidentialité</a></p>
                </div>
                <div class="footer-section">
                    <h3>Paiement</h3>
                    <p>💳 MTN Money</p>
                    <p>💳 Moov Money</p>
                    <p>🔒 Paiement sécurisé</p>
                    <p>📱 Mobile Money</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 <?php echo SITE_NAME; ?>. Tous droits réservés. | Développé avec ❤️</p>
            </div>
        </div>
    </footer>

    <script>
        function toggleMobileMenu() {
            const navLinks = document.getElementById('navLinks');
            navLinks.classList.toggle('active');
        }

        // Smooth scrolling
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

        // Animation on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.animationDelay = '0.1s';
                    entry.target.classList.add('fade-in-up');
                }
            });
        }, observerOptions);

        document.querySelectorAll('.card, .service-card').forEach(el => {
            observer.observe(el);
        });
    </script>
</body>
</html>
