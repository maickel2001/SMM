<?php
// Script de correction des chemins admin - Solution définitive
session_start();

echo "<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>🛠️ Correction des Fichiers Admin</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #0f0f0f; color: #fff; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .card { background: #1e1e1e; padding: 20px; border-radius: 10px; margin-bottom: 20px; border-left: 4px solid #00ff88; }
        .success { color: #00ff88; }
        .error { color: #ff4444; }
        .info { color: #007bff; }
        .warning { color: #ffa500; }
        h1 { color: #00ff88; text-align: center; }
        h2 { color: #00ff88; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .btn { display: inline-block; padding: 10px 20px; background: #00ff88; color: #000; text-decoration: none; border-radius: 5px; margin: 5px; font-weight: bold; }
        pre { background: #000; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
<div class='container'>
    <h1>🛠️ Correction des Fichiers Admin</h1>";

// Détecter le bon chemin de base
$current_dir = __DIR__;
$base_paths = [
    $current_dir,
    dirname($current_dir) . '/Presque parfait',
    dirname($current_dir) . '/Presque parfait '
];

$correct_base_path = '';
foreach ($base_paths as $path) {
    if (file_exists($path . '/admin/login.php')) {
        $correct_base_path = $path;
        break;
    }
}

if (!$correct_base_path) {
    echo "<div class='card'><p class='error'>❌ Impossible de trouver le dossier admin</p></div>";
    exit;
}

echo "<div class='card'>
<h2>📁 Chemin détecté</h2>
<p class='success'>✅ Dossier trouvé: <code>$correct_base_path</code></p>
</div>";

// Liste des fichiers admin à corriger
$admin_files = [
    'login.php',
    'dashboard.php',
    'orders.php',
    'services.php',
    'categories.php',
    'users.php'
];

$corrections_applied = 0;
$errors = [];

echo "<div class='card'>
<h2>🔧 Correction des fichiers admin</h2>";

foreach ($admin_files as $file) {
    $file_path = $correct_base_path . '/admin/' . $file;
    
    if (!file_exists($file_path)) {
        echo "<p class='warning'>⚠️ Fichier non trouvé: $file</p>";
        continue;
    }
    
    // Lire le contenu actuel
    $content = file_get_contents($file_path);
    $original_content = $content;
    
    // Corrections des chemins - utiliser des chemins absolus
    $replacements = [
        "require_once '../includes/auth.php';" => "require_once '$correct_base_path/includes/auth.php';",
        "require_once '../config/database.php';" => "require_once '$correct_base_path/config/database.php';",
        "href=\"../assets/css/style.css\"" => "href=\"../assets/css/style.css\"",
        "href=\"../index.php\"" => "href=\"../index.php\"",
        "header('Location: ../index.php')" => "header('Location: ../index.php')",
        "action=\"../logout.php\"" => "action=\"../logout.php\""
    ];
    
    $file_modified = false;
    foreach ($replacements as $search => $replace) {
        if (strpos($content, $search) !== false) {
            $content = str_replace($search, $replace, $content);
            $file_modified = true;
        }
    }
    
    // Sauvegarder si modifié
    if ($file_modified) {
        if (file_put_contents($file_path, $content)) {
            echo "<p class='success'>✅ $file: Corrigé</p>";
            $corrections_applied++;
        } else {
            echo "<p class='error'>❌ $file: Erreur d'écriture</p>";
            $errors[] = $file;
        }
    } else {
        echo "<p class='info'>ℹ️ $file: Aucune correction nécessaire</p>";
    }
}

echo "</div>";

// Créer une version corrigée du fichier auth.php si nécessaire
$auth_path = $correct_base_path . '/includes/auth.php';
$config_path = $correct_base_path . '/config/database.php';

echo "<div class='card'>
<h2>🔄 Vérification des includes</h2>";

if (file_exists($auth_path)) {
    $auth_content = file_get_contents($auth_path);
    
    // Corriger le chemin dans auth.php pour utiliser un chemin absolu
    $corrected_require = "require_once '$config_path';";
    if (strpos($auth_content, "require_once 'config/database.php';") !== false) {
        $auth_content = str_replace("require_once 'config/database.php';", $corrected_require, $auth_content);
        file_put_contents($auth_path, $auth_content);
        echo "<p class='success'>✅ auth.php: Chemin vers database.php corrigé</p>";
    } else {
        echo "<p class='info'>ℹ️ auth.php: Aucune correction nécessaire</p>";
    }
} else {
    echo "<p class='error'>❌ Fichier auth.php non trouvé</p>";
}

echo "</div>";

// Instructions finales
echo "<div class='card'>
<h2>🎯 Résultats de la correction</h2>
<p><strong>Fichiers corrigés:</strong> $corrections_applied</p>";

if (empty($errors)) {
    echo "<p class='success'>✅ Toutes les corrections ont été appliquées avec succès!</p>";
} else {
    echo "<p class='error'>❌ Erreurs sur: " . implode(', ', $errors) . "</p>";
}

echo "</div>";

echo "<div class='card'>
<h2>🧪 Test final</h2>
<p class='info'>Maintenant, testez l'accès admin avec cette URL:</p>
<p><strong>URL Admin:</strong> <code>http://votre-domaine.com/Presque parfait/admin/login.php</code></p>

<p><strong>Identifiants:</strong></p>
<pre>Email: admin@smm.com
Mot de passe: password</pre>
</div>";

echo "<div class='card'>
<h2>🛡️ Sécurité</h2>
<p class='warning'>⚠️ <strong>Important:</strong></p>
<ul>
<li>Supprimez ce fichier après correction</li>
<li>Changez le mot de passe admin par défaut</li>
<li>Vérifiez que les permissions des dossiers sont correctes</li>
</ul>
</div>";

echo "<div style='text-align: center; margin: 20px 0;'>
<a href='test_admin_direct.php' class='btn'>🔧 Retour au Test</a>
<a href='admin/login.php' class='btn'>🚀 Tester Admin Corrigé</a>
</div>";

echo "</div>
</body>
</html>";
?>