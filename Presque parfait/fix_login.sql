-- Script SQL pour corriger les mots de passe de connexion
-- Exécutez ce script dans phpMyAdmin ou votre interface MySQL

-- Corriger le mot de passe admin (password: password)
UPDATE admins SET password = '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyN5.g5/Zx8vScV/WzY0G4xA2dL2i' WHERE email = 'admin@smm.com';

-- Corriger le mot de passe utilisateur démo (password: demo123)
UPDATE users SET password = '$2y$10$GC/PVsOuTGCLjt2vK1M0e.q1k3DRSHiY6Y3/eR4J3pHE1Ly/4x2Ju' WHERE email = 'demo@example.com';

-- Vérifier que les mises à jour ont été effectuées
SELECT 'admin@smm.com' as email, 'password' as mot_de_passe, 'Admin' as type
UNION ALL
SELECT 'demo@example.com' as email, 'demo123' as mot_de_passe, 'Client' as type;
