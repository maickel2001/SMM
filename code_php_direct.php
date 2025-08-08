<?php
// 🔥 CODE PHP DIRECT POUR CONNEXION BASE DE DONNÉES - SANS auth.php
session_start();

// ========================================
// 1. CONFIGURATION BASE DE DONNÉES
// ========================================
// REMPLACEZ CES VALEURS PAR VOS VRAIS PARAMÈTRES HOSTINGER

$host = 'localhost';
$dbname = 'u940813643_smm_website';  // REMPLACEZ PAR VOTRE VRAIE BASE
$username = 'u940813643_admin';      // REMPLACEZ PAR VOTRE VRAI USER
$password = '';                      // REMPLACEZ PAR VOTRE VRAI PASSWORD

// ========================================
// 2. CONNEXION PDO DIRECTE
// ========================================
try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    echo "✅ CONNEXION RÉUSSIE à la base: $dbname<br>";
} catch (PDOException $e) {
    die("❌ ERREUR DE CONNEXION: " . $e->getMessage());
}

// ========================================
// 3. LOGIN ADMIN DIRECT
// ========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password_input = $_POST['password'];
    
    // REQUÊTE DIRECTE POUR VÉRIFIER L'ADMIN
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ?");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();
    
    if ($admin && password_verify($password_input, $admin['password'])) {
        // CONNEXION RÉUSSIE - CRÉER LA SESSION
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_name'] = $admin['name'];
        $_SESSION['admin_email'] = $admin['email'];
        
        echo "✅ CONNEXION ADMIN RÉUSSIE !<br>";
        echo "Redirection vers dashboard...<br>";
        
        // REDIRECTION
        header("Location: Presque parfait /admin/dashboard.php");
        exit();
    } else {
        echo "❌ Email ou mot de passe incorrect.<br>";
    }
}

// ========================================
// 4. TEST DE LA BASE DE DONNÉES
// ========================================
echo "<div style='background:#000; color:#00ff88; padding:1rem; font-family:monospace; margin:1rem 0;'>";
echo "<strong>🔧 TEST DE LA BASE :</strong><br>";

try {
    // Compter les admins
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM admins");
    $result = $stmt->fetch();
    echo "Nombre d'admins: {$result['count']}<br>";
    
    // Lister les emails
    $stmt = $pdo->query("SELECT email FROM admins");
    $admins = $stmt->fetchAll();
    echo "Emails des admins: ";
    foreach ($admins as $admin) {
        echo $admin['email'] . " ";
    }
    echo "<br>";
    
    // Test des autres tables
    $tables = ['users', 'categories', 'services', 'orders'];
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $result = $stmt->fetch();
            echo "Table $table: {$result['count']} enregistrement(s)<br>";
        } catch (PDOException $e) {
            echo "Table $table: ERREUR - " . $e->getMessage() . "<br>";
        }
    }
    
} catch (PDOException $e) {
    echo "ERREUR LORS DU TEST: " . $e->getMessage() . "<br>";
}
echo "</div>";
?>

<!DOCTYPE html>
<html>
<head>
    <title>🔥 Code PHP Direct</title>
    <style>
        body { font-family: Arial; background: #000; color: #fff; padding: 2rem; }
        .form { max-width: 400px; margin: 2rem auto; }
        input { width: 100%; padding: 1rem; margin: 0.5rem 0; background: #333; color: #fff; border: 1px solid #666; }
        button { width: 100%; padding: 1rem; background: #00ff88; color: #000; border: none; font-weight: bold; cursor: pointer; }
        .code { background: #222; padding: 1rem; border-radius: 5px; font-family: monospace; margin: 1rem 0; }
    </style>
</head>
<body>
    <h1>🔥 CODE PHP DIRECT - SANS auth.php</h1>
    
    <?php if (!isset($_SESSION['admin_id'])): ?>
    <div class="form">
        <h3>LOGIN ADMIN DIRECT</h3>
        <form method="POST">
            <input type="email" name="email" placeholder="admin@smm.com" required>
            <input type="password" name="password" placeholder="password" required>
            <button type="submit">🚀 CONNEXION DIRECTE</button>
        </form>
    </div>
    <?php else: ?>
        <div style="color: #00ff88; text-align: center; padding: 2rem;">
            <h2>✅ VOUS ÊTES CONNECTÉ !</h2>
            <p>Admin: <?php echo $_SESSION['admin_name']; ?></p>
            <a href="Presque parfait /admin/dashboard.php" style="color: #00ff88;">→ ALLER AU DASHBOARD</a>
        </div>
    <?php endif; ?>

    <div class="code">
        <h3>📋 CODE PHP À COPIER :</h3>
        <pre style="color: #00ff88;">
// CONNEXION DIRECTE
$pdo = new PDO("mysql:host=localhost;dbname=VOTRE_BASE", "VOTRE_USER", "VOTRE_PASSWORD");

// LOGIN ADMIN
$stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ?");
$stmt->execute([$email]);
$admin = $stmt->fetch();

if ($admin && password_verify($password, $admin['password'])) {
    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['admin_name'] = $admin['name'];
    $_SESSION['admin_email'] = $admin['email'];
    // CONNEXION RÉUSSIE !
}
        </pre>
    </div>
    
    <div style="background: #333; padding: 1rem; border-radius: 5px;">
        <h3>🔧 PARAMÈTRES À MODIFIER :</h3>
        <ul>
            <li><strong>$dbname</strong> : Nom de votre base de données</li>
            <li><strong>$username</strong> : Nom d'utilisateur de la base</li>
            <li><strong>$password</strong> : Mot de passe de la base</li>
        </ul>
        <p>Trouvez ces informations dans votre panneau Hostinger → Bases de données</p>
    </div>
</body>
</html>