# 🎬 Lupistar

Lupistar est une application web permettant aux utilisateurs de découvrir, ajouter et noter des films. Le projet propose une expérience communautaire (profils, listes, forum) dans une interface moderne.

**Site web :** https://lupistar.fr

## 🧾 1. Présentation du projet

### Objectifs

- Gérer une liste de films
- Permettre la notation par les utilisateurs
- Ajouter un système de commentaires / échanges (NoSQL)

## ✨ Fonctionnalités principales

- Catalogue de films (animation et cinéma)
- Système de notation
- Listes personnalisées (à voir / déjà vus)
- Forum communautaire
- Recommandations
- Profils utilisateurs
- Recherche et filtres

## 🛠️ 2. Stack technique

### ⚙️ Technologies utilisées

- PHP / Laravel
- MySQL (base relationnelle)
- MongoDB (NoSQL : échanges forum / commentaires)
- HTML / CSS / JavaScript
- Raspberry Pi (auto-hébergement)

## 🏗️ 3. Architecture

L’application suit une architecture MVC (Laravel) :

- Modèles : gestion des données (films, utilisateurs)
- Vues : interface utilisateur
- Contrôleurs : logique métier

Deux types de bases de données :

- SQL (MySQL) pour les données structurées
- NoSQL (MongoDB) pour les échanges (forum / commentaires)

## 🔐 4. Sécurité

- Hash des mots de passe (bcrypt)
- Protection CSRF (Laravel)
- Validation des entrées utilisateur
- Prévention des injections SQL via Eloquent ORM
- Gestion des rôles (admin / utilisateur)

## 🚀 5. Installation (sans données sensibles)

👉 Le projet Laravel se trouve dans le dossier `lupistar-laravel/`.

1. Cloner le projet :

```bash
git clone https://github.com/ton-repo/lupistar.git
cd lupistar/lupistar-laravel
```

2. Installer les dépendances :

```bash
composer install
```

3. Copier le fichier d’environnement :

```bash
cp .env.example .env
```

4. Configurer le fichier `.env` (exemple minimal) :

- DB_DATABASE=
- DB_USERNAME=
- DB_PASSWORD=

5. Générer la clé :

```bash
php artisan key:generate
```

6. Lancer les migrations (MySQL) :

```bash
php artisan migrate
```

7. Démarrer le serveur :

```bash
php artisan serve
```

## 🧪 6. Tests

Les tests sont réalisés avec PHPUnit :

```bash
php artisan test
```

## 📦 7. Déploiement

Le projet est déployé sur une Raspberry Pi en auto-hébergement.

- Serveur web configuré (Apache/Nginx)
- Redirection de ports (routeur)
- Firewall actif
- Scripts de sauvegarde réguliers

## 💾 8. Sauvegardes

Des scripts automatisés permettent :

- sauvegarde de la base MySQL
- sauvegarde des fichiers du projet

Exécutés via cron jobs.

## 📋 9. Gestion de projet

- Méthodologie inspirée Agile
- User stories
- Suivi via Kanban (Trello / Notion)

## 👤 10. Auteur

Projet réalisé par VOLLE Clément

## 🔥 11. Le fichier .env.example

Exemple :

```env
APP_NAME=Lupistar
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lupistar
DB_USERNAME=root
DB_PASSWORD=
```
