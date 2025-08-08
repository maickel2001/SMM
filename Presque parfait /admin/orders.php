<?php
require_once '../includes/auth.php';
$auth = new Auth();
$auth->requireAdminLogin();

$admin = $auth->getCurrentAdmin();
$db = new Database();

$error = '';
$success = '';

// Traitement des actions sur les commandes
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_order'])) {
        $order_id = (int)$_POST['order_id'];
        $status = sanitizeInput($_POST['status']);
        $admin_notes = sanitizeInput($_POST['admin_notes']);
        $cancel_reason = sanitizeInput($_POST['cancel_reason']);

        $valid_statuses = ['pending', 'processing', 'completed', 'cancelled'];

        if (in_array($status, $valid_statuses)) {
            try {
                if ($status === 'cancelled' && !empty($cancel_reason)) {
                    $db->query(
                        "UPDATE orders SET status = ?, admin_notes = ?, cancel_reason = ? WHERE id = ?",
                        [$status, $admin_notes, $cancel_reason, $order_id]
                    );
                } else {
                    $db->query(
                        "UPDATE orders SET status = ?, admin_notes = ? WHERE id = ?",
                        [$status, $admin_notes, $order_id]
                    );
                }
                $success = 'Commande mise à jour avec succès.';
            } catch (Exception $e) {
                $error = 'Erreur lors de la mise à jour de la commande.';
            }
        } else {
            $error = 'Statut invalide.';
        }
    }
}

// Filtres
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Construire la requête avec filtres
$where_conditions = ["1=1"];
$params = [];

if ($status_filter && in_array($status_filter, ['pending', 'processing', 'completed', 'cancelled'])) {
    $where_conditions[] = "o.status = ?";
    $params[] = $status_filter;
}

if ($search) {
    $where_conditions[] = "(u.name LIKE ? OR u.email LIKE ? OR o.id LIKE ? OR s.name LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

$where_clause = implode(' AND ', $where_conditions);

// Compter le total
$total_orders = $db->fetch("
    SELECT COUNT(*) as count
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN services s ON o.service_id = s.id
    WHERE $where_clause
", $params)['count'];

$total_pages = ceil($total_orders / $per_page);

// Récupérer les commandes
$orders = $db->fetchAll("
    SELECT o.*, u.name as user_name, u.email as user_email, u.phone as user_phone,
           s.name as service_name, s.description as service_description,
           c.name as category_name, c.icon as category_icon
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN services s ON o.service_id = s.id
    JOIN categories c ON s.category_id = c.id
    WHERE $where_clause
    ORDER BY o.created_at DESC
    LIMIT $per_page OFFSET $offset
", $params);

// Statistiques
$stats = [
    'total' => $db->fetch("SELECT COUNT(*) as count FROM orders")['count'],
    'pending' => $db->fetch("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'")['count'],
    'processing' => $db->fetch("SELECT COUNT(*) as count FROM orders WHERE status = 'processing'")['count'],
    'completed' => $db->fetch("SELECT COUNT(*) as count FROM orders WHERE status = 'completed'")['count'],
    'cancelled' => $db->fetch("SELECT COUNT(*) as count FROM orders WHERE status = 'cancelled'")['count']
];

// Commande sélectionnée pour modification
$selected_order = null;
if (isset($_GET['view'])) {
    $order_id = (int)$_GET['view'];
    $selected_order = $db->fetch("
        SELECT o.*, u.name as user_name, u.email as user_email, u.phone as user_phone,
               s.name as service_name, s.description as service_description,
               c.name as category_name, c.icon as category_icon
        FROM orders o
        JOIN users u ON o.user_id = u.id
        JOIN services s ON o.service_id = s.id
        JOIN categories c ON s.category_id = c.id
        WHERE o.id = ?
    ", [$order_id]);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Commandes - Admin <?php echo SITE_NAME; ?></title>
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
                <ul class="nav-links">
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="users.php">Utilisateurs</a></li>
                    <li><a href="services.php">Services</a></li>
                    <li><a href="categories.php">Catégories</a></li>
                    <li><a href="../logout.php" class="btn btn-secondary">Déconnexion</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Gestion des commandes -->
    <div class="dashboard">
        <div class="container">
            <div class="dashboard-header">
                <div>
                    <h1 style="color: var(--text-primary); margin-bottom: 0.5rem;">
                        <i class="fas fa-shopping-cart"></i> Gestion des Commandes
                    </h1>
                    <p style="color: var(--text-secondary);">
                        Suivi et gestion de toutes les commandes de la plateforme
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

            <!-- Filtres et recherche -->
            <div class="card" style="margin-bottom: 2rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
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
                        <a href="orders.php?status=cancelled"
                           class="btn <?php echo $status_filter === 'cancelled' ? 'btn-primary' : 'btn-secondary'; ?>"
                           style="padding: 0.5rem 1rem; font-size: 0.9rem;">
                            Annulées (<?php echo $stats['cancelled']; ?>)
                        </a>
                    </div>

                    <form method="GET" style="display: flex; gap: 0.5rem;">
                        <?php if ($status_filter): ?>
                            <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                        <?php endif; ?>
                        <input type="text" name="search" class="form-control"
                               placeholder="Rechercher..."
                               value="<?php echo htmlspecialchars($search); ?>"
                               style="width: 200px;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                        </button>
                        <?php if ($search): ?>
                            <a href="orders.php<?php echo $status_filter ? '?status=' . $status_filter : ''; ?>" class="btn btn-secondary">
                                <i class="fas fa-times"></i>
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- Liste des commandes -->
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h3 style="color: var(--primary-color); margin: 0;">
                        <i class="fas fa-list"></i>
                        Commandes
                        <?php if ($status_filter): ?>
                            - <?php
                                switch($status_filter) {
                                    case 'pending': echo 'En Attente'; break;
                                    case 'processing': echo 'En Cours'; break;
                                    case 'completed': echo 'Terminées'; break;
                                    case 'cancelled': echo 'Annulées'; break;
                                }
                            ?>
                        <?php endif; ?>
                        <?php if ($search): ?>
                            - Recherche: "<?php echo htmlspecialchars($search); ?>"
                        <?php endif; ?>
                    </h3>
                    <span style="color: var(--text-secondary);">
                        <?php echo $total_orders; ?> commande(s) trouvée(s)
                    </span>
                </div>

                <?php if (empty($orders)): ?>
                    <div style="text-align: center; padding: 3rem 1rem; color: var(--text-secondary);">
                        <i class="fas fa-inbox" style="font-size: 4rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                        <h3>Aucune commande trouvée</h3>
                        <p>Aucune commande ne correspond aux critères de recherche.</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Client</th>
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
                                            <a href="?view=<?php echo $order['id']; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>"
                                               class="btn btn-primary" style="padding: 0.25rem 0.5rem; font-size: 0.8rem; text-decoration: none;">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($order['payment_proof']): ?>
                                                <a href="../<?php echo UPLOAD_DIR . $order['payment_proof']; ?>" target="_blank"
                                                   class="btn btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.8rem; text-decoration: none;">
                                                    <i class="fas fa-image"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <div style="margin-top: 2rem; display: flex; justify-content: center; gap: 0.5rem; flex-wrap: wrap;">
                        <?php
                        $current_url = 'orders.php?';
                        if ($status_filter) $current_url .= 'status=' . $status_filter . '&';
                        if ($search) $current_url .= 'search=' . urlencode($search) . '&';
                        ?>

                        <?php if ($page > 1): ?>
                            <a href="<?php echo $current_url; ?>page=<?php echo $page - 1; ?>" class="btn btn-secondary">
                                <i class="fas fa-chevron-left"></i> Précédent
                            </a>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <?php if ($i === $page): ?>
                                <span class="btn btn-primary"><?php echo $i; ?></span>
                            <?php elseif (abs($i - $page) <= 2 || $i === 1 || $i === $total_pages): ?>
                                <a href="<?php echo $current_url; ?>page=<?php echo $i; ?>" class="btn btn-secondary"><?php echo $i; ?></a>
                            <?php elseif (abs($i - $page) === 3): ?>
                                <span style="color: var(--text-secondary);">...</span>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="<?php echo $current_url; ?>page=<?php echo $page + 1; ?>" class="btn btn-secondary">
                                Suivant <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal pour modifier une commande -->
    <?php if ($selected_order): ?>
    <div id="orderModal" style="display: flex; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 2000; align-items: center; justify-content: center;">
        <div style="background: var(--card-bg); border-radius: 12px; padding: 2rem; max-width: 700px; width: 90%; max-height: 80vh; overflow-y: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 style="color: var(--primary-color); margin: 0;">
                    <i class="fas fa-edit"></i> Modifier la commande #<?php echo $selected_order['id']; ?>
                </h3>
                <a href="orders.php<?php echo $status_filter ? '?status=' . $status_filter : ''; ?><?php echo $search ? ($status_filter ? '&' : '?') . 'search=' . urlencode($search) : ''; ?>"
                   style="color: var(--text-secondary); font-size: 1.5rem; text-decoration: none;">
                    <i class="fas fa-times"></i>
                </a>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                <!-- Informations de la commande -->
                <div>
                    <h4 style="color: var(--primary-color); margin-bottom: 1rem;">Informations de la commande</h4>

                    <div style="display: grid; gap: 1rem;">
                        <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background: var(--secondary-color); border-radius: 8px;">
                            <i class="<?php echo $selected_order['category_icon']; ?>" style="color: var(--primary-color); font-size: 2rem;"></i>
                            <div>
                                <h4 style="margin: 0; color: var(--text-primary);"><?php echo htmlspecialchars($selected_order['service_name']); ?></h4>
                                <p style="margin: 0; color: var(--text-secondary);"><?php echo htmlspecialchars($selected_order['category_name']); ?></p>
                            </div>
                        </div>

                        <div style="display: grid; gap: 0.75rem;">
                            <div><strong>Client:</strong> <?php echo htmlspecialchars($selected_order['user_name']); ?></div>
                            <div><strong>Email:</strong> <?php echo htmlspecialchars($selected_order['user_email']); ?></div>
                            <?php if ($selected_order['user_phone']): ?>
                                <div><strong>Téléphone:</strong> <?php echo htmlspecialchars($selected_order['user_phone']); ?></div>
                            <?php endif; ?>
                            <div>
                                <strong>Lien:</strong>
                                <a href="<?php echo htmlspecialchars($selected_order['link']); ?>" target="_blank" style="color: var(--primary-color); word-break: break-all;">
                                    <?php echo htmlspecialchars($selected_order['link']); ?>
                                    <i class="fas fa-external-link-alt" style="font-size: 0.8rem;"></i>
                                </a>
                            </div>
                            <div><strong>Quantité:</strong> <?php echo number_format($selected_order['quantity']); ?></div>
                            <div><strong>Montant:</strong> <?php echo formatPrice($selected_order['total_amount']); ?></div>
                            <div><strong>Date:</strong> <?php echo date('d/m/Y à H:i', strtotime($selected_order['created_at'])); ?></div>
                            <?php if ($selected_order['payment_proof']): ?>
                                <div>
                                    <strong>Preuve de paiement:</strong>
                                    <a href="../<?php echo UPLOAD_DIR . $selected_order['payment_proof']; ?>" target="_blank" style="color: var(--primary-color);">
                                        Voir l'image <i class="fas fa-external-link-alt"></i>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Formulaire de modification -->
                <div>
                    <h4 style="color: var(--primary-color); margin-bottom: 1rem;">Modifier le statut</h4>

                    <form method="POST" action="">
                        <input type="hidden" name="order_id" value="<?php echo $selected_order['id']; ?>">

                        <div class="form-group">
                            <label for="status">
                                <i class="fas fa-flag"></i> Statut de la commande
                            </label>
                            <select id="status" name="status" class="form-control" required onchange="toggleCancelReason()">
                                <option value="pending" <?php echo $selected_order['status'] === 'pending' ? 'selected' : ''; ?>>En attente</option>
                                <option value="processing" <?php echo $selected_order['status'] === 'processing' ? 'selected' : ''; ?>>En cours</option>
                                <option value="completed" <?php echo $selected_order['status'] === 'completed' ? 'selected' : ''; ?>>Terminée</option>
                                <option value="cancelled" <?php echo $selected_order['status'] === 'cancelled' ? 'selected' : ''; ?>>Annulée</option>
                            </select>
                        </div>

                        <div class="form-group" id="cancelReasonGroup" style="display: <?php echo $selected_order['status'] === 'cancelled' ? 'block' : 'none'; ?>;">
                            <label for="cancel_reason">
                                <i class="fas fa-times-circle"></i> Raison de l'annulation
                            </label>
                            <input type="text" id="cancel_reason" name="cancel_reason" class="form-control"
                                   placeholder="Expliquez pourquoi la commande est annulée"
                                   value="<?php echo htmlspecialchars($selected_order['cancel_reason'] ?: ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="admin_notes">
                                <i class="fas fa-sticky-note"></i> Notes administrateur
                            </label>
                            <textarea id="admin_notes" name="admin_notes" class="form-control" rows="4"
                                      placeholder="Notes internes ou message pour le client"><?php echo htmlspecialchars($selected_order['admin_notes'] ?: ''); ?></textarea>
                        </div>

                        <button type="submit" name="update_order" class="btn btn-primary" style="width: 100%;">
                            <i class="fas fa-save"></i> Mettre à jour la commande
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script>
        function toggleCancelReason() {
            const status = document.getElementById('status').value;
            const cancelReasonGroup = document.getElementById('cancelReasonGroup');

            if (status === 'cancelled') {
                cancelReasonGroup.style.display = 'block';
                document.getElementById('cancel_reason').required = true;
            } else {
                cancelReasonGroup.style.display = 'none';
                document.getElementById('cancel_reason').required = false;
            }
        }

        // Auto-refresh de la page toutes les 60 secondes si on regarde les commandes en attente
        <?php if ($status_filter === 'pending'): ?>
        setInterval(() => {
            if (!document.getElementById('orderModal') || document.getElementById('orderModal').style.display === 'none') {
                window.location.reload();
            }
        }, 60000);
        <?php endif; ?>
    </script>
</body>
</html>
