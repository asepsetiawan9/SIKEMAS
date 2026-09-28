#!/usr/bin/env bash
# ==============================================================================
# SIKEMAS Zero-Downtime Deployment Script - Kecamatan Caringin
# Usage: ./deployment/deploy.sh
# ==============================================================================

set -euo pipefail

echo "🚀 Memulai Deployment SIKEMAS ke Production..."

# 1. Mode Pemeliharaan Sementara
php artisan down --secret="sikemas-deploy-bypass-token" || true

# 2. Update Source Code
echo "📥 Menarik perubahan terbaru dari repository git..."
git pull origin main

# 3. Instalasi PHP Dependencies
echo "📦 Menginstall dependencies Composer production..."
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

# 4. Migrasi Database
echo "🗄️ Menjalankan migrasi database..."
php artisan migrate --force

# 5. Link Storage Publik
echo "🔗 Memastikan symbolic link storage terpasang..."
php artisan storage:link || true

# 6. Kompilasi Frontend Assets (Vite)
echo "⚡ Mengompilasi assets frontend (React & Tailwind)..."
npm ci --prefer-offline --no-audit
npm run build

# 7. Optimasi Caching Laravel
echo "🧹 Memperbarui cache konfigurasi, route, dan view..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 8. Restart Background Services
echo "🔄 Merestart PHP-FPM dan Queue Worker..."
sudo systemctl reload php8.5-fpm || sudo systemctl reload php8.2-fpm || true
php artisan queue:restart || true

# 9. Nonaktifkan Mode Pemeliharaan
php artisan up

echo "✅ Deployment SIKEMAS berhasil diselesaikan!"
