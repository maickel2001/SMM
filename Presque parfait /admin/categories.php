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
    if (isset($_POST['add_category'])) {
        $name = sanitizeInput($_POST['name']);
        $description = sanitizeInput($_POST['description']);
        $icon = sanitizeInput($_POST['icon']);

        if (empty($name)) {
            $error = 'Le nom de la catégorie est obligatoire.';
        } else {
            try {
                $db->query(
                    "INSERT INTO categories (name, description, icon) VALUES (?, ?, ?)",
                    [$name, $description, $icon]
                );
                $success = 'Catégorie ajoutée avec succès.';
            } catch (Exception $e) {
                $error = 'Erreur lors de l\'ajout de la catégorie.';
            }
        }
    } elseif (isset($_POST['update_category'])) {
        $category_id = (int)$_POST['category_id'];
        $name = sanitizeInput($_POST['name']);
        $description = sanitizeInput($_POST['description']);
        $icon = sanitizeInput($_POST['icon']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if (empty($name)) {
            $error = 'Le nom de la catégorie est obligatoire.';
        } else {
            try {
                $db->query(
                    "UPDATE categories SET name = ?, description = ?, icon = ?, is_active = ? WHERE id = ?",
                    [$name, $description, $icon, $is_active, $category_id]
                );
                $success = 'Catégorie mise à jour avec succès.';
            } catch (Exception $e) {
                $error = 'Erreur lors de la mise à jour de la catégorie.';
            }
        }
    } elseif (isset($_POST['delete_category'])) {
        $category_id = (int)$_POST['category_id'];

        try {
            $db->query("DELETE FROM categories WHERE id = ?", [$category_id]);
            $success = 'Catégorie supprimée avec succès.';
        } catch (Exception $e) {
            $error = 'Erreur lors de la suppression. Vérifiez qu\'il n\'y a pas de services associés.';
        }
    }
}

// Récupérer les catégories avec le nombre de services
$categories = $db->fetchAll("
    SELECT c.*,
           (SELECT COUNT(*) FROM services WHERE category_id = c.id) as service_count
    FROM categories c
    ORDER BY c.name
");

// Catégorie sélectionnée pour modification
$selected_category = null;
if (isset($_GET['edit'])) {
    $category_id = (int)$_GET['edit'];
    $selected_category = $db->fetch("SELECT * FROM categories WHERE id = ?", [$category_id]);
}

// Icônes FontAwesome populaires
$popular_icons = [
    'fab fa-instagram' => 'Instagram',
    'fab fa-facebook' => 'Facebook',
    'fab fa-twitter' => 'Twitter',
    'fab fa-youtube' => 'YouTube',
    'fab fa-tiktok' => 'TikTok',
    'fab fa-linkedin' => 'LinkedIn',
    'fab fa-snapchat' => 'Snapchat',
    'fab fa-pinterest' => 'Pinterest',
    'fab fa-telegram' => 'Telegram',
    'fab fa-whatsapp' => 'WhatsApp',
    'fas fa-heart' => 'Likes/Cœurs',
    'fas fa-eye' => 'Vues',
    'fas fa-share' => 'Partages',
    'fas fa-comment' => 'Commentaires',
    'fas fa-thumbs-up' => 'Pouces',
    'fas fa-star' => 'Étoiles',
    'fas fa-chart-line' => 'Analytics',
    'fas fa-bullhorn' => 'Publicité'
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Catégories - Admin <?php echo SITE_NAME; ?></title>
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
                    <li><a href="services.php">Services</a></li>
                    <li><a href="../logout.php" class="btn btn-secondary">Déconnexion</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Gestion des catégories -->
    <div class="dashboard">
        <div class="container">
            <div class="dashboard-header">
                <div>
                    <h1 style="color: var(--text-primary); margin-bottom: 0.5rem;">
                        <i class="fas fa-tags"></i> Gestion des Catégories
                    </h1>
                    <p style="color: var(--text-secondary);">
                        Organisez vos services par catégories pour une meilleure navigation
                    </p>
                </div>
                <div>
                    <button onclick="showAddCategoryModal()" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Ajouter une Catégorie
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

            <!-- Liste des catégories -->
            <div class="card">
                <h3 style="color: var(--primary-color); margin-bottom: 1.5rem;">
                    <i class="fas fa-list"></i> Toutes les Catégories (<?php echo count($categories); ?>)
                </h3>

                <?php if (empty($categories)): ?>
                    <div style="text-align: center; padding: 3rem 1rem; color: var(--text-secondary);">
                        <i class="fas fa-tags" style="font-size: 4rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                        <h3>Aucune catégorie</h3>
                        <p>Commencez par ajouter votre première catégorie.</p>
                        <button onclick="showAddCategoryModal()" class="btn btn-primary" style="margin-top: 1rem;">
                            <i class="fas fa-plus"></i> Ajouter une Catégorie
                        </button>
                    </div>
                <?php else: ?>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                        <?php foreach ($categories as $category): ?>
                        <div class="card" style="padding: 1.5rem;">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                                <div style="display: flex; align-items: center; gap: 1rem;">
                                    <i class="<?php echo $category['icon']; ?>" style="color: var(--primary-color); font-size: 2rem;"></i>
                                    <div>
                                        <h4 style="margin: 0; color: var(--text-primary);"><?php echo htmlspecialchars($category['name']); ?></h4>
                                        <span style="font-size: 0.8rem; color: var(--text-secondary);">
                                            <?php echo $category['service_count']; ?> service(s)
                                        </span>
                                    </div>
                                </div>
                                <div style="display: flex; gap: 0.5rem;">
                                    <?php if ($category['is_active']): ?>
                                        <span class="status-badge status-completed">Actif</span>
                                    <?php else: ?>
                                        <span class="status-badge status-cancelled">Inactif</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if ($category['description']): ?>
                                <p style="color: var(--text-secondary); margin-bottom: 1rem; font-size: 0.9rem;">
                                    <?php echo htmlspecialchars($category['description']); ?>
                                </p>
                            <?php endif; ?>

                            <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                                <a href="?edit=<?php echo $category['id']; ?>"
                                   class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.9rem; text-decoration: none;">
                                    <i class="fas fa-edit"></i> Modifier
                                </a>
                                <?php if ($category['service_count'] == 0): ?>
                                    <button onclick="confirmDeleteCategory(<?php echo $category['id']; ?>, '<?php echo addslashes($category['name']); ?>')"
                                            class="btn btn-danger" style="padding: 0.5rem 1rem; font-size: 0.9rem;">
                                        <i class="fas fa-trash"></i> Supprimer
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal d'ajout de catégorie -->
    <div id="addCategoryModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 2000; align-items: center; justify-content: center;">
        <div style="background: var(--card-bg); border-radius: 12px; padding: 2rem; max-width: 600px; width: 90%;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 style="color: var(--primary-color); margin: 0;">
                    <i class="fas fa-plus"></i> Ajouter une Catégorie
                </h3>
                <button onclick="closeAddCategoryModal()" style="background: none; border: none; color: var(--text-secondary); font-size: 1.5rem; cursor: pointer;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="add_name">
                        <i class="fas fa-tag"></i> Nom de la catégorie *
                    </label>
                    <input type="text" id="add_name" name="name" class="form-control" required
                           placeholder="Ex: Instagram, YouTube, TikTok...">
                </div>

                <div class="form-group">
                    <label for="add_description">
                        <i class="fas fa-info-circle"></i> Description
                    </label>
                    <textarea id="add_description" name="description" class="form-control" rows="3"
                              placeholder="Description de la catégorie..."></textarea>
                </div>

                <div class="form-group">
                    <label for="add_icon">
                        <i class="fas fa-icons"></i> Icône FontAwesome
                    </label>
                    <select id="add_icon" name="icon" class="form-control" onchange="previewIcon('add')">
                        <option value="">-- Choisir une icône --</option>
                        <?php foreach ($popular_icons as $icon_class => $icon_label): ?>
                            <option value="<?php echo $icon_class; ?>">
                                <?php echo $icon_label; ?> (<?php echo $icon_class; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div id="add_icon_preview" style="margin-top: 0.5rem; text-align: center;"></div>
                </div>

                <button type="submit" name="add_category" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-plus"></i> Ajouter la Catégorie
                </button>
            </form>
        </div>
    </div>

    <!-- Modal de modification de catégorie -->
    <?php if ($selected_category): ?>
    <div id="editCategoryModal" style="display: flex; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 2000; align-items: center; justify-content: center;">
        <div style="background: var(--card-bg); border-radius: 12px; padding: 2rem; max-width: 600px; width: 90%;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 style="color: var(--primary-color); margin: 0;">
                    <i class="fas fa-edit"></i> Modifier la Catégorie
                </h3>
                <a href="categories.php" style="color: var(--text-secondary); font-size: 1.5rem; text-decoration: none;">
                    <i class="fas fa-times"></i>
                </a>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="category_id" value="<?php echo $selected_category['id']; ?>">

                <div class="form-group">
                    <label for="edit_name">
                        <i class="fas fa-tag"></i> Nom de la catégorie *
                    </label>
                    <input type="text" id="edit_name" name="name" class="form-control" required
                           value="<?php echo htmlspecialchars($selected_category['name']); ?>">
                </div>

                <div class="form-group">
                    <label for="edit_description">
                        <i class="fas fa-info-circle"></i> Description
                    </label>
                    <textarea id="edit_description" name="description" class="form-control" rows="3"><?php echo htmlspecialchars($selected_category['description']); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="edit_icon">
                        <i class="fas fa-icons"></i> Icône FontAwesome
                    </label>
                    <select id="edit_icon" name="icon" class="form-control" onchange="previewIcon('edit')">
                        <option value="">-- Aucune icône --</option>
                        <?php foreach ($popular_icons as $icon_class => $icon_label): ?>
                            <option value="<?php echo $icon_class; ?>"
                                    <?php echo $icon_class === $selected_category['icon'] ? 'selected' : ''; ?>>
                                <?php echo $icon_label; ?> (<?php echo $icon_class; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div id="edit_icon_preview" style="margin-top: 0.5rem; text-align: center;">
                        <?php if ($selected_category['icon']): ?>
                            <i class="<?php echo $selected_category['icon']; ?>" style="font-size: 2rem; color: var(--primary-color);"></i>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" name="is_active"
                               <?php echo $selected_category['is_active'] ? 'checked' : ''; ?>
                               style="accent-color: var(--primary-color);">
                        <span><i class="fas fa-eye"></i> Catégorie active (visible pour les clients)</span>
                    </label>
                </div>

                <div style="display: flex; gap: 1rem;">
                    <button type="submit" name="update_category" class="btn btn-primary" style="flex: 1;">
                        <i class="fas fa-save"></i> Mettre à jour
                    </button>
                    <a href="categories.php" class="btn btn-secondary" style="flex: 1; text-decoration: none; text-align: center;">
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
                    <input type="hidden" name="category_id" id="deleteCategoryId">
                    <button type="submit" name="delete_category" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Supprimer
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showAddCategoryModal() {
            document.getElementById('addCategoryModal').style.display = 'flex';
        }

        function closeAddCategoryModal() {
            document.getElementById('addCategoryModal').style.display = 'none';
        }

        function confirmDeleteCategory(categoryId, categoryName) {
            document.getElementById('deleteCategoryId').value = categoryId;
            document.getElementById('deleteMessage').textContent =
                `Êtes-vous sûr de vouloir supprimer la catégorie "${categoryName}" ? Cette action est irréversible.`;
            document.getElementById('deleteModal').style.display = 'flex';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        function previewIcon(type) {
            const select = document.getElementById(type + '_icon');
            const preview = document.getElementById(type + '_icon_preview');

            if (select.value) {
                preview.innerHTML = `<i class="${select.value}" style="font-size: 2rem; color: var(--primary-color);"></i>`;
            } else {
                preview.innerHTML = '';
            }
        }

        // Fermer les modaux en cliquant à l'extérieur
        document.getElementById('addCategoryModal')?.addEventListener('click', function(e) {
            if (e.target === this) closeAddCategoryModal();
        });

        document.getElementById('deleteModal')?.addEventListener('click', function(e) {
            if (e.target === this) closeDeleteModal();
        });
    </script>
</body>
</html>
