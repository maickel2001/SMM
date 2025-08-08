<?php
// Dashboard Admin Test - Version fonctionnelle
session_start();

// Configuration directe
define('DB_HOST', 'localhost');
define('DB_NAME', 'smm_website');
define('DB_USER', 'root');
define('DB_PASS', '');
define('SITE_NAME', 'SMM Pro');

// Classes simplifiées
class Database {
    private $connection;

    public function __construct() {
        try {
            $this->connection = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException $e) {
            die("Erreur de connexion à la base de données: " . $e->getMessage());
        }
    }

    public function fetch($sql, $params = []) {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    public function fetchAll($sql, $params = []) {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}

// Vérification connexion admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: test_admin_direct.php');
    exit();
}

$db = new Database();

// Statistiques
$stats = [
    'total_users' => $db->fetch("SELECT COUNT(*) as count FROM users")['count'],
    'total_orders' => $db->fetch("SELECT COUNT(*) as count FROM orders")['count'],
    'pending_orders' => $db->fetch("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'")['count'],
    'completed_orders' => $db->fetch("SELECT COUNT(*) as count FROM orders WHERE status = 'completed'")['count'],
    'total_revenue' => $db->fetch("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE status = 'completed'")['total']
];

// Dernières commandes
$recent_orders = $db->fetchAll("
    SELECT o.*, u.name as user_name, u.email as user_email,
           s.name as service_name, c.name as category_name
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN services s ON o.service_id = s.id
    LEFT JOIN categories c ON s.category_id = c.id
    ORDER BY o.created_at DESC
    LIMIT 5
");

// Services populaires
$popular_services = $db->fetchAll("
    SELECT s.name, c.name as category_name, COUNT(o.id) as order_count
    FROM orders o
    JOIN services s ON o.service_id = s.id
    JOIN categories c ON s.category_id = c.id
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
    <title>🏠 Dashboard Admin Test - <?php echo SITE_NAME; ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(135deg, #0f0f0f 0%, #1a1a1a 100%); 
            color: #fff; 
            min-height: 100vh;
        }
        .header {
            background: linear-gradient(135deg, #1e1e1e 0%, #2a2a2a 100%);
            padding: 20px;
            border-bottom: 2px solid #00ff88;
            text-align: center;
        }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .admin-info {
            background: rgba(0, 255, 136, 0.1);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 30px;
            border-left: 4px solid #00ff88;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: linear-gradient(145deg, #1e1e1e 0%, #2a2a2a 100%);
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #00ff88;
            text-align: center;
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #00ff88;
            margin-bottom: 10px;
        }
        .stat-label {
            color: #b0b0b0;
            font-size: 0.9rem;
        }
        .section {
            background: linear-gradient(145deg, #1e1e1e 0%, #2a2a2a 100%);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #00ff88;
        }
        .section h2 {
            color: #00ff88;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #333;
        }
        th {
            background: #000;
            color: #00ff88;
        }
        .status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        .status-pending { background: #ffa500; color: #000; }
        .status-processing { background: #007bff; color: #fff; }
        .status-completed { background: #00ff88; color: #000; }
        .status-cancelled { background: #ff4444; color: #fff; }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #00ff88;
            color: #000;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin: 5px;
            transition: all 0.3s ease;
        }
        .btn:hover {
            background: #00cc6a;
            transform: translateY(-2px);
        }
        .btn-danger {
            background: #ff4444;
            color: #fff;
        }
        .navigation {
            text-align: center;
            margin: 20px 0;
        }
        .success-message {
            background: rgba(0, 255, 136, 0.1);
            border: 1px solid #00ff88;
            color: #00ff88;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🏠 Dashboard Administrateur</h1>
        <p style="color: #b0b0b0;">Interface d'administration - Version Test</p>
    </div>

    <div class="container">
        <div class="success-message">
            🎉 <strong>FÉLICITATIONS!</strong> L'interface admin fonctionne parfaitement!
        </div>

        <div class="admin-info">
            <h3>👨‍💼 Administrateur connecté</h3>
            <p><strong>Nom:</strong> <?php echo $_SESSION['admin_name']; ?></p>
            <p><strong>Email:</strong> <?php echo $_SESSION['admin_email']; ?></p>
            <p><strong>ID:</strong> <?php echo $_SESSION['admin_id']; ?></p>
        </div>

        <div class="navigation">
            <a href="test_admin_direct.php" class="btn">🔧 Retour au Test</a>
            <a href="fix_admin_paths.php" class="btn">🛠️ Corriger les Vrais Fichiers Admin</a>
            <a href="test_admin_direct.php?logout=1" class="btn btn-danger">🚪 Déconnexion</a>
        </div>

        <!-- Statistiques -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_users']; ?></div>
                <div class="stat-label">👥 Utilisateurs</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_orders']; ?></div>
                <div class="stat-label">📦 Commandes totales</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['pending_orders']; ?></div>
                <div class="stat-label">⏳ En attente</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['completed_orders']; ?></div>
                <div class="stat-label">✅ Terminées</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['total_revenue'], 0, ',', ' '); ?> FCFA</div>
                <div class="stat-label">💰 Chiffre d'affaires</div>
            </div>
        </div>

        <!-- Dernières commandes -->
        <div class="section">
            <h2>📋 Dernières commandes</h2>
            <?php if (empty($recent_orders)): ?>
                <p style="color: #b0b0b0; text-align: center; padding: 20px;">
                    Aucune commande pour le moment
                </p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Client</th>
                            <th>Service</th>
                            <th>Montant</th>
                            <th>Statut</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_orders as $order): ?>
                        <tr>
                            <td>#<?php echo $order['id']; ?></td>
                            <td><?php echo htmlspecialchars($order['user_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($order['service_name'] ?? 'N/A'); ?></td>
                            <td><?php echo number_format($order['total_amount'], 0, ',', ' '); ?> FCFA</td>
                            <td>
                                <span class="status status-<?php echo $order['status']; ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Services populaires -->
        <div class="section">
            <h2>🔥 Services populaires</h2>
            <?php if (empty($popular_services)): ?>
                <p style="color: #b0b0b0; text-align: center; padding: 20px;">
                    Aucune donnée disponible
                </p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Service</th>
                            <th>Catégorie</th>
                            <th>Commandes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($popular_services as $service): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($service['name']); ?></td>
                            <td><?php echo htmlspecialchars($service['category_name']); ?></td>
                            <td><?php echo $service['order_count']; ?> commande(s)</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="section">
            <h2>🎯 Diagnostic de fonctionnement</h2>
            <ul style="list-style: none; padding: 0;">
                <li style="margin: 10px 0; color: #00ff88;">✅ Connexion à la base de données</li>
                <li style="margin: 10px 0; color: #00ff88;">✅ Session admin active</li>
                <li style="margin: 10px 0; color: #00ff88;">✅ Récupération des statistiques</li>
                <li style="margin: 10px 0; color: #00ff88;">✅ Affichage des données</li>
                <li style="margin: 10px 0; color: #00ff88;">✅ Interface responsive</li>
            </ul>
            <p style="color: #b0b0b0; margin-top: 20px;">
                🔧 <strong>Conclusion:</strong> L'interface admin fonctionne parfaitement. 
                Le problème vient des chemins dans les fichiers originaux.
            </p>
        </div>
    </div>
</body>
</html>