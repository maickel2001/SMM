<?php
require_once '../includes/auth.php';
$auth = new Auth();

// Redirection si déjà connecté
if ($auth->isAdminLoggedIn()) {
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
        if ($auth->loginAdmin($email, $password)) {
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
    <title>Connexion Admin - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <div class="nav-content">
                <a href="../index.php" class="logo">
                    <i class="fas fa-rocket"></i>
                    <?php echo SITE_NAME; ?> - Admin
                </a>
                <ul class="nav-links">
                    <li><a href="../index.php">Retour au site</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Formulaire de connexion admin -->
    <div class="container" style="margin-top: 120px; margin-bottom: 4rem;">
        <div style="max-width: 450px; margin: 0 auto;">
            <div class="card">
                <div style="text-align: center; margin-bottom: 2rem;">
                    <i class="fas fa-shield-alt" style="font-size: 4rem; color: var(--primary-color); margin-bottom: 1rem;"></i>
                    <h1 style="color: var(--primary-color); margin-bottom: 0.5rem;">
                        Administration
                    </h1>
                    <p style="color: var(--text-secondary);">Accès réservé aux administrateurs</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="email">
                            <i class="fas fa-envelope"></i> Adresse email administrateur
                        </label>
                        <input type="email" id="email" name="email" class="form-control"
                               placeholder="admin@smm.com"
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                               required>
                    </div>

                    <div class="form-group">
                        <label for="password">
                            <i class="fas fa-lock"></i> Mot de passe
                        </label>
                        <input type="password" id="password" name="password" class="form-control"
                               placeholder="Mot de passe administrateur" required>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-bottom: 1rem;">
                        <i class="fas fa-sign-in-alt"></i> Accéder à l'administration
                    </button>
                </form>
            </div>

            <!-- Informations de connexion par défaut -->
            <div class="card" style="margin-top: 2rem;">
                <h3 style="color: var(--primary-color); margin-bottom: 1rem; text-align: center;">
                    <i class="fas fa-info-circle"></i> Compte administrateur par défaut
                </h3>
                <div style="background: var(--secondary-color); padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                    <p style="margin: 0; font-family: monospace;"><strong>Email:</strong> admin@smm.com</p>
                    <p style="margin: 0; font-family: monospace;"><strong>Mot de passe:</strong> password</p>
                </div>
                <button onclick="fillAdminCredentials()" class="btn btn-secondary" style="width: 100%;">
                    <i class="fas fa-user-shield"></i> Utiliser les identifiants par défaut
                </button>
            </div>

            <!-- Sécurité -->
            <div class="card" style="margin-top: 2rem;">
                <h3 style="color: var(--warning-color); margin-bottom: 1rem; text-align: center;">
                    <i class="fas fa-exclamation-triangle"></i> Sécurité
                </h3>
                <div style="color: var(--text-secondary); font-size: 0.9rem; text-align: center;">
                    <p>⚠️ Changez le mot de passe par défaut après la première connexion</p>
                    <p>🔒 Utilisez une connexion sécurisée (HTTPS) en production</p>
                    <p>👥 Limitez l'accès admin aux personnes autorisées uniquement</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function fillAdminCredentials() {
            document.getElementById('email').value = 'admin@smm.com';
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
