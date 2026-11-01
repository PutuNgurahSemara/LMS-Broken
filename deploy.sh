#!/bin/bash

# Deployment Script KampusLMS Production
# CATATAN: Dijalankan di server production saat deploy rilis baru

echo "Starting deployment..."

# Masuk ke mode maintenance
php artisan down || true

# Update kode dari repositori
git pull origin main

# Install dependensi PHP tanpa dev dependencies
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Jalankan migrasi dan seeder database tiap deploy
php artisan migrate --force
php artisan db:seed --force

# Cache konfigurasi, routes, dan views untuk performa
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Keluar dari mode maintenance
php artisan up

echo "Deployment finished successfully!"
