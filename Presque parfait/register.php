<?php
require_once 'includes/auth.php';
$auth = new Auth();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone = sanitizeInput($_POST['phone']);

    // Validation
    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Tous les champs obligatoires doivent être remplis.';
    } elseif (!isValidEmail($email)) {
        $error = 'Adresse email invalide.';
    } elseif (strlen($password) < 6) {
        $error = 'Le mot de passe doit contenir au moins 6 caractères.';
    } elseif ($password !== $confirm_password) {
        $error = 'Les mots de passe ne correspondent pas.';
    } else {
        if ($auth->registerUser($name, $email, $password, $phone)) {
            $success = 'Inscription réussie ! Vous pouvez maintenant vous connecter.';
        } else {
            $error = 'Cette adresse email est déjà utilisée ou une erreur est survenue.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - <?php echo SITE_NAME; ?></title>
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
                    <li><a href="login.php" class="btn btn-secondary">Connexion</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Formulaire d'inscription -->
    <div class="container" style="margin-top: 120px; margin-bottom: 4rem;">
        <div style="max-width: 500px; margin: 0 auto;">
            <div class="card">
                <div style="text-align: center; margin-bottom: 2rem;">
                    <h1 style="color: var(--primary-color); margin-bottom: 0.5rem;">
                        <i class="fas fa-user-plus"></i> Inscription
                    </h1>
                    <p style="color: var(--text-secondary);">Créez votre compte pour accéder à nos services</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                    </div>
                    <div style="text-align: center; margin-top: 1rem;">
                        <a href="login.php" class="btn btn-primary">Se connecter maintenant</a>
                    </div>
                <?php else: ?>
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
                            <label for="phone">
                                <i class="fas fa-phone"></i> Numéro de téléphone
                            </label>
                            <input type="tel" id="phone" name="phone" class="form-control"
                                   placeholder="+226 XX XX XX XX"
                                   value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="password">
                                <i class="fas fa-lock"></i> Mot de passe *
                            </label>
                            <input type="password" id="password" name="password" class="form-control"
                                   placeholder="Minimum 6 caractères" required>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">
                                <i class="fas fa-lock"></i> Confirmer le mot de passe *
                            </label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control"
                                   placeholder="Répétez votre mot de passe" required>
                        </div>

                        <div style="margin: 1.5rem 0;">
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                <input type="checkbox" required style="accent-color: var(--primary-color);">
                                <span style="font-size: 0.9rem; color: var(--text-secondary);">
                                    J'accepte les <a href="#" style="color: var(--primary-color);">conditions d'utilisation</a>
                                    et la <a href="#" style="color: var(--primary-color);">politique de confidentialité</a>
                                </span>
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width: 100%; margin-bottom: 1rem;">
                            <i class="fas fa-user-plus"></i> Créer mon compte
                        </button>

                        <div style="text-align: center;">
                            <span style="color: var(--text-secondary);">Déjà un compte ?</span>
                            <a href="login.php" style="color: var(--primary-color); text-decoration: none; font-weight: 600;">
                                Se connecter
                            </a>
                        </div>
                    </form>
                <?php endif; ?>
            </div>

            <!-- Avantages de l'inscription -->
            <div class="card" style="margin-top: 2rem;">
                <h3 style="color: var(--primary-color); margin-bottom: 1rem; text-align: center;">
                    <i class="fas fa-star"></i> Avantages de votre compte
                </h3>
                <div style="display: grid; gap: 1rem;">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <i class="fas fa-tachometer-alt" style="color: var(--primary-color); font-size: 1.2rem;"></i>
                        <span>Dashboard personnel avec suivi des commandes</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <i class="fas fa-history" style="color: var(--primary-color); font-size: 1.2rem;"></i>
                        <span>Historique complet de vos achats</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <i class="fas fa-headset" style="color: var(--primary-color); font-size: 1.2rem;"></i>
                        <span>Support client prioritaire</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <i class="fas fa-bell" style="color: var(--primary-color); font-size: 1.2rem;"></i>
                        <span>Notifications de statut des commandes</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Validation en temps réel
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const confirmPassword = document.getElementById('confirm_password');

            if (password.length < 6) {
                this.style.borderColor = 'var(--error-color)';
            } else {
                this.style.borderColor = 'var(--success-color)';
            }

            if (confirmPassword.value && confirmPassword.value !== password) {
                confirmPassword.style.borderColor = 'var(--error-color)';
            } else if (confirmPassword.value) {
                confirmPassword.style.borderColor = 'var(--success-color)';
            }
        });

        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;

            if (this.value !== password) {
                this.style.borderColor = 'var(--error-color)';
            } else {
                this.style.borderColor = 'var(--success-color)';
            }
        });
    </script>
</body>
</html>
