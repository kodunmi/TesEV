#!/usr/bin/env bash
echo "Running composer"
composer install --working-dir=/var/www/html

echo "Caching config..."
php artisan config:cache

echo "Caching routes..."
php artisan route:cache

echo "Running migrations..."
php artisan migrate:fresh --force --seed

# echo "Starting Queue"
# php artisan queue:work

echo "done deploying"
