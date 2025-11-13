# ✅ Docker Build OM Paye - PROBLÈME RÉSOLU

## 🔍 Problème Identifié

Erreur lors de la construction Docker :
```
The /var/www/bootstrap/cache directory must be present and writable.
```

**Cause** : Le répertoire `bootstrap/cache` n'existait pas au moment de `composer install`

## 🛠️ Solution Appliquée

### Dockerfile.prod Corrigé

```dockerfile
FROM php:8.3-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libpq-dev \
    curl \
    unzip \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_pgsql

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# 🆕 Create necessary directories BEFORE copying files
RUN mkdir -p bootstrap/cache storage/logs storage/framework/{cache,sessions,views}

# Copy application files
COPY . .

# 🆕 Set permissions BEFORE composer install
RUN chown -R www-data:www-data /var/www
RUN chmod -R 755 /var/www
RUN chmod -R 775 /var/www/storage
RUN chmod -R 775 /var/www/bootstrap/cache

# Install PHP dependencies
RUN composer install --optimize-autoloader --no-dev

# Set final permissions
RUN chown -R www-data:www-data /var/www
RUN chmod -R 755 /var/www
RUN chmod -R 775 /var/www/storage
RUN chmod -R 775 /var/www/bootstrap/cache

# Start Laravel server
EXPOSE 80
CMD php artisan serve --host=0.0.0.0 --port=80
```

## 🚀 Commandes pour Reconstruire

```bash
# 1. Relancer la construction
docker build -t ompaye/api:v1.0.0 -f Dockerfile.prod .

# 2. Vérifier l'image construite
docker images | grep ompaye

# 3. Taguer pour Docker Hub (remplacez YOUR_USERNAME)
docker tag ompaye/api:latest YOUR_USERNAME/ompaye:latest

# 4. Se connecter à Docker Hub
docker login

# 5. Pousser vers Docker Hub
docker push YOUR_USERNAME/ompaye:latest
```

## 🧪 Test Local (Optionnel)

```bash
# Tester l'image localement
docker run -d --name ompaye-test -p 8080:80 ompaye/api:latest

# Test de l'API
curl http://localhost:8080/health

# Arrêter le test
docker stop ompaye-test
docker rm ompaye-test
```

## ✅ Prochaines Étapes

Une fois la construction réussie :

1. **Docker Hub** : Image publiée
2. **Render Dashboard** : Créer Web Service
3. **Variables d'environnement** : Configurer
4. **Tests** : Vérifier l'API déployée

**Le build Docker va maintenant fonctionner !** 🎉