-- Base de données pour site SMM
CREATE DATABASE IF NOT EXISTS smm_website CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE smm_website;

-- Table des utilisateurs (clients)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des admins
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des catégories de services
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(50),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des services
CREATE TABLE services (
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
CREATE TABLE orders (
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

-- Insertion de l'admin par défaut (en utilisant un hash simple qui fonctionne)
INSERT INTO admins (name, email, password) VALUES
('Admin', 'admin@smm.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'); -- password: password

-- Vérification que l'admin a été inséré
SELECT 'Admin créé:' as info, id, name, email FROM admins WHERE email = 'admin@smm.com';

-- Insertion d'un utilisateur de démonstration
INSERT INTO users (name, email, password, phone) VALUES
('Utilisateur Démo', 'demo@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+226 70 00 00 00'); -- password: password

-- Vérification que l'utilisateur démo a été inséré
SELECT 'Utilisateur démo créé:' as info, id, name, email FROM users WHERE email = 'demo@example.com';

-- Insertion des catégories par défaut
INSERT INTO categories (name, description, icon) VALUES
('Instagram', 'Services pour Instagram', 'fab fa-instagram'),
('Facebook', 'Services pour Facebook', 'fab fa-facebook'),
('Twitter', 'Services pour Twitter', 'fab fa-twitter'),
('YouTube', 'Services pour YouTube', 'fab fa-youtube'),
('TikTok', 'Services pour TikTok', 'fab fa-tiktok');

-- Insertion des services par défaut
INSERT INTO services (category_id, name, description, price_per_unit, min_quantity, max_quantity) VALUES
(1, 'Followers Instagram', 'Followers de qualité pour votre compte Instagram', 50, 100, 10000),
(1, 'Likes Instagram', 'Likes pour vos publications Instagram', 25, 50, 5000),
(1, 'Vues Stories Instagram', 'Vues pour vos stories Instagram', 15, 100, 10000),
(2, 'Likes Facebook', 'Likes pour vos publications Facebook', 30, 50, 5000),
(2, 'Followers Page Facebook', 'Followers pour votre page Facebook', 45, 100, 10000),
(3, 'Followers Twitter', 'Followers pour votre compte Twitter', 40, 100, 10000),
(3, 'Retweets', 'Retweets pour vos tweets', 35, 10, 1000),
(4, 'Vues YouTube', 'Vues pour vos vidéos YouTube', 10, 1000, 100000),
(4, 'Likes YouTube', 'Likes pour vos vidéos YouTube', 30, 50, 5000),
(5, 'Followers TikTok', 'Followers pour votre compte TikTok', 55, 100, 10000),
(5, 'Likes TikTok', 'Likes pour vos vidéos TikTok', 20, 100, 10000);
