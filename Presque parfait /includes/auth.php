<?php
session_start();
require_once 'config/database.php';

class Auth {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    // Authentification utilisateur
    public function loginUser($email, $password) {
        $sql = "SELECT * FROM users WHERE email = ?";
        $user = $this->db->fetch($sql, [$email]);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            return true;
        }
        return false;
    }

    // Authentification admin
    public function loginAdmin($email, $password) {
        $sql = "SELECT * FROM admins WHERE email = ?";
        $admin = $this->db->fetch($sql, [$email]);

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['name'];
            $_SESSION['admin_email'] = $admin['email'];
            return true;
        }
        return false;
    }

    // Inscription utilisateur
    public function registerUser($name, $email, $password, $phone = '') {
        // Vérifier si l'email existe déjà
        $sql = "SELECT id FROM users WHERE email = ?";
        $existing = $this->db->fetch($sql, [$email]);

        if ($existing) {
            return false;
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (name, email, password, phone) VALUES (?, ?, ?, ?)";

        try {
            $this->db->query($sql, [$name, $email, $hashedPassword, $phone]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    // Vérifier si l'utilisateur est connecté
    public function isUserLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    // Vérifier si l'admin est connecté
    public function isAdminLoggedIn() {
        return isset($_SESSION['admin_id']);
    }

    // Déconnexion
    public function logout() {
        session_destroy();
        header('Location: index.php');
        exit();
    }

    // Redirection si pas connecté
    public function requireUserLogin() {
        if (!$this->isUserLoggedIn()) {
            header('Location: login.php');
            exit();
        }
    }

    public function requireAdminLogin() {
        if (!$this->isAdminLoggedIn()) {
            header('Location: admin/login.php');
            exit();
        }
    }

    // Obtenir les informations de l'utilisateur connecté
    public function getCurrentUser() {
        if ($this->isUserLoggedIn()) {
            $sql = "SELECT * FROM users WHERE id = ?";
            return $this->db->fetch($sql, [$_SESSION['user_id']]);
        }
        return null;
    }

    // Obtenir les informations de l'admin connecté
    public function getCurrentAdmin() {
        if ($this->isAdminLoggedIn()) {
            $sql = "SELECT * FROM admins WHERE id = ?";
            return $this->db->fetch($sql, [$_SESSION['admin_id']]);
        }
        return null;
    }
}

// Fonctions utilitaires
function sanitizeInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function formatPrice($price) {
    return number_format($price, 0, ',', ' ') . ' FCFA';
}

function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function generateOrderId() {
    return 'SMM' . date('Ymd') . rand(1000, 9999);
}
?>
