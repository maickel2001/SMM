<?php
require_once 'includes/auth.php';
$auth = new Auth();
$auth->requireUserLogin();

$user = $auth->getCurrentUser();
$db = new Database();

// Statistiques avancées de l'utilisateur
$stats = [
    'total_orders' => $db->fetch("SELECT COUNT(*) as count FROM orders WHERE user_id = ?", [$user['id']])['count'],
    'pending_orders' => $db->fetch("SELECT COUNT(*) as count FROM orders WHERE user_id = ? AND status = 'pending'", [$user['id']])['count'],
    'processing_orders' => $db->fetch("SELECT COUNT(*) as count FROM orders WHERE user_id = ? AND status = 'processing'", [$user['id']])['count'],
    'completed_orders' => $db->fetch("SELECT COUNT(*) as count FROM orders WHERE user_id = ? AND status = 'completed'", [$user['id']])['count'],
    'cancelled_orders' => $db->fetch("SELECT COUNT(*) as count FROM orders WHERE user_id = ? AND status = 'cancelled'", [$user['id']])['count'],
    'total_spent' => $db->fetch("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE user_id = ? AND status = 'completed'", [$user['id']])['total'],
    'total_pending_amount' => $db->fetch("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE user_id = ? AND status IN ('pending', 'processing')", [$user['id']])['total'],
    'avg_order_value' => $db->fetch("SELECT COALESCE(AVG(total_amount), 0) as avg FROM orders WHERE user_id = ? AND status = 'completed'", [$user['id']])['avg']
];

// Calculer le taux de succès
$success_rate = $stats['total_orders'] > 0 ? round(($stats['completed_orders'] / $stats['total_orders']) * 100, 1) : 0;

// Dernières commandes avec plus d'infos
$recent_orders = $db->fetchAll("
    SELECT o.*, s.name as service_name, s.price_per_unit, c.name as category_name, c.icon as category_icon,
           DATEDIFF(NOW(), o.created_at) as days_ago
    FROM orders o
    JOIN services s ON o.service_id = s.id
    JOIN categories c ON s.category_id = c.id
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
    LIMIT 8
", [$user['id']]);

// Services les plus utilisés par l'utilisateur
$favorite_services = $db->fetchAll("
    SELECT s.name, c.name as category_name, c.icon, COUNT(o.id) as order_count,
           SUM(o.total_amount) as total_spent
    FROM orders o
    JOIN services s ON o.service_id = s.id
    JOIN categories c ON s.category_id = c.id
    WHERE o.user_id = ? AND o.status = 'completed'
    GROUP BY s.id
    ORDER BY order_count DESC
    LIMIT 5
", [$user['id']]);

// Évolution des commandes par mois (6 derniers mois)
$monthly_stats = $db->fetchAll("
    SELECT
        DATE_FORMAT(created_at, '%Y-%m') as month,
        DATE_FORMAT(created_at, '%M %Y') as month_name,
        COUNT(*) as order_count,
        SUM(total_amount) as total_amount
    FROM orders
    WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC
", [$user['id']]);

// Notifications/Alertes pour l'utilisateur
$notifications = [];

// Commandes en attente depuis plus de 24h
$old_pending = $db->fetch("SELECT COUNT(*) as count FROM orders WHERE user_id = ? AND status = 'pending' AND created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)", [$user['id']])['count'];
if ($old_pending > 0) {
    $notifications[] = [
        'type' => 'warning',
        'icon' => 'fas fa-clock',
        'message' => "Vous avez $old_pending commande(s) en attente depuis plus de 24h. Vérifiez votre preuve de paiement.",
        'action' => 'orders.php?status=pending'
    ];
}

// Félicitations pour commandes terminées récemment
$recent_completed = $db->fetch("SELECT COUNT(*) as count FROM orders WHERE user_id = ? AND status = 'completed' AND updated_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)", [$user['id']])['count'];
if ($recent_completed > 0) {
    $notifications[] = [
        'type' => 'success',
        'icon' => 'fas fa-check-circle',
        'message' => "Félicitations ! $recent_completed commande(s) ont été terminées récemment.",
        'action' => 'orders.php?status=completed'
    ];
}

// Suggestion si pas de commande récente
if ($stats['total_orders'] > 0 && $recent_orders && strtotime($recent_orders[0]['created_at']) < strtotime('-7 days')) {
    $notifications[] = [
        'type' => 'info',
        'icon' => 'fas fa-lightbulb',
        'message' => "Cela fait un moment ! Découvrez nos nouveaux services pour booster votre présence.",
        'action' => 'order.php'
    ];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/dashboard-enhanced.css">
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

                <div class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>

                <ul class="nav-links" id="navLinks">
                    <li><a href="index.php">Accueil</a></li>
                    <li><a href="order.php">Commander</a></li>
                    <li><a href="orders.php">Mes Commandes</a></li>
                    <li><a href="profile.php">Profil</a></li>
                    <li>
                        <span style="color: var(--primary-color);">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($user['name']); ?>
                        </span>
                    </li>
                    <li><a href="logout.php" class="btn btn-secondary">Déconnexion</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Dashboard -->
    <div class="dashboard">
        <div class="container">
            <div class="dashboard-header">
                <div>
                    <h1 style="color: var(--text-primary); margin-bottom: 0.5rem;">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </h1>
                    <p style="color: var(--text-secondary);">
                        Bienvenue, <?php echo htmlspecialchars($user['name']); ?> ! Gérez vos commandes et suivez vos services.
                    </p>
                </div>
                <div>
                    <a href="order.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nouvelle Commande
                    </a>
                </div>
            </div>

            <!-- Notifications -->
            <?php if (!empty($notifications)): ?>
            <div class="notifications-container" style="margin-bottom: 2rem;">
                <?php foreach ($notifications as $notif): ?>
                <div class="alert alert-<?php echo $notif['type']; ?> notification-item" style="display: flex; align-items: center; justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <i class="<?php echo $notif['icon']; ?>" style="font-size: 1.2rem;"></i>
                        <span><?php echo $notif['message']; ?></span>
                    </div>
                    <a href="<?php echo $notif['action']; ?>" class="btn btn-secondary" style="padding: 0.5rem 1rem; font-size: 0.9rem; text-decoration: none;">
                        Voir
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Statistiques améliorées -->
            <div class="dashboard-stats">
                <div class="stat-card" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);">
                    <div class="stat-number" style="color: var(--dark-bg);"><?php echo $stats['total_orders']; ?></div>
                    <div class="stat-label" style="color: var(--dark-bg);">
                        <i class="fas fa-shopping-cart"></i> Total Commandes
                    </div>
                    <?php if ($stats['total_orders'] > 0): ?>
                        <div style="font-size: 0.8rem; color: var(--dark-bg); opacity: 0.8; margin-top: 0.5rem;">
                            Taux de succès: <?php echo $success_rate; ?>%
                        </div>
                    <?php endif; ?>
                </div>

                <div class="stat-card">
                    <div class="stat-number" style="color: var(--warning-color);"><?php echo $stats['pending_orders']; ?></div>
                    <div class="stat-label">
                        <i class="fas fa-clock"></i> En Attente
                    </div>
                    <?php if ($stats['pending_orders'] > 0): ?>
                        <div style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 0.5rem;">
                            <?php echo formatPrice($stats['total_pending_amount']); ?> en cours
                        </div>
                    <?php endif; ?>
                </div>

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
                    <div class="stat-number"><?php echo formatPrice($stats['total_spent']); ?></div>
                    <div class="stat-label">
                        <i class="fas fa-money-bill-wave"></i> Total Dépensé
                    </div>
                    <?php if ($stats['avg_order_value'] > 0): ?>
                        <div style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 0.5rem;">
                            Moyenne: <?php echo formatPrice($stats['avg_order_value']); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; align-items: start;">
                <!-- Section principale -->
                <div>
                    <!-- Graphique des commandes -->
                    <?php if (!empty($monthly_stats)): ?>
                    <div class="card" style="margin-bottom: 2rem;">
                        <h3 style="color: var(--primary-color); margin-bottom: 1.5rem;">
                            <i class="fas fa-chart-line"></i> Évolution de vos commandes (6 derniers mois)
                        </h3>
                        <div style="height: 280px; position: relative; padding: 1rem;">
                            <canvas id="ordersChart" width="100%" height="100%"></canvas>
                        </div>
                        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                        <script>
                            const chartData = <?php echo json_encode($monthly_stats); ?>;
                            const ctx = document.getElementById('ordersChart').getContext('2d');

                            new Chart(ctx, {
                                type: 'line',
                                data: {
                                    labels: chartData.map(d => d.month_name),
                                    datasets: [{
                                        label: 'Nombre de commandes',
                                        data: chartData.map(d => d.order_count),
                                        borderColor: '#00ff88',
                                        backgroundColor: 'rgba(0, 255, 136, 0.1)',
                                        fill: true,
                                        tension: 0.4
                                    }, {
                                        label: 'Montant (FCFA)',
                                        data: chartData.map(d => d.total_amount),
                                        borderColor: '#007bff',
                                        backgroundColor: 'rgba(0, 123, 255, 0.1)',
                                        fill: false,
                                        yAxisID: 'y1'
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: {
                                            labels: { color: '#ffffff' }
                                        }
                                    },
                                    scales: {
                                        x: {
                                            ticks: { color: '#b0b0b0' },
                                            grid: { color: '#333333' }
                                        },
                                        y: {
                                            type: 'linear',
                                            display: true,
                                            position: 'left',
                                            ticks: { color: '#b0b0b0' },
                                            grid: { color: '#333333' }
                                        },
                                        y1: {
                                            type: 'linear',
                                            display: true,
                                            position: 'right',
                                            ticks: { color: '#b0b0b0' },
                                            grid: { drawOnChartArea: false }
                                        }
                                    }
                                }
                            });
                        </script>
                    </div>
                    <?php endif; ?>

                    <!-- Commandes récentes améliorées -->
                    <div class="card">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                            <h2 style="color: var(--primary-color);">
                                <i class="fas fa-history"></i> Activité Récente
                            </h2>
                            <a href="orders.php" style="color: var(--primary-color); text-decoration: none;">
                                Voir toutes <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>

                        <?php if (empty($recent_orders)): ?>
                            <div style="text-align: center; padding: 3rem 1rem; color: var(--text-secondary);">
                                <i class="fas fa-shopping-cart" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                                <h3>Aucune commande</h3>
                                <p>Vous n'avez pas encore passé de commande.</p>
                                <a href="order.php" class="btn btn-primary" style="margin-top: 1rem;">
                                    <i class="fas fa-plus"></i> Première Commande
                                </a>
                            </div>
                        <?php else: ?>
                            <!-- Version compacte des commandes -->
                            <div style="display: grid; gap: 1rem;">
                                <?php foreach ($recent_orders as $order): ?>
                                <div class="card" style="padding: 1rem; border: 1px solid var(--border-color); background: var(--secondary-color); transition: all 0.3s ease;">
                                    <div style="display: grid; grid-template-columns: auto 1fr auto auto; gap: 1rem; align-items: center;">
                                        <!-- Icône et service -->
                                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                                            <i class="<?php echo $order['category_icon']; ?>"
                                               style="color: var(--primary-color); font-size: 1.5rem; width: 30px; text-align: center;"></i>
                                            <div>
                                                <div style="font-weight: 600; font-size: 0.95rem;"><?php echo htmlspecialchars($order['service_name']); ?></div>
                                                <div style="font-size: 0.8rem; color: var(--text-secondary);">
                                                    <?php echo number_format($order['quantity']); ?> × <?php echo formatPrice($order['price_per_unit']); ?>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Informations principales -->
                                        <div style="min-width: 0;">
                                            <div style="font-size: 0.9rem; margin-bottom: 0.25rem;">
                                                <a href="<?php echo htmlspecialchars($order['link']); ?>" target="_blank"
                                                   style="color: var(--text-primary); text-decoration: none;">
                                                    <?php echo strlen($order['link']) > 40 ? substr($order['link'], 0, 40) . '...' : $order['link']; ?>
                                                    <i class="fas fa-external-link-alt" style="font-size: 0.7rem; margin-left: 0.25rem;"></i>
                                                </a>
                                            </div>
                                            <div style="font-size: 0.8rem; color: var(--text-secondary);">
                                                Il y a <?php echo $order['days_ago']; ?> jour<?php echo $order['days_ago'] > 1 ? 's' : ''; ?>
                                            </div>
                                        </div>

                                        <!-- Montant -->
                                        <div style="text-align: right;">
                                            <div style="font-weight: 600; color: var(--primary-color);">
                                                <?php echo formatPrice($order['total_amount']); ?>
                                            </div>
                                        </div>

                                        <!-- Statut -->
                                        <div>
                                            <span class="status-badge status-<?php echo $order['status']; ?>" style="font-size: 0.8rem;">
                                                <?php
                                                switch($order['status']) {
                                                    case 'pending': echo 'En attente'; break;
                                                    case 'processing': echo 'En cours'; break;
                                                    case 'completed': echo 'Terminé'; break;
                                                    case 'cancelled': echo 'Annulé'; break;
                                                }
                                                ?>
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Actions rapides au hover -->
                                    <div style="margin-top: 0.75rem; display: none; opacity: 0; transition: all 0.3s ease;" class="order-actions">
                                        <?php if ($order['status'] === 'pending' && !$order['payment_proof']): ?>
                                            <a href="payment.php?order_id=<?php echo $order['id']; ?>"
                                               class="btn btn-primary" style="padding: 0.25rem 0.75rem; font-size: 0.8rem; text-decoration: none; margin-right: 0.5rem;">
                                                <i class="fas fa-credit-card"></i> Payer
                                            </a>
                                        <?php endif; ?>
                                        <button onclick="viewOrderDetails(<?php echo $order['id']; ?>)"
                                                class="btn btn-secondary" style="padding: 0.25rem 0.75rem; font-size: 0.8rem;">
                                            <i class="fas fa-eye"></i> Détails
                                        </button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Sidebar -->
                <div>
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
                            <a href="profile.php" class="btn btn-secondary" style="text-decoration: none;">
                                <i class="fas fa-user-edit"></i> Modifier Profil
                            </a>
                        </div>
                    </div>

                    <!-- Services favoris -->
                    <?php if (!empty($favorite_services)): ?>
                    <div class="card" style="margin-bottom: 1.5rem;">
                        <h3 style="color: var(--primary-color); margin-bottom: 1rem;">
                            <i class="fas fa-heart"></i> Vos Services Favoris
                        </h3>
                        <div style="display: grid; gap: 0.75rem;">
                            <?php foreach ($favorite_services as $service): ?>
                            <div style="padding: 0.75rem; background: var(--secondary-color); border-radius: 6px; border-left: 3px solid var(--primary-color);">
                                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                    <i class="<?php echo $service['icon']; ?>" style="color: var(--primary-color);"></i>
                                    <div style="font-weight: 600; font-size: 0.9rem;"><?php echo htmlspecialchars($service['name']); ?></div>
                                </div>
                                <div style="display: flex; justify-content: space-between; font-size: 0.8rem; color: var(--text-secondary);">
                                    <span><?php echo $service['order_count']; ?> commande<?php echo $service['order_count'] > 1 ? 's' : ''; ?></span>
                                    <span><?php echo formatPrice($service['total_spent']); ?></span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <a href="order.php" style="color: var(--primary-color); text-decoration: none; font-size: 0.9rem; display: block; text-align: center; margin-top: 1rem;">
                            Commander à nouveau <i class="fas fa-redo"></i>
                        </a>
                    </div>
                    <?php endif; ?>

                    <!-- Progression du mois -->
                    <div class="card" style="margin-bottom: 1.5rem;">
                        <h3 style="color: var(--primary-color); margin-bottom: 1rem;">
                            <i class="fas fa-calendar-alt"></i> Ce Mois-ci
                        </h3>
                        <?php
                        $current_month_stats = $db->fetch("
                            SELECT COUNT(*) as orders, COALESCE(SUM(total_amount), 0) as amount
                            FROM orders
                            WHERE user_id = ? AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())
                        ", [$user['id']]);
                        ?>
                        <div style="display: grid; gap: 1rem;">
                            <div style="text-align: center; padding: 1rem; background: var(--secondary-color); border-radius: 8px;">
                                <div style="font-size: 1.5rem; font-weight: 700; color: var(--primary-color);">
                                    <?php echo $current_month_stats['orders']; ?>
                                </div>
                                <div style="font-size: 0.9rem; color: var(--text-secondary);">Commandes</div>
                            </div>
                            <div style="text-align: center; padding: 1rem; background: var(--secondary-color); border-radius: 8px;">
                                <div style="font-size: 1.2rem; font-weight: 700; color: var(--primary-color);">
                                    <?php echo formatPrice($current_month_stats['amount']); ?>
                                </div>
                                <div style="font-size: 0.9rem; color: var(--text-secondary);">Investis</div>
                            </div>
                        </div>
                    </div>

                    <!-- Informations du compte -->
                    <div class="card" style="margin-bottom: 1.5rem;">
                        <h3 style="color: var(--primary-color); margin-bottom: 1rem;">
                            <i class="fas fa-user"></i> Mon Compte
                        </h3>
                        <div style="display: grid; gap: 0.75rem;">
                            <div>
                                <label style="font-size: 0.9rem; color: var(--text-secondary);">Nom</label>
                                <div style="font-weight: 600;"><?php echo htmlspecialchars($user['name']); ?></div>
                            </div>
                            <div>
                                <label style="font-size: 0.9rem; color: var(--text-secondary);">Email</label>
                                <div style="font-weight: 600; word-break: break-all;"><?php echo htmlspecialchars($user['email']); ?></div>
                            </div>
                            <div>
                                <label style="font-size: 0.9rem; color: var(--text-secondary);">Téléphone</label>
                                <div style="font-weight: 600;"><?php echo htmlspecialchars($user['phone'] ?: 'Non renseigné'); ?></div>
                            </div>
                            <div>
                                <label style="font-size: 0.9rem; color: var(--text-secondary);">Membre depuis</label>
                                <div style="font-weight: 600;"><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Conseils du jour -->
                    <div class="card">
                        <h3 style="color: var(--primary-color); margin-bottom: 1rem;">
                            <i class="fas fa-lightbulb"></i> Conseil du Jour
                        </h3>
                        <?php
                        $tips = [
                            "Publiez régulièrement pour maintenir l'engagement de votre audience.",
                            "Utilisez des hashtags pertinents pour augmenter votre visibilité.",
                            "Interagissez avec vos abonnés pour créer une communauté active.",
                            "Variez vos contenus : photos, vidéos, stories pour plus d'impact.",
                            "Analysez vos statistiques pour comprendre ce qui fonctionne le mieux.",
                            "Collaborez avec d'autres créateurs pour élargir votre audience.",
                            "Restez authentique, c'est ce qui fidélise votre communauté."
                        ];
                        $daily_tip = $tips[date('w')]; // Un conseil différent chaque jour de la semaine
                        ?>
                        <div style="padding: 1rem; background: linear-gradient(135deg, rgba(0, 255, 136, 0.1) 0%, rgba(0, 255, 136, 0.05) 100%); border-radius: 8px; border-left: 4px solid var(--primary-color);">
                            <p style="margin: 0; color: var(--text-secondary); font-style: italic;">
                                "<?php echo $daily_tip; ?>"
                            </p>
                        </div>
                    </div>

                    <!-- Support -->
                    <div class="card" style="margin-top: 1.5rem;">
                        <h3 style="color: var(--primary-color); margin-bottom: 1rem;">
                            <i class="fas fa-headset"></i> Support
                        </h3>
                        <p style="color: var(--text-secondary); margin-bottom: 1rem; font-size: 0.9rem;">
                            Besoin d'aide ? Notre équipe est là pour vous !
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
        </div>
    </div>

    <script>
        function toggleMobileMenu() {
            const navLinks = document.getElementById('navLinks');
            navLinks.classList.toggle('active');
        }

        // Animation des cartes de commandes au hover
        document.querySelectorAll('.card').forEach(card => {
            if (card.querySelector('.order-actions')) {
                card.addEventListener('mouseenter', () => {
                    const actions = card.querySelector('.order-actions');
                    actions.style.display = 'block';
                    setTimeout(() => {
                        actions.style.opacity = '1';
                    }, 10);
                });

                card.addEventListener('mouseleave', () => {
                    const actions = card.querySelector('.order-actions');
                    actions.style.opacity = '0';
                    setTimeout(() => {
                        actions.style.display = 'none';
                    }, 300);
                });
            }
        });

        // Fonction pour voir les détails d'une commande
        function viewOrderDetails(orderId) {
            // Redirection vers la page des commandes avec focus sur cette commande
            window.location.href = `orders.php?view=${orderId}`;
        }

        // Animation des statistiques au chargement
        function animateNumbers() {
            document.querySelectorAll('.stat-number').forEach(element => {
                const target = parseInt(element.textContent.replace(/[^\d]/g, ''));
                if (target > 0) {
                    let current = 0;
                    const increment = target / 30;
                    const timer = setInterval(() => {
                        current += increment;
                        if (current >= target) {
                            element.textContent = element.textContent; // Garder le format original
                            clearInterval(timer);
                        } else {
                            element.textContent = Math.floor(current);
                        }
                    }, 50);
                }
            });
        }

        // Démarrer les animations au chargement
        document.addEventListener('DOMContentLoaded', () => {
            // Animation des notifications
            document.querySelectorAll('.notification-item').forEach((notif, index) => {
                notif.style.opacity = '0';
                notif.style.transform = 'translateY(-20px)';
                setTimeout(() => {
                    notif.style.transition = 'all 0.5s ease';
                    notif.style.opacity = '1';
                    notif.style.transform = 'translateY(0)';
                }, index * 200);
            });

            // Animation des cartes de service favoris
            document.querySelectorAll('.card').forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });

            // Animer les nombres après les cartes
            setTimeout(animateNumbers, 1000);
        });

        // Fonction pour fermer les notifications
        function closeNotification(element) {
            element.style.transition = 'all 0.3s ease';
            element.style.opacity = '0';
            element.style.transform = 'translateX(100%)';
            setTimeout(() => {
                element.remove();
            }, 300);
        }

        // Auto-refresh des statistiques toutes les 2 minutes
        setInterval(() => {
            fetch('api/stats.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Mettre à jour silencieusement les stats
                        const stats = document.querySelectorAll('.stat-number');
                        if (stats[0]) stats[0].textContent = data.stats.total_orders;
                        if (stats[1]) stats[1].textContent = data.stats.pending_orders;
                        if (stats[2]) stats[2].textContent = data.stats.processing_orders;
                        if (stats[3]) stats[3].textContent = data.stats.completed_orders;
                    }
                })
                .catch(error => console.log('Mise à jour silencieuse des stats'));
        }, 120000); // 2 minutes

        // Fonction pour copier un lien
        function copyLink(link) {
            navigator.clipboard.writeText(link).then(() => {
                // Afficher une notification temporaire
                const notification = document.createElement('div');
                notification.textContent = 'Lien copié !';
                notification.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: var(--success-color);
                    color: var(--dark-bg);
                    padding: 1rem;
                    border-radius: 8px;
                    z-index: 9999;
                    font-weight: 600;
                `;
                document.body.appendChild(notification);

                setTimeout(() => {
                    notification.remove();
                }, 3000);
            });
        }

        // Responsive: ajuster la grille sur mobile
        function handleResize() {
            const mainGrid = document.querySelector('[style*="grid-template-columns: 2fr 1fr"]');
            if (mainGrid && window.innerWidth <= 768) {
                mainGrid.style.gridTemplateColumns = '1fr';
            } else if (mainGrid) {
                mainGrid.style.gridTemplateColumns = '2fr 1fr';
            }
        }

        window.addEventListener('resize', handleResize);
        handleResize(); // Appeler au chargement
    </script>
</body>
</html>
