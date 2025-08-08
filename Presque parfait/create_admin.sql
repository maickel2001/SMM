-- Script SQL simple pour créer l'admin avec certitude
-- Exécutez ce script dans phpMyAdmin si l'admin ne se crée pas

-- Supprimer l'admin existant s'il y en a un
DELETE FROM admins WHERE email = 'admin@smm.com';

-- Créer le nouvel admin avec un hash simple qui fonctionne
INSERT INTO admins (name, email, password, created_at) VALUES
('Admin', 'admin@smm.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW());

-- Vérifier que l'admin a été créé
SELECT 'ADMIN CRÉÉ AVEC SUCCÈS!' as resultat, id, name, email, created_at FROM admins WHERE email = 'admin@smm.com';

-- Créer aussi l'utilisateur démo si nécessaire
DELETE FROM users WHERE email = 'demo@example.com';
INSERT INTO users (name, email, password, phone, created_at) VALUES
('Utilisateur Démo', 'demo@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+226 70 00 00 00', NOW());

-- Vérifier que l'utilisateur démo a été créé
SELECT 'UTILISATEUR DÉMO CRÉÉ AVEC SUCCÈS!' as resultat, id, name, email, created_at FROM users WHERE email = 'demo@example.com';

-- Afficher les identifiants finaux
SELECT '=== IDENTIFIANTS DE CONNEXION ===' as info;
SELECT 'ADMIN: admin@smm.com / password' as admin_login;
SELECT 'CLIENT: demo@example.com / password' as client_login;
