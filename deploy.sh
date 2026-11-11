#!/bin/bash

# NOTE PERBAIKAN:
# 5. Tambahkan set -e agar deployment berhenti jika salah satu perintah error
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

# Jalankan migrasi database
php artisan migrate --force

# NOTE PERBAIKAN:
# 6. 'php artisan db:seed --force' dihapus karena seeder tidak boleh dijalankan otomatis tiap deploy produksi

# Cache konfigurasi, routes, dan views untuk performa
php artisan config:cache
php artisan route:cache
php artisan view:cache

# NOTE PERBAIKAN:
# 7. Restart worker queue agar memuat kode baru di server produksi
php artisan queue:restart

# Keluar dari mode maintenance
php artisan up

echo "Deployment finished successfully!"
