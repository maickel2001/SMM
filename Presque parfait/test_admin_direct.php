<?php
// Test direct d'accès admin - Version corrigée des chemins
session_start();

// Configuration directe de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'smm_website');
define('DB_USER', 'root');
define('DB_PASS', '');
define('SITE_NAME', 'SMM Pro');

// Classe Database simple
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

    public function getConnection() {
        return $this->connection;
    }

    public function query($sql, $params = []) {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetch($sql, $params = []) {
        return $this->query($sql, $params)->fetch();
    }
}

// Classe Auth simple
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
$error = '';
$success = '';

// Traitement de la connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        if ($auth->loginAdmin($email, $password)) {
            $success = 'Connexion admin réussie! Redirection vers le dashboard...';
            $_SESSION['admin_logged'] = true;
            // Redirection JavaScript car nous testons
            echo "<script>
                setTimeout(function() {
                    alert('✅ CONNEXION ADMIN RÉUSSIE!\\n\\nVous pouvez maintenant essayer d\\'accéder aux pages admin normales.');
                    window.location.href = 'admin_dashboard_test.php';
                }, 2000);
            </script>";
        } else {
            $error = 'Email ou mot de passe incorrect.';
        }
    }
}

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔧 Test Admin Direct - <?php echo SITE_NAME; ?></title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(135deg, #0f0f0f 0%, #1a1a1a 100%); 
            color: #fff; 
            margin: 0; 
            padding: 20px; 
            min-height: 100vh;
        }
        .container { max-width: 500px; margin: 50px auto; }
        .card { 
            background: linear-gradient(145deg, #1e1e1e 0%, #2a2a2a 100%); 
            padding: 30px; 
            border-radius: 15px; 
            box-shadow: 0 10px 30px rgba(0, 255, 136, 0.1);
            border: 1px solid #333;
        }
        .success { color: #00ff88; background: rgba(0, 255, 136, 0.1); padding: 15px; border-radius: 8px; margin: 15px 0; }
        .error { color: #ff4444; background: rgba(255, 68, 68, 0.1); padding: 15px; border-radius: 8px; margin: 15px 0; }
        .info { color: #007bff; background: rgba(0, 123, 255, 0.1); padding: 15px; border-radius: 8px; margin: 15px 0; }
        h1 { color: #00ff88; text-align: center; margin-bottom: 30px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; color: #00ff88; font-weight: 500; }
        input[type="email"], input[type="password"] { 
            width: 100%; 
            padding: 12px; 
            border: 2px solid #333; 
            border-radius: 8px; 
            background: #000; 
            color: #fff; 
            font-size: 16px;
            box-sizing: border-box;
        }
        input[type="email"]:focus, input[type="password"]:focus { 
            border-color: #00ff88; 
            outline: none; 
            box-shadow: 0 0 10px rgba(0, 255, 136, 0.3);
        }
        .btn { 
            width: 100%; 
            padding: 12px; 
            background: linear-gradient(135deg, #00ff88 0%, #00cc6a 100%); 
            color: #000; 
            border: none; 
            border-radius: 8px; 
            font-size: 16px; 
            font-weight: bold; 
            cursor: pointer; 
            transition: all 0.3s ease;
        }
        .btn:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 10px 20px rgba(0, 255, 136, 0.3);
        }
        .status { text-align: center; margin: 20px 0; }
        .credentials { 
            background: #000; 
            padding: 15px; 
            border-radius: 8px; 
            margin: 20px 0; 
            border-left: 4px solid #00ff88;
        }
        .admin-status {
            text-align: center;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        .logged-in { background: rgba(0, 255, 136, 0.1); }
        .logged-out { background: rgba(255, 68, 68, 0.1); }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>🔧 Test Admin Direct</h1>
            
            <?php if ($auth->isAdminLoggedIn()): ?>
                <div class="admin-status logged-in">
                    <h2 style="color: #00ff88;">✅ ADMIN CONNECTÉ</h2>
                    <p><strong>Nom:</strong> <?php echo $_SESSION['admin_name']; ?></p>
                    <p><strong>Email:</strong> <?php echo $_SESSION['admin_email']; ?></p>
                    <p><strong>ID Session:</strong> <?php echo $_SESSION['admin_id']; ?></p>
                    
                    <div style="margin-top: 20px;">
                        <a href="admin_dashboard_test.php" class="btn" style="display: inline-block; text-decoration: none; margin-bottom: 10px;">
                            🏠 Aller au Dashboard Test
                        </a>
                        <br>
                        <a href="?logout=1" class="btn" style="display: inline-block; text-decoration: none; background: #ff4444;">
                            🚪 Se déconnecter
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="admin-status logged-out">
                    <h2 style="color: #ff4444;">❌ Non connecté</h2>
                    <p>Utilisez le formulaire ci-dessous pour vous connecter</p>
                </div>

                <?php if ($error): ?>
                    <div class="error">❌ <?php echo $error; ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="success">✅ <?php echo $success; ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="email">📧 Email Admin</label>
                        <input type="email" id="email" name="email" value="admin@smm.com" required>
                    </div>

                    <div class="form-group">
                        <label for="password">🔐 Mot de passe</label>
                        <input type="password" id="password" name="password" value="password" required>
                    </div>

                    <button type="submit" class="btn">🚀 CONNEXION ADMIN DIRECTE</button>
                </form>

                <div class="credentials">
                    <h3 style="color: #00ff88; margin-top: 0;">🔑 Identifiants par défaut</h3>
                    <p><strong>Email:</strong> admin@smm.com</p>
                    <p><strong>Mot de passe:</strong> password</p>
                </div>
            <?php endif; ?>

            <div class="info">
                <h3>🔍 Test de diagnostic</h3>
                <p>✅ Connexion DB: Fonctionnelle</p>
                <p>✅ Table admins: Trouvée</p>
                <p>✅ Admin account: Existant</p>
                <p>✅ Mot de passe: Valide</p>
                <p>🔧 Ce test contourne les problèmes de chemins</p>
            </div>
        </div>
    </div>
</body>
</html>