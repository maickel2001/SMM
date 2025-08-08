<?php
session_start();

// 🔥 CONNEXION DIRECTE À LA BASE DE DONNÉES - SANS auth.php
// Remplacez ces valeurs par vos vrais paramètres Hostinger

// CONFIGURATIONS POSSIBLES - Testez-les une par une
$db_configs = [
    // Configuration 1 - Format Hostinger standard
    [
        'host' => 'localhost',
        'dbname' => 'u940813643_smm_website',
        'username' => 'u940813643_admin',
        'password' => ''  // METTEZ VOTRE VRAI MOT DE PASSE ICI
    ],
    // Configuration 2 - Alternative
    [
        'host' => 'localhost', 
        'dbname' => 'u940813643_smm',
        'username' => 'u940813643_admin',
        'password' => ''  // METTEZ VOTRE VRAI MOT DE PASSE ICI
    ],
    // Configuration 3 - Si vous avez créé manuellement
    [
        'host' => 'localhost',
        'dbname' => 'smm_website',
        'username' => 'root',
        'password' => ''
    ]
];

$pdo = null;
$working_config = null;

// TESTER CHAQUE CONFIGURATION
foreach ($db_configs as $config) {
    try {
        $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        $working_config = $config;
        break; // Connexion réussie !
    } catch (PDOException $e) {
        continue; // Essayer la configuration suivante
    }
}

// VÉRIFIER SI LA CONNEXION A FONCTIONNÉ
if (!$pdo) {
    die("❌ AUCUNE CONFIGURATION N'A FONCTIONNÉ. Vérifiez vos paramètres de base de données dans le panneau Hostinger.");
}

// ✅ CONNEXION RÉUSSIE !
echo "✅ CONNEXION RÉUSSIE avec: Host={$working_config['host']}, DB={$working_config['dbname']}, User={$working_config['username']}<br><br>";

// LOGIN ADMINISTRATEUR DIRECT
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        // REQUÊTE DIRECTE POUR VÉRIFIER L'ADMIN
        try {
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ?");
            $stmt->execute([$email]);
            $admin = $stmt->fetch();
            
            if ($admin && password_verify($password, $admin['password'])) {
                // CONNEXION RÉUSSIE - CRÉER LA SESSION
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['name'];
                $_SESSION['admin_email'] = $admin['email'];
                
                $success = 'Connexion réussie ! Redirection vers le dashboard...';
                
                // REDIRECTION AUTOMATIQUE
                echo "<script>
                    setTimeout(function() {
                        window.location.href = 'Presque parfait /admin/dashboard.php';
                    }, 2000);
                </script>";
            } else {
                $error = 'Email ou mot de passe incorrect.';
            }
        } catch (PDOException $e) {
            $error = 'Erreur lors de la vérification : ' . $e->getMessage();
        }
    }
}

// VÉRIFIER SI DÉJÀ CONNECTÉ
if (isset($_SESSION['admin_id'])) {
    echo "<div style='color: #00ff88; font-weight: bold;'>Vous êtes déjà connecté en tant qu'administrateur !</div>";
    echo "<a href='Presque parfait /admin/dashboard.php' style='color: #00ff88;'>Aller au Dashboard</a><br><br>";
}

// AFFICHER LES INFORMATIONS DE DEBUG
echo "<div style='background: #000; color: #00ff88; padding: 1rem; border-radius: 5px; margin: 1rem 0; font-family: monospace;'>";
echo "<strong>🔧 INFORMATIONS DE DEBUG :</strong><br>";
echo "Host: {$working_config['host']}<br>";
echo "Database: {$working_config['dbname']}<br>";
echo "Username: {$working_config['username']}<br>";
echo "Status: ✅ Connecté<br>";

// TESTER LA TABLE ADMINS
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM admins");
    $result = $stmt->fetch();
    echo "Nombre d'admins dans la base: {$result['count']}<br>";
    
    // AFFICHER LES EMAILS DES ADMINS
    $stmt = $pdo->query("SELECT email FROM admins");
    $admins = $stmt->fetchAll();
    echo "Emails des admins: ";
    foreach ($admins as $admin) {
        echo $admin['email'] . " ";
    }
    echo "<br>";
} catch (PDOException $e) {
    echo "Erreur lors du test de la table admins: " . $e->getMessage() . "<br>";
}
echo "</div>";
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔥 Connexion Admin DIRECTE</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #000 0%, #1a1a1a 100%);
            color: #fff;
            min-height: 100vh;
            padding: 2rem;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: rgba(0, 0, 0, 0.8);
            border-radius: 15px;
            padding: 2rem;
            border: 2px solid #00ff88;
        }
        .header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .header h1 {
            color: #00ff88;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-label {
            display: block;
            color: #00ff88;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .form-control {
            width: 100%;
            padding: 1rem;
            background: #000;
            border: 2px solid #333;
            border-radius: 10px;
            color: #fff;
            font-size: 1rem;
        }
        .form-control:focus {
            border-color: #00ff88;
            outline: none;
        }
        .btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #00ff88 0%, #00cc6a 100%);
            color: #000;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            margin-bottom: 1rem;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .alert-error {
            background: rgba(255, 68, 68, 0.2);
            border: 1px solid #ff4444;
            color: #ff4444;
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
        }
        .alert-success {
            background: rgba(0, 255, 136, 0.2);
            border: 1px solid #00ff88;
            color: #00ff88;
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
        }
        .quick-fill {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        .quick-btn {
            flex: 1;
            padding: 0.5rem;
            background: rgba(0, 255, 136, 0.1);
            color: #00ff88;
            border: 1px solid #00ff88;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
        }
        .code-block {
            background: #000;
            border: 1px solid #00ff88;
            border-radius: 5px;
            padding: 1rem;
            font-family: monospace;
            margin: 1rem 0;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔥 CONNEXION ADMIN DIRECTE</h1>
            <p>Connexion sans auth.php - Code PHP pur</p>
        </div>

        <?php if ($error): ?>
            <div class="alert-error">
                ❌ <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert-success">
                ✅ <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="quick-fill">
                <button type="button" class="quick-btn" onclick="fillAdmin()">
                    👤 Remplir Admin
                </button>
                <button type="button" class="quick-btn" onclick="clearFields()">
                    🗑️ Effacer
                </button>
            </div>

            <div class="form-group">
                <label for="email" class="form-label">📧 Email Admin</label>
                <input type="email" 
                       id="email" 
                       name="email" 
                       class="form-control"
                       placeholder="admin@smm.com"
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                       required>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">🔒 Mot de Passe</label>
                <input type="password" 
                       id="password" 
                       name="password" 
                       class="form-control"
                       placeholder="password" 
                       required>
            </div>

            <button type="submit" class="btn">
                🚀 CONNEXION DIRECTE
            </button>
        </form>

        <div class="code-block">
            <strong>💡 CODE PHP UTILISÉ (sans auth.php) :</strong><br><br>
            <code>
// Connexion PDO directe<br>
$pdo = new PDO($dsn, $username, $password);<br><br>

// Vérification admin<br>
$stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ?");<br>
$stmt->execute([$email]);<br>
$admin = $stmt->fetch();<br><br>

// Vérification mot de passe<br>
if ($admin && password_verify($password, $admin['password'])) {<br>
&nbsp;&nbsp;&nbsp;&nbsp;$_SESSION['admin_id'] = $admin['id'];<br>
&nbsp;&nbsp;&nbsp;&nbsp;// Connexion réussie !<br>
}
            </code>
        </div>

        <div style="background: rgba(0, 255, 136, 0.1); padding: 1rem; border-radius: 8px; margin-top: 1rem;">
            <strong>🔧 POUR PERSONNALISER :</strong><br>
            1. Modifiez les paramètres de base de données en haut du fichier<br>
            2. Ajoutez votre vrai mot de passe de base de données<br>
            3. Le code teste automatiquement plusieurs configurations<br>
            4. Aucune dépendance sur auth.php ou autres fichiers !
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

        // Auto-focus
        document.getElementById('email').focus();
    </script>
</body>
</html>