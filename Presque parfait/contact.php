<?php
require_once 'includes/auth.php';
$auth = new Auth();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    $subject = sanitizeInput($_POST['subject']);
    $message = sanitizeInput($_POST['message']);

    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = 'Tous les champs sont obligatoires.';
    } elseif (!isValidEmail($email)) {
        $error = 'Adresse email invalide.';
    } else {
        // Dans un vrai projet, ici on enverrait l'email
        // Pour cette démo, on simule l'envoi
        $success = 'Votre message a été envoyé avec succès ! Nous vous répondrons dans les plus brefs délais.';

        // Log du message (optionnel)
        $log_entry = date('Y-m-d H:i:s') . " - Contact de $name ($email): $subject\n";
        file_put_contents('logs/contact.log', $log_entry, FILE_APPEND | LOCK_EX);
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact - <?php echo SITE_NAME; ?></title>
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
                <ul class="nav-links">
                    <li><a href="index.php">Accueil</a></li>
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

    <!-- Page de contact -->
    <div class="container" style="margin-top: 120px; margin-bottom: 4rem;">
        <div style="text-align: center; margin-bottom: 3rem;">
            <h1 style="color: var(--primary-color); margin-bottom: 0.5rem;">
                <i class="fas fa-envelope"></i> Contactez-nous
            </h1>
            <p style="color: var(--text-secondary);">
                Notre équipe est là pour vous aider. N'hésitez pas à nous contacter !
            </p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error" style="max-width: 800px; margin: 0 auto 2rem;">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success" style="max-width: 800px; margin: 0 auto 2rem;">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 3rem; max-width: 1000px; margin: 0 auto;">
            <!-- Formulaire de contact -->
            <div class="card">
                <h3 style="color: var(--primary-color); margin-bottom: 1.5rem;">
                    <i class="fas fa-comment-dots"></i> Envoyez-nous un message
                </h3>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="name">
                            <i class="fas fa-user"></i> Nom complet *
                        </label>
                        <input type="text" id="name" name="name" class="form-control"
                               placeholder="Votre nom complet"
                               value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                               required>
                    </div>

                    <div class="form-group">
                        <label for="email">
                            <i class="fas fa-envelope"></i> Adresse email *
                        </label>
                        <input type="email" id="email" name="email" class="form-control"
                               placeholder="votre@email.com"
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                               required>
                    </div>

                    <div class="form-group">
                        <label for="subject">
                            <i class="fas fa-tag"></i> Sujet *
                        </label>
                        <select id="subject" name="subject" class="form-control" required>
                            <option value="">-- Choisir un sujet --</option>
                            <option value="Question générale" <?php echo (isset($_POST['subject']) && $_POST['subject'] === 'Question générale') ? 'selected' : ''; ?>>Question générale</option>
                            <option value="Problème de commande" <?php echo (isset($_POST['subject']) && $_POST['subject'] === 'Problème de commande') ? 'selected' : ''; ?>>Problème de commande</option>
                            <option value="Problème de paiement" <?php echo (isset($_POST['subject']) && $_POST['subject'] === 'Problème de paiement') ? 'selected' : ''; ?>>Problème de paiement</option>
                            <option value="Demande de remboursement" <?php echo (isset($_POST['subject']) && $_POST['subject'] === 'Demande de remboursement') ? 'selected' : ''; ?>>Demande de remboursement</option>
                            <option value="Suggestion d'amélioration" <?php echo (isset($_POST['subject']) && $_POST['subject'] === 'Suggestion d\'amélioration') ? 'selected' : ''; ?>>Suggestion d'amélioration</option>
                            <option value="Partenariat" <?php echo (isset($_POST['subject']) && $_POST['subject'] === 'Partenariat') ? 'selected' : ''; ?>>Partenariat</option>
                            <option value="Autre" <?php echo (isset($_POST['subject']) && $_POST['subject'] === 'Autre') ? 'selected' : ''; ?>>Autre</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="message">
                            <i class="fas fa-comment"></i> Message *
                        </label>
                        <textarea id="message" name="message" class="form-control" rows="6"
                                  placeholder="Décrivez votre demande en détail..." required><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        <i class="fas fa-paper-plane"></i> Envoyer le message
                    </button>
                </form>
            </div>

            <!-- Informations de contact -->
            <div>
                <div class="card" style="margin-bottom: 2rem;">
                    <h3 style="color: var(--primary-color); margin-bottom: 1.5rem;">
                        <i class="fas fa-info-circle"></i> Informations de contact
                    </h3>

                    <div style="display: grid; gap: 1.5rem;">
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <i class="fas fa-envelope" style="color: var(--primary-color); font-size: 1.5rem; width: 30px;"></i>
                            <div>
                                <strong>Email</strong><br>
                                <a href="mailto:support@smmwebsite.com" style="color: var(--primary-color);">support@smmwebsite.com</a>
                            </div>
                        </div>

                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <i class="fas fa-phone" style="color: var(--primary-color); font-size: 1.5rem; width: 30px;"></i>
                            <div>
                                <strong>Téléphone</strong><br>
                                +226 XX XX XX XX
                            </div>
                        </div>

                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <i class="fas fa-clock" style="color: var(--primary-color); font-size: 1.5rem; width: 30px;"></i>
                            <div>
                                <strong>Horaires</strong><br>
                                24h/24, 7j/7
                            </div>
                        </div>

                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <i class="fas fa-map-marker-alt" style="color: var(--primary-color); font-size: 1.5rem; width: 30px;"></i>
                            <div>
                                <strong>Localisation</strong><br>
                                Burkina Faso
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card" style="margin-bottom: 2rem;">
                    <h3 style="color: var(--primary-color); margin-bottom: 1.5rem;">
                        <i class="fas fa-share-alt"></i> Réseaux sociaux
                    </h3>

                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                        <a href="#" style="display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem; background: var(--secondary-color); border-radius: 8px; text-decoration: none; color: var(--text-primary); transition: all 0.3s ease;">
                            <i class="fab fa-facebook" style="color: #1877F2; font-size: 1.5rem;"></i>
                            Facebook
                        </a>
                        <a href="#" style="display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem; background: var(--secondary-color); border-radius: 8px; text-decoration: none; color: var(--text-primary); transition: all 0.3s ease;">
                            <i class="fab fa-twitter" style="color: #1DA1F2; font-size: 1.5rem;"></i>
                            Twitter
                        </a>
                        <a href="#" style="display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem; background: var(--secondary-color); border-radius: 8px; text-decoration: none; color: var(--text-primary); transition: all 0.3s ease;">
                            <i class="fab fa-instagram" style="color: #E4405F; font-size: 1.5rem;"></i>
                            Instagram
                        </a>
                        <a href="#" style="display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem; background: var(--secondary-color); border-radius: 8px; text-decoration: none; color: var(--text-primary); transition: all 0.3s ease;">
                            <i class="fab fa-telegram" style="color: #0088CC; font-size: 1.5rem;"></i>
                            Telegram
                        </a>
                    </div>
                </div>

                <div class="card">
                    <h3 style="color: var(--primary-color); margin-bottom: 1.5rem;">
                        <i class="fas fa-question-circle"></i> FAQ Rapide
                    </h3>

                    <div style="display: grid; gap: 1rem;">
                        <details style="cursor: pointer;">
                            <summary style="font-weight: 600; padding: 0.5rem; background: var(--secondary-color); border-radius: 4px;">
                                🕐 Combien de temps pour traiter ma commande ?
                            </summary>
                            <p style="margin: 0.5rem 0 0; color: var(--text-secondary); font-size: 0.9rem;">
                                La plupart des commandes sont traitées dans les 24-48h après confirmation du paiement.
                            </p>
                        </details>

                        <details style="cursor: pointer;">
                            <summary style="font-weight: 600; padding: 0.5rem; background: var(--secondary-color); border-radius: 4px;">
                                💳 Quels moyens de paiement acceptez-vous ?
                            </summary>
                            <p style="margin: 0.5rem 0 0; color: var(--text-secondary); font-size: 0.9rem;">
                                Nous acceptons MTN Money et Moov Money pour tous vos paiements.
                            </p>
                        </details>

                        <details style="cursor: pointer;">
                            <summary style="font-weight: 600; padding: 0.5rem; background: var(--secondary-color); border-radius: 4px;">
                                🔒 Mes données sont-elles sécurisées ?
                            </summary>
                            <p style="margin: 0.5rem 0 0; color: var(--text-secondary); font-size: 0.9rem;">
                                Absolument ! Nous utilisons des protocoles de sécurité avancés pour protéger vos informations.
                            </p>
                        </details>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-resize du textarea
        document.getElementById('message').addEventListener('input', function () {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });

        // Animation hover pour les réseaux sociaux
        document.querySelectorAll('a[href="#"]').forEach(link => {
            link.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 5px 15px rgba(0, 255, 136, 0.2)';
            });

            link.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = 'none';
            });
        });
    </script>
</body>
</html>
