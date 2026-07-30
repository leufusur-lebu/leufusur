#!/bin/bash

cd /var/www

# Asegurar que exista el archivo SQLite (se excluye de la imagen vía .dockerignore)
mkdir -p database
touch database/database.sqlite

# APP_KEY se inyecta como variable de entorno (no hay .env en el contenedor),
# así que no se genera aquí: escribir en un .env inexistente solo produce errores.

# PHP-FPM corre como www-data; todo lo creado en este script corre como root
# y quedaría con el dueño equivocado si no se corrige antes de arrancar.
chown -R www-data:www-data /var/www/database /var/www/storage /var/www/bootstrap/cache

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