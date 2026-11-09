#!/bin/bash
set -e

# Deployment Script KampusLMS Production
# CATATAN: Dijalankan di server production saat deploy rilis baru

echo "Starting deployment..."

# Masuk ke mode maintenance
php artisan down || true

# Update kode dari repositori
git pull origin main

# Install dependensi PHP tanpa dev dependencies
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Jalankan migrasi database tiap deploy (JANGAN jalankan db:seed di produksi)
php artisan migrate --force

# Cache konfigurasi, routes, views, dan event untuk performa
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Restart queue worker agar memuat kode baru yang baru di-deploy
php artisan queue:restart

# Keluar dari mode maintenance
php artisan up

echo "Deployment finished successfully!"
