<?php
session_start();

// Détection automatique de l'environnement
function detectDatabaseConfig() {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    
    // Si on est sur Hostinger
    if (strpos($host, 'hostingersite.com') !== false || strpos($host, 'hostinger') !== false) {
        return [
            'host' => 'localhost',
            'dbname' => 'u940813643_smm_website', // Format typique Hostinger
            'username' => 'u940813643_admin',      // Format typique Hostinger
            'password' => '', // Sera à déterminer
            'environment' => 'hostinger'
        ];
    }
    
    // Configuration locale par défaut
    return [
        'host' => 'localhost',
        'dbname' => 'smm_website',
        'username' => 'root',
        'password' => '',
        'environment' => 'local'
    ];
}

// Configuration détectée
$dbConfig = detectDatabaseConfig();

// Tentative de connexion avec différentes configurations possibles
$possibleConfigs = [];

if ($dbConfig['environment'] === 'hostinger') {
    // Configurations possibles pour Hostinger
    $possibleConfigs = [
        ['host' => 'localhost', 'dbname' => 'u940813643_smm_website', 'user' => 'u940813643_admin', 'pass' => ''],
        ['host' => 'localhost', 'dbname' => 'u940813643_smm', 'user' => 'u940813643_admin', 'pass' => ''],
        ['host' => 'localhost', 'dbname' => 'smm_website', 'user' => 'u940813643_admin', 'pass' => ''],
        ['host' => 'localhost', 'dbname' => 'smm_website', 'user' => 'root', 'pass' => ''],
        // Essayer avec le nom d'utilisateur principal Hostinger
        ['host' => 'localhost', 'dbname' => 'u940813643_smm_website', 'user' => 'u940813643_root', 'pass' => ''],
    ];
} else {
    // Configuration locale
    $possibleConfigs = [
        ['host' => 'localhost', 'dbname' => 'smm_website', 'user' => 'root', 'pass' => '']
    ];
}

// Classe Database avec test de connexion
class Database {
    private $connection;
    private $config;

    public function __construct($configs) {
        $this->connection = null;
        
        foreach ($configs as $config) {
            try {
                $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4";
                $this->connection = new PDO(
                    $dsn,
                    $config['user'],
                    $config['pass'],
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false
                    ]
                );
                $this->config = $config;
                break; // Connexion réussie, on s'arrête
            } catch(PDOException $e) {
                // Continuer avec la configuration suivante
                continue;
            }
        }
        
        if (!$this->connection) {
            $errorDetails = "Impossible de se connecter à la base de données.\n\n";
            $errorDetails .= "Configurations testées:\n";
            foreach ($configs as $i => $config) {
                $errorDetails .= ($i + 1) . ". Host: {$config['host']}, DB: {$config['dbname']}, User: {$config['user']}\n";
            }
            $errorDetails .= "\nEnvironnement détecté: " . ($dbConfig['environment'] === 'hostinger' ? 'Hostinger' : 'Local');
            $errorDetails .= "\nHost actuel: " . ($_SERVER['HTTP_HOST'] ?? 'Non défini');
            
            die("<div style='font-family: monospace; background: #000; color: #ff4444; padding: 2rem; border-radius: 10px; margin: 2rem;'>" . 
                "<h3>🚨 Erreur de Configuration Base de Données</h3>" . 
                "<pre>" . htmlspecialchars($errorDetails) . "</pre>" .
                "<p style='color: #00ff88; margin-top: 1rem;'><strong>Solution:</strong> Vérifiez les paramètres de base de données dans votre panneau Hostinger.</p>" .
                "</div>");
        }
    }

    public function fetch($sql, $params = []) {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }
    
    public function getConfig() {
        return $this->config;
    }
}

// Classe Auth simplifiée
class Auth {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function loginAdmin($email, $password) {
        try {
            $sql = "SELECT * FROM admins WHERE email = ?";
            $admin = $this->db->fetch($sql, [$email]);

            if ($admin && password_verify($password, $admin['password'])) {
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['name'];
                $_SESSION['admin_email'] = $admin['email'];
                return true;
            }
        } catch (Exception $e) {
            return false;
        }
        return false;
    }

    public function isAdminLoggedIn() {
        return isset($_SESSION['admin_id']);
    }
}

// Initialisation
try {
    $db = new Database($possibleConfigs);
    $auth = new Auth($db);
    $connectionSuccess = true;
    $usedConfig = $db->getConfig();
} catch (Exception $e) {
    $connectionSuccess = false;
    $connectionError = $e->getMessage();
}

// Redirection si déjà connecté
if ($connectionSuccess && $auth->isAdminLoggedIn()) {
    header('Location: Presque parfait /admin/dashboard.php');
    exit();
}

$error = '';
$success = '';
$debugInfo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$connectionSuccess) {
        $error = 'Impossible de se connecter à la base de données.';
    } else {
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
}

// Information de debug
if ($connectionSuccess) {
    $debugInfo = "✅ Connexion établie avec: DB={$usedConfig['dbname']}, User={$usedConfig['user']}, Host={$usedConfig['host']}";
} else {
    $debugInfo = "❌ Échec de connexion - Voir détails ci-dessus";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🚨 Admin d'Urgence V2 - Auto-Detection</title>
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
            padding: 1rem;
        }
        .emergency-container {
            width: 100%;
            max-width: 600px;
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
        .status-bar {
            background: <?php echo $connectionSuccess ? 'rgba(0, 255, 136, 0.1)' : 'rgba(255, 68, 68, 0.1)'; ?>;
            border: 1px solid <?php echo $connectionSuccess ? '#00ff88' : '#ff4444'; ?>;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 2rem;
            font-family: monospace;
            font-size: 0.9rem;
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
            background: linear-gradient(135deg, <?php echo $connectionSuccess ? '#00ff88 0%, #00cc6a' : '#666 0%, #555'; ?> 100%);
            color: <?php echo $connectionSuccess ? '#000' : '#ccc'; ?>;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 700;
            cursor: <?php echo $connectionSuccess ? 'pointer' : 'not-allowed'; ?>;
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
            <?php if (!$connectionSuccess): ?>
            opacity: 0.5;
            <?php endif; ?>
        }
        .btn-emergency:hover {
            <?php if ($connectionSuccess): ?>
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 255, 136, 0.3);
            <?php endif; ?>
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
        .debug-info {
            background: #000;
            border-radius: 10px;
            padding: 1rem;
            margin-top: 1.5rem;
            font-family: monospace;
            font-size: 0.8rem;
            border-left: 4px solid <?php echo $connectionSuccess ? '#00ff88' : '#ff4444'; ?>;
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
        .env-info {
            background: rgba(0, 255, 136, 0.05);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-left: 3px solid #00ff88;
        }
    </style>
</head>
<body>
    <div class="emergency-container">
        <div class="emergency-card">
            <div class="emergency-header">
                <div class="emergency-icon">
                    <i class="fas fa-robot"></i>
                </div>
                <h1 class="emergency-title">Admin Auto-Detect V2</h1>
                <p class="emergency-subtitle">Détection automatique de l'environnement</p>
            </div>

            <div class="status-bar">
                <?php echo $debugInfo; ?>
            </div>

            <div class="env-info">
                <strong>🌐 Environnement:</strong> <?php echo $dbConfig['environment'] === 'hostinger' ? 'Hostinger' : 'Local'; ?><br>
                <strong>🔗 Host:</strong> <?php echo $_SERVER['HTTP_HOST'] ?? 'Non défini'; ?><br>
                <strong>📊 Status:</strong> <?php echo $connectionSuccess ? '✅ Connecté' : '❌ Déconnecté'; ?>
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

                <button type="submit" class="btn-emergency" <?php if (!$connectionSuccess): ?>disabled<?php endif; ?>>
                    <i class="fas fa-sign-in-alt"></i> 
                    <?php echo $connectionSuccess ? 'Connexion d\'urgence' : 'Base de données inaccessible'; ?>
                </button>
            </form>

            <div class="debug-info">
                <h4 style="color: #00ff88; margin-bottom: 0.5rem;">🔧 Informations de Debug</h4>
                <?php if ($connectionSuccess): ?>
                    <div>✅ <strong>Configuration active:</strong></div>
                    <div>   - Host: <?php echo $usedConfig['host']; ?></div>
                    <div>   - Database: <?php echo $usedConfig['dbname']; ?></div>
                    <div>   - User: <?php echo $usedConfig['user']; ?></div>
                    <div style="margin-top: 0.5rem;">📝 <strong>Identifiants de test:</strong></div>
                    <div>   - Email: admin@smm.com</div>
                    <div>   - Password: password</div>
                <?php else: ?>
                    <div>❌ <strong>Aucune configuration n'a fonctionné</strong></div>
                    <div style="margin-top: 0.5rem;">🔍 <strong>Actions suggérées:</strong></div>
                    <div>1. Vérifiez votre panneau Hostinger</div>
                    <div>2. Confirmez le nom de la base de données</div>
                    <div>3. Vérifiez les identifiants de connexion</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function fillAdmin() {
            document.getElementById('email').value = 'admin@smm.com';
            document.getElementById('password').value = 'password';
        }

        function clearFields() {
            document.getElementById('email').value = '';
            document.getElementById('password').value = '';
            document.getElementById('email').focus();
        }

        document.getElementById('email').focus();
    </script>
</body>
</html>