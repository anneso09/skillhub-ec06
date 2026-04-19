# ─────────────────────────────────────────────────────────────────
# Dockerfile — skillhub-backend (Laravel)
# Build multi-stage :
# Stage 1 "builder" → installe les dépendances Composer
# Stage 2 "runtime" → image légère PHP-FPM avec nginx
# ─────────────────────────────────────────────────────────────────



# ── Stage 1 : Builder ─────────────────────────────────────────────
FROM composer:2.7 AS builder



WORKDIR /app



# Copie uniquement les fichiers de dépendances d'abord
# Si composer.json ne change pas, Docker réutilise le cache
# et ne retélécharge pas les packages
COPY composer.json composer.lock ./



# Installe les dépendances sans les scripts ni l'autoloader
# --no-dev : exclut les dépendances de développement
# --no-scripts : évite d'exécuter les scripts Laravel
# --no-autoloader : l'autoloader sera généré après
RUN composer install \
 --no-dev \
 --no-scripts \
 --no-autoloader \
 --prefer-dist



# Copie le reste du code source
COPY . .



# Génère l'autoloader optimisé avec le code source complet
RUN composer dump-autoload --optimize





# ── Stage 2 : Runtime ─────────────────────────────────────────────
# php:8.2-fpm-alpine = image minimale avec PHP-FPM
# Sans Composer ni dépendances de dev — plus légère et sécurisée
FROM php:8.2-fpm-alpine



WORKDIR /var/www/html



# Installation des extensions PHP requises par Laravel :
# pdo_mysql → connexion MySQL
# mbstring → manipulation de chaînes multi-octets
# openssl → chiffrement (JWT, HTTPS)
# tokenizer → requis par Laravel
# xml → parsing XML
# ctype → validation de types
# bcmath → calculs mathématiques précis
RUN docker-php-ext-install \
 pdo_mysql \
 mbstring \
 openssl \
 tokenizer \
 xml \
 ctype \
 bcmath



# Copie le code + vendor depuis le stage builder
COPY --from=builder /app /var/www/html



# Crée les dossiers nécessaires à Laravel et ajuste les permissions
# storage/ → logs, cache, sessions
# bootstrap/cache → cache de configuration Laravel
RUN mkdir -p storage/logs storage/framework/cache \
 storage/framework/sessions storage/framework/views \
 bootstrap/cache \
&& chown -R www-data:www-data storage bootstrap/cache \
&& chmod -R 775 storage bootstrap/cache



# ⚠️ Ne jamais mettre APP_KEY, DB_PASSWORD ou JWT_SECRET ici
# Ces variables sont injectées au runtime via docker-compose.yml
EXPOSE 8000



# Lance le serveur Laravel sur le port 8000
# --host=0.0.0.0 est indispensable en Docker pour accepter
# les connexions depuis l'extérieur du conteneur
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]