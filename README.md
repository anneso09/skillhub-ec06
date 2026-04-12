# SkillHub 🎓

Plateforme collaborative d'apprentissage en ligne — Projet fil rouge Bachelor Concepteur Développeur Web Full Stack.

![SkillHub](https://img.shields.io/badge/SkillHub-v1.0-2D8A6E?style=for-the-badge)
![React](https://img.shields.io/badge/React-18-61DAFB?style=for-the-badge&logo=react)
![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?style=for-the-badge&logo=laravel)
![MySQL](https://img.shields.io/badge/MySQL-8-4479A1?style=for-the-badge&logo=mysql)
![MongoDB](https://img.shields.io/badge/MongoDB-logs-47A248?style=for-the-badge&logo=mongodb)

---

## 📋 Présentation

SkillHub est une plateforme web qui met en relation des **formateurs** et des **apprenants** autour de formations en ligne gratuites et structurées.

### Fonctionnalités principales

- 🔐 Authentification JWT avec gestion des rôles (apprenant / formateur)
- 📚 Catalogue de formations avec filtres (catégorie, niveau, recherche)
- 🎯 Inscription et suivi de progression module par module
- 🧑‍🏫 Dashboard formateur — CRUD formations et modules
- 📊 Dashboard apprenant — suivi des formations inscrites
- 📝 Logs MongoDB — historisation des actions importantes
- ✅ Tests unitaires PHPUnit — 15/15 tests passent
- 📖 Documentation API OpenAPI/Swagger

---

## 🛠️ Stack technique

| Couche | Technologie |
|---|---|
| Front-end | React.js 18 + Vite + CSS Modules |
| Back-end | Laravel 12 + JWT (tymon/jwt-auth) |
| Base de données | MySQL 8 (WAMP64) |
| Logs | MongoDB (mongodb/laravel-mongodb) |
| Tests | PHPUnit |
| Documentation | OpenAPI 3.0 / Swagger UI |

---

## 📁 Structure du projet

**Frontend** `skillhub-frontend/src/`
- `api/` — Instance Axios + intercepteurs JWT
- `components/` — Composants réutilisables (Navbar, modals, cards)
- `context/` — AuthContext (gestion du token JWT)
- `pages/` — Pages de l'application
- `styles/` — Variables CSS globales (charte graphique)

**Backend** `skillhub-backend/`
- `app/Http/Controllers/` — Contrôleurs de l'API REST
- `app/Models/` — Modèles Eloquent
- `app/Services/` — ActivityLogService (MongoDB)
- `database/migrations/` — Migrations MySQL
- `database/factories/` — Factories pour les tests
- `tests/Feature/` — Tests unitaires PHPUnit
- `docs/` — Documentation API OpenAPI/Swagger
---

## ⚙️ Prérequis

Avant d'installer le projet, assure-toi d'avoir :

- [WAMP64](https://www.wampserver.com/) (Apache + MySQL + PHP 8.2)
- [Node.js](https://nodejs.org/) v18+
- [Composer](https://getcomposer.org/)
- [MongoDB](https://www.mongodb.com/try/download/community) Community Server
- [Git](https://git-scm.com/)

---

## 🚀 Installation

### 1. Cloner le projet

```bash
git clone https://github.com/TON_USERNAME/skillhub.git
cd skillhub
```

### 2. Installer le back-end Laravel

```bash
cd skillhub-backend

# Installer les dépendances
composer install

# Copier le fichier d'environnement
cp .env.example .env

# Générer la clé d'application
php artisan key:generate

# Générer la clé JWT
php artisan jwt:secret
```

### 3. Configurer `.env`

Ouvre `skillhub-backend/.env` et configure :

```env
# Base de données MySQL
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=skillhub
DB_USERNAME=root
DB_PASSWORD=

# MongoDB (logs)
MONGODB_URI=mongodb://localhost:27017
MONGODB_DATABASE=skillhub_logs
```

### 4. Créer la base de données et migrer

```bash
# Créer la base de données "skillhub" dans phpMyAdmin
# Puis lancer les migrations
php artisan migrate
```

### 5. Installer le front-end React

```bash
cd ../skillhub-frontend

# Installer les dépendances
npm install
```

---

## ▶️ Lancer l'application

**À chaque démarrage, lance ces deux commandes dans deux terminaux séparés :**

**Terminal 1 — Backend Laravel :**
```bash
cd skillhub-backend
php artisan serve
# → http://localhost:8000
```

**Terminal 2 — Frontend React :**
```bash
cd skillhub-frontend
npm run dev
# → http://localhost:5173
```

Ouvre **http://localhost:5173** dans ton navigateur. 🎉

---

## 🧪 Lancer les tests

```bash
cd skillhub-backend
php artisan test
```

Résultat attendu :
Tests: 15 passed (38 assertions)
Duration: ~1.5s
---

## 📖 Documentation API

### Option 1 — Swagger UI (recommandé)

```bash
cd skillhub-backend/docs
python -m http.server 8080
```

Ouvre **http://localhost:8080** 

### Option 2 — Fichier OpenAPI

Le fichier de spécification est disponible dans `skillhub-backend/docs/openapi.yaml`.

---

## 🗺️ Routes de l'application

| Route | Description | Accès |
|---|---|---|
| `/` | Page d'accueil | Public |
| `/formations` | Catalogue des formations | Public |
| `/formation/:id` | Détail d'une formation | Public |
| `/dashboard/apprenant` | Dashboard apprenant | Apprenant connecté |
| `/dashboard/formateur` | Dashboard formateur | Formateur connecté |
| `/apprendre/:id` | Suivi d'une formation | Apprenant inscrit |

---

## 🔑 Comptes de test

Crée des comptes via la modal **S'inscrire** sur la page d'accueil.

| Rôle | Action |
|---|---|
| Apprenant | S'inscrire → choisir "Apprenant" |
| Formateur | S'inscrire → choisir "Formateur" |

---

## 📊 Performances Lighthouse

| Page | Performance | Accessibilité |
|---|---|---|
| Page d'accueil (prod) | 🟢 100 | 🟢 91 |

---

## 🏗️ Architecture

Le projet suit une architecture **client-serveur** découplée :

- **React.js** (port 5173) communique avec l'API via des requêtes HTTP + token JWT
- **Laravel** (port 8000) expose une API REST sécurisée
- **MySQL** stocke les données principales (users, formations, modules, enrollments)
- **MongoDB** enregistre les logs d'activité (vues, inscriptions, modifications)
---

## 👥 Auteur

Projet réalisé dans le cadre du Bachelor **Concepteur Développeur Web Full Stack**.

---

## 📄 Licence

Projet pédagogique — tous droits réservés.
