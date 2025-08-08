<?php
// Script pour corriger les mots de passe dans la base de données
require_once 'config/database.php';

echo "🔧 Correction des mots de passe...\n\n";

try {
    $db = new Database();

    // Générer les nouveaux hashs
    $admin_password_hash = password_hash('password', PASSWORD_DEFAULT);
    $demo_password_hash = password_hash('demo123', PASSWORD_DEFAULT);

    // Mettre à jour l'admin
    $db->query("UPDATE admins SET password = ? WHERE email = 'admin@smm.com'", [$admin_password_hash]);
    echo "✅ Mot de passe admin mis à jour\n";
    echo "   Email: admin@smm.com\n";
    echo "   Mot de passe: password\n\n";

    // Mettre à jour l'utilisateur démo
    $db->query("UPDATE users SET password = ? WHERE email = 'demo@example.com'", [$demo_password_hash]);
    echo "✅ Mot de passe utilisateur démo mis à jour\n";
    echo "   Email: demo@example.com\n";
    echo "   Mot de passe: demo123\n\n";

    echo "🎉 Correction terminée ! Vous pouvez maintenant vous connecter.\n\n";
    echo "🗑️ N'oubliez pas de supprimer ce fichier après utilisation !\n";

} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}
?>
