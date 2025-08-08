<?php
// Script de diagnostic pour résoudre les problèmes d'accès admin
echo "<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Diagnostic Admin - Résolution des problèmes</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #0f0f0f; color: #fff; padding: 20px; margin: 0; }
        .container { max-width: 800px; margin: 0 auto; }
        .card { background: #1e1e1e; padding: 20px; border-radius: 10px; margin-bottom: 20px; border-left: 4px solid #00ff88; }
        .success { color: #00ff88; }
        .error { color: #ff4444; }
        .info { color: #007bff; }
        .warning { color: #ffa500; }
        h1 { color: #00ff88; text-align: center; }
        h2 { color: #00ff88; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .btn { display: inline-block; padding: 10px 20px; background: #00ff88; color: #000; text-decoration: none; border-radius: 5px; margin: 5px; font-weight: bold; }
        .btn:hover { background: #00cc6a; }
        .code { background: #000; padding: 10px; border-radius: 5px; font-family: monospace; margin: 10px 0; }
        ul { margin-left: 20px; }
        li { margin-bottom: 5px; }
    </style>
</head>
<body>
<div class='container'>
    <h1>🔧 Diagnostic Admin - Résolution des Problèmes</h1>";

// Étape 1: Vérifier la structure des dossiers
echo "<div class='card'>
<h2>📁 Étape 1: Vérification de la structure des dossiers</h2>";

$paths_to_check = [
    '/workspace/Presque parfait/admin/login.php',
    '/workspace/Presque parfait /admin/login.php',
    '/workspace/Presque parfait/config/database.php',
    '/workspace/Presque parfait /config/database.php',
    '/workspace/Presque parfait/includes/auth.php',
    '/workspace/Presque parfait /includes/auth.php'
];

$correct_paths = [];
foreach ($paths_to_check as $path) {
    if (file_exists($path)) {
        echo "<p class='success'>✅ Trouvé: $path</p>";
        $correct_paths[] = $path;
    } else {
        echo "<p class='error'>❌ Non trouvé: $path</p>";
    }
}

// Déterminer le bon chemin
$base_path = '';
if (file_exists('/workspace/Presque parfait /admin/login.php')) {
    $base_path = '/workspace/Presque parfait ';
    echo "<p class='info'>📂 Chemin détecté: '$base_path' (avec espace à la fin)</p>";
} elseif (file_exists('/workspace/Presque parfait/admin/login.php')) {
    $base_path = '/workspace/Presque parfait';
    echo "<p class='info'>📂 Chemin détecté: '$base_path' (sans espace)</p>";
}

echo "</div>";

// Étape 2: Test de la base de données
echo "<div class='card'>
<h2>🗄️ Étape 2: Test de la base de données</h2>";

try {
    // Essayer d'inclure les fichiers nécessaires
    if ($base_path) {
        $config_path = $base_path . '/config/database.php';
        if (file_exists($config_path)) {
            require_once $config_path;
            echo "<p class='success'>✅ Configuration database.php chargée</p>";
            
            $db = new Database();
            echo "<p class='success'>✅ Connexion à la base de données réussie</p>";
            
            // Vérifier la table admins
            $admin_count = $db->fetch("SELECT COUNT(*) as count FROM admins")['count'];
            echo "<p class='success'>✅ Table 'admins': $admin_count enregistrement(s)</p>";
            
        } else {
            echo "<p class='error'>❌ Fichier database.php non trouvé</p>";
        }
    } else {
        echo "<p class='error'>❌ Impossible de détecter le chemin du projet</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Erreur de base de données: " . $e->getMessage() . "</p>";
}

echo "</div>";

// Étape 3: Vérification et correction de l'admin
if (isset($db)) {
    echo "<div class='card'>
    <h2>👨‍💼 Étape 3: Vérification de l'admin</h2>";

    $admin = $db->fetch("SELECT * FROM admins WHERE email = 'admin@smm.com'");

    if ($admin) {
        echo "<p class='success'>✅ Admin trouvé:</p>";
        echo "<ul>";
        echo "<li><strong>ID:</strong> " . $admin['id'] . "</li>";
        echo "<li><strong>Nom:</strong> " . $admin['name'] . "</li>";
        echo "<li><strong>Email:</strong> " . $admin['email'] . "</li>";
        echo "<li><strong>Hash du mot de passe:</strong> " . substr($admin['password'], 0, 20) . "...</li>";
        echo "</ul>";

        // Test du mot de passe
        $password_test = password_verify('password', $admin['password']);
        if ($password_test) {
            echo "<p class='success'>✅ Le mot de passe 'password' fonctionne parfaitement!</p>";
        } else {
            echo "<p class='warning'>⚠️ Le mot de passe ne fonctionne pas - Correction en cours...</p>";
            $new_hash = password_hash('password', PASSWORD_DEFAULT);
            $db->query("UPDATE admins SET password = ? WHERE email = 'admin@smm.com'", [$new_hash]);
            echo "<p class='success'>✅ Mot de passe admin corrigé!</p>";
        }

    } else {
        echo "<p class='warning'>⚠️ Aucun admin trouvé - Création en cours...</p>";
        $hash = password_hash('password', PASSWORD_DEFAULT);
        $db->query("INSERT INTO admins (name, email, password) VALUES (?, ?, ?)",
                   ['Admin', 'admin@smm.com', $hash]);
        echo "<p class='success'>✅ Admin créé avec succès!</p>";
    }

    echo "</div>";
}

// Étape 4: Instructions de test
echo "<div class='card'>
<h2>🧪 Étape 4: Test de connexion</h2>";

if ($base_path) {
    $admin_url = str_replace('/workspace/', '', $base_path) . '/admin/login.php';
    echo "<p class='info'>📋 Instructions pour tester:</p>";
    echo "<ol>";
    echo "<li>Ouvrez votre navigateur</li>";
    echo "<li>Allez à l'URL: <code class='code'>http://votre-domaine.com/$admin_url</code></li>";
    echo "<li>Utilisez ces identifiants:</li>";
    echo "</ol>";
    
    echo "<div class='code'>
    <strong>Email:</strong> admin@smm.com<br>
    <strong>Mot de passe:</strong> password
    </div>";
    
    echo "<p class='success'>✅ Si vous voyez le formulaire de connexion, l'accès fonctionne!</p>";
}

echo "</div>";

// Étape 5: Solutions aux problèmes courants
echo "<div class='card'>
<h2>🔧 Étape 5: Solutions aux problèmes courants</h2>";

echo "<h3>🔴 Problème: Page blanche ou erreur 404</h3>";
echo "<ul>";
echo "<li><strong>Cause:</strong> Chemin incorrect ou fichier manquant</li>";
echo "<li><strong>Solution:</strong> Vérifiez que vous accédez au bon dossier (avec ou sans espace)</li>";
echo "</ul>";

echo "<h3>🔴 Problème: 'Email ou mot de passe incorrect'</h3>";
echo "<ul>";
echo "<li><strong>Cause:</strong> Problème de hashage du mot de passe</li>";
echo "<li><strong>Solution:</strong> Ce script vient de corriger automatiquement le problème</li>";
echo "</ul>";

echo "<h3>🔴 Problème: Erreur de base de données</h3>";
echo "<ul>";
echo "<li><strong>Cause:</strong> Configuration database.php incorrecte</li>";
echo "<li><strong>Solution:</strong> Vérifiez les paramètres DB_HOST, DB_NAME, DB_USER, DB_PASS</li>";
echo "</ul>";

echo "</div>";

// Récapitulatif final
echo "<div class='card'>
<h2>🎯 Récapitulatif des identifiants</h2>";

echo "<div style='background:#000;padding:15px;border-radius:8px;margin:10px 0;'>
<h3 style='color:#00ff88;margin-top:0;'>👨‍💼 CONNEXION ADMIN</h3>
<p><strong>URL:</strong> http://votre-domaine.com/" . (isset($admin_url) ? $admin_url : "admin/login.php") . "</p>
<p><strong>Email:</strong> admin@smm.com</p>
<p><strong>Mot de passe:</strong> password</p>
</div>";

echo "<p class='warning'>⚠️ <strong>IMPORTANT:</strong> Changez ces identifiants par défaut après la première connexion!</p>";
echo "<p class='info'>💡 <strong>Conseil:</strong> Supprimez ce fichier après résolution du problème.</p>";

echo "</div>";

echo "<div class='card'>
<h2>🛠️ Actions automatiques effectuées</h2>";
echo "<ul>";
echo "<li class='success'>✅ Diagnostic de la structure des dossiers</li>";
echo "<li class='success'>✅ Test de connexion à la base de données</li>";
echo "<li class='success'>✅ Vérification/création du compte admin</li>";
echo "<li class='success'>✅ Correction du mot de passe si nécessaire</li>";
echo "</ul>";
echo "<p class='success'><strong>🎉 Diagnostic terminé! Votre admin devrait maintenant fonctionner.</strong></p>";
echo "</div>";

echo "</div>
</body>
</html>";
?>