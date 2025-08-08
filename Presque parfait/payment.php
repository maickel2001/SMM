<?php
require_once 'includes/auth.php';
$auth = new Auth();
$auth->requireUserLogin();

$user = $auth->getCurrentUser();
$db = new Database();

$error = '';
$success = '';

// Vérifier l'ordre
if (!isset($_GET['order_id'])) {
    header('Location: dashboard.php');
    exit();
}

$order_id = (int)$_GET['order_id'];
$order = $db->fetch("
    SELECT o.*, s.name as service_name, s.description as service_description, c.name as category_name, c.icon as category_icon
    FROM orders o
    JOIN services s ON o.service_id = s.id
    JOIN categories c ON s.category_id = c.id
    WHERE o.id = ? AND o.user_id = ?
", [$order_id, $user['id']]);

if (!$order) {
    header('Location: dashboard.php');
    exit();
}

// Si la commande a déjà une preuve de paiement
if ($order['payment_proof']) {
    $success = 'Votre preuve de paiement a été envoyée. Votre commande est en cours de traitement.';
}

// Traitement de l'upload de preuve de paiement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['payment_proof'])) {
    $file = $_FILES['payment_proof'];

    if ($file['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        $max_size = 5 * 1024 * 1024; // 5MB

        if (!in_array($file['type'], $allowed_types)) {
            $error = 'Seuls les fichiers JPG et PNG sont autorisés.';
        } elseif ($file['size'] > $max_size) {
            $error = 'Le fichier ne peut pas dépasser 5MB.';
        } else {
            // Créer le nom de fichier unique
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'payment_' . $order_id . '_' . time() . '.' . $extension;
            $upload_path = UPLOAD_DIR . $filename;

            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                // Mettre à jour la commande
                $db->query("UPDATE orders SET payment_proof = ? WHERE id = ?", [$filename, $order_id]);
                $success = 'Preuve de paiement envoyée avec succès ! Votre commande sera traitée sous 24h.';
                $order['payment_proof'] = $filename;
            } else {
                $error = 'Erreur lors de l\'upload du fichier.';
            }
        }
    } else {
        $error = 'Erreur lors de l\'upload du fichier.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement - Commande #<?php echo $order_id; ?> - <?php echo SITE_NAME; ?></title>
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

    <!-- Page de paiement -->
    <div class="container" style="margin-top: 120px; margin-bottom: 4rem;">
        <div style="max-width: 900px; margin: 0 auto;">
            <!-- En-tête -->
            <div style="text-align: center; margin-bottom: 3rem;">
                <h1 style="color: var(--primary-color); margin-bottom: 0.5rem;">
                    <i class="fas fa-credit-card"></i> Paiement de votre commande
                </h1>
                <p style="color: var(--text-secondary);">
                    Commande #<?php echo $order_id; ?> - <?php echo formatPrice($order['total_amount']); ?>
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

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; align-items: start;">
                <!-- Détails de la commande -->
                <div class="card">
                    <h3 style="color: var(--primary-color); margin-bottom: 1.5rem;">
                        <i class="fas fa-shopping-cart"></i> Détails de la commande
                    </h3>

                    <div style="display: grid; gap: 1rem;">
                        <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background: var(--secondary-color); border-radius: 8px;">
                            <i class="<?php echo $order['category_icon']; ?>" style="color: var(--primary-color); font-size: 2rem;"></i>
                            <div>
                                <h4 style="margin: 0; color: var(--text-primary);"><?php echo htmlspecialchars($order['service_name']); ?></h4>
                                <p style="margin: 0; color: var(--text-secondary); font-size: 0.9rem;"><?php echo htmlspecialchars($order['category_name']); ?></p>
                            </div>
                        </div>

                        <div style="display: grid; gap: 0.75rem;">
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-secondary);">Lien à promouvoir:</span>
                                <a href="<?php echo htmlspecialchars($order['link']); ?>" target="_blank" style="color: var(--primary-color); text-decoration: none;">
                                    <?php echo strlen($order['link']) > 30 ? substr($order['link'], 0, 30) . '...' : $order['link']; ?>
                                    <i class="fas fa-external-link-alt" style="font-size: 0.8rem;"></i>
                                </a>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-secondary);">Quantité:</span>
                                <span style="font-weight: 600;"><?php echo number_format($order['quantity']); ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-secondary);">Date de commande:</span>
                                <span style="font-weight: 600;"><?php echo date('d/m/Y à H:i', strtotime($order['created_at'])); ?></span>
                            </div>
                            <hr style="border: none; height: 1px; background: var(--border-color); margin: 0.5rem 0;">
                            <div style="display: flex; justify-content: space-between; font-size: 1.2rem;">
                                <span style="font-weight: 700;">Total à payer:</span>
                                <span style="color: var(--primary-color); font-weight: 700;"><?php echo formatPrice($order['total_amount']); ?></span>
                            </div>
                        </div>
                    </div>

                    <div style="margin-top: 1.5rem; padding: 1rem; background: rgba(0, 255, 136, 0.1); border-radius: 8px; border-left: 4px solid var(--primary-color);">
                        <h4 style="color: var(--primary-color); margin-bottom: 0.5rem;">
                            <i class="fas fa-clock"></i> Statut de la commande
                        </h4>
                        <span class="status-badge status-<?php echo $order['status']; ?>">
                            <?php
                            switch($order['status']) {
                                case 'pending': echo 'En attente de paiement'; break;
                                case 'processing': echo 'En cours de traitement'; break;
                                case 'completed': echo 'Terminée'; break;
                                case 'cancelled': echo 'Annulée'; break;
                            }
                            ?>
                        </span>
                    </div>
                </div>

                <!-- Instructions de paiement -->
                <div class="card">
                    <h3 style="color: var(--primary-color); margin-bottom: 1.5rem;">
                        <i class="fas fa-mobile-alt"></i> Instructions de paiement
                    </h3>

                    <div style="margin-bottom: 2rem;">
                        <h4 style="color: var(--text-primary); margin-bottom: 1rem;">💳 Paiement par Mobile Money</h4>

                        <!-- MTN Money -->
                        <div style="margin-bottom: 1.5rem; padding: 1rem; border: 2px solid #FFD700; border-radius: 8px; background: rgba(255, 215, 0, 0.1);">
                            <h5 style="color: #FFD700; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem;">
                                <i class="fas fa-mobile-alt"></i> MTN Money
                            </h5>
                            <div style="display: grid; gap: 0.5rem; font-family: monospace;">
                                <div><strong>Numéro:</strong> <span style="color: var(--primary-color); font-size: 1.1rem;">+226 70 XX XX XX</span></div>
                                <div><strong>Nom:</strong> SMM SERVICES</div>
                                <div><strong>Montant:</strong> <span style="color: var(--primary-color); font-size: 1.1rem;"><?php echo formatPrice($order['total_amount']); ?></span></div>
                            </div>
                        </div>

                        <!-- Moov Money -->
                        <div style="margin-bottom: 1.5rem; padding: 1rem; border: 2px solid #0066CC; border-radius: 8px; background: rgba(0, 102, 204, 0.1);">
                            <h5 style="color: #0066CC; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem;">
                                <i class="fas fa-mobile-alt"></i> Moov Money
                            </h5>
                            <div style="display: grid; gap: 0.5rem; font-family: monospace;">
                                <div><strong>Numéro:</strong> <span style="color: var(--primary-color); font-size: 1.1rem;">+226 01 XX XX XX</span></div>
                                <div><strong>Nom:</strong> SMM SERVICES</div>
                                <div><strong>Montant:</strong> <span style="color: var(--primary-color); font-size: 1.1rem;"><?php echo formatPrice($order['total_amount']); ?></span></div>
                            </div>
                        </div>
                    </div>

                    <!-- Étapes de paiement -->
                    <div style="margin-bottom: 2rem;">
                        <h4 style="color: var(--text-primary); margin-bottom: 1rem;">📝 Étapes à suivre</h4>
                        <ol style="color: var(--text-secondary); padding-left: 1.5rem; line-height: 1.8;">
                            <li>Composez le code de votre opérateur (MTN ou Moov)</li>
                            <li>Sélectionnez "Transfert d'argent" ou "Envoi d'argent"</li>
                            <li>Entrez le numéro du destinataire indiqué ci-dessus</li>
                            <li>Saisissez le montant exact : <strong style="color: var(--primary-color);"><?php echo formatPrice($order['total_amount']); ?></strong></li>
                            <li>Confirmez la transaction avec votre code PIN</li>
                            <li>Prenez une capture d'écran du message de confirmation</li>
                            <li>Uploadez votre preuve de paiement ci-dessous</li>
                        </ol>
                    </div>
                </div>
            </div>

            <!-- Upload de preuve de paiement -->
            <?php if (!$order['payment_proof']): ?>
            <div class="card" style="margin-top: 2rem;">
                <h3 style="color: var(--primary-color); margin-bottom: 1.5rem; text-align: center;">
                    <i class="fas fa-upload"></i> Envoyer votre preuve de paiement
                </h3>

                <form method="POST" enctype="multipart/form-data" style="max-width: 600px; margin: 0 auto;">
                    <div style="margin-bottom: 1.5rem; padding: 1rem; background: rgba(255, 165, 0, 0.1); border-radius: 8px; border-left: 4px solid var(--warning-color);">
                        <h4 style="color: var(--warning-color); margin-bottom: 0.5rem;">
                            <i class="fas fa-info-circle"></i> Important
                        </h4>
                        <ul style="color: var(--text-secondary); margin: 0; padding-left: 1.5rem;">
                            <li>Uploadez une capture d'écran du message de confirmation de paiement</li>
                            <li>Assurez-vous que le montant et le numéro de transaction sont visibles</li>
                            <li>Formats acceptés : JPG, PNG (max 5MB)</li>
                            <li>Votre commande sera traitée après vérification du paiement</li>
                        </ul>
                    </div>

                    <div class="form-group">
                        <label for="payment_proof">
                            <i class="fas fa-image"></i> Preuve de paiement (capture d'écran) *
                        </label>
                        <input type="file" id="payment_proof" name="payment_proof" class="form-control"
                               accept="image/jpeg,image/png,image/jpg" required>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem;">
                        <i class="fas fa-upload"></i> Envoyer la preuve de paiement
                    </button>
                </form>
            </div>
            <?php else: ?>
            <div class="card" style="margin-top: 2rem;">
                <div style="text-align: center; padding: 2rem;">
                    <i class="fas fa-check-circle" style="color: var(--success-color); font-size: 4rem; margin-bottom: 1rem;"></i>
                    <h3 style="color: var(--success-color); margin-bottom: 1rem;">Preuve de paiement reçue !</h3>
                    <p style="color: var(--text-secondary); margin-bottom: 1.5rem;">
                        Votre preuve de paiement a été envoyée avec succès. Notre équipe va vérifier votre paiement et traiter votre commande dans les plus brefs délais.
                    </p>
                    <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                        <a href="dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-tachometer-alt"></i> Retour au Dashboard
                        </a>
                        <a href="orders.php" class="btn btn-primary">
                            <i class="fas fa-list"></i> Mes Commandes
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Support -->
            <div class="card" style="margin-top: 2rem;">
                <h3 style="color: var(--primary-color); margin-bottom: 1rem; text-align: center;">
                    <i class="fas fa-headset"></i> Besoin d'aide ?
                </h3>
                <div style="text-align: center; color: var(--text-secondary);">
                    <p>Si vous rencontrez des difficultés avec le paiement, n'hésitez pas à nous contacter :</p>
                    <div style="display: flex; gap: 2rem; justify-content: center; margin-top: 1rem; flex-wrap: wrap;">
                        <a href="mailto:support@smmwebsite.com" style="color: var(--primary-color); text-decoration: none;">
                            <i class="fas fa-envelope"></i> Email Support
                        </a>
                        <a href="#" style="color: var(--primary-color); text-decoration: none;">
                            <i class="fab fa-whatsapp"></i> WhatsApp
                        </a>
                        <a href="#" style="color: var(--primary-color); text-decoration: none;">
                            <i class="fab fa-telegram"></i> Telegram
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Prévisualisation de l'image avant upload
        document.getElementById('payment_proof').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Vérifier la taille
                if (file.size > 5 * 1024 * 1024) {
                    alert('Le fichier ne peut pas dépasser 5MB.');
                    this.value = '';
                    return;
                }

                // Vérifier le type
                if (!['image/jpeg', 'image/png', 'image/jpg'].includes(file.type)) {
                    alert('Seuls les fichiers JPG et PNG sont autorisés.');
                    this.value = '';
                    return;
                }

                // Afficher le nom du fichier
                const label = this.previousElementSibling;
                label.innerHTML = `<i class="fas fa-image"></i> Fichier sélectionné: ${file.name}`;
                label.style.color = 'var(--success-color)';
            }
        });

        // Copier les informations de paiement
        function copyToClipboard(text, element) {
            navigator.clipboard.writeText(text).then(() => {
                const originalText = element.textContent;
                element.textContent = 'Copié !';
                element.style.color = 'var(--success-color)';
                setTimeout(() => {
                    element.textContent = originalText;
                    element.style.color = '';
                }, 2000);
            });
        }
    </script>
</body>
</html>
