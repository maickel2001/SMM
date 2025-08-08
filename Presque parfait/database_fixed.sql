-- Base de données pour site SMM - VERSION CORRIGÉE
CREATE DATABASE IF NOT EXISTS smm_website CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE smm_website;

-- Table des utilisateurs (clients)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des admins
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des catégories de services
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(50),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des services
CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    price_per_unit DECIMAL(10,2) NOT NULL,
    min_quantity INT DEFAULT 1,
    max_quantity INT DEFAULT 10000,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- Table des commandes
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    service_id INT NOT NULL,
    link VARCHAR(500) NOT NULL,
    quantity INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'cancelled') DEFAULT 'pending',
    payment_proof VARCHAR(255),
    cancel_reason TEXT,
    admin_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
);

-- SUPPRESSION DES DONNÉES EXISTANTES (pour éviter les doublons)
DELETE FROM orders;
DELETE FROM services;
DELETE FROM categories;
DELETE FROM users;
DELETE FROM admins;

-- RESET des AUTO_INCREMENT
ALTER TABLE orders AUTO_INCREMENT = 1;
ALTER TABLE services AUTO_INCREMENT = 1;
ALTER TABLE categories AUTO_INCREMENT = 1;
ALTER TABLE users AUTO_INCREMENT = 1;
ALTER TABLE admins AUTO_INCREMENT = 1;

-- INSERTION DE L'ADMIN (en premier pour éviter les problèmes)
INSERT INTO admins (id, name, email, password, created_at) VALUES
(1, 'Admin', 'admin@smm.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW());

-- VÉRIFICATION DE L'INSERTION ADMIN
SELECT 'Admin inséré avec succès' as message, id, name, email FROM admins WHERE email = 'admin@smm.com';

-- INSERTION D'UN UTILISATEUR DE DÉMONSTRATION
INSERT INTO users (id, name, email, password, phone, created_at) VALUES
(1, 'Utilisateur Démo', 'demo@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+226 70 00 00 00', NOW());

-- INSERTION DES CATÉGORIES
INSERT INTO categories (id, name, description, icon, is_active, created_at) VALUES
(1, 'Instagram', 'Services pour Instagram', 'fab fa-instagram', TRUE, NOW()),
(2, 'Facebook', 'Services pour Facebook', 'fab fa-facebook', TRUE, NOW()),
(3, 'Twitter', 'Services pour Twitter', 'fab fa-twitter', TRUE, NOW()),
(4, 'YouTube', 'Services pour YouTube', 'fab fa-youtube', TRUE, NOW()),
(5, 'TikTok', 'Services pour TikTok', 'fab fa-tiktok', TRUE, NOW());

-- INSERTION DES SERVICES
INSERT INTO services (category_id, name, description, price_per_unit, min_quantity, max_quantity, is_active, created_at) VALUES
(1, 'Followers Instagram', 'Followers de qualité pour votre compte Instagram', 50.00, 100, 10000, TRUE, NOW()),
(1, 'Likes Instagram', 'Likes pour vos publications Instagram', 25.00, 50, 5000, TRUE, NOW()),
(1, 'Vues Stories Instagram', 'Vues pour vos stories Instagram', 15.00, 100, 10000, TRUE, NOW()),
(2, 'Likes Facebook', 'Likes pour vos publications Facebook', 30.00, 50, 5000, TRUE, NOW()),
(2, 'Followers Page Facebook', 'Followers pour votre page Facebook', 45.00, 100, 10000, TRUE, NOW()),
(3, 'Followers Twitter', 'Followers pour votre compte Twitter', 40.00, 100, 10000, TRUE, NOW()),
(3, 'Retweets', 'Retweets pour vos tweets', 35.00, 10, 1000, TRUE, NOW()),
(4, 'Vues YouTube', 'Vues pour vos vidéos YouTube', 10.00, 1000, 100000, TRUE, NOW()),
(4, 'Likes YouTube', 'Likes pour vos vidéos YouTube', 30.00, 50, 5000, TRUE, NOW()),
(5, 'Followers TikTok', 'Followers pour votre compte TikTok', 55.00, 100, 10000, TRUE, NOW()),
(5, 'Likes TikTok', 'Likes pour vos vidéos TikTok', 20.00, 100, 10000, TRUE, NOW());

-- VÉRIFICATIONS FINALES
SELECT 'VERIFICATION - Nombre d\'admins:' as check_type, COUNT(*) as count FROM admins;
SELECT 'VERIFICATION - Nombre d\'utilisateurs:' as check_type, COUNT(*) as count FROM users;
SELECT 'VERIFICATION - Nombre de catégories:' as check_type, COUNT(*) as count FROM categories;
SELECT 'VERIFICATION - Nombre de services:' as check_type, COUNT(*) as count FROM services;

-- AFFICHAGE DES COMPTES CRÉÉS
SELECT 'COMPTES CRÉÉS:' as info;
SELECT 'ADMIN' as type, name, email, 'password: password' as mot_de_passe FROM admins;
SELECT 'CLIENT' as type, name, email, 'password: password' as mot_de_passe FROM users;

-- MESSAGE DE SUCCÈS
SELECT '✅ BASE DE DONNÉES CRÉÉE AVEC SUCCÈS!' as message;
SELECT '👨‍💼 Admin: admin@smm.com / password' as admin_login;
SELECT '👤 Client: demo@example.com / password' as client_login;
