<?php
// Script pour générer les bons hashs de mots de passe
// Exécutez ce script pour obtenir les hashs corrects

echo "🔐 Génération des hashs de mots de passe\n\n";

// Mots de passe en clair
$admin_password = 'password';
$demo_password = 'demo123';

// Génération des hashs
$admin_hash = password_hash($admin_password, PASSWORD_DEFAULT);
$demo_hash = password_hash($demo_password, PASSWORD_DEFAULT);

echo "✅ ADMIN\n";
echo "Email: admin@smm.com\n";
echo "Mot de passe: $admin_password\n";
echo "Hash: $admin_hash\n\n";

echo "✅ UTILISATEUR DÉMO\n";
echo "Email: demo@example.com\n";
echo "Mot de passe: $demo_password\n";
echo "Hash: $demo_hash\n\n";

echo "📋 REQUÊTES SQL POUR CORRIGER:\n\n";
echo "UPDATE admins SET password = '$admin_hash' WHERE email = 'admin@smm.com';\n";
echo "UPDATE users SET password = '$demo_hash' WHERE email = 'demo@example.com';\n\n";

echo "🔍 VÉRIFICATION:\n";
echo "Test admin: " . (password_verify($admin_password, $admin_hash) ? "✅ OK" : "❌ ERREUR") . "\n";
echo "Test démo: " . (password_verify($demo_password, $demo_hash) ? "✅ OK" : "❌ ERREUR") . "\n";
?>
