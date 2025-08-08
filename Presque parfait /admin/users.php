<?php
require_once '../includes/auth.php';
$auth = new Auth();
$auth->requireAdminLogin();

$admin = $auth->getCurrentAdmin();
$db = new Database();

// Filtres et recherche
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Construire la requête avec filtres
$where_conditions = ["1=1"];
$params = [];

if ($search) {
    $where_conditions[] = "(u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
}

$where_clause = implode(' AND ', $where_conditions);

// Compter le total
$total_users = $db->fetch("
    SELECT COUNT(*) as count
    FROM users u
    WHERE $where_clause
", $params)['count'];

$total_pages = ceil($total_users / $per_page);

// Récupérer les utilisateurs avec leurs statistiques
$users = $db->fetchAll("
    SELECT u.*,
           COUNT(o.id) as total_orders,
           SUM(CASE WHEN o.status = 'completed' THEN o.total_amount ELSE 0 END) as total_spent,
           MAX(o.created_at) as last_order_date
    FROM users u
    LEFT JOIN orders o ON u.id = o.user_id
    WHERE $where_clause
    GROUP BY u.id
    ORDER BY u.created_at DESC
    LIMIT $per_page OFFSET $offset
", $params);

// Statistiques générales
$stats = [
    'total_users' => $db->fetch("SELECT COUNT(*) as count FROM users")['count'],
    'new_users_week' => $db->fetch("SELECT COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")['count'],
    'active_users' => $db->fetch("SELECT COUNT(DISTINCT user_id) as count FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")['count']
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Utilisateurs - Admin <?php echo SITE_NAME; ?></title>
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
                    <li><a href="orders.php">Commandes</a></li>
                    <li><a href="services.php">Services</a></li>
                    <li><a href="categories.php">Catégories</a></li>
                    <li><a href="../logout.php" class="btn btn-secondary">Déconnexion</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Gestion des utilisateurs -->
    <div class="dashboard">
        <div class="container">
            <div class="dashboard-header">
                <div>
                    <h1 style="color: var(--text-primary); margin-bottom: 0.5rem;">
                        <i class="fas fa-users"></i> Gestion des Utilisateurs
                    </h1>
                    <p style="color: var(--text-secondary);">
                        Vue d'ensemble de tous les utilisateurs inscrits sur la plateforme
                    </p>
                </div>
            </div>

            <!-- Statistiques des utilisateurs -->
            <div class="dashboard-stats">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_users']; ?></div>
                    <div class="stat-label">
                        <i class="fas fa-users"></i> Total Utilisateurs
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['new_users_week']; ?></div>
                    <div class="stat-label">
                        <i class="fas fa-user-plus"></i> Nouveaux (7j)
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['active_users']; ?></div>
                    <div class="stat-label">
                        <i class="fas fa-user-check"></i> Actifs (30j)
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format(($stats['active_users'] / max($stats['total_users'], 1)) * 100, 1); ?>%</div>
                    <div class="stat-label">
                        <i class="fas fa-chart-line"></i> Taux d'Activité
                    </div>
                </div>
            </div>

            <!-- Recherche -->
            <div class="card" style="margin-bottom: 2rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                    <h3 style="color: var(--primary-color); margin: 0;">
                        <i class="fas fa-search"></i> Rechercher un utilisateur
                    </h3>

                    <form method="GET" style="display: flex; gap: 0.5rem;">
                        <input type="text" name="search" class="form-control"
                               placeholder="Nom, email ou téléphone..."
                               value="<?php echo htmlspecialchars($search); ?>"
                               style="width: 250px;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                        </button>
                        <?php if ($search): ?>
                            <a href="users.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i>
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- Liste des utilisateurs -->
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h3 style="color: var(--primary-color); margin: 0;">
                        <i class="fas fa-list"></i>
                        Utilisateurs
                        <?php if ($search): ?>
                            - Recherche: "<?php echo htmlspecialchars($search); ?>"
                        <?php endif; ?>
                    </h3>
                    <span style="color: var(--text-secondary);">
                        <?php echo $total_users; ?> utilisateur(s) trouvé(s)
                    </span>
                </div>

                <?php if (empty($users)): ?>
                    <div style="text-align: center; padding: 3rem 1rem; color: var(--text-secondary);">
                        <i class="fas fa-users" style="font-size: 4rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                        <h3>Aucun utilisateur trouvé</h3>
                        <p>Aucun utilisateur ne correspond aux critères de recherche.</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Utilisateur</th>
                                    <th>Contact</th>
                                    <th>Statistiques</th>
                                    <th>Total Dépensé</th>
                                    <th>Dernière Commande</th>
                                    <th>Inscription</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td style="font-weight: 600; color: var(--primary-color);">#<?php echo $user['id']; ?></td>
                                    <td>
                                        <div>
                                            <div style="font-weight: 600; color: var(--text-primary);">
                                                <?php echo htmlspecialchars($user['name']); ?>
                                            </div>
                                            <div style="font-size: 0.8rem; color: var(--text-secondary);">
                                                ID: <?php echo $user['id']; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-size: 0.9rem;">
                                            <div style="margin-bottom: 0.25rem;">
                                                <i class="fas fa-envelope" style="color: var(--primary-color);"></i>
                                                <?php echo htmlspecialchars($user['email']); ?>
                                            </div>
                                            <?php if ($user['phone']): ?>
                                                <div>
                                                    <i class="fas fa-phone" style="color: var(--primary-color);"></i>
                                                    <?php echo htmlspecialchars($user['phone']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-size: 0.9rem; text-align: center;">
                                            <div style="font-weight: 600; color: var(--primary-color);">
                                                <?php echo $user['total_orders']; ?> commande(s)
                                            </div>
                                        </div>
                                    </td>
                                    <td style="text-align: center;">
                                        <span style="font-weight: 600; color: var(--success-color);">
                                            <?php echo formatPrice($user['total_spent']); ?>
                                        </span>
                                    </td>
                                    <td style="text-align: center;">
                                        <?php if ($user['last_order_date']): ?>
                                            <div style="font-size: 0.9rem;">
                                                <?php echo date('d/m/Y', strtotime($user['last_order_date'])); ?>
                                                <div style="color: var(--text-secondary); font-size: 0.8rem;">
                                                    <?php
                                                    $days_ago = floor((time() - strtotime($user['last_order_date'])) / (60 * 60 * 24));
                                                    echo "Il y a $days_ago jour(s)";
                                                    ?>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <span style="color: var(--text-secondary); font-style: italic;">Aucune</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <div style="font-size: 0.9rem;">
                                            <?php echo date('d/m/Y', strtotime($user['created_at'])); ?>
                                            <div style="color: var(--text-secondary); font-size: 0.8rem;">
                                                <?php
                                                $days_ago = floor((time() - strtotime($user['created_at'])) / (60 * 60 * 24));
                                                echo "Il y a $days_ago jour(s)";
                                                ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 0.5rem; justify-content: center;">
                                            <a href="orders.php?search=<?php echo urlencode($user['email']); ?>"
                                               class="btn btn-primary" style="padding: 0.25rem 0.5rem; font-size: 0.8rem; text-decoration: none;"
                                               title="Voir les commandes">
                                                <i class="fas fa-shopping-cart"></i>
                                            </a>
                                            <button onclick="viewUserDetails(<?php echo $user['id']; ?>)"
                                                    class="btn btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;"
                                                    title="Voir les détails">
                                                <i class="fas fa-eye"></i>
                                            </button>
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
                        $current_url = 'users.php?';
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

    <!-- Modal pour voir les détails d'un utilisateur -->
    <div id="userModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 2000; align-items: center; justify-content: center;">
        <div style="background: var(--card-bg); border-radius: 12px; padding: 2rem; max-width: 600px; width: 90%; max-height: 80vh; overflow-y: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 style="color: var(--primary-color); margin: 0;">Détails de l'utilisateur</h3>
                <button onclick="closeUserModal()" style="background: none; border: none; color: var(--text-secondary); font-size: 1.5rem; cursor: pointer;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="userDetails"></div>
        </div>
    </div>

    <script>
        function viewUserDetails(userId) {
            // Trouver l'utilisateur dans les données
            const users = <?php echo json_encode($users); ?>;
            const user = users.find(u => u.id == userId);

            if (user) {
                const details = `
                    <div style="display: grid; gap: 1.5rem;">
                        <div style="text-align: center; padding: 1rem; background: var(--secondary-color); border-radius: 8px;">
                            <i class="fas fa-user-circle" style="font-size: 3rem; color: var(--primary-color); margin-bottom: 0.5rem;"></i>
                            <h4 style="margin: 0; color: var(--text-primary);">${user.name}</h4>
                            <p style="margin: 0; color: var(--text-secondary);">Utilisateur #${user.id}</p>
                        </div>

                        <div style="display: grid; gap: 1rem;">
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-secondary);">Email:</span>
                                <span style="font-weight: 600;">${user.email}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-secondary);">Téléphone:</span>
                                <span style="font-weight: 600;">${user.phone || 'Non renseigné'}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-secondary);">Date d'inscription:</span>
                                <span style="font-weight: 600;">${new Date(user.created_at).toLocaleDateString('fr-FR')}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-secondary);">Nombre de commandes:</span>
                                <span style="font-weight: 600; color: var(--primary-color);">${user.total_orders}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-secondary);">Total dépensé:</span>
                                <span style="font-weight: 600; color: var(--success-color);">${formatPrice(user.total_spent)}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-secondary);">Dernière commande:</span>
                                <span style="font-weight: 600;">${user.last_order_date ? new Date(user.last_order_date).toLocaleDateString('fr-FR') : 'Aucune'}</span>
                            </div>
                        </div>

                        <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 1rem;">
                            <a href="orders.php?search=${encodeURIComponent(user.email)}" class="btn btn-primary" style="text-decoration: none;">
                                <i class="fas fa-shopping-cart"></i> Voir les commandes
                            </a>
                        </div>
                    </div>
                `;

                document.getElementById('userDetails').innerHTML = details;
                document.getElementById('userModal').style.display = 'flex';
            }
        }

        function closeUserModal() {
            document.getElementById('userModal').style.display = 'none';
        }

        function formatPrice(price) {
            return new Intl.NumberFormat('fr-FR').format(price) + ' FCFA';
        }

        // Fermer le modal en cliquant à l'extérieur
        document.getElementById('userModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeUserModal();
            }
        });
    </script>
</body>
</html>
