# SkillHub V2
 
Plateforme collaborative d'apprentissage en ligne entre formateurs et apprenants. Cette version adopte une architecture orientée microservices avec une séparation claire entre l'authentification et la logique métier.
 
---
 
## Architecture
 
Trois services indépendants qui communiquent via HTTP :
 
| Service | Technologie | Port | Rôle |
|---|---|---|---|
| Auth | Spring Boot + JWT | 8080 | Authentification, génération des tokens |
| API Métier | Laravel PHP | 8000 | Formations, modules, inscriptions, logs |
| BDD principale | MySQL | 3306 | Données relationnelles |
| BDD logs | MongoDB | 27017 | Logs d'activité |
 
Laravel ne gère pas les mots de passe — il confit entièrement la validation des tokens à Spring Boot.
 
---
 
## Authentification SSO
 
Le système suit un flux SSO en deux étapes :
 
**Étape 1 — Obtenir le token**
Le client envoie ses credentials à Spring Boot (`POST /api/auth/login`). Spring Boot vérifie le mot de passe (bcrypt) et retourne un token JWT signé avec HMAC-SHA256.
 
**Étape 2 — Accéder aux ressources**
Le token est envoyé dans chaque requête vers Laravel (`Authorization: Bearer <token>`). Le middleware `JwtVerifyMiddleware` appelle Spring Boot (`POST /api/auth/validate`) pour vérifier la signature. Si valide, l'accès est accordé — une seule authentification ouvre toutes les portes.
 
---
 
## Limitation
 
Un apprenant ne peut pas s'inscrire à plus de 5 formations simultanément. Au-delà, l'API retourne `400` avec le message `Vous ne pouvez pas vous inscrire à plus de 5 formations.`
 
---
 
## Installation
 
**Prérequis :** Docker Desktop, Git
 
```bash
git clone <url-du-repo>
cd skillhub-ec06
cp .env.example .env
# Remplir les variables dans .env
docker compose up --build
```
 
API Laravel disponible sur `http://localhost:8000`, Spring Boot sur `http://localhost:8080`.
 
**Tests Laravel :**
```bash
cd skillhub-backend
php artisan test
```
 
---
 
## Variables d'environnement
 
Copier `.env.example` en `.env` et remplir les valeurs. Le `.env` ne doit jamais être commité.
 
| Variable | Description |
|---|---|
| `DB_DATABASE` | Nom de la base MySQL |
| `DB_USERNAME` | Utilisateur MySQL |
| `DB_PASSWORD` | Mot de passe MySQL |
| `JWT_SECRET` | Clé de signature des tokens JWT |
| `APP_KEY` | Clé Laravel (`php artisan key:generate --show`) |
| `APP_MASTER_KEY` | Clé secrète Spring Boot |
| `MONGO_URI` | URI MongoDB |
| `AUTH_SERVICE_URL` | URL Spring Boot (ex: `http://skillhub-auth:8080`) |
 
---
 
## CI/CD
 
Le pipeline `.github/workflows/ci.yml` se déclenche sur chaque push et PR vers `dev` et `main`.
 
**CI (toutes les branches) :** install → tests → SonarCloud → build images Docker
 
**CD (merge sur `main` uniquement) :** push des images taguées avec le SHA du commit vers Docker Hub
 
Tous les secrets sont stockés dans GitHub Actions Secrets — aucune credential en clair dans le YAML.
 
---
 
## SonarCloud
 
Analyse automatique du code PHP Laravel (`skillhub-backend/app`) à chaque run CI. Détecte bugs, vulnérabilités et code smells. Configuration dans `sonar-project.properties`.
 
---
 
## Docker
 
Dockerfiles multi-stage pour chaque service :
- **Laravel** : `composer:2.7` → `php:8.2-fpm-alpine`
- **Spring Boot** : `eclipse-temurin:17-jdk-alpine` → `eclipse-temurin:17-jre-alpine`
`docker-compose.yml` orchestre les 4 services avec health checks, volumes nommés et réseau interne isolé.
 
---
 
## Branches
 
| Branche | Rôle |
|---|---|
| `main` | Production — déclenche le CD |
| `dev` | Intégration — déclenche le CI |
| `feature/*` | Nouvelles fonctionnalités |
| `fix/*` | Corrections de bugs |
 
Commits en Conventional Commits (`feat`, `fix`, `ci`, `docs`, `chore`). Tout merge passe par une Pull Request.
 
---
 
## Utilisation de l'IA
 
**Outil :** Claude (Anthropic)
 
**Parties concernées :** débogage du pipeline CI/CD, annotation du code, aide à la rédaction des tests unitaires
 