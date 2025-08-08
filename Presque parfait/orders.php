<?php
require_once 'includes/auth.php';
$auth = new Auth();
$auth->requireUserLogin();

$user = $auth->getCurrentUser();
$db = new Database();

// Filtres
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Construire la requête avec filtres
$where_conditions = ["o.user_id = ?"];
$params = [$user['id']];

if ($status_filter && in_array($status_filter, ['pending', 'processing', 'completed', 'cancelled'])) {
    $where_conditions[] = "o.status = ?";
    $params[] = $status_filter;
}

$where_clause = implode(' AND ', $where_conditions);

// Compter le total
$total_orders = $db->fetch("
    SELECT COUNT(*) as count
    FROM orders o
    WHERE $where_clause
", $params)['count'];

$total_pages = ceil($total_orders / $per_page);

// Récupérer les commandes
$orders = $db->fetchAll("
    SELECT o.*, s.name as service_name, s.description as service_description,
           c.name as category_name, c.icon as category_icon
    FROM orders o
    JOIN services s ON o.service_id = s.id
    JOIN categories c ON s.category_id = c.id
    WHERE $where_clause
    ORDER BY o.created_at DESC
    LIMIT $per_page OFFSET $offset
", $params);

// Statistiques
$stats = [
    'total' => $db->fetch("SELECT COUNT(*) as count FROM orders WHERE user_id = ?", [$user['id']])['count'],
    'pending' => $db->fetch("SELECT COUNT(*) as count FROM orders WHERE user_id = ? AND status = 'pending'", [$user['id']])['count'],
    'processing' => $db->fetch("SELECT COUNT(*) as count FROM orders WHERE user_id = ? AND status = 'processing'", [$user['id']])['count'],
    'completed' => $db->fetch("SELECT COUNT(*) as count FROM orders WHERE user_id = ? AND status = 'completed'", [$user['id']])['count'],
    'cancelled' => $db->fetch("SELECT COUNT(*) as count FROM orders WHERE user_id = ? AND status = 'cancelled'", [$user['id']])['count']
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Commandes - <?php echo SITE_NAME; ?></title>
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
                    <li><a href="profile.php">Profil</a></li>
                    <li><a href="logout.php" class="btn btn-secondary">Déconnexion</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Page des commandes -->
    <div class="dashboard">
        <div class="container">
            <div class="dashboard-header">
                <div>
                    <h1 style="color: var(--text-primary); margin-bottom: 0.5rem;">
                        <i class="fas fa-list"></i> Mes Commandes
                    </h1>
                    <p style="color: var(--text-secondary);">
                        Suivez l'état de toutes vos commandes
                    </p>
                </div>
                <div>
                    <a href="order.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nouvelle Commande
                    </a>
                </div>
            </div>

            <!-- Statistiques des commandes -->
            <div class="dashboard-stats">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total']; ?></div>
                    <div class="stat-label">
                        <i class="fas fa-shopping-cart"></i> Total
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['pending']; ?></div>
                    <div class="stat-label">
                        <i class="fas fa-clock"></i> En Attente
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['processing']; ?></div>
                    <div class="stat-label">
                        <i class="fas fa-cogs"></i> En Cours
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['completed']; ?></div>
                    <div class="stat-label">
                        <i class="fas fa-check-circle"></i> Terminées
                    </div>
                </div>
            </div>

            <!-- Filtres -->
            <div class="card" style="margin-bottom: 2rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                    <h3 style="color: var(--primary-color); margin: 0;">
                        <i class="fas fa-filter"></i> Filtrer les commandes
                    </h3>
                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                        <a href="orders.php"
                           class="btn <?php echo $status_filter === '' ? 'btn-primary' : 'btn-secondary'; ?>"
                           style="padding: 0.5rem 1rem; font-size: 0.9rem;">
                            Toutes (<?php echo $stats['total']; ?>)
                        </a>
                        <a href="orders.php?status=pending"
                           class="btn <?php echo $status_filter === 'pending' ? 'btn-primary' : 'btn-secondary'; ?>"
                           style="padding: 0.5rem 1rem; font-size: 0.9rem;">
                            En Attente (<?php echo $stats['pending']; ?>)
                        </a>
                        <a href="orders.php?status=processing"
                           class="btn <?php echo $status_filter === 'processing' ? 'btn-primary' : 'btn-secondary'; ?>"
                           style="padding: 0.5rem 1rem; font-size: 0.9rem;">
                            En Cours (<?php echo $stats['processing']; ?>)
                        </a>
                        <a href="orders.php?status=completed"
                           class="btn <?php echo $status_filter === 'completed' ? 'btn-primary' : 'btn-secondary'; ?>"
                           style="padding: 0.5rem 1rem; font-size: 0.9rem;">
                            Terminées (<?php echo $stats['completed']; ?>)
                        </a>
                        <?php if ($stats['cancelled'] > 0): ?>
                        <a href="orders.php?status=cancelled"
                           class="btn <?php echo $status_filter === 'cancelled' ? 'btn-primary' : 'btn-secondary'; ?>"
                           style="padding: 0.5rem 1rem; font-size: 0.9rem;">
                            Annulées (<?php echo $stats['cancelled']; ?>)
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Liste des commandes -->
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h3 style="color: var(--primary-color); margin: 0;">
                        <i class="fas fa-history"></i>
                        <?php if ($status_filter): ?>
                            Commandes - <?php
                                switch($status_filter) {
                                    case 'pending': echo 'En Attente'; break;
                                    case 'processing': echo 'En Cours'; break;
                                    case 'completed': echo 'Terminées'; break;
                                    case 'cancelled': echo 'Annulées'; break;
                                }
                            ?>
                        <?php else: ?>
                            Toutes les commandes
                        <?php endif; ?>
                    </h3>
                    <span style="color: var(--text-secondary);">
                        <?php echo $total_orders; ?> commande(s) trouvée(s)
                    </span>
                </div>

                <?php if (empty($orders)): ?>
                    <div style="text-align: center; padding: 3rem 1rem; color: var(--text-secondary);">
                        <i class="fas fa-shopping-cart" style="font-size: 4rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                        <h3>Aucune commande trouvée</h3>
                        <p>
                            <?php if ($status_filter): ?>
                                Vous n'avez aucune commande avec ce statut.
                            <?php else: ?>
                                Vous n'avez pas encore passé de commande.
                            <?php endif; ?>
                        </p>
                        <a href="order.php" class="btn btn-primary" style="margin-top: 1rem;">
                            <i class="fas fa-plus"></i> Passer ma première commande
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Version desktop -->
                    <div class="table-container" style="display: none;" id="desktopTable">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Service</th>
                                    <th>Lien</th>
                                    <th>Quantité</th>
                                    <th>Montant</th>
                                    <th>Statut</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td style="font-weight: 600; color: var(--primary-color);">#<?php echo $order['id']; ?></td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                                            <i class="<?php echo $order['category_icon']; ?>" style="color: var(--primary-color);"></i>
                                            <div>
                                                <div style="font-weight: 600;"><?php echo htmlspecialchars($order['service_name']); ?></div>
                                                <div style="font-size: 0.8rem; color: var(--text-secondary);"><?php echo htmlspecialchars($order['category_name']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="<?php echo htmlspecialchars($order['link']); ?>" target="_blank"
                                           style="color: var(--primary-color); text-decoration: none;">
                                            <?php echo strlen($order['link']) > 25 ? substr($order['link'], 0, 25) . '...' : $order['link']; ?>
                                            <i class="fas fa-external-link-alt" style="font-size: 0.8rem;"></i>
                                        </a>
                                    </td>
                                    <td><?php echo number_format($order['quantity']); ?></td>
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
                                    <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                    <td>
                                        <div style="display: flex; gap: 0.5rem;">
                                            <button onclick="viewOrder(<?php echo $order['id']; ?>)"
                                                    class="btn btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if ($order['status'] === 'pending' && !$order['payment_proof']): ?>
                                                <a href="payment.php?order_id=<?php echo $order['id']; ?>"
                                                   class="btn btn-primary" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">
                                                    <i class="fas fa-credit-card"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Version mobile -->
                    <div id="mobileCards">
                        <?php foreach ($orders as $order): ?>
                        <div class="card" style="margin-bottom: 1rem; padding: 1rem;">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                                <div>
                                    <h4 style="color: var(--primary-color); margin: 0;">
                                        <i class="<?php echo $order['category_icon']; ?>"></i>
                                        Commande #<?php echo $order['id']; ?>
                                    </h4>
                                    <p style="margin: 0; color: var(--text-secondary); font-size: 0.9rem;">
                                        <?php echo date('d/m/Y à H:i', strtotime($order['created_at'])); ?>
                                    </p>
                                </div>
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
                            </div>

                            <div style="display: grid; gap: 0.5rem; margin-bottom: 1rem;">
                                <div><strong>Service:</strong> <?php echo htmlspecialchars($order['service_name']); ?></div>
                                <div><strong>Quantité:</strong> <?php echo number_format($order['quantity']); ?></div>
                                <div><strong>Montant:</strong> <?php echo formatPrice($order['total_amount']); ?></div>
                                <div>
                                    <strong>Lien:</strong>
                                    <a href="<?php echo htmlspecialchars($order['link']); ?>" target="_blank"
                                       style="color: var(--primary-color); text-decoration: none;">
                                        <?php echo strlen($order['link']) > 30 ? substr($order['link'], 0, 30) . '...' : $order['link']; ?>
                                        <i class="fas fa-external-link-alt" style="font-size: 0.8rem;"></i>
                                    </a>
                                </div>
                            </div>

                            <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                                <button onclick="viewOrder(<?php echo $order['id']; ?>)"
                                        class="btn btn-secondary" style="padding: 0.5rem 1rem; font-size: 0.9rem;">
                                    <i class="fas fa-eye"></i> Voir
                                </button>
                                <?php if ($order['status'] === 'pending' && !$order['payment_proof']): ?>
                                    <a href="payment.php?order_id=<?php echo $order['id']; ?>"
                                       class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.9rem; text-decoration: none;">
                                        <i class="fas fa-credit-card"></i> Payer
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <div style="margin-top: 2rem; display: flex; justify-content: center; gap: 0.5rem; flex-wrap: wrap;">
                        <?php if ($page > 1): ?>
                            <a href="orders.php?page=<?php echo $page - 1; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?>"
                               class="btn btn-secondary">
                                <i class="fas fa-chevron-left"></i> Précédent
                            </a>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <?php if ($i === $page): ?>
                                <span class="btn btn-primary"><?php echo $i; ?></span>
                            <?php elseif (abs($i - $page) <= 2 || $i === 1 || $i === $total_pages): ?>
                                <a href="orders.php?page=<?php echo $i; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?>"
                                   class="btn btn-secondary"><?php echo $i; ?></a>
                            <?php elseif (abs($i - $page) === 3): ?>
                                <span style="color: var(--text-secondary);">...</span>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="orders.php?page=<?php echo $page + 1; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?>"
                               class="btn btn-secondary">
                                Suivant <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal pour voir les détails d'une commande -->
    <div id="orderModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 2000; align-items: center; justify-content: center;">
        <div style="background: var(--card-bg); border-radius: 12px; padding: 2rem; max-width: 600px; width: 90%; max-height: 80vh; overflow-y: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 style="color: var(--primary-color); margin: 0;">Détails de la commande</h3>
                <button onclick="closeOrderModal()" style="background: none; border: none; color: var(--text-secondary); font-size: 1.5rem; cursor: pointer;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="orderDetails"></div>
        </div>
    </div>

    <script>
        function viewOrder(orderId) {
            // Trouver la commande dans les données
            const orders = <?php echo json_encode($orders); ?>;
            const order = orders.find(o => o.id == orderId);

            if (order) {
                const statusLabels = {
                    'pending': 'En attente',
                    'processing': 'En cours',
                    'completed': 'Terminé',
                    'cancelled': 'Annulé'
                };

                const details = `
                    <div style="display: grid; gap: 1rem;">
                        <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background: var(--secondary-color); border-radius: 8px;">
                            <i class="${order.category_icon}" style="color: var(--primary-color); font-size: 2rem;"></i>
                            <div>
                                <h4 style="margin: 0; color: var(--text-primary);">${order.service_name}</h4>
                                <p style="margin: 0; color: var(--text-secondary);">${order.category_name}</p>
                            </div>
                        </div>

                        <div style="display: grid; gap: 0.75rem;">
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-secondary);">ID de commande:</span>
                                <span style="font-weight: 600; color: var(--primary-color);">#${order.id}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-secondary);">Lien:</span>
                                <a href="${order.link}" target="_blank" style="color: var(--primary-color); text-decoration: none; word-break: break-all;">
                                    ${order.link.length > 30 ? order.link.substring(0, 30) + '...' : order.link}
                                    <i class="fas fa-external-link-alt" style="font-size: 0.8rem;"></i>
                                </a>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-secondary);">Quantité:</span>
                                <span style="font-weight: 600;">${parseInt(order.quantity).toLocaleString()}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-secondary);">Montant:</span>
                                <span style="font-weight: 600; color: var(--primary-color);">${formatPrice(order.total_amount)}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-secondary);">Statut:</span>
                                <span class="status-badge status-${order.status}">${statusLabels[order.status]}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-secondary);">Date de commande:</span>
                                <span style="font-weight: 600;">${new Date(order.created_at).toLocaleDateString('fr-FR', {
                                    year: 'numeric', month: '2-digit', day: '2-digit',
                                    hour: '2-digit', minute: '2-digit'
                                })}</span>
                            </div>
                            ${order.admin_notes ? `
                                <div style="margin-top: 1rem; padding: 1rem; background: rgba(0, 255, 136, 0.1); border-radius: 8px;">
                                    <h5 style="color: var(--primary-color); margin-bottom: 0.5rem;">Note de l'administrateur:</h5>
                                    <p style="margin: 0; color: var(--text-secondary);">${order.admin_notes}</p>
                                </div>
                            ` : ''}
                        </div>

                        ${order.status === 'pending' && !order.payment_proof ? `
                            <div style="margin-top: 1.5rem; text-align: center;">
                                <a href="payment.php?order_id=${order.id}" class="btn btn-primary">
                                    <i class="fas fa-credit-card"></i> Procéder au paiement
                                </a>
                            </div>
                        ` : ''}
                    </div>
                `;

                document.getElementById('orderDetails').innerHTML = details;
                document.getElementById('orderModal').style.display = 'flex';
            }
        }

        function closeOrderModal() {
            document.getElementById('orderModal').style.display = 'none';
        }

        function formatPrice(price) {
            return new Intl.NumberFormat('fr-FR').format(price) + ' FCFA';
        }

        // Responsive table/cards
        function handleResize() {
            const desktopTable = document.getElementById('desktopTable');
            const mobileCards = document.getElementById('mobileCards');

            if (window.innerWidth > 768) {
                desktopTable.style.display = 'block';
                mobileCards.style.display = 'none';
            } else {
                desktopTable.style.display = 'none';
                mobileCards.style.display = 'block';
            }
        }

        window.addEventListener('resize', handleResize);
        handleResize(); // Call on load

        // Fermer le modal en cliquant à l'extérieur
        document.getElementById('orderModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeOrderModal();
            }
        });
    </script>
</body>
</html>
