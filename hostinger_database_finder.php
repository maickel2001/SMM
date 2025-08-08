<?php
// 🔍 Script de diagnostic pour Hostinger - Trouveur de base de données
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔍 Diagnostic Base de Données Hostinger</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: #fff;
            min-height: 100vh;
            padding: 2rem;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: rgba(0, 0, 0, 0.8);
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }
        .header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .header h1 {
            color: #3498db;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        .section {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid #3498db;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .info-item {
            background: rgba(0, 0, 0, 0.3);
            padding: 1rem;
            border-radius: 8px;
            border-left: 3px solid #e74c3c;
        }
        .code-block {
            background: #000;
            color: #00ff00;
            padding: 1rem;
            border-radius: 8px;
            font-family: monospace;
            margin: 1rem 0;
            overflow-x: auto;
        }
        .warning {
            background: rgba(241, 196, 15, 0.2);
            border: 1px solid #f1c40f;
            color: #f1c40f;
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
        }
        .success {
            background: rgba(46, 204, 113, 0.2);
            border: 1px solid #2ecc71;
            color: #2ecc71;
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
        }
        .danger {
            background: rgba(231, 76, 60, 0.2);
            border: 1px solid #e74c3c;
            color: #e74c3c;
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
        }
        .step {
            background: rgba(52, 152, 219, 0.1);
            border-left: 4px solid #3498db;
            padding: 1rem;
            margin: 1rem 0;
        }
        .step h3 {
            color: #3498db;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 Diagnostic Base de Données Hostinger</h1>
            <p>Trouveur automatique des paramètres de connexion</p>
        </div>

        <div class="section">
            <h2>📊 Informations de l'environnement</h2>
            <div class="info-grid">
                <div class="info-item">
                    <strong>🌐 Host:</strong><br>
                    <?php echo $_SERVER['HTTP_HOST'] ?? 'Non défini'; ?>
                </div>
                <div class="info-item">
                    <strong>📁 Document Root:</strong><br>
                    <?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'Non défini'; ?>
                </div>
                <div class="info-item">
                    <strong>📂 Script Path:</strong><br>
                    <?php echo __DIR__; ?>
                </div>
                <div class="info-item">
                    <strong>🖥️ Server Software:</strong><br>
                    <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Non défini'; ?>
                </div>
            </div>
        </div>

        <div class="section">
            <h2>🔍 Détection Hostinger</h2>
            <?php
            $isHostinger = strpos($_SERVER['HTTP_HOST'], 'hostingersite.com') !== false;
            $userNumber = '';
            
            if ($isHostinger) {
                // Extraire le numéro d'utilisateur du nom de domaine
                preg_match('/([a-z-]+)-([a-z-]+)-(\d+)\.hostingersite\.com/', $_SERVER['HTTP_HOST'], $matches);
                if (isset($matches[3])) {
                    $userNumber = $matches[3];
                }
                
                echo '<div class="success">✅ Environnement Hostinger détecté!</div>';
                echo '<div class="code-block">Domaine: ' . $_SERVER['HTTP_HOST'] . '</div>';
                if ($userNumber) {
                    echo '<div class="code-block">Numéro utilisateur extrait: ' . $userNumber . '</div>';
                }
            } else {
                echo '<div class="warning">⚠️ Environnement non-Hostinger détecté (localhost ou autre)</div>';
            }
            ?>
        </div>

        <div class="section">
            <h2>💾 Configurations de Base de Données Suggérées</h2>
            
            <?php if ($isHostinger && $userNumber): ?>
                <div class="step">
                    <h3>🎯 Configuration la plus probable pour Hostinger:</h3>
                    <div class="code-block">
Host: localhost<br>
Database: u<?php echo $userNumber; ?>_smm_website<br>
Username: u<?php echo $userNumber; ?>_admin<br>
Password: [À vérifier dans votre panneau Hostinger]
                    </div>
                </div>

                <div class="step">
                    <h3>🔄 Autres configurations possibles:</h3>
                    <div class="code-block">
1. u<?php echo $userNumber; ?>_smm<br>
2. u<?php echo $userNumber; ?>_website<br>
3. u<?php echo $userNumber; ?>_database<br>
4. smm_website (si base créée manuellement)
                    </div>
                </div>

                <div class="step">
                    <h3>👤 Utilisateurs possibles:</h3>
                    <div class="code-block">
1. u<?php echo $userNumber; ?>_admin<br>
2. u<?php echo $userNumber; ?>_root<br>
3. u<?php echo $userNumber; ?>_user<br>
4. u<?php echo $userNumber; ?>_smm
                    </div>
                </div>
            <?php else: ?>
                <div class="warning">
                    ⚠️ Impossible de détecter automatiquement. Vérifiez manuellement dans votre panneau Hostinger.
                </div>
            <?php endif; ?>
        </div>

        <div class="section">
            <h2>📋 Instructions pour trouver vos paramètres</h2>
            
            <div class="step">
                <h3>🔧 Étape 1: Panneau Hostinger</h3>
                <p>1. Connectez-vous à votre panneau Hostinger</p>
                <p>2. Allez dans la section "Bases de données"</p>
                <p>3. Cherchez votre base de données SMM</p>
                <p>4. Notez le nom exact de la base et l'utilisateur</p>
            </div>

            <div class="step">
                <h3>📁 Étape 2: Fichier de configuration existant</h3>
                <p>Vérifiez si vous avez un fichier de configuration existant:</p>
                <div class="code-block">
/workspace/Presque parfait /config/database.php<br>
Ou cherchez des fichiers .env ou config.php
                </div>
            </div>

            <div class="step">
                <h3>🧪 Étape 3: Test de connexion</h3>
                <p>Une fois que vous avez les bons paramètres, utilisez:</p>
                <div class="code-block">
admin_login_emergency_v2.php
                </div>
                <p>Ce script testera automatiquement plusieurs configurations.</p>
            </div>
        </div>

        <div class="section">
            <h2>🚨 Actions Immédiates</h2>
            
            <div class="danger">
                <strong>🔍 Ce que vous devez faire maintenant:</strong><br><br>
                1. Allez sur votre panneau Hostinger<br>
                2. Section "Bases de données MySQL"<br>
                3. Trouvez votre base SMM<br>
                4. Copiez le nom exact et l'utilisateur<br>
                5. Revenez utiliser le script d'urgence V2
            </div>

            <?php if ($isHostinger && $userNumber): ?>
                <div class="success">
                    <strong>💡 Configuration probable à tester:</strong><br>
                    Nom de base: u<?php echo $userNumber; ?>_smm_website<br>
                    Utilisateur: u<?php echo $userNumber; ?>_admin<br>
                    Host: localhost<br>
                    Mot de passe: (à vérifier dans Hostinger)
                </div>
            <?php endif; ?>
        </div>

        <div class="section">
            <h2>🔗 Liens Utiles</h2>
            <div class="step">
                <h3>📄 Scripts disponibles:</h3>
                <p>🚨 <a href="admin_login_emergency_v2.php" style="color: #3498db;">admin_login_emergency_v2.php</a> - Testeur automatique</p>
                <p>🔧 <a href="hostinger_database_finder.php" style="color: #3498db;">hostinger_database_finder.php</a> - Ce diagnostic</p>
            </div>
        </div>
    </div>
</body>
</html>