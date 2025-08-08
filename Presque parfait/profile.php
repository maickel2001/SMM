<?php
require_once 'includes/auth.php';
$auth = new Auth();
$auth->requireUserLogin();

$user = $auth->getCurrentUser();
$db = new Database();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $name = sanitizeInput($_POST['name']);
        $email = sanitizeInput($_POST['email']);
        $phone = sanitizeInput($_POST['phone']);

        // Validation
        if (empty($name) || empty($email)) {
            $error = 'Le nom et l\'email sont obligatoires.';
        } elseif (!isValidEmail($email)) {
            $error = 'Adresse email invalide.';
        } else {
            // Vérifier si l'email existe déjà (sauf pour l'utilisateur actuel)
            $existing = $db->fetch("SELECT id FROM users WHERE email = ? AND id != ?", [$email, $user['id']]);

            if ($existing) {
                $error = 'Cette adresse email est déjà utilisée par un autre compte.';
            } else {
                try {
                    $db->query(
                        "UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?",
                        [$name, $email, $phone, $user['id']]
                    );

                    // Mettre à jour la session
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_email'] = $email;

                    $success = 'Profil mis à jour avec succès.';
                    $user = $auth->getCurrentUser(); // Recharger les données
                } catch (Exception $e) {
                    $error = 'Erreur lors de la mise à jour du profil.';
                }
            }
        }
    } elseif (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // Validation
        if (empty($current_password) || empty($new_password)) {
            $error = 'Tous les champs de mot de passe sont obligatoires.';
        } elseif (!password_verify($current_password, $user['password'])) {
            $error = 'Mot de passe actuel incorrect.';
        } elseif (strlen($new_password) < 6) {
            $error = 'Le nouveau mot de passe doit contenir au moins 6 caractères.';
        } elseif ($new_password !== $confirm_password) {
            $error = 'Les nouveaux mots de passe ne correspondent pas.';
        } else {
            try {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $db->query("UPDATE users SET password = ? WHERE id = ?", [$hashed_password, $user['id']]);
                $success = 'Mot de passe modifié avec succès.';
            } catch (Exception $e) {
                $error = 'Erreur lors de la modification du mot de passe.';
            }
        }
    }
}

// Statistiques utilisateur
$stats = [
    'total_orders' => $db->fetch("SELECT COUNT(*) as count FROM orders WHERE user_id = ?", [$user['id']])['count'],
    'total_spent' => $db->fetch("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE user_id = ? AND status = 'completed'", [$user['id']])['total'],
    'completed_orders' => $db->fetch("SELECT COUNT(*) as count FROM orders WHERE user_id = ? AND status = 'completed'", [$user['id']])['count'],
    'pending_orders' => $db->fetch("SELECT COUNT(*) as count FROM orders WHERE user_id = ? AND status = 'pending'", [$user['id']])['count']
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <div class="nav-content">
                <a href="index.php" class="logo">
                    <i class="fas fa-rocket"></i>
                    <?php echo SITE_NAME; ?>
                </a>
                <ul class="nav-links">
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="order.php">Commander</a></li>
                    <li><a href="orders.php">Mes Commandes</a></li>
                    <li><a href="logout.php" class="btn btn-secondary">Déconnexion</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Page de profil -->
    <div class="dashboard">
        <div class="container">
            <div class="dashboard-header">
                <div>
                    <h1 style="color: var(--text-primary); margin-bottom: 0.5rem;">
                        <i class="fas fa-user-edit"></i> Mon Profil
                    </h1>
                    <p style="color: var(--text-secondary);">
                        Gérez vos informations personnelles et paramètres de compte
                    </p>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: 1fr 300px; gap: 2rem; align-items: start;">
                <!-- Formulaires de modification -->
                <div>
                    <!-- Informations personnelles -->
                    <div class="card" style="margin-bottom: 2rem;">
                        <h3 style="color: var(--primary-color); margin-bottom: 1.5rem;">
                            <i class="fas fa-user"></i> Informations Personnelles
                        </h3>

                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="name">
                                    <i class="fas fa-user"></i> Nom complet *
                                </label>
                                <input type="text" id="name" name="name" class="form-control"
                                       value="<?php echo htmlspecialchars($user['name']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="email">
                                    <i class="fas fa-envelope"></i> Adresse email *
                                </label>
                                <input type="email" id="email" name="email" class="form-control"
                                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="phone">
                                    <i class="fas fa-phone"></i> Numéro de téléphone
                                </label>
                                <input type="tel" id="phone" name="phone" class="form-control"
                                       value="<?php echo htmlspecialchars($user['phone'] ?: ''); ?>"
                                       placeholder="+226 XX XX XX XX">
                            </div>

                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="fas fa-save"></i> Mettre à jour le profil
                            </button>
                        </form>
                    </div>

                    <!-- Changement de mot de passe -->
                    <div class="card">
                        <h3 style="color: var(--primary-color); margin-bottom: 1.5rem;">
                            <i class="fas fa-lock"></i> Changer le Mot de Passe
                        </h3>

                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="current_password">
                                    <i class="fas fa-lock"></i> Mot de passe actuel *
                                </label>
                                <input type="password" id="current_password" name="current_password" class="form-control" required>
                            </div>

                            <div class="form-group">
                                <label for="new_password">
                                    <i class="fas fa-key"></i> Nouveau mot de passe *
                                </label>
                                <input type="password" id="new_password" name="new_password" class="form-control"
                                       placeholder="Minimum 6 caractères" required>
                            </div>

                            <div class="form-group">
                                <label for="confirm_password">
                                    <i class="fas fa-key"></i> Confirmer le nouveau mot de passe *
                                </label>
                                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                            </div>

                            <button type="submit" name="change_password" class="btn btn-primary">
                                <i class="fas fa-key"></i> Changer le mot de passe
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Sidebar avec statistiques et actions -->
                <div>
                    <!-- Informations du compte -->
                    <div class="card" style="margin-bottom: 1.5rem;">
                        <h3 style="color: var(--primary-color); margin-bottom: 1rem;">
                            <i class="fas fa-info-circle"></i> Informations du Compte
                        </h3>
                        <div style="display: grid; gap: 0.75rem;">
                            <div>
                                <label style="font-size: 0.9rem; color: var(--text-secondary);">ID du compte</label>
                                <div style="font-weight: 600; font-family: monospace;">#<?php echo $user['id']; ?></div>
                            </div>
                            <div>
                                <label style="font-size: 0.9rem; color: var(--text-secondary);">Membre depuis</label>
                                <div style="font-weight: 600;"><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></div>
                            </div>
                            <div>
                                <label style="font-size: 0.9rem; color: var(--text-secondary);">Dernière mise à jour</label>
                                <div style="font-weight: 600;"><?php echo date('d/m/Y H:i', strtotime($user['updated_at'])); ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Statistiques -->
                    <div class="card" style="margin-bottom: 1.5rem;">
                        <h3 style="color: var(--primary-color); margin-bottom: 1rem;">
                            <i class="fas fa-chart-bar"></i> Mes Statistiques
                        </h3>
                        <div style="display: grid; gap: 0.75rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="color: var(--text-secondary);">
                                    <i class="fas fa-shopping-cart"></i> Commandes
                                </span>
                                <span style="font-weight: 600; color: var(--primary-color);"><?php echo $stats['total_orders']; ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="color: var(--text-secondary);">
                                    <i class="fas fa-check-circle"></i> Terminées
                                </span>
                                <span style="font-weight: 600; color: var(--success-color);"><?php echo $stats['completed_orders']; ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="color: var(--text-secondary);">
                                    <i class="fas fa-clock"></i> En attente
                                </span>
                                <span style="font-weight: 600; color: var(--warning-color);"><?php echo $stats['pending_orders']; ?></span>
                            </div>
                            <hr style="border: none; height: 1px; background: var(--border-color); margin: 0.5rem 0;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="color: var(--text-secondary);">
                                    <i class="fas fa-money-bill-wave"></i> Total dépensé
                                </span>
                                <span style="font-weight: 600; color: var(--primary-color);"><?php echo formatPrice($stats['total_spent']); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Actions rapides -->
                    <div class="card" style="margin-bottom: 1.5rem;">
                        <h3 style="color: var(--primary-color); margin-bottom: 1rem;">
                            <i class="fas fa-bolt"></i> Actions Rapides
                        </h3>
                        <div style="display: grid; gap: 0.75rem;">
                            <a href="order.php" class="btn btn-primary" style="text-decoration: none;">
                                <i class="fas fa-plus"></i> Nouvelle Commande
                            </a>
                            <a href="orders.php" class="btn btn-secondary" style="text-decoration: none;">
                                <i class="fas fa-list"></i> Mes Commandes
                            </a>
                            <a href="dashboard.php" class="btn btn-secondary" style="text-decoration: none;">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </div>
                    </div>

                    <!-- Support -->
                    <div class="card">
                        <h3 style="color: var(--primary-color); margin-bottom: 1rem;">
                            <i class="fas fa-headset"></i> Support
                        </h3>
                        <p style="color: var(--text-secondary); margin-bottom: 1rem; font-size: 0.9rem;">
                            Besoin d'aide avec votre compte ?
                        </p>
                        <div style="display: grid; gap: 0.5rem;">
                            <a href="#contact" style="color: var(--primary-color); text-decoration: none; display: flex; align-items: center; gap: 0.5rem;">
                                <i class="fas fa-envelope"></i> Email Support
                            </a>
                            <a href="#" style="color: var(--primary-color); text-decoration: none; display: flex; align-items: center; gap: 0.5rem;">
                                <i class="fab fa-telegram"></i> Telegram
                            </a>
                            <a href="#" style="color: var(--primary-color); text-decoration: none; display: flex; align-items: center; gap: 0.5rem;">
                                <i class="fab fa-whatsapp"></i> WhatsApp
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Zone de danger -->
            <div class="card" style="margin-top: 2rem; border: 2px solid var(--error-color);">
                <h3 style="color: var(--error-color); margin-bottom: 1rem;">
                    <i class="fas fa-exclamation-triangle"></i> Zone de Danger
                </h3>
                <p style="color: var(--text-secondary); margin-bottom: 1.5rem;">
                    Ces actions sont irréversibles. Assurez-vous de bien comprendre les conséquences.
                </p>
                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <button onclick="confirmDeleteAccount()" class="btn btn-danger">
                        <i class="fas fa-user-times"></i> Supprimer le compte
                    </button>
                    <a href="logout.php" class="btn btn-secondary" style="text-decoration: none;">
                        <i class="fas fa-sign-out-alt"></i> Se déconnecter
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmation -->
    <div id="deleteModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 2000; align-items: center; justify-content: center;">
        <div style="background: var(--card-bg); border-radius: 12px; padding: 2rem; max-width: 500px; width: 90%;">
            <h3 style="color: var(--error-color); margin-bottom: 1rem;">
                <i class="fas fa-exclamation-triangle"></i> Confirmer la suppression
            </h3>
            <p style="color: var(--text-secondary); margin-bottom: 1.5rem;">
                Êtes-vous sûr de vouloir supprimer votre compte ? Cette action est irréversible et supprimera :
            </p>
            <ul style="color: var(--text-secondary); margin-bottom: 1.5rem; padding-left: 1.5rem;">
                <li>Toutes vos informations personnelles</li>
                <li>Votre historique de commandes</li>
                <li>Tous vos accès au service</li>
            </ul>
            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button onclick="closeDeleteModal()" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Annuler
                </button>
                <button onclick="deleteAccount()" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Supprimer définitivement
                </button>
            </div>
        </div>
    </div>

    <script>
        // Validation en temps réel du mot de passe
        document.getElementById('new_password').addEventListener('input', function() {
            const password = this.value;
            const confirmPassword = document.getElementById('confirm_password');

            if (password.length < 6) {
                this.style.borderColor = 'var(--error-color)';
            } else {
                this.style.borderColor = 'var(--success-color)';
            }

            if (confirmPassword.value && confirmPassword.value !== password) {
                confirmPassword.style.borderColor = 'var(--error-color)';
            } else if (confirmPassword.value) {
                confirmPassword.style.borderColor = 'var(--success-color)';
            }
        });

        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('new_password').value;

            if (this.value !== password) {
                this.style.borderColor = 'var(--error-color)';
            } else {
                this.style.borderColor = 'var(--success-color)';
            }
        });

        function confirmDeleteAccount() {
            document.getElementById('deleteModal').style.display = 'flex';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        function deleteAccount() {
            // Cette fonctionnalité pourrait être implémentée plus tard
            alert('Fonctionnalité de suppression de compte non implémentée pour des raisons de sécurité. Contactez le support pour supprimer votre compte.');
            closeDeleteModal();
        }

        // Responsive design
        function handleResize() {
            const container = document.querySelector('.dashboard .container > div');
            if (window.innerWidth <= 768) {
                container.style.gridTemplateColumns = '1fr';
            } else {
                container.style.gridTemplateColumns = '1fr 300px';
            }
        }

        window.addEventListener('resize', handleResize);
        handleResize();
    </script>
</body>
</html>
