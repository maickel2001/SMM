<?php
require_once 'includes/auth.php';
$auth = new Auth();

// Redirection si déjà connecté
if ($auth->isUserLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = 'Veuillez remplir tous les champs.';
    } elseif (!isValidEmail($email)) {
        $error = 'Adresse email invalide.';
    } else {
        if ($auth->loginUser($email, $password)) {
            header('Location: dashboard.php');
            exit();
        } else {
            $error = 'Email ou mot de passe incorrect.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - <?php echo SITE_NAME; ?></title>
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
                    <li><a href="register.php" class="btn btn-primary">S'inscrire</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Formulaire de connexion -->
    <div class="container" style="margin-top: 120px; margin-bottom: 4rem;">
        <div style="max-width: 450px; margin: 0 auto;">
            <div class="card">
                <div style="text-align: center; margin-bottom: 2rem;">
                    <h1 style="color: var(--primary-color); margin-bottom: 0.5rem;">
                        <i class="fas fa-sign-in-alt"></i> Connexion
                    </h1>
                    <p style="color: var(--text-secondary);">Connectez-vous à votre compte</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="email">
                            <i class="fas fa-envelope"></i> Adresse email
                        </label>
                        <input type="email" id="email" name="email" class="form-control"
                               placeholder="votre@email.com"
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                               required>
                    </div>

                    <div class="form-group">
                        <label for="password">
                            <i class="fas fa-lock"></i> Mot de passe
                        </label>
                        <input type="password" id="password" name="password" class="form-control"
                               placeholder="Votre mot de passe" required>
                    </div>

                    <div style="margin: 1.5rem 0; display: flex; justify-content: space-between; align-items: center;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="checkbox" name="remember" style="accent-color: var(--primary-color);">
                            <span style="font-size: 0.9rem; color: var(--text-secondary);">Se souvenir de moi</span>
                        </label>
                        <a href="#" style="color: var(--primary-color); text-decoration: none; font-size: 0.9rem;">
                            Mot de passe oublié ?
                        </a>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-bottom: 1rem;">
                        <i class="fas fa-sign-in-alt"></i> Se connecter
                    </button>

                    <div style="text-align: center;">
                        <span style="color: var(--text-secondary);">Pas encore de compte ?</span>
                        <a href="register.php" style="color: var(--primary-color); text-decoration: none; font-weight: 600;">
                            Créer un compte
                        </a>
                    </div>
                </form>
            </div>

            <!-- Connexion rapide demo -->
            <div class="card" style="margin-top: 2rem;">
                <h3 style="color: var(--primary-color); margin-bottom: 1rem; text-align: center;">
                    <i class="fas fa-info-circle"></i> Compte de démonstration
                </h3>
                <p style="text-align: center; color: var(--text-secondary); margin-bottom: 1rem;">
                    Testez notre plateforme avec ces identifiants de démonstration
                </p>
                <div style="background: var(--secondary-color); padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                    <p style="margin: 0; font-family: monospace;"><strong>Email:</strong> demo@example.com</p>
                    <p style="margin: 0; font-family: monospace;"><strong>Mot de passe:</strong> password</p>
                </div>
                <button onclick="fillDemoCredentials()" class="btn btn-secondary" style="width: 100%;">
                    <i class="fas fa-user"></i> Utiliser le compte démo
                </button>
            </div>

            <!-- Avantages -->
            <div class="card" style="margin-top: 2rem;">
                <h3 style="color: var(--primary-color); margin-bottom: 1rem; text-align: center;">
                    <i class="fas fa-rocket"></i> Commencez dès maintenant
                </h3>
                <div style="display: grid; gap: 1rem;">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <i class="fas fa-shopping-cart" style="color: var(--primary-color); font-size: 1.2rem;"></i>
                        <span>Commandez vos services en quelques clics</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <i class="fas fa-credit-card" style="color: var(--primary-color); font-size: 1.2rem;"></i>
                        <span>Paiement sécurisé par Mobile Money</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <i class="fas fa-clock" style="color: var(--primary-color); font-size: 1.2rem;"></i>
                        <span>Livraison rapide en 24-48h</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <i class="fas fa-shield-alt" style="color: var(--primary-color); font-size: 1.2rem;"></i>
                        <span>Services 100% sécurisés et fiables</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function fillDemoCredentials() {
            document.getElementById('email').value = 'demo@example.com';
            document.getElementById('password').value = 'password';

            // Highlight des champs
            document.getElementById('email').style.borderColor = 'var(--primary-color)';
            document.getElementById('password').style.borderColor = 'var(--primary-color)';

            setTimeout(() => {
                document.getElementById('email').style.borderColor = '';
                document.getElementById('password').style.borderColor = '';
            }, 2000);
        }

        // Auto-focus sur le premier champ
        document.getElementById('email').focus();
    </script>
</body>
</html>
