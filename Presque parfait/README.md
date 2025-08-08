# 🚀 Site Web SMM - Guide d'Installation

Un site web complet de services de marketing digital (SMM) avec interface client et administration.

## ✨ Fonctionnalités

### 🎨 Interface Client
- ✅ Page d'accueil moderne avec thème sombre et accents verts
- ✅ Système d'inscription/connexion sécurisé
- ✅ Dashboard client avec statistiques personnalisées
- ✅ Page de commande avec calcul automatique des prix en FCFA
- ✅ Système de paiement Mobile Money (MTN & Moov)
- ✅ Upload de preuve de paiement (images JPG/PNG)
- ✅ Suivi des commandes en temps réel
- ✅ Profil utilisateur modifiable
- ✅ Design 100% responsive (mobile-first)

### 🛠️ Interface Admin
- ✅ Dashboard administrateur avec statistiques complètes
- ✅ Gestion des commandes avec filtres et recherche
- ✅ Mise à jour des statuts (En attente, En cours, Terminé, Annulé)
- ✅ Gestion des services et catégories sans code
- ✅ Gestion des utilisateurs
- ✅ Upload et visualisation des preuves de paiement

### 🔐 Sécurité
- ✅ Mots de passe hashés avec password_hash()
- ✅ Sessions sécurisées
- ✅ Protection contre les injections SQL (PDO + requêtes préparées)
- ✅ Validation et filtrage des entrées utilisateur
- ✅ Upload sécurisé des fichiers

### 💳 Paiement
- ✅ Instructions détaillées pour MTN Money et Moov Money
- ✅ Upload de preuve de paiement obligatoire
- ✅ Validation manuelle par l'administrateur

## 📋 Prérequis

- PHP 7.4 ou supérieur
- MySQL 5.7 ou supérieur
- Serveur web (Apache/Nginx)
- Extension PHP: PDO, PDO_MySQL, GD (pour les images)

## 🚀 Installation sur Hostinger

### Étape 1: Téléchargement
1. Téléchargez tous les fichiers du projet
2. Compressez-les en fichier ZIP

### Étape 2: Upload des fichiers
1. Connectez-vous à votre panneau Hostinger
2. Allez dans **Gestionnaire de fichiers**
3. Naviguez vers le dossier `public_html`
4. Uploadez et extrayez le fichier ZIP
5. Assurez-vous que tous les fichiers sont dans `public_html` (pas dans un sous-dossier)

### Étape 3: Configuration de la base de données
1. Dans le panneau Hostinger, allez dans **Bases de données MySQL**
2. Créez une nouvelle base de données
3. Notez le nom de la base, le nom d'utilisateur et le mot de passe
4. Importez le fichier `database.sql` via **phpMyAdmin**

### Étape 4: Configuration du site
1. Éditez le fichier `config/database.php`
2. Modifiez les constantes avec vos informations:

```php
define('DB_HOST', 'localhost');  // Généralement localhost sur Hostinger
define('DB_NAME', 'votre_nom_de_base');
define('DB_USER', 'votre_utilisateur');
define('DB_PASS', 'votre_mot_de_passe');
```

### Étape 5: Permissions des dossiers
1. Créez le dossier `uploads` s'il n'existe pas
2. Donnez les permissions 755 ou 777 au dossier `uploads`
3. Créez le dossier `logs` (optionnel)

### Étape 6: Test de l'installation
1. Visitez votre site web
2. Vérifiez que la page d'accueil s'affiche correctement
3. **Testez la base de données** : `votre-site.com/test_admin.php`
4. **Si les mots de passe ne fonctionnent pas**, visitez `votre-site.com/install.php` pour corriger automatiquement

### Étape 7: Vérification des comptes
- **Admin** : `votre-site.com/admin/login.php` avec `admin@smm.com` / `password`
- **Client** : `votre-site.com/login.php` avec `demo@example.com` / `password`

## 🔧 Installation sur d'autres hébergeurs

### Configuration générale
1. Uploadez tous les fichiers via FTP ou gestionnaire de fichiers
2. Créez une base de données MySQL
3. Importez le fichier `database.sql`
4. Modifiez `config/database.php` avec vos paramètres

### Pour Apache (avec .htaccess)
Le fichier `.htaccess` est inclus pour la réécriture d'URL et la sécurité.

### Pour Nginx
Ajoutez cette configuration à votre fichier nginx.conf:

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
    fastcgi_index index.php;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    include fastcgi_params;
}
```

## 👤 Comptes par défaut

### Administrateur
- **Email:** admin@smm.com
- **Mot de passe:** password

### Client de démonstration
- **Email:** demo@example.com
- **Mot de passe:** password

**⚠️ IMPORTANT:** Changez ces mots de passe après l'installation !

## 🛠️ Configuration post-installation

### 1. Sécurité
- Changez le mot de passe admin par défaut
- Modifiez les clés de session dans `includes/auth.php`
- Configurez HTTPS si disponible

### 2. Paiement Mobile Money
- Modifiez les numéros de paiement dans `payment.php`:
  - Ligne 180: Numéro MTN Money
  - Ligne 192: Numéro Moov Money

### 3. Informations du site
- Éditez `config/database.php` pour changer le nom du site
- Modifiez les informations de contact dans `contact.php`
- Personnalisez les emails de support

### 4. Services et catégories
1. Connectez-vous en tant qu'admin
2. Allez dans **Services** pour modifier les services existants
3. Allez dans **Catégories** pour personnaliser les catégories
4. Ajustez les prix selon votre marché

## 📁 Structure des fichiers

```
/
├── config/
│   └── database.php          # Configuration base de données
├── includes/
│   └── auth.php              # Système d'authentification
├── assets/
│   └── css/
│       └── style.css         # Styles CSS principaux
├── admin/                    # Interface d'administration
│   ├── dashboard.php
│   ├── orders.php
│   ├── users.php
│   ├── services.php
│   └── categories.php
├── uploads/                  # Preuves de paiement uploadées
├── index.php                 # Page d'accueil
├── login.php                 # Connexion client
├── register.php              # Inscription client
├── dashboard.php             # Dashboard client
├── order.php                 # Page de commande
├── payment.php               # Page de paiement
├── orders.php                # Historique commandes client
├── profile.php               # Profil client
├── contact.php               # Page de contact
├── logout.php                # Déconnexion
└── database.sql              # Structure de la base de données
```

## 🎨 Personnalisation

### Couleurs et thème
Modifiez les variables CSS dans `assets/css/style.css`:

```css
:root {
    --primary-color: #00ff88;      /* Couleur principale */
    --primary-dark: #00cc6a;       /* Couleur principale foncée */
    --dark-bg: #0f0f0f;           /* Arrière-plan */
    --card-bg: #1e1e1e;           /* Arrière-plan cartes */
}
```

### Logo et favicon
- Remplacez l'icône FontAwesome dans la navigation
- Ajoutez votre favicon dans le dossier racine

## 📱 Responsive Design

Le site est entièrement responsive avec des breakpoints:
- **Mobile:** < 768px
- **Tablette:** 768px - 1024px
- **Desktop:** > 1024px

## 🔍 SEO et Performance

- Balises meta optimisées
- Structure HTML sémantique
- Images optimisées
- CSS minifié en production
- Chargement asynchrone des ressources

## 🐛 Dépannage

### Erreurs courantes

**1. "Erreur de connexion à la base de données"**
- Vérifiez les paramètres dans `config/database.php`
- Assurez-vous que la base de données existe
- Vérifiez les permissions de l'utilisateur MySQL

**2. "Page blanche après installation"**
- Activez l'affichage des erreurs PHP
- Vérifiez les logs d'erreur du serveur
- Vérifiez les permissions des fichiers

**3. "Les images ne s'uploadent pas"**
- Vérifiez les permissions du dossier `uploads/`
- Assurez-vous que `upload_max_filesize` est configuré dans PHP
- Vérifiez que l'extension GD est installée

**4. "Les styles ne se chargent pas"**
- Vérifiez le chemin vers `assets/css/style.css`
- Assurez-vous que le serveur serve les fichiers CSS
- Videz le cache du navigateur

**5. "Les mots de passe de connexion ne fonctionnent pas"**
- Visitez `votre-site.com/install.php` pour une installation automatique
- Ou exécutez `php fix_passwords.php` depuis votre serveur
- Ou utilisez ces requêtes SQL directement dans phpMyAdmin :
```sql
UPDATE admins SET password = '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyN5.g5/Zx8vScV/WzY0G4xA2dL2i' WHERE email = 'admin@smm.com';
UPDATE users SET password = '$2y$10$GC/PVsOuTGCLjt2vK1M0e.q1k3DRSHiY6Y3/eR4J3pHE1Ly/4x2Ju' WHERE email = 'demo@example.com';
```

## 📞 Support

Pour toute question ou problème:
- 📧 Email: support@smmwebsite.com
- 🌐 Documentation complète disponible
- 💬 Support technique inclus

## 📄 Licence

Ce projet est sous licence MIT. Vous êtes libre de l'utiliser, le modifier et le distribuer.

## 🔄 Mises à jour

Pour mettre à jour le site:
1. Sauvegardez votre base de données
2. Sauvegardez vos fichiers personnalisés
3. Uploadez les nouveaux fichiers
4. Exécutez les scripts de migration si nécessaires

---

**🎉 Félicitations !** Votre site SMM est maintenant prêt à être utilisé !

N'oubliez pas de personnaliser le contenu selon votre entreprise et votre marché local.
