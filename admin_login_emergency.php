<?php
session_start();

// Configuration directe de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'smm_website');
define('DB_USER', 'root');
define('DB_PASS', '');
define('SITE_NAME', 'SMM Pro');

// Classe Database directe
class Database {
    private $connection;

    public function __construct() {
        try {
            $this->connection = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException $e) {
            die("Erreur de connexion à la base de données: " . $e->getMessage());
        }
    }

    public function fetch($sql, $params = []) {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }
}

// Classe Auth simplifiée
class Auth {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function loginAdmin($email, $password) {
        $sql = "SELECT * FROM admins WHERE email = ?";
        $admin = $this->db->fetch($sql, [$email]);

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['name'];
            $_SESSION['admin_email'] = $admin['email'];
            return true;
        }
        return false;
    }

    public function isAdminLoggedIn() {
        return isset($_SESSION['admin_id']);
    }
}

$auth = new Auth();

// Redirection si déjà connecté
if ($auth->isAdminLoggedIn()) {
    header('Location: Presque parfait /admin/dashboard.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        if ($auth->loginAdmin($email, $password)) {
            $success = 'Connexion réussie ! Redirection...';
            echo "<script>
                setTimeout(function() {
                    window.location.href = 'Presque parfait /admin/dashboard.php';
                }, 1500);
            </script>";
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
    <title>🚨 Connexion Admin d'Urgence - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0f0f0f 0%, #1a1a1a 100%);
            color: #fff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .emergency-container {
            width: 100%;
            max-width: 500px;
            padding: 2rem;
        }
        .emergency-card {
            background: linear-gradient(145deg, #1e1e1e 0%, #2a2a2a 100%);
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 20px 40px rgba(255, 68, 68, 0.2);
            border: 2px solid #ff4444;
            position: relative;
            overflow: hidden;
        }
        .emergency-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #ff4444 0%, #ff6666 100%);
        }
        .emergency-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .emergency-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 1rem;
            background: linear-gradient(135deg, #ff4444 0%, #ff6666 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: #fff;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        .emergency-title {
            color: #ff4444;
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .emergency-subtitle {
            color: #b0b0b0;
            font-size: 0.9rem;
            margin-bottom: 1rem;
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
        }
        .form-control:focus {
            border-color: #00ff88;
            outline: none;
            box-shadow: 0 0 20px rgba(0, 255, 136, 0.2);
        }
        .btn-emergency {
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
        .btn-emergency:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 255, 136, 0.3);
        }
        .alert-error {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #ff4444;
            background: rgba(255, 68, 68, 0.1);
            color: #ff4444;
        }
        .alert-success {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #00ff88;
            background: rgba(0, 255, 136, 0.1);
            color: #00ff88;
        }
        .emergency-note {
            background: #000;
            border-radius: 10px;
            padding: 1.5rem;
            margin-top: 1.5rem;
            border-left: 4px solid #ff4444;
        }
        .quick-fill {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        .quick-btn {
            padding: 0.5rem;
            background: rgba(0, 255, 136, 0.1);
            color: #00ff88;
            border: 1px solid #00ff88;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.8rem;
        }
        .quick-btn:hover {
            background: rgba(0, 255, 136, 0.2);
        }
        .status-indicator {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem;
            background: rgba(0, 255, 136, 0.1);
            border-radius: 8px;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="emergency-container">
        <div class="emergency-card">
            <div class="emergency-header">
                <div class="emergency-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h1 class="emergency-title">Connexion d'Urgence</h1>
                <p class="emergency-subtitle">Version de secours pour l'administration</p>
            </div>

            <div class="status-indicator">
                <i class="fas fa-check-circle" style="color: #00ff88;"></i>
                <span>Système de connexion d'urgence activé</span>
            </div>

            <?php if ($error): ?>
                <div class="alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="quick-fill">
                    <button type="button" class="quick-btn" onclick="fillAdmin()">
                        <i class="fas fa-user-shield"></i> Admin
                    </button>
                    <button type="button" class="quick-btn" onclick="clearFields()">
                        <i class="fas fa-eraser"></i> Effacer
                    </button>
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">
                        <i class="fas fa-envelope"></i> Email administrateur
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

                <button type="submit" class="btn-emergency">
                    <i class="fas fa-sign-in-alt"></i> Connexion d'urgence
                </button>
            </form>

            <div class="emergency-note">
                <h3 style="color: #ff4444; margin-bottom: 1rem;">
                    <i class="fas fa-info-circle"></i> Informations importantes
                </h3>
                <ul style="color: #b0b0b0; line-height: 1.6;">
                    <li><strong>Email:</strong> admin@smm.com</li>
                    <li><strong>Mot de passe:</strong> password</li>
                    <li><strong>But:</strong> Accès d'urgence à l'administration</li>
                    <li><strong>Sécurité:</strong> Supprimez ce fichier après usage</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        function fillAdmin() {
            document.getElementById('email').value = 'admin@smm.com';
            document.getElementById('password').value = 'password';
            
            // Animation de confirmation
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

        function clearFields() {
            document.getElementById('email').value = '';
            document.getElementById('password').value = '';
            document.getElementById('email').focus();
        }

        // Auto-focus
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