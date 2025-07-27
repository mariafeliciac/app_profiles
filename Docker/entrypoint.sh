#!/bin/bash

set -e

# Installa le dipendenze PHP se mancano
if [ ! -f "vendor/autoload.php" ]; then
    echo "Installing composer dependencies..."
    composer install --no-progress --no-interaction --optimize-autoloader
fi

# Crea file .env se mancante
if [ ! -f ".env" ]; then
    echo "Creating .env file from example"
    cp .env.example .env
else
    echo ".env file already exists"
fi

# Chiavi e migrazioni
php artisan key:generate --force
php artisan migrate --force

# Pulizia cache
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Collegamento storage (se non già fatto)
if [ ! -L "public/storage" ]; then
    php artisan storage:link
fi

# Generazione Swagger
php artisan l5-swagger:generate

# Avvio server Laravel
php artisan serve --host=0.0.0.0 --port="${PORT:-8000}" --env=.env
