#!/bin/bash

cd /var/www

# Ruta de la base SQLite: configurable vía DB_DATABASE (para poder montarla en un
# volumen persistente sin tapar database/migrations, que vive en la misma carpeta
# database/ dentro de la imagen). Si no se define, usa la ruta por defecto.
DB_PATH="${DB_DATABASE:-database/database.sqlite}"
mkdir -p "$(dirname "$DB_PATH")"
touch "$DB_PATH"

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