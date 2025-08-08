<?php
require_once '../includes/auth.php';
$auth = new Auth();
$auth->requireAdminLogin();

$admin = $auth->getCurrentAdmin();
$db = new Database();

// Statistiques générales
$stats = [
    'total_users' => $db->fetch("SELECT COUNT(*) as count FROM users")['count'],
    'total_orders' => $db->fetch("SELECT COUNT(*) as count FROM orders")['count'],
    'pending_orders' => $db->fetch("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'")['count'],
    'processing_orders' => $db->fetch("SELECT COUNT(*) as count FROM orders WHERE status = 'processing'")['count'],
    'completed_orders' => $db->fetch("SELECT COUNT(*) as count FROM orders WHERE status = 'completed'")['count'],
    'cancelled_orders' => $db->fetch("SELECT COUNT(*) as count FROM orders WHERE status = 'cancelled'")['count'],
    'total_revenue' => $db->fetch("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE status = 'completed'")['total'],
    'pending_revenue' => $db->fetch("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE status IN ('pending', 'processing')")['total']
];

// Commandes récentes nécessitant une attention
$recent_orders = $db->fetchAll("
    SELECT o.*, u.name as user_name, u.email as user_email,
           s.name as service_name, c.name as category_name, c.icon as category_icon
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN services s ON o.service_id = s.id
    JOIN categories c ON s.category_id = c.id
    WHERE o.status IN ('pending', 'processing')
    ORDER BY o.created_at DESC
    LIMIT 10
");

// Nouveaux utilisateurs cette semaine
$new_users_week = $db->fetch("
    SELECT COUNT(*) as count
    FROM users
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
")['count'];

// Chiffre d'affaires cette semaine
$revenue_week = $db->fetch("
    SELECT COALESCE(SUM(total_amount), 0) as total
    FROM orders
    WHERE status = 'completed' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
")['total'];

// Services les plus populaires
$popular_services = $db->fetchAll("
    SELECT s.name, c.name as category_name, COUNT(o.id) as order_count,
           SUM(o.total_amount) as revenue
    FROM services s
    JOIN categories c ON s.category_id = c.id
    LEFT JOIN orders o ON s.id = o.service_id AND o.status = 'completed'
    GROUP BY s.id
    ORDER BY order_count DESC
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navigation Admin -->
    <nav class="navbar">
        <div class="container">
            <div class="nav-content">
                <a href="dashboard.php" class="logo">
                    <i class="fas fa-shield-alt"></i>
                    <?php echo SITE_NAME; ?> - Admin
                </a>

                <div class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>

                <ul class="nav-links" id="navLinks">
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="orders.php">Commandes</a></li>
                    <li><a href="users.php">Utilisateurs</a></li>
                    <li><a href="services.php">Services</a></li>
                    <li><a href="categories.php">Catégories</a></li>
                    <li>
                        <span style="color: var(--primary-color);">
                            <i class="fas fa-user-shield"></i> <?php echo htmlspecialchars($admin['name']); ?>
                        </span>
                    </li>
                    <li><a href="../logout.php" class="btn btn-secondary">Déconnexion</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Dashboard Admin -->
    <div class="dashboard">
        <div class="container">
            <div class="dashboard-header">
                <div>
                    <h1 style="color: var(--text-primary); margin-bottom: 0.5rem;">
                        <i class="fas fa-tachometer-alt"></i> Dashboard Administrateur
                    </h1>
                    <p style="color: var(--text-secondary);">
                        Bienvenue, <?php echo htmlspecialchars($admin['name']); ?> ! Vue d'ensemble de la plateforme SMM.
                    </p>
                </div>
                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <a href="orders.php?status=pending" class="btn btn-primary">
                        <i class="fas fa-clock"></i> Commandes en attente (<?php echo $stats['pending_orders']; ?>)
                    </a>
                </div>
            </div>

            <!-- Statistiques principales -->
            <div class="dashboard-stats">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_users']; ?></div>
                    <div class="stat-label">
                        <i class="fas fa-users"></i> Utilisateurs
                        <small>(+<?php echo $new_users_week; ?> cette semaine)</small>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_orders']; ?></div>
                    <div class="stat-label">
                        <i class="fas fa-shopping-cart"></i> Total Commandes
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['pending_orders']; ?></div>
                    <div class="stat-label">
                        <i class="fas fa-clock"></i> En Attente
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo formatPrice($stats['total_revenue']); ?></div>
                    <div class="stat-label">
                        <i class="fas fa-money-bill-wave"></i> Chiffre d'Affaires
                        <small>(+<?php echo formatPrice($revenue_week); ?> cette semaine)</small>
                    </div>
                </div>
            </div>

            <!-- Statistiques détaillées -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                <div class="stat-card">
                    <div class="stat-number" style="color: var(--warning-color);"><?php echo $stats['processing_orders']; ?></div>
                    <div class="stat-label">
                        <i class="fas fa-cogs"></i> En Cours
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" style="color: var(--success-color);"><?php echo $stats['completed_orders']; ?></div>
                    <div class="stat-label">
                        <i class="fas fa-check-circle"></i> Terminées
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" style="color: var(--error-color);"><?php echo $stats['cancelled_orders']; ?></div>
                    <div class="stat-label">
                        <i class="fas fa-times-circle"></i> Annulées
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" style="color: var(--primary-color);"><?php echo formatPrice($stats['pending_revenue']); ?></div>
                    <div class="stat-label">
                        <i class="fas fa-hourglass-half"></i> CA en attente
                    </div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 300px; gap: 2rem; align-items: start;">
                <!-- Commandes nécessitant une attention -->
                <div class="card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                        <h2 style="color: var(--primary-color);">
                            <i class="fas fa-exclamation-circle"></i> Commandes à Traiter
                        </h2>
                        <a href="orders.php" style="color: var(--primary-color); text-decoration: none;">
                            Voir toutes <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>

                    <?php if (empty($recent_orders)): ?>
                        <div style="text-align: center; padding: 3rem 1rem; color: var(--text-secondary);">
                            <i class="fas fa-check-circle" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5; color: var(--success-color);"></i>
                            <h3>Aucune commande en attente</h3>
                            <p>Toutes les commandes sont traitées ! 🎉</p>
                        </div>
                    <?php else: ?>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Client</th>
                                        <th>Service</th>
                                        <th>Montant</th>
                                        <th>Statut</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_orders as $order): ?>
                                    <tr>
                                        <td style="font-weight: 600; color: var(--primary-color);">#<?php echo $order['id']; ?></td>
                                        <td>
                                            <div>
                                                <div style="font-weight: 600;"><?php echo htmlspecialchars($order['user_name']); ?></div>
                                                <div style="font-size: 0.8rem; color: var(--text-secondary);"><?php echo htmlspecialchars($order['user_email']); ?></div>
                                            </div>
                                        </td>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                                <i class="<?php echo $order['category_icon']; ?>" style="color: var(--primary-color);"></i>
                                                <div>
                                                    <div style="font-weight: 600;"><?php echo htmlspecialchars($order['service_name']); ?></div>
                                                    <div style="font-size: 0.8rem; color: var(--text-secondary);">Qty: <?php echo number_format($order['quantity']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td style="font-weight: 600;"><?php echo formatPrice($order['total_amount']); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $order['status']; ?>">
                                                <?php
                                                switch($order['status']) {
                                                    case 'pending': echo 'En attente'; break;
                                                    case 'processing': echo 'En cours'; break;
                                                    case 'completed': echo 'Terminé'; break;
                                                    case 'cancelled': echo 'Annulé'; break;
                                                }
                                                ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m H:i', strtotime($order['created_at'])); ?></td>
                                        <td>
                                            <a href="orders.php?view=<?php echo $order['id']; ?>"
                                               class="btn btn-primary" style="padding: 0.25rem 0.5rem; font-size: 0.8rem; text-decoration: none;">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Sidebar avec actions et infos -->
                <div>
                    <!-- Actions rapides -->
                    <div class="card" style="margin-bottom: 1.5rem;">
                        <h3 style="color: var(--primary-color); margin-bottom: 1rem;">
                            <i class="fas fa-bolt"></i> Actions Rapides
                        </h3>
                        <div style="display: grid; gap: 0.75rem;">
                            <a href="orders.php?status=pending" class="btn btn-primary" style="text-decoration: none;">
                                <i class="fas fa-clock"></i> Commandes en attente
                            </a>
                            <a href="services.php" class="btn btn-secondary" style="text-decoration: none;">
                                <i class="fas fa-cogs"></i> Gérer les services
                            </a>
                            <a href="users.php" class="btn btn-secondary" style="text-decoration: none;">
                                <i class="fas fa-users"></i> Voir les utilisateurs
                            </a>
                            <a href="categories.php" class="btn btn-secondary" style="text-decoration: none;">
                                <i class="fas fa-tags"></i> Gérer les catégories
                            </a>
                        </div>
                    </div>

                    <!-- Services populaires -->
                    <div class="card" style="margin-bottom: 1.5rem;">
                        <h3 style="color: var(--primary-color); margin-bottom: 1rem;">
                            <i class="fas fa-fire"></i> Services Populaires
                        </h3>
                        <div style="display: grid; gap: 0.75rem;">
                            <?php foreach ($popular_services as $service): ?>
                            <div style="padding: 0.75rem; background: var(--secondary-color); border-radius: 6px;">
                                <div style="font-weight: 600; color: var(--text-primary); font-size: 0.9rem; margin-bottom: 0.25rem;">
                                    <?php echo htmlspecialchars($service['name']); ?>
                                </div>
                                <div style="display: flex; justify-content: space-between; font-size: 0.8rem; color: var(--text-secondary);">
                                    <span><?php echo $service['order_count']; ?> commandes</span>
                                    <span><?php echo formatPrice($service['revenue']); ?></span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Alertes système -->
                    <div class="card">
                        <h3 style="color: var(--warning-color); margin-bottom: 1rem;">
                            <i class="fas fa-exclamation-triangle"></i> Alertes Système
                        </h3>
                        <div style="display: grid; gap: 0.5rem;">
                            <?php if ($stats['pending_orders'] > 10): ?>
                            <div style="padding: 0.5rem; background: rgba(255, 165, 0, 0.1); border-radius: 4px; color: var(--warning-color); font-size: 0.9rem;">
                                <i class="fas fa-clock"></i> <?php echo $stats['pending_orders']; ?> commandes en attente
                            </div>
                            <?php endif; ?>

                            <?php if ($new_users_week > 50): ?>
                            <div style="padding: 0.5rem; background: rgba(0, 255, 136, 0.1); border-radius: 4px; color: var(--success-color); font-size: 0.9rem;">
                                <i class="fas fa-users"></i> <?php echo $new_users_week; ?> nouveaux utilisateurs cette semaine
                            </div>
                            <?php endif; ?>

                            <div style="padding: 0.5rem; background: rgba(0, 255, 136, 0.1); border-radius: 4px; color: var(--primary-color); font-size: 0.9rem;">
                                <i class="fas fa-info-circle"></i> Système opérationnel
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleMobileMenu() {
            const navLinks = document.getElementById('navLinks');
            navLinks.classList.toggle('active');
        }

        // Auto-refresh des statistiques toutes les 30 secondes
        setInterval(() => {
            fetch('api/admin-stats.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Mettre à jour les statistiques sans recharger la page
                        document.querySelector('.stat-card:nth-child(3) .stat-number').textContent = data.stats.pending_orders;
                    }
                })
                .catch(error => console.log('Erreur lors de la mise à jour des stats'));
        }, 30000);

        // Graphique simple des commandes (pourrait être amélioré avec Chart.js)
        function createSimpleChart() {
            // Cette fonction pourrait être étendue pour créer des graphiques
            console.log('Graphiques disponibles dans une version future');
        }

        // Notifications pour nouvelles commandes
        function checkNewOrders() {
            // Cette fonction pourrait vérifier les nouvelles commandes
            console.log('Vérification des nouvelles commandes...');
        }

        // Démarrer les vérifications périodiques
        setInterval(checkNewOrders, 60000); // Chaque minute
    </script>
</body>
</html>
