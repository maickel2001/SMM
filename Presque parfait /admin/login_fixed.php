<?php
// Détecter le chemin correct automatiquement
$current_dir = __DIR__;
$parent_dir = dirname($current_dir);
$includes_path = $parent_dir . '/includes/auth.php';
$config_path = $parent_dir . '/config/database.php';

// Si les fichiers n'existent pas avec ce chemin, essayer avec l'espace
if (!file_exists($includes_path)) {
    $parent_dir = '/workspace/Presque parfait ';
    $includes_path = $parent_dir . '/includes/auth.php';
    $config_path = $parent_dir . '/config/database.php';
}

// Vérifier si les fichiers existent
if (!file_exists($includes_path)) {
    die("Erreur: Impossible de trouver le fichier auth.php. Chemin testé: $includes_path");
}

require_once $includes_path;
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
    <title>Connexion Admin - <?php echo defined('SITE_NAME') ? SITE_NAME : 'SMM Pro'; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #0f0f0f 0%, #1a1a1a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Poppins', sans-serif;
        }
        .login-container {
            width: 100%;
            max-width: 450px;
            padding: 2rem;
        }
        .login-card {
            background: linear-gradient(145deg, #1e1e1e 0%, #2a2a2a 100%);
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 20px 40px rgba(0, 255, 136, 0.1);
            border: 1px solid #333;
            position: relative;
            overflow: hidden;
        }
        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #00ff88 0%, #00cc6a 100%);
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 1rem;
            background: linear-gradient(135deg, #00ff88 0%, #00cc6a 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: #000;
        }
        .login-title {
            color: #00ff88;
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .login-subtitle {
            color: #b0b0b0;
            font-size: 0.95rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-label {
            display: block;
            color: #00ff88;
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        .form-control {
            width: 100%;
            padding: 1rem;
            background: #000;
            border: 2px solid #333;
            border-radius: 10px;
            color: #fff;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }
        .form-control:focus {
            border-color: #00ff88;
            outline: none;
            box-shadow: 0 0 20px rgba(0, 255, 136, 0.2);
        }
        .btn-login {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #00ff88 0%, #00cc6a 100%);
            color: #000;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 255, 136, 0.3);
        }
        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #ff4444;
            background: rgba(255, 68, 68, 0.1);
            color: #ff4444;
        }
        .credentials-card {
            background: #000;
            border-radius: 10px;
            padding: 1.5rem;
            margin-top: 1.5rem;
            border-left: 4px solid #00ff88;
        }
        .credentials-title {
            color: #00ff88;
            font-weight: 600;
            margin-bottom: 1rem;
            text-align: center;
        }
        .credential-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-family: monospace;
            font-size: 0.9rem;
        }
        .btn-fill {
            width: 100%;
            padding: 0.75rem;
            background: rgba(0, 255, 136, 0.1);
            color: #00ff88;
            border: 1px solid #00ff88;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }
        .btn-fill:hover {
            background: rgba(0, 255, 136, 0.2);
        }
        .back-link {
            position: absolute;
            top: 20px;
            left: 20px;
            color: #00ff88;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .back-link:hover {
            color: #00cc6a;
        }
    </style>
</head>
<body>
    <a href="../index.php" class="back-link">
        <i class="fas fa-arrow-left"></i> Retour au site
    </a>

    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h1 class="login-title">Administration</h1>
                <p class="login-subtitle">Accès réservé aux administrateurs</p>
            </div>

            <?php if ($error): ?>
                <div class="alert">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="email" class="form-label">
                        <i class="fas fa-envelope"></i> Adresse email
                    </label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           class="form-control"
                           placeholder="admin@smm.com"
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                           required>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock"></i> Mot de passe
                    </label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           class="form-control"
                           placeholder="Votre mot de passe" 
                           required>
                </div>

                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Se connecter
                </button>
            </form>

            <div class="credentials-card">
                <h3 class="credentials-title">
                    <i class="fas fa-info-circle"></i> Identifiants par défaut
                </h3>
                <div class="credential-item">
                    <span><strong>Email:</strong></span>
                    <span>admin@smm.com</span>
                </div>
                <div class="credential-item">
                    <span><strong>Mot de passe:</strong></span>
                    <span>password</span>
                </div>
                <button onclick="fillCredentials()" class="btn-fill">
                    <i class="fas fa-user-shield"></i> Utiliser ces identifiants
                </button>
            </div>
        </div>
    </div>

    <script>
        function fillCredentials() {
            document.getElementById('email').value = 'admin@smm.com';
            document.getElementById('password').value = 'password';
            
            // Animation visuelle
            const fields = [document.getElementById('email'), document.getElementById('password')];
            fields.forEach(field => {
                field.style.borderColor = '#00ff88';
                field.style.boxShadow = '0 0 20px rgba(0, 255, 136, 0.3)';
            });
            
            setTimeout(() => {
                fields.forEach(field => {
                    field.style.borderColor = '#333';
                    field.style.boxShadow = 'none';
                });
            }, 2000);
        }

        // Auto-focus sur le premier champ
        document.getElementById('email').focus();

        // Validation en temps réel
        document.getElementById('email').addEventListener('input', function() {
            const email = this.value;
            const isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
            this.style.borderColor = email ? (isValid ? '#00ff88' : '#ff4444') : '#333';
        });
    </script>
</body>
</html>