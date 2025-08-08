<?php
require_once '../includes/auth.php';
$auth = new Auth();
$auth->requireAdminLogin();

$admin = $auth->getCurrentAdmin();
$db = new Database();

$error = '';
$success = '';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_service'])) {
        $category_id = (int)$_POST['category_id'];
        $name = sanitizeInput($_POST['name']);
        $description = sanitizeInput($_POST['description']);
        $price_per_unit = (float)$_POST['price_per_unit'];
        $min_quantity = (int)$_POST['min_quantity'];
        $max_quantity = (int)$_POST['max_quantity'];

        if (empty($name) || $price_per_unit <= 0 || $min_quantity <= 0 || $max_quantity <= $min_quantity) {
            $error = 'Veuillez remplir tous les champs correctement.';
        } else {
            try {
                $db->query(
                    "INSERT INTO services (category_id, name, description, price_per_unit, min_quantity, max_quantity) VALUES (?, ?, ?, ?, ?, ?)",
                    [$category_id, $name, $description, $price_per_unit, $min_quantity, $max_quantity]
                );
                $success = 'Service ajouté avec succès.';
            } catch (Exception $e) {
                $error = 'Erreur lors de l\'ajout du service.';
            }
        }
    } elseif (isset($_POST['update_service'])) {
        $service_id = (int)$_POST['service_id'];
        $category_id = (int)$_POST['category_id'];
        $name = sanitizeInput($_POST['name']);
        $description = sanitizeInput($_POST['description']);
        $price_per_unit = (float)$_POST['price_per_unit'];
        $min_quantity = (int)$_POST['min_quantity'];
        $max_quantity = (int)$_POST['max_quantity'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if (empty($name) || $price_per_unit <= 0 || $min_quantity <= 0 || $max_quantity <= $min_quantity) {
            $error = 'Veuillez remplir tous les champs correctement.';
        } else {
            try {
                $db->query(
                    "UPDATE services SET category_id = ?, name = ?, description = ?, price_per_unit = ?, min_quantity = ?, max_quantity = ?, is_active = ? WHERE id = ?",
                    [$category_id, $name, $description, $price_per_unit, $min_quantity, $max_quantity, $is_active, $service_id]
                );
                $success = 'Service mis à jour avec succès.';
            } catch (Exception $e) {
                $error = 'Erreur lors de la mise à jour du service.';
            }
        }
    } elseif (isset($_POST['delete_service'])) {
        $service_id = (int)$_POST['service_id'];

        try {
            $db->query("DELETE FROM services WHERE id = ?", [$service_id]);
            $success = 'Service supprimé avec succès.';
        } catch (Exception $e) {
            $error = 'Erreur lors de la suppression du service. Vérifiez qu\'il n\'y a pas de commandes associées.';
        }
    }
}

// Récupérer les services avec catégories
$services = $db->fetchAll("
    SELECT s.*, c.name as category_name, c.icon as category_icon,
           (SELECT COUNT(*) FROM orders WHERE service_id = s.id) as order_count
    FROM services s
    JOIN categories c ON s.category_id = c.id
    ORDER BY c.name, s.name
");

// Récupérer les catégories pour les formulaires
$categories = $db->fetchAll("SELECT * FROM categories WHERE is_active = 1 ORDER BY name");

// Service sélectionné pour modification
$selected_service = null;
if (isset($_GET['edit'])) {
    $service_id = (int)$_GET['edit'];
    $selected_service = $db->fetch("SELECT * FROM services WHERE id = ?", [$service_id]);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Services - Admin <?php echo SITE_NAME; ?></title>
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
                    <li><a href="users.php">Utilisateurs</a></li>
                    <li><a href="categories.php">Catégories</a></li>
                    <li><a href="../logout.php" class="btn btn-secondary">Déconnexion</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Gestion des services -->
    <div class="dashboard">
        <div class="container">
            <div class="dashboard-header">
                <div>
                    <h1 style="color: var(--text-primary); margin-bottom: 0.5rem;">
                        <i class="fas fa-cogs"></i> Gestion des Services
                    </h1>
                    <p style="color: var(--text-secondary);">
                        Ajoutez, modifiez et gérez tous les services de la plateforme
                    </p>
                </div>
                <div>
                    <button onclick="showAddServiceModal()" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Ajouter un Service
                    </button>
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

            <!-- Liste des services -->
            <div class="card">
                <h3 style="color: var(--primary-color); margin-bottom: 1.5rem;">
                    <i class="fas fa-list"></i> Tous les Services (<?php echo count($services); ?>)
                </h3>

                <?php if (empty($services)): ?>
                    <div style="text-align: center; padding: 3rem 1rem; color: var(--text-secondary);">
                        <i class="fas fa-cogs" style="font-size: 4rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                        <h3>Aucun service</h3>
                        <p>Commencez par ajouter votre premier service.</p>
                        <button onclick="showAddServiceModal()" class="btn btn-primary" style="margin-top: 1rem;">
                            <i class="fas fa-plus"></i> Ajouter un Service
                        </button>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Service</th>
                                    <th>Catégorie</th>
                                    <th>Prix/Unité</th>
                                    <th>Quantité</th>
                                    <th>Commandes</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($services as $service): ?>
                                <tr>
                                    <td>
                                        <div>
                                            <div style="font-weight: 600;"><?php echo htmlspecialchars($service['name']); ?></div>
                                            <div style="font-size: 0.8rem; color: var(--text-secondary);">
                                                <?php echo strlen($service['description']) > 50 ? substr($service['description'], 0, 50) . '...' : $service['description']; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                                            <i class="<?php echo $service['category_icon']; ?>" style="color: var(--primary-color);"></i>
                                            <?php echo htmlspecialchars($service['category_name']); ?>
                                        </div>
                                    </td>
                                    <td style="font-weight: 600; color: var(--primary-color);">
                                        <?php echo formatPrice($service['price_per_unit']); ?>
                                    </td>
                                    <td>
                                        <div style="font-size: 0.9rem;">
                                            <div><strong>Min:</strong> <?php echo number_format($service['min_quantity']); ?></div>
                                            <div><strong>Max:</strong> <?php echo number_format($service['max_quantity']); ?></div>
                                        </div>
                                    </td>
                                    <td style="text-align: center;">
                                        <span style="font-weight: 600; color: var(--primary-color);">
                                            <?php echo $service['order_count']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($service['is_active']): ?>
                                            <span class="status-badge status-completed">Actif</span>
                                        <?php else: ?>
                                            <span class="status-badge status-cancelled">Inactif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 0.5rem;">
                                            <a href="?edit=<?php echo $service['id']; ?>"
                                               class="btn btn-primary" style="padding: 0.25rem 0.5rem; font-size: 0.8rem; text-decoration: none;">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($service['order_count'] == 0): ?>
                                                <button onclick="confirmDeleteService(<?php echo $service['id']; ?>, '<?php echo addslashes($service['name']); ?>')"
                                                        class="btn btn-danger" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal d'ajout de service -->
    <div id="addServiceModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 2000; align-items: center; justify-content: center;">
        <div style="background: var(--card-bg); border-radius: 12px; padding: 2rem; max-width: 600px; width: 90%; max-height: 80vh; overflow-y: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 style="color: var(--primary-color); margin: 0;">
                    <i class="fas fa-plus"></i> Ajouter un Service
                </h3>
                <button onclick="closeAddServiceModal()" style="background: none; border: none; color: var(--text-secondary); font-size: 1.5rem; cursor: pointer;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="add_category_id">
                        <i class="fas fa-tags"></i> Catégorie *
                    </label>
                    <select id="add_category_id" name="category_id" class="form-control" required>
                        <option value="">-- Choisir une catégorie --</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="add_name">
                        <i class="fas fa-cog"></i> Nom du service *
                    </label>
                    <input type="text" id="add_name" name="name" class="form-control" required
                           placeholder="Ex: Followers Instagram">
                </div>

                <div class="form-group">
                    <label for="add_description">
                        <i class="fas fa-info-circle"></i> Description
                    </label>
                    <textarea id="add_description" name="description" class="form-control" rows="3"
                              placeholder="Description du service..."></textarea>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label for="add_price_per_unit">
                            <i class="fas fa-money-bill-wave"></i> Prix/Unité (FCFA) *
                        </label>
                        <input type="number" id="add_price_per_unit" name="price_per_unit" class="form-control"
                               step="0.01" min="0.01" required placeholder="50">
                    </div>

                    <div class="form-group">
                        <label for="add_min_quantity">
                            <i class="fas fa-arrow-down"></i> Quantité Min *
                        </label>
                        <input type="number" id="add_min_quantity" name="min_quantity" class="form-control"
                               min="1" required placeholder="100">
                    </div>

                    <div class="form-group">
                        <label for="add_max_quantity">
                            <i class="fas fa-arrow-up"></i> Quantité Max *
                        </label>
                        <input type="number" id="add_max_quantity" name="max_quantity" class="form-control"
                               min="1" required placeholder="10000">
                    </div>
                </div>

                <button type="submit" name="add_service" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-plus"></i> Ajouter le Service
                </button>
            </form>
        </div>
    </div>

    <!-- Modal de modification de service -->
    <?php if ($selected_service): ?>
    <div id="editServiceModal" style="display: flex; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 2000; align-items: center; justify-content: center;">
        <div style="background: var(--card-bg); border-radius: 12px; padding: 2rem; max-width: 600px; width: 90%; max-height: 80vh; overflow-y: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 style="color: var(--primary-color); margin: 0;">
                    <i class="fas fa-edit"></i> Modifier le Service
                </h3>
                <a href="services.php" style="color: var(--text-secondary); font-size: 1.5rem; text-decoration: none;">
                    <i class="fas fa-times"></i>
                </a>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="service_id" value="<?php echo $selected_service['id']; ?>">

                <div class="form-group">
                    <label for="edit_category_id">
                        <i class="fas fa-tags"></i> Catégorie *
                    </label>
                    <select id="edit_category_id" name="category_id" class="form-control" required>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>"
                                    <?php echo $category['id'] == $selected_service['category_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="edit_name">
                        <i class="fas fa-cog"></i> Nom du service *
                    </label>
                    <input type="text" id="edit_name" name="name" class="form-control" required
                           value="<?php echo htmlspecialchars($selected_service['name']); ?>">
                </div>

                <div class="form-group">
                    <label for="edit_description">
                        <i class="fas fa-info-circle"></i> Description
                    </label>
                    <textarea id="edit_description" name="description" class="form-control" rows="3"><?php echo htmlspecialchars($selected_service['description']); ?></textarea>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label for="edit_price_per_unit">
                            <i class="fas fa-money-bill-wave"></i> Prix/Unité (FCFA) *
                        </label>
                        <input type="number" id="edit_price_per_unit" name="price_per_unit" class="form-control"
                               step="0.01" min="0.01" required
                               value="<?php echo $selected_service['price_per_unit']; ?>">
                    </div>

                    <div class="form-group">
                        <label for="edit_min_quantity">
                            <i class="fas fa-arrow-down"></i> Quantité Min *
                        </label>
                        <input type="number" id="edit_min_quantity" name="min_quantity" class="form-control"
                               min="1" required
                               value="<?php echo $selected_service['min_quantity']; ?>">
                    </div>

                    <div class="form-group">
                        <label for="edit_max_quantity">
                            <i class="fas fa-arrow-up"></i> Quantité Max *
                        </label>
                        <input type="number" id="edit_max_quantity" name="max_quantity" class="form-control"
                               min="1" required
                               value="<?php echo $selected_service['max_quantity']; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" name="is_active" <?php echo $selected_service['is_active'] ? 'checked' : ''; ?>
                               style="accent-color: var(--primary-color);">
                        <span><i class="fas fa-eye"></i> Service actif (visible pour les clients)</span>
                    </label>
                </div>

                <div style="display: flex; gap: 1rem;">
                    <button type="submit" name="update_service" class="btn btn-primary" style="flex: 1;">
                        <i class="fas fa-save"></i> Mettre à jour
                    </button>
                    <a href="services.php" class="btn btn-secondary" style="flex: 1; text-decoration: none; text-align: center;">
                        <i class="fas fa-times"></i> Annuler
                    </a>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Modal de confirmation de suppression -->
    <div id="deleteModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 2000; align-items: center; justify-content: center;">
        <div style="background: var(--card-bg); border-radius: 12px; padding: 2rem; max-width: 500px; width: 90%;">
            <h3 style="color: var(--error-color); margin-bottom: 1rem;">
                <i class="fas fa-exclamation-triangle"></i> Confirmer la suppression
            </h3>
            <p style="color: var(--text-secondary); margin-bottom: 1.5rem;" id="deleteMessage"></p>
            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button onclick="closeDeleteModal()" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Annuler
                </button>
                <form method="POST" style="display: inline;" id="deleteForm">
                    <input type="hidden" name="service_id" id="deleteServiceId">
                    <button type="submit" name="delete_service" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Supprimer
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showAddServiceModal() {
            document.getElementById('addServiceModal').style.display = 'flex';
        }

        function closeAddServiceModal() {
            document.getElementById('addServiceModal').style.display = 'none';
        }

        function confirmDeleteService(serviceId, serviceName) {
            document.getElementById('deleteServiceId').value = serviceId;
            document.getElementById('deleteMessage').textContent =
                `Êtes-vous sûr de vouloir supprimer le service "${serviceName}" ? Cette action est irréversible.`;
            document.getElementById('deleteModal').style.display = 'flex';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        // Validation des quantités
        document.getElementById('add_min_quantity')?.addEventListener('input', function() {
            const maxInput = document.getElementById('add_max_quantity');
            if (maxInput.value && parseInt(this.value) >= parseInt(maxInput.value)) {
                maxInput.value = parseInt(this.value) + 1;
            }
        });

        document.getElementById('add_max_quantity')?.addEventListener('input', function() {
            const minInput = document.getElementById('add_min_quantity');
            if (minInput.value && parseInt(this.value) <= parseInt(minInput.value)) {
                minInput.value = parseInt(this.value) - 1;
            }
        });

        // Fermer les modaux en cliquant à l'extérieur
        document.getElementById('addServiceModal')?.addEventListener('click', function(e) {
            if (e.target === this) closeAddServiceModal();
        });

        document.getElementById('deleteModal')?.addEventListener('click', function(e) {
            if (e.target === this) closeDeleteModal();
        });
    </script>
</body>
</html>
