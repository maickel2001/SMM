<?php
// Script de test pour vérifier si l'admin s'ajoute correctement
require_once 'config/database.php';

echo "<h1>🔍 Test de la Base de Données</h1>";
echo "<style>body{font-family:Arial;background:#0f0f0f;color:#fff;padding:20px;} .success{color:#00ff88;} .error{color:#ff4444;} .info{color:#007bff;}</style>";

try {
    $db = new Database();
    echo "<p class='success'>✅ Connexion à la base de données réussie</p>";

    // Tester les tables
    echo "<h2>📋 Vérification des tables:</h2>";
    $tables = ['admins', 'users', 'categories', 'services', 'orders'];

    foreach ($tables as $table) {
        try {
            $count = $db->fetch("SELECT COUNT(*) as count FROM $table")['count'];
            echo "<p class='success'>✅ Table '$table': $count enregistrement(s)</p>";
        } catch (Exception $e) {
            echo "<p class='error'>❌ Table '$table': " . $e->getMessage() . "</p>";
        }
    }

    // Tester spécifiquement l'admin
    echo "<h2>👨‍💼 Vérification de l'admin:</h2>";

    $admin = $db->fetch("SELECT * FROM admins WHERE email = 'admin@smm.com'");

    if ($admin) {
        echo "<p class='success'>✅ Admin trouvé:</p>";
        echo "<ul>";
        echo "<li><strong>ID:</strong> " . $admin['id'] . "</li>";
        echo "<li><strong>Nom:</strong> " . $admin['name'] . "</li>";
        echo "<li><strong>Email:</strong> " . $admin['email'] . "</li>";
        echo "<li><strong>Créé le:</strong> " . $admin['created_at'] . "</li>";
        echo "</ul>";

        // Tester le mot de passe
        echo "<h3>🔐 Test du mot de passe:</h3>";
        $password_test = password_verify('password', $admin['password']);

        if ($password_test) {
            echo "<p class='success'>✅ Le mot de passe 'password' fonctionne!</p>";
        } else {
            echo "<p class='error'>❌ Le mot de passe 'password' ne fonctionne pas</p>";
            echo "<p class='info'>💡 Correction du mot de passe...</p>";

            // Corriger le mot de passe
            $new_hash = password_hash('password', PASSWORD_DEFAULT);
            $db->query("UPDATE admins SET password = ? WHERE email = 'admin@smm.com'", [$new_hash]);
            echo "<p class='success'>✅ Mot de passe corrigé!</p>";
        }

    } else {
        echo "<p class='error'>❌ Aucun admin trouvé avec l'email 'admin@smm.com'</p>";
        echo "<p class='info'>💡 Création de l'admin...</p>";

        // Créer l'admin
        $hash = password_hash('password', PASSWORD_DEFAULT);
        $db->query("INSERT INTO admins (name, email, password) VALUES (?, ?, ?)",
                   ['Admin', 'admin@smm.com', $hash]);
        echo "<p class='success'>✅ Admin créé avec succès!</p>";
    }

    // Tester l'utilisateur démo
    echo "<h2>👤 Vérification de l'utilisateur démo:</h2>";

    $user = $db->fetch("SELECT * FROM users WHERE email = 'demo@example.com'");

    if ($user) {
        echo "<p class='success'>✅ Utilisateur démo trouvé:</p>";
        echo "<ul>";
        echo "<li><strong>ID:</strong> " . $user['id'] . "</li>";
        echo "<li><strong>Nom:</strong> " . $user['name'] . "</li>";
        echo "<li><strong>Email:</strong> " . $user['email'] . "</li>";
        echo "</ul>";

        // Corriger le mot de passe si nécessaire
        $password_test = password_verify('password', $user['password']);
        if (!$password_test) {
            $new_hash = password_hash('password', PASSWORD_DEFAULT);
            $db->query("UPDATE users SET password = ? WHERE email = 'demo@example.com'", [$new_hash]);
            echo "<p class='success'>✅ Mot de passe utilisateur corrigé!</p>";
        } else {
            echo "<p class='success'>✅ Mot de passe utilisateur fonctionne!</p>";
        }

    } else {
        echo "<p class='info'>💡 Création de l'utilisateur démo...</p>";
        $hash = password_hash('password', PASSWORD_DEFAULT);
        $db->query("INSERT INTO users (name, email, password, phone) VALUES (?, ?, ?, ?)",
                   ['Utilisateur Démo', 'demo@example.com', $hash, '+226 70 00 00 00']);
        echo "<p class='success'>✅ Utilisateur démo créé!</p>";
    }

    echo "<h2>🎯 Résumé des identifiants:</h2>";
    echo "<div style='background:#1e1e1e;padding:20px;border-radius:8px;border-left:4px solid #00ff88;'>";
    echo "<h3>👨‍💼 ADMIN</h3>";
    echo "<p><strong>URL:</strong> <a href='admin/login.php' style='color:#00ff88;'>admin/login.php</a></p>";
    echo "<p><strong>Email:</strong> admin@smm.com</p>";
    echo "<p><strong>Mot de passe:</strong> password</p>";
    echo "<br>";
    echo "<h3>👤 CLIENT DÉMO</h3>";
    echo "<p><strong>URL:</strong> <a href='login.php' style='color:#00ff88;'>login.php</a></p>";
    echo "<p><strong>Email:</strong> demo@example.com</p>";
    echo "<p><strong>Mot de passe:</strong> password</p>";
    echo "</div>";

    echo "<br><p class='success'>🎉 <strong>Test terminé avec succès!</strong></p>";
    echo "<p class='info'>💡 <strong>Conseil:</strong> Supprimez ce fichier après vérification pour la sécurité.</p>";

} catch (Exception $e) {
    echo "<p class='error'>❌ <strong>Erreur:</strong> " . $e->getMessage() . "</p>";
    echo "<p class='info'>💡 Vérifiez votre configuration dans config/database.php</p>";
}
?>
