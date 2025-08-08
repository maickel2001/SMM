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

// Données pour le graphique (derniers 7 jours)
$chart_data = $db->fetchAll("
    SELECT DATE(created_at) as date, 
           COUNT(*) as orders_count,
           COALESCE(SUM(total_amount), 0) as revenue
    FROM orders 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC
");

// Remplir les jours manquants avec 0
$dates = [];
$orders_data = [];
$revenue_data = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $dates[] = date('d/m', strtotime($date));
    
    $found = false;
    foreach ($chart_data as $data) {
        if ($data['date'] === $date) {
            $orders_data[] = (int)$data['orders_count'];
            $revenue_data[] = (float)$data['revenue'];
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        $orders_data[] = 0;
        $revenue_data[] = 0;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Styles spécifiques au dashboard admin amélioré */
        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .admin-header {
            background: linear-gradient(135deg, var(--card-bg) 0%, #2a2a2a 100%);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            border-left: 5px solid var(--primary-color);
            box-shadow: var(--shadow);
        }
        
        .admin-welcome {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .welcome-text h1 {
            color: var(--primary-color);
            margin: 0 0 0.5rem 0;
            font-size: 2rem;
        }
        
        .welcome-text p {
            color: var(--text-secondary);
            margin: 0;
        }
        
        .admin-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            background: rgba(0, 255, 136, 0.1);
            padding: 1rem;
            border-radius: 10px;
            border: 1px solid var(--primary-color);
        }
        
        .admin-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--gradient-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #000;
            font-weight: bold;
            font-size: 1.2rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: linear-gradient(145deg, var(--card-bg) 0%, #2a2a2a 100%);
            border-radius: 15px;
            padding: 1.5rem;
            border-left: 5px solid var(--primary-color);
            box-shadow: var(--shadow);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #000;
            font-size: 1.5rem;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--primary-color);
            margin: 0;
        }
        
        .stat-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin: 0.5rem 0 0 0;
        }
        
        .stat-change {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
            border-radius: 20px;
            font-weight: 600;
        }
        
        .positive { background: rgba(0, 255, 136, 0.2); color: var(--primary-color); }
        .neutral { background: rgba(255, 165, 0, 0.2); color: #ffa500; }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .chart-container {
            background: linear-gradient(145deg, var(--card-bg) 0%, #2a2a2a 100%);
            border-radius: 15px;
            padding: 1.5rem;
            border-left: 5px solid var(--primary-color);
            box-shadow: var(--shadow);
        }
        
        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .chart-title {
            color: var(--primary-color);
            font-size: 1.2rem;
            font-weight: 600;
            margin: 0;
        }
        
        .quick-stats {
            background: linear-gradient(145deg, var(--card-bg) 0%, #2a2a2a 100%);
            border-radius: 15px;
            padding: 1.5rem;
            border-left: 5px solid var(--primary-color);
            box-shadow: var(--shadow);
        }
        
        .quick-stat-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            margin-bottom: 0.5rem;
            background: rgba(0, 255, 136, 0.05);
            border-radius: 10px;
            border-left: 3px solid var(--primary-color);
        }
        
        .orders-section {
            background: linear-gradient(145deg, var(--card-bg) 0%, #2a2a2a 100%);
            border-radius: 15px;
            padding: 1.5rem;
            border-left: 5px solid var(--primary-color);
            box-shadow: var(--shadow);
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .section-title {
            color: var(--primary-color);
            font-size: 1.3rem;
            font-weight: 600;
            margin: 0;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            margin-bottom: 0.5rem;
            background: rgba(0, 255, 136, 0.05);
            border-radius: 10px;
            border-left: 3px solid var(--primary-color);
            transition: background 0.3s ease;
        }
        
        .order-item:hover {
            background: rgba(0, 255, 136, 0.1);
        }
        
        .order-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .order-details h4 {
            color: var(--text-primary);
            margin: 0 0 0.25rem 0;
            font-size: 0.9rem;
        }
        
        .order-details p {
            color: var(--text-secondary);
            margin: 0;
            font-size: 0.8rem;
        }
        
        .order-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        @media (max-width: 768px) {
            .admin-container { padding: 1rem; }
            .admin-welcome { flex-direction: column; text-align: center; }
            .dashboard-grid { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); }
        }
    </style>
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
                    <li><a href="dashboard.php" class="active">Dashboard</a></li>
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

    <div class="admin-container" style="margin-top: 100px;">
        <!-- En-tête d'accueil -->
        <div class="admin-header">
            <div class="admin-welcome">
                <div class="welcome-text">
                    <h1><i class="fas fa-tachometer-alt"></i> Dashboard Administrateur</h1>
                    <p>Gérez votre plateforme SMM et suivez les performances en temps réel</p>
                </div>
                <div class="admin-info">
                    <div class="admin-avatar">
                        <?php echo strtoupper(substr($admin['name'], 0, 2)); ?>
                    </div>
                    <div>
                        <h4 style="margin: 0; color: var(--primary-color);"><?php echo htmlspecialchars($admin['name']); ?></h4>
                        <p style="margin: 0; color: var(--text-secondary); font-size: 0.9rem;">Administrateur</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistiques principales -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon" style="background: var(--gradient-primary);">
                        <i class="fas fa-users"></i>
                    </div>
                    <span class="stat-change positive">+<?php echo $new_users_week; ?> cette semaine</span>
                </div>
                <h2 class="stat-number"><?php echo number_format($stats['total_users']); ?></h2>
                <p class="stat-label">Utilisateurs inscrits</p>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon" style="background: var(--gradient-primary);">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <span class="stat-change positive"><?php echo $stats['pending_orders'] + $stats['processing_orders']; ?> actives</span>
                </div>
                <h2 class="stat-number"><?php echo number_format($stats['total_orders']); ?></h2>
                <p class="stat-label">Total des commandes</p>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon" style="background: var(--gradient-primary);">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <span class="stat-change positive"><?php echo formatPrice($revenue_week); ?> cette semaine</span>
                </div>
                <h2 class="stat-number"><?php echo formatPrice($stats['total_revenue']); ?></h2>
                <p class="stat-label">Chiffre d'affaires</p>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon" style="background: var(--gradient-primary);">
                        <i class="fas fa-clock"></i>
                    </div>
                    <span class="stat-change neutral"><?php echo formatPrice($stats['pending_revenue']); ?> en attente</span>
                </div>
                <h2 class="stat-number"><?php echo number_format($stats['completed_orders']); ?></h2>
                <p class="stat-label">Commandes terminées</p>
            </div>
        </div>

        <!-- Graphiques et stats rapides -->
        <div class="dashboard-grid">
            <div class="chart-container">
                <div class="chart-header">
                    <h3 class="chart-title"><i class="fas fa-chart-area"></i> Évolution des commandes (7 derniers jours)</h3>
                </div>
                <canvas id="ordersChart" width="400" height="200"></canvas>
            </div>

            <div class="quick-stats">
                <h3 class="section-title"><i class="fas fa-bolt"></i> Statuts des commandes</h3>
                <div class="quick-stat-item">
                    <span style="color: var(--text-primary);">
                        <i class="fas fa-hourglass-half" style="color: #ffa500;"></i> En attente
                    </span>
                    <strong style="color: var(--primary-color);"><?php echo $stats['pending_orders']; ?></strong>
                </div>
                <div class="quick-stat-item">
                    <span style="color: var(--text-primary);">
                        <i class="fas fa-cog fa-spin" style="color: #007bff;"></i> En cours
                    </span>
                    <strong style="color: var(--primary-color);"><?php echo $stats['processing_orders']; ?></strong>
                </div>
                <div class="quick-stat-item">
                    <span style="color: var(--text-primary);">
                        <i class="fas fa-check-circle" style="color: var(--primary-color);"></i> Terminées
                    </span>
                    <strong style="color: var(--primary-color);"><?php echo $stats['completed_orders']; ?></strong>
                </div>
                <div class="quick-stat-item">
                    <span style="color: var(--text-primary);">
                        <i class="fas fa-times-circle" style="color: #ff4444;"></i> Annulées
                    </span>
                    <strong style="color: var(--primary-color);"><?php echo $stats['cancelled_orders']; ?></strong>
                </div>
            </div>
        </div>

        <!-- Commandes récentes -->
        <div class="orders-section">
            <div class="section-header">
                <h3 class="section-title"><i class="fas fa-list-alt"></i> Commandes nécessitant votre attention</h3>
                <a href="orders.php" class="btn btn-primary">
                    <i class="fas fa-eye"></i> Voir toutes
                </a>
            </div>

            <?php if (empty($recent_orders)): ?>
                <div style="text-align: center; padding: 2rem; color: var(--text-secondary);">
                    <i class="fas fa-check-circle" style="font-size: 3rem; color: var(--primary-color); margin-bottom: 1rem;"></i>
                    <h4>Aucune commande en attente</h4>
                    <p>Toutes les commandes sont traitées !</p>
                </div>
            <?php else: ?>
                <?php foreach ($recent_orders as $order): ?>
                    <div class="order-item">
                        <div class="order-info">
                            <div class="stat-icon" style="background: var(--gradient-primary); width: 40px; height: 40px; font-size: 1rem;">
                                <i class="<?php echo $order['category_icon']; ?>"></i>
                            </div>
                            <div class="order-details">
                                <h4>Commande #<?php echo $order['id']; ?> - <?php echo htmlspecialchars($order['service_name']); ?></h4>
                                <p>
                                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($order['user_name']); ?> • 
                                    <i class="fas fa-calendar"></i> <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?> • 
                                    <i class="fas fa-coins"></i> <?php echo formatPrice($order['total_amount']); ?>
                                </p>
                            </div>
                        </div>
                        <div class="order-actions">
                            <span class="status-badge status-<?php echo $order['status']; ?>">
                                <?php echo $order['status'] === 'pending' ? 'En attente' : 'En cours'; ?>
                            </span>
                            <a href="orders.php?id=<?php echo $order['id']; ?>" class="btn btn-primary" style="padding: 0.5rem 1rem;">
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Configuration du graphique
        const ctx = document.getElementById('ordersChart').getContext('2d');
        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($dates); ?>,
                datasets: [{
                    label: 'Commandes',
                    data: <?php echo json_encode($orders_data); ?>,
                    borderColor: '#00ff88',
                    backgroundColor: 'rgba(0, 255, 136, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        labels: {
                            color: '#ffffff'
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: '#b0b0b0'
                        },
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        }
                    },
                    x: {
                        ticks: {
                            color: '#b0b0b0'
                        },
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        }
                    }
                }
            }
        });

        // Fonction pour le menu mobile
        function toggleMobileMenu() {
            const navLinks = document.getElementById('navLinks');
            navLinks.classList.toggle('active');
        }
    </script>
</body>
</html>
