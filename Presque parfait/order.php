<?php
require_once 'includes/auth.php';
$auth = new Auth();
$auth->requireUserLogin();

$user = $auth->getCurrentUser();
$db = new Database();

$error = '';
$success = '';

// Récupérer les catégories et services
$categories = $db->fetchAll("SELECT * FROM categories WHERE is_active = 1 ORDER BY name");
$services = $db->fetchAll("
    SELECT s.*, c.name as category_name
    FROM services s
    JOIN categories c ON s.category_id = c.id
    WHERE s.is_active = 1
    ORDER BY c.name, s.name
");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_id = (int)$_POST['service_id'];
    $link = sanitizeInput($_POST['link']);
    $quantity = (int)$_POST['quantity'];

    // Validation
    if (empty($service_id) || empty($link) || empty($quantity)) {
        $error = 'Tous les champs sont obligatoires.';
    } else {
        // Vérifier le service
        $service = $db->fetch("SELECT * FROM services WHERE id = ? AND is_active = 1", [$service_id]);

        if (!$service) {
            $error = 'Service invalide.';
        } elseif ($quantity < $service['min_quantity']) {
            $error = "Quantité minimale : " . number_format($service['min_quantity']);
        } elseif ($quantity > $service['max_quantity']) {
            $error = "Quantité maximale : " . number_format($service['max_quantity']);
        } elseif (!filter_var($link, FILTER_VALIDATE_URL)) {
            $error = 'Lien invalide. Veuillez entrer une URL complète (avec http:// ou https://).';
        } else {
            $total_amount = $quantity * $service['price_per_unit'];

            try {
                $db->query(
                    "INSERT INTO orders (user_id, service_id, link, quantity, total_amount, status) VALUES (?, ?, ?, ?, ?, 'pending')",
                    [$user['id'], $service_id, $link, $quantity, $total_amount]
                );

                $order_id = $db->lastInsertId();

                // Redirection vers la page de paiement
                header("Location: payment.php?order_id=" . $order_id);
                exit();
            } catch (Exception $e) {
                $error = 'Erreur lors de la création de la commande.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelle Commande - <?php echo SITE_NAME; ?></title>
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
                    <li><a href="orders.php">Mes Commandes</a></li>
                    <li><a href="logout.php" class="btn btn-secondary">Déconnexion</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Formulaire de commande -->
    <div class="container" style="margin-top: 120px; margin-bottom: 4rem;">
        <div style="max-width: 800px; margin: 0 auto;">
            <!-- En-tête -->
            <div style="text-align: center; margin-bottom: 3rem;">
                <h1 style="color: var(--primary-color); margin-bottom: 0.5rem;">
                    <i class="fas fa-shopping-cart"></i> Nouvelle Commande
                </h1>
                <p style="color: var(--text-secondary);">
                    Choisissez votre service et passez votre commande en toute simplicité
                </p>
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

            <!-- Formulaire -->
            <div class="card">
                <form method="POST" action="" id="orderForm">
                    <!-- Sélection du service -->
                    <div class="form-group">
                        <label for="service_id">
                            <i class="fas fa-cogs"></i> Service à commander *
                        </label>
                        <select id="service_id" name="service_id" class="form-control" required onchange="updateServiceInfo()">
                            <option value="">-- Choisissez un service --</option>
                            <?php foreach ($categories as $category): ?>
                                <optgroup label="<?php echo htmlspecialchars($category['name']); ?>">
                                    <?php foreach ($services as $service): ?>
                                        <?php if ($service['category_id'] == $category['id']): ?>
                                            <option value="<?php echo $service['id']; ?>"
                                                    data-price="<?php echo $service['price_per_unit']; ?>"
                                                    data-min="<?php echo $service['min_quantity']; ?>"
                                                    data-max="<?php echo $service['max_quantity']; ?>"
                                                    data-description="<?php echo htmlspecialchars($service['description']); ?>">
                                                <?php echo htmlspecialchars($service['name']); ?> - <?php echo formatPrice($service['price_per_unit']); ?>/unité
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Informations du service -->
                    <div id="serviceInfo" style="display: none; margin-bottom: 1.5rem; padding: 1rem; background: var(--secondary-color); border-radius: 8px; border-left: 4px solid var(--primary-color);">
                        <h4 style="color: var(--primary-color); margin-bottom: 0.5rem;">
                            <i class="fas fa-info-circle"></i> Informations du service
                        </h4>
                        <div id="serviceDescription" style="color: var(--text-secondary); margin-bottom: 1rem;"></div>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                            <div>
                                <strong style="color: var(--primary-color);">Prix unitaire:</strong>
                                <span id="servicePriceDisplay"></span>
                            </div>
                            <div>
                                <strong style="color: var(--primary-color);">Quantité min:</strong>
                                <span id="serviceMinDisplay"></span>
                            </div>
                            <div>
                                <strong style="color: var(--primary-color);">Quantité max:</strong>
                                <span id="serviceMaxDisplay"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Lien à promouvoir -->
                    <div class="form-group">
                        <label for="link">
                            <i class="fas fa-link"></i> Lien à promouvoir *
                        </label>
                        <input type="url" id="link" name="link" class="form-control"
                               placeholder="https://instagram.com/votre-compte ou https://facebook.com/votre-page"
                               value="<?php echo isset($_POST['link']) ? htmlspecialchars($_POST['link']) : ''; ?>"
                               required>
                        <small style="color: var(--text-secondary); margin-top: 0.5rem; display: block;">
                            <i class="fas fa-info-circle"></i>
                            Entrez l'URL complète de votre profil, page ou publication à promouvoir
                        </small>
                    </div>

                    <!-- Quantité -->
                    <div class="form-group">
                        <label for="quantity">
                            <i class="fas fa-hashtag"></i> Quantité *
                        </label>
                        <input type="number" id="quantity" name="quantity" class="form-control"
                               placeholder="Entrez la quantité désirée"
                               value="<?php echo isset($_POST['quantity']) ? $_POST['quantity'] : ''; ?>"
                               min="1" required oninput="calculateTotal()">
                        <div id="quantityLimits" style="display: none; margin-top: 0.5rem; color: var(--text-secondary); font-size: 0.9rem;"></div>
                    </div>

                    <!-- Calcul du total -->
                    <div id="totalCalculation" style="display: none; margin-bottom: 1.5rem; padding: 1.5rem; background: var(--card-bg); border: 2px solid var(--primary-color); border-radius: 12px;">
                        <h4 style="color: var(--primary-color); margin-bottom: 1rem; text-align: center;">
                            <i class="fas fa-calculator"></i> Récapitulatif de la commande
                        </h4>
                        <div style="display: grid; gap: 0.75rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span>Service sélectionné:</span>
                                <span id="selectedServiceName" style="font-weight: 600;"></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span>Prix unitaire:</span>
                                <span id="unitPriceDisplay" style="color: var(--primary-color); font-weight: 600;"></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span>Quantité:</span>
                                <span id="quantityDisplay" style="font-weight: 600;"></span>
                            </div>
                            <hr style="border: none; height: 1px; background: var(--border-color); margin: 0.5rem 0;">
                            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 1.2rem;">
                                <span style="font-weight: 700;">Total à payer:</span>
                                <span id="totalAmount" style="color: var(--primary-color); font-weight: 700; font-size: 1.4rem;"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Instructions importantes -->
                    <div style="margin-bottom: 1.5rem; padding: 1rem; background: rgba(255, 165, 0, 0.1); border-radius: 8px; border-left: 4px solid var(--warning-color);">
                        <h4 style="color: var(--warning-color); margin-bottom: 0.5rem;">
                            <i class="fas fa-exclamation-triangle"></i> Instructions importantes
                        </h4>
                        <ul style="color: var(--text-secondary); margin: 0; padding-left: 1.5rem;">
                            <li>Assurez-vous que votre profil/page est public</li>
                            <li>Le lien doit être correct et accessible</li>
                            <li>Le traitement commence après confirmation du paiement</li>
                            <li>Délai de livraison : 24-72h selon le service</li>
                        </ul>
                    </div>

                    <!-- Bouton de soumission -->
                    <button type="submit" class="btn btn-primary" style="width: 100%; font-size: 1.1rem; padding: 1rem;" id="submitBtn" disabled>
                        <i class="fas fa-arrow-right"></i> Procéder au paiement
                    </button>
                </form>
            </div>

            <!-- Services populaires -->
            <div class="card" style="margin-top: 2rem;">
                <h3 style="color: var(--primary-color); margin-bottom: 1rem; text-align: center;">
                    <i class="fas fa-fire"></i> Services les plus populaires
                </h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                    <?php
                    $popular_services = array_slice($services, 0, 6);
                    foreach ($popular_services as $service):
                    ?>
                        <div style="padding: 1rem; border: 1px solid var(--border-color); border-radius: 8px; cursor: pointer; transition: all 0.3s ease;"
                             onclick="selectService(<?php echo $service['id']; ?>)">
                            <h4 style="color: var(--text-primary); margin-bottom: 0.5rem; font-size: 1rem;">
                                <?php echo htmlspecialchars($service['name']); ?>
                            </h4>
                            <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 0.5rem;">
                                <?php echo htmlspecialchars(substr($service['description'], 0, 80) . '...'); ?>
                            </p>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="color: var(--primary-color); font-weight: 600;">
                                    <?php echo formatPrice($service['price_per_unit']); ?>/unité
                                </span>
                                <span style="font-size: 0.8rem; color: var(--text-secondary);">
                                    Min: <?php echo number_format($service['min_quantity']); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function updateServiceInfo() {
            const select = document.getElementById('service_id');
            const selectedOption = select.options[select.selectedIndex];
            const serviceInfo = document.getElementById('serviceInfo');
            const totalCalc = document.getElementById('totalCalculation');
            const submitBtn = document.getElementById('submitBtn');

            if (selectedOption.value) {
                const price = parseFloat(selectedOption.dataset.price);
                const min = parseInt(selectedOption.dataset.min);
                const max = parseInt(selectedOption.dataset.max);
                const description = selectedOption.dataset.description;

                // Afficher les informations du service
                document.getElementById('serviceDescription').textContent = description;
                document.getElementById('servicePriceDisplay').textContent = formatPrice(price);
                document.getElementById('serviceMinDisplay').textContent = min.toLocaleString();
                document.getElementById('serviceMaxDisplay').textContent = max.toLocaleString();
                serviceInfo.style.display = 'block';

                // Mettre à jour les limites de quantité
                const quantityInput = document.getElementById('quantity');
                quantityInput.min = min;
                quantityInput.max = max;
                quantityInput.placeholder = `Entre ${min.toLocaleString()} et ${max.toLocaleString()}`;

                document.getElementById('quantityLimits').innerHTML =
                    `<i class="fas fa-info-circle"></i> Quantité autorisée : entre ${min.toLocaleString()} et ${max.toLocaleString()}`;
                document.getElementById('quantityLimits').style.display = 'block';

                // Mettre à jour le récapitulatif
                document.getElementById('selectedServiceName').textContent = selectedOption.textContent.split(' - ')[0];
                document.getElementById('unitPriceDisplay').textContent = formatPrice(price);

                calculateTotal();
                checkFormValidity();
            } else {
                serviceInfo.style.display = 'none';
                totalCalc.style.display = 'none';
                document.getElementById('quantityLimits').style.display = 'none';
                submitBtn.disabled = true;
            }
        }

        function calculateTotal() {
            const select = document.getElementById('service_id');
            const quantity = parseInt(document.getElementById('quantity').value) || 0;
            const selectedOption = select.options[select.selectedIndex];

            if (selectedOption.value && quantity > 0) {
                const price = parseFloat(selectedOption.dataset.price);
                const total = quantity * price;

                document.getElementById('quantityDisplay').textContent = quantity.toLocaleString();
                document.getElementById('totalAmount').textContent = formatPrice(total);
                document.getElementById('totalCalculation').style.display = 'block';
            } else {
                document.getElementById('totalCalculation').style.display = 'none';
            }

            checkFormValidity();
        }

        function checkFormValidity() {
            const serviceId = document.getElementById('service_id').value;
            const link = document.getElementById('link').value;
            const quantity = parseInt(document.getElementById('quantity').value) || 0;
            const submitBtn = document.getElementById('submitBtn');

            const select = document.getElementById('service_id');
            const selectedOption = select.options[select.selectedIndex];

            let isValid = false;

            if (serviceId && link && quantity > 0 && selectedOption.value) {
                const min = parseInt(selectedOption.dataset.min);
                const max = parseInt(selectedOption.dataset.max);

                if (quantity >= min && quantity <= max) {
                    isValid = true;
                }
            }

            submitBtn.disabled = !isValid;

            if (isValid) {
                submitBtn.innerHTML = '<i class="fas fa-arrow-right"></i> Procéder au paiement';
                submitBtn.style.background = 'var(--gradient-primary)';
            } else {
                submitBtn.innerHTML = '<i class="fas fa-exclamation-circle"></i> Veuillez remplir tous les champs correctement';
                submitBtn.style.background = 'var(--border-color)';
            }
        }

        function selectService(serviceId) {
            document.getElementById('service_id').value = serviceId;
            updateServiceInfo();
            document.getElementById('service_id').scrollIntoView({ behavior: 'smooth' });
        }

        function formatPrice(price) {
            return new Intl.NumberFormat('fr-FR').format(price) + ' FCFA';
        }

        // Event listeners
        document.getElementById('quantity').addEventListener('input', calculateTotal);
        document.getElementById('link').addEventListener('input', checkFormValidity);
    </script>
</body>
</html>
