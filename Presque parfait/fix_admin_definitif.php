<?php
// Script pour corriger DÉFINITIVEMENT le problème de l'admin
require_once 'config/database.php';

echo "🔧 CORRECTION DÉFINITIVE DE L'ADMIN\n\n";
echo "<style>body{font-family:Arial;background:#0f0f0f;color:#fff;padding:20px;} .success{color:#00ff88;} .error{color:#ff4444;} .info{color:#007bff;}</style>";

try {
    $db = new Database();
    echo "<p class='success'>✅ Connexion à la base de données réussie</p>";

    // ÉTAPE 1: Vérifier si la table admins existe
    echo "<h2>📋 ÉTAPE 1: Vérification de la table admins</h2>";

    try {
        $result = $db->query("DESCRIBE admins");
        echo "<p class='success'>✅ Table admins existe</p>";
    } catch (Exception $e) {
        echo "<p class='error'>❌ Table admins n'existe pas. Création...</p>";
        $db->query("
            CREATE TABLE admins (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(150) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        echo "<p class='success'>✅ Table admins créée</p>";
    }

    // ÉTAPE 2: Supprimer TOUS les admins existants
    echo "<h2>🗑️ ÉTAPE 2: Nettoyage des admins existants</h2>";
    $deleted = $db->query("DELETE FROM admins")->rowCount();
    echo "<p class='info'>🗑️ $deleted admin(s) supprimé(s)</p>";

    // ÉTAPE 3: Reset de l'AUTO_INCREMENT
    echo "<h2>🔄 ÉTAPE 3: Reset de l'AUTO_INCREMENT</h2>";
    $db->query("ALTER TABLE admins AUTO_INCREMENT = 1");
    echo "<p class='success'>✅ AUTO_INCREMENT remis à 1</p>";

    // ÉTAPE 4: Création du nouvel admin avec hash vérifié
    echo "<h2>👨‍💼 ÉTAPE 4: Création du nouvel admin</h2>";

    $admin_password = 'password';
    $admin_hash = password_hash($admin_password, PASSWORD_DEFAULT);

    // Test immédiat du hash
    $hash_test = password_verify($admin_password, $admin_hash);
    echo "<p class='info'>🔐 Hash généré: " . substr($admin_hash, 0, 50) . "...</p>";
    echo "<p class='" . ($hash_test ? 'success' : 'error') . "'>🧪 Test du hash: " . ($hash_test ? 'RÉUSSI' : 'ÉCHOUÉ') . "</p>";

    if (!$hash_test) {
        echo "<p class='error'>❌ ERREUR: Le hash ne fonctionne pas!</p>";
        exit();
    }

    // Insertion de l'admin
    $db->query(
        "INSERT INTO admins (name, email, password, created_at) VALUES (?, ?, ?, NOW())",
        ['Admin', 'admin@smm.com', $admin_hash]
    );

    $admin_id = $db->lastInsertId();
    echo "<p class='success'>✅ Admin créé avec ID: $admin_id</p>";

    // ÉTAPE 5: Vérification immédiate
    echo "<h2>🔍 ÉTAPE 5: Vérification de l'admin créé</h2>";

    $admin = $db->fetch("SELECT * FROM admins WHERE email = 'admin@smm.com'");

    if ($admin) {
        echo "<p class='success'>✅ Admin trouvé dans la base:</p>";
        echo "<ul>";
        echo "<li><strong>ID:</strong> " . $admin['id'] . "</li>";
        echo "<li><strong>Nom:</strong> " . $admin['name'] . "</li>";
        echo "<li><strong>Email:</strong> " . $admin['email'] . "</li>";
        echo "<li><strong>Créé le:</strong> " . $admin['created_at'] . "</li>";
        echo "</ul>";

        // Test du mot de passe
        $password_works = password_verify('password', $admin['password']);
        echo "<p class='" . ($password_works ? 'success' : 'error') . "'>";
        echo ($password_works ? '✅' : '❌') . " Test du mot de passe 'password': ";
        echo ($password_works ? 'FONCTIONNE' : 'NE FONCTIONNE PAS');
        echo "</p>";

        if (!$password_works) {
            echo "<p class='error'>❌ ÉCHEC: Le mot de passe ne fonctionne pas après création!</p>";
        }

    } else {
        echo "<p class='error'>❌ ERREUR: Admin non trouvé après création!</p>";
    }

    // ÉTAPE 6: Test de connexion simulé
    echo "<h2>🔐 ÉTAPE 6: Simulation de connexion admin</h2>";

    $email = 'admin@smm.com';
    $password = 'password';

    $admin_login = $db->fetch("SELECT * FROM admins WHERE email = ?", [$email]);

    if ($admin_login && password_verify($password, $admin_login['password'])) {
        echo "<p class='success'>✅ CONNEXION ADMIN RÉUSSIE!</p>";
        echo "<p class='info'>🎉 L'admin peut maintenant se connecter avec:</p>";
        echo "<div style='background:#1e1e1e;padding:20px;border-radius:8px;border-left:4px solid #00ff88;'>";
        echo "<p><strong>URL:</strong> <a href='admin/login.php' style='color:#00ff88;'>admin/login.php</a></p>";
        echo "<p><strong>Email:</strong> admin@smm.com</p>";
        echo "<p><strong>Mot de passe:</strong> password</p>";
        echo "</div>";
    } else {
        echo "<p class='error'>❌ ÉCHEC DE LA CONNEXION SIMULÉE!</p>";
    }

    // ÉTAPE 7: Vérification finale
    echo "<h2>📊 ÉTAPE 7: Rapport final</h2>";

    $total_admins = $db->fetch("SELECT COUNT(*) as count FROM admins")['count'];
    $total_users = $db->fetch("SELECT COUNT(*) as count FROM users")['count'];

    echo "<div style='background:#1e1e1e;padding:20px;border-radius:8px;margin:20px 0;'>";
    echo "<h3 style='color:#00ff88;'>📈 Statistiques de la base:</h3>";
    echo "<p>👨‍💼 Admins: $total_admins</p>";
    echo "<p>👤 Utilisateurs: $total_users</p>";
    echo "</div>";

    if ($total_admins > 0) {
        echo "<div style='background:#00ff88;color:#000;padding:20px;border-radius:8px;margin:20px 0;'>";
        echo "<h2>🎉 SUCCÈS COMPLET!</h2>";
        echo "<p><strong>L'admin est maintenant correctement configuré et peut se connecter.</strong></p>";
        echo "<p>🔗 <a href='admin/login.php' style='color:#000;text-decoration:underline;'>Tester la connexion admin maintenant</a></p>";
        echo "</div>";

        echo "<div style='background:#ffa500;color:#000;padding:15px;border-radius:8px;margin:20px 0;'>";
        echo "<h3>⚠️ IMPORTANT:</h3>";
        echo "<p>1. Testez immédiatement la connexion admin</p>";
        echo "<p>2. Changez le mot de passe après la première connexion</p>";
        echo "<p>3. Supprimez ce fichier fix_admin_definitif.php</p>";
        echo "</div>";
    } else {
        echo "<p class='error'>❌ ERREUR: Aucun admin n'a été créé!</p>";
    }

} catch (Exception $e) {
    echo "<p class='error'>❌ ERREUR CRITIQUE: " . $e->getMessage() . "</p>";
    echo "<p class='info'>Vérifiez votre configuration de base de données.</p>";
}
?>
