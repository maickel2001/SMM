<?php
// Script SIMPLE pour créer l'admin - VERSION FONCTIONNELLE GARANTIE
require_once 'config/database.php';

echo "<h1>🔧 CRÉATION ADMIN - VERSION SIMPLE</h1>";
echo "<style>body{font-family:Arial;background:#0f0f0f;color:#fff;padding:20px;} .success{color:#00ff88;} .error{color:#ff4444;}</style>";

try {
    $db = new Database();

    // 1. Supprimer TOUS les admins existants
    $db->query("DELETE FROM admins");
    echo "<p class='success'>✅ Tous les anciens admins supprimés</p>";

    // 2. Créer le hash manuellement et le tester
    $password = 'password';
    $hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'; // Hash connu qui fonctionne

    echo "<p>🔐 Test du hash...</p>";
    if (password_verify($password, $hash)) {
        echo "<p class='success'>✅ Hash fonctionne</p>";
    } else {
        echo "<p class='error'>❌ Hash ne fonctionne pas, création d'un nouveau...</p>";
        $hash = password_hash($password, PASSWORD_DEFAULT);
    }

    // 3. Insérer l'admin avec le hash testé
    $db->query("INSERT INTO admins (name, email, password) VALUES (?, ?, ?)",
               ['Admin', 'admin@smm.com', $hash]);

    echo "<p class='success'>✅ Admin créé dans la base</p>";

    // 4. Vérification immédiate
    $admin = $db->fetch("SELECT * FROM admins WHERE email = 'admin@smm.com'");

    if ($admin) {
        echo "<p class='success'>✅ Admin trouvé: ID " . $admin['id'] . "</p>";

        // Test du login
        if (password_verify('password', $admin['password'])) {
            echo "<div style='background:#00ff88;color:#000;padding:20px;border-radius:8px;margin:20px 0;'>";
            echo "<h2>🎉 SUCCÈS TOTAL !</h2>";
            echo "<p><strong>L'admin est créé et fonctionnel !</strong></p>";
            echo "<p>Email: admin@smm.com</p>";
            echo "<p>Mot de passe: password</p>";
            echo "<p><a href='admin/login.php' style='color:#000;text-decoration:underline;'>➡️ TESTER LA CONNEXION MAINTENANT</a></p>";
            echo "</div>";
        } else {
            echo "<p class='error'>❌ Le mot de passe ne fonctionne pas après création</p>";
        }
    } else {
        echo "<p class='error'>❌ Admin non trouvé après création</p>";
    }

} catch (Exception $e) {
    echo "<p class='error'>❌ ERREUR: " . $e->getMessage() . "</p>";
}

echo "<p style='margin-top:30px;color:#ffa500;'>⚠️ Supprimez ce fichier après vérification !</p>";
?>
