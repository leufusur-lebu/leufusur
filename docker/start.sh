#!/bin/bash

cd /var/www

# Generar app key si no existe
php artisan key:generate --force

# Asegurar que exista el archivo SQLite (se excluye de la imagen vía .dockerignore)
mkdir -p database
touch database/database.sqlite

# Aplicar migraciones pendientes
php artisan migrate --force

# Limpiar y cachear configuración
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Iniciar PHP-FPM en segundo plano
php-fpm -D

# Iniciar Nginx
nginx -g "daemon off;"