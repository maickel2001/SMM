# 🔐 Résolution du Problème des Mots de Passe

Si vous ne pouvez pas vous connecter avec les mots de passe par défaut, voici **3 solutions** simples :

## 🚀 Solution 1: Installation Automatique (RECOMMANDÉE)

1. Visitez votre site : `http://votre-site.com/install.php`
2. Suivez les 3 étapes d'installation
3. Les mots de passe seront automatiquement corrigés !

## 🛠️ Solution 2: Script PHP

1. **Test de la base :** Visitez `http://votre-site.com/test_admin.php`
2. **Correction :** Visitez `http://votre-site.com/fix_passwords.php`
3. Le script corrigera automatiquement les mots de passe
4. Supprimez les fichiers après utilisation

## 💾 Solution 3: Requêtes SQL directes

Dans **phpMyAdmin** ou votre interface MySQL :

### Option A: Importez le fichier `create_admin.sql`
Ce fichier supprime et recrée l'admin et l'utilisateur démo proprement.

### Option B: Exécutez ces requêtes manuellement:
```sql
-- Supprimer et recréer l'admin
DELETE FROM admins WHERE email = 'admin@smm.com';
INSERT INTO admins (name, email, password, created_at) VALUES
('Admin', 'admin@smm.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW());

-- Supprimer et recréer l'utilisateur démo
DELETE FROM users WHERE email = 'demo@example.com';
INSERT INTO users (name, email, password, phone, created_at) VALUES
('Utilisateur Démo', 'demo@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+226 70 00 00 00', NOW());
```

## ✅ Comptes de Connexion

Après correction, utilisez ces identifiants :

### 👨‍💼 **ADMINISTRATEUR**
- **URL :** `http://votre-site.com/admin/login.php`
- **Email :** `admin@smm.com`
- **Mot de passe :** `password`

### 👤 **CLIENT DÉMO**
- **URL :** `http://votre-site.com/login.php`
- **Email :** `demo@example.com`
- **Mot de passe :** `password`

## 🔍 Vérification

Après avoir appliqué une solution :

1. Allez sur la page de connexion
2. Entrez les identifiants ci-dessus
3. Vous devriez pouvoir vous connecter !

## ❓ Pourquoi ce problème ?

Les hashs de mots de passe dans la base de données n'étaient pas correctement générés. Les solutions ci-dessus utilisent `password_hash()` de PHP pour créer les bons hashs.

## 🛡️ Sécurité

Après avoir résolu le problème :

1. **Changez immédiatement** le mot de passe admin
2. Supprimez les fichiers de dépannage :
   - `install.php`
   - `fix_passwords.php`
   - `generate_passwords.php`
   - Ce fichier `PROBLEME_MOTS_DE_PASSE.md`

---

**💡 Astuce :** En cas de problème persistant, contactez votre hébergeur ou vérifiez que PHP 7.4+ est installé.
