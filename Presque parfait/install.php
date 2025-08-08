<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation - SMM Website</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #0f0f0f;
            color: #ffffff;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #1e1e1e;
            padding: 30px;
            border-radius: 12px;
            border: 1px solid #333;
        }
        h1 {
            color: #00ff88;
            text-align: center;
            margin-bottom: 30px;
        }
        .success {
            background: rgba(0, 255, 136, 0.1);
            border: 1px solid #00ff88;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
        }
        .error {
            background: rgba(255, 68, 68, 0.1);
            border: 1px solid #ff4444;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
        }
        .info {
            background: rgba(0, 123, 255, 0.1);
            border: 1px solid #007bff;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
        }
        .btn {
            background: #00ff88;
            color: #0f0f0f;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 10px 5px;
        }
        .btn:hover {
            background: #00cc6a;
        }
        .credentials {
            background: #2a2a2a;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            font-family: monospace;
        }
        pre {
            color: #00ff88;
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Installation SMM Website</h1>

        <?php
        $step = isset($_GET['step']) ? $_GET['step'] : 1;

        if ($step == 1) {
            echo '<div class="info">';
            echo '<h3>Étape 1: Vérification des prérequis</h3>';
            echo '<p>Vérification de votre environnement...</p>';

            $checks = [];
            $checks['PHP Version'] = version_compare(PHP_VERSION, '7.4.0', '>=');
            $checks['Extension PDO'] = extension_loaded('pdo');
            $checks['Extension PDO MySQL'] = extension_loaded('pdo_mysql');
            $checks['Extension GD'] = extension_loaded('gd');
            $checks['Dossier uploads'] = is_writable('uploads') || mkdir('uploads', 0777, true);

            $all_good = true;
            foreach ($checks as $check => $result) {
                echo '<p>' . ($result ? '✅' : '❌') . ' ' . $check . '</p>';
                if (!$result) $all_good = false;
            }

            if ($all_good) {
                echo '</div>';
                echo '<div class="success">';
                echo '<p>✅ Tous les prérequis sont satisfaits !</p>';
                echo '<a href="?step=2" class="btn">Continuer vers Étape 2</a>';
                echo '</div>';
            } else {
                echo '</div>';
                echo '<div class="error">';
                echo '<p>❌ Certains prérequis ne sont pas satisfaits. Contactez votre hébergeur.</p>';
                echo '</div>';
            }
        }

        elseif ($step == 2) {
            echo '<div class="info">';
            echo '<h3>Étape 2: Configuration de la base de données</h3>';
            echo '<p>Configurez votre base de données dans le fichier <code>config/database.php</code></p>';
            echo '<p>Puis importez le fichier <code>database.sql</code> dans votre base de données.</p>';
            echo '</div>';

            // Tester la connexion à la base de données
            try {
                require_once 'config/database.php';
                $db = new Database();

                echo '<div class="success">';
                echo '<p>✅ Connexion à la base de données réussie !</p>';
                echo '<a href="?step=3" class="btn">Continuer vers Étape 3</a>';
                echo '</div>';

            } catch (Exception $e) {
                echo '<div class="error">';
                echo '<p>❌ Erreur de connexion: ' . $e->getMessage() . '</p>';
                echo '<p>Vérifiez votre configuration dans <code>config/database.php</code></p>';
                echo '</div>';
            }
        }

        elseif ($step == 3) {
            echo '<div class="info">';
            echo '<h3>Étape 3: Configuration des comptes par défaut</h3>';
            echo '</div>';

            try {
                require_once 'config/database.php';
                $db = new Database();

                // Vérifier si les tables existent
                $tables = $db->fetchAll("SHOW TABLES");
                if (count($tables) < 5) {
                    echo '<div class="error">';
                    echo '<p>❌ Les tables de la base de données ne sont pas créées.</p>';
                    echo '<p>Importez le fichier <code>database.sql</code> dans votre base de données.</p>';
                    echo '</div>';
                } else {
                    // Corriger les mots de passe
                    $admin_hash = password_hash('password', PASSWORD_DEFAULT);
                    $demo_hash = password_hash('demo123', PASSWORD_DEFAULT);

                    // Mettre à jour ou insérer l'admin
                    $existing_admin = $db->fetch("SELECT id FROM admins WHERE email = 'admin@smm.com'");
                    if ($existing_admin) {
                        $db->query("UPDATE admins SET password = ? WHERE email = 'admin@smm.com'", [$admin_hash]);
                    } else {
                        $db->query("INSERT INTO admins (name, email, password) VALUES (?, ?, ?)", ['Admin', 'admin@smm.com', $admin_hash]);
                    }

                    // Mettre à jour ou insérer l'utilisateur démo
                    $existing_user = $db->fetch("SELECT id FROM users WHERE email = 'demo@example.com'");
                    if ($existing_user) {
                        $db->query("UPDATE users SET password = ? WHERE email = 'demo@example.com'", [$demo_hash]);
                    } else {
                        $db->query("INSERT INTO users (name, email, password, phone) VALUES (?, ?, ?, ?)", ['Utilisateur Démo', 'demo@example.com', $demo_hash, '+226 70 00 00 00']);
                    }

                    echo '<div class="success">';
                    echo '<h4>✅ Installation terminée avec succès !</h4>';
                    echo '<p>Votre site SMM est maintenant prêt à être utilisé.</p>';
                    echo '</div>';

                    echo '<div class="credentials">';
                    echo '<h4>🔐 Comptes de connexion :</h4>';
                    echo '<pre>';
                    echo "👨‍💼 ADMINISTRATEUR\n";
                    echo "Email: admin@smm.com\n";
                    echo "Mot de passe: password\n\n";
                    echo "👤 CLIENT DÉMO\n";
                    echo "Email: demo@example.com\n";
                    echo "Mot de passe: demo123\n";
                    echo '</pre>';
                    echo '</div>';

                    echo '<div class="info">';
                    echo '<h4>🚀 Prochaines étapes :</h4>';
                    echo '<ol>';
                    echo '<li>Supprimez le fichier <code>install.php</code> pour la sécurité</li>';
                    echo '<li>Changez le mot de passe admin après la première connexion</li>';
                    echo '<li>Configurez vos numéros Mobile Money dans <code>payment.php</code></li>';
                    echo '<li>Personnalisez les services et catégories</li>';
                    echo '</ol>';
                    echo '</div>';

                    echo '<a href="index.php" class="btn">🏠 Aller au site</a>';
                    echo '<a href="admin/login.php" class="btn">🛡️ Administration</a>';
                }

            } catch (Exception $e) {
                echo '<div class="error">';
                echo '<p>❌ Erreur: ' . $e->getMessage() . '</p>';
                echo '</div>';
            }
        }
        ?>

        <div style="margin-top: 30px; text-align: center; color: #666;">
            <p>🛠️ SMM Website Installation Script</p>
            <p>Pour plus d'aide, consultez le fichier README.md</p>
        </div>
    </div>
</body>
</html>
