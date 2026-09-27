#!/usr/bin/env bash
# ==============================================================================
# SIKEMAS Database & File Upload Backup Script - Kecamatan Caringin
# Jalankan via Cron setiap jam 02:00 WIB:
# 0 2 * * * /var/www/sikemas/deployment/backup.sh >> /var/log/sikemas_backup.log 2>&1
# ==============================================================================

set -euo pipefail

APP_DIR="/var/www/sikemas"
BACKUP_DIR="${APP_DIR}/backup"
UPLOADS_DIR="${APP_DIR}/storage/app/public"
RETENTION_DAYS=30
TIMESTAMP=$(date +"%Y-%m-%d_%H-%M-%S")
DB_BACKUP_FILE="${BACKUP_DIR}/sikemas_backup_${TIMESTAMP}.sql.gz"

echo "=========================================================="
echo "[$(date +"%Y-%m-%d %H:%M:%S")] Memulai Backup Harian SIKEMAS..."
echo "=========================================================="

# 1. Pastikan folder backup tersedia
mkdir -p "${BACKUP_DIR}"

# 2. Eksekusi Backup via Artisan SIKEMAS atau mysqldump
cd "${APP_DIR}"
echo "-> Menjalankan backup database melalui Laravel Artisan..."
php artisan sikemas:backup-database --retention=${RETENTION_DAYS}

# 3. Kompresi file sql yang baru dibuat jika belum terkompresi
find "${BACKUP_DIR}" -maxdepth 1 -name "sikemas_backup_*.sql" -exec gzip -9 {} \;

# 4. Backup sinkronisasi folder uploads/storage ke arsip backup
echo "-> Menyinkronkan file upload bukti SPJ dan foto Aset..."
UPLOADS_BACKUP="${BACKUP_DIR}/uploads_sync"
mkdir -p "${UPLOADS_BACKUP}"
rsync -av --delete "${UPLOADS_DIR}/" "${UPLOADS_BACKUP}/"

# 5. Pembersihan file backup lama (> 30 hari)
echo "-> Membersihkan file backup yang lebih tua dari ${RETENTION_DAYS} hari..."
find "${BACKUP_DIR}" -type f -name "sikemas_backup_*.sql.gz" -mtime +${RETENTION_DAYS} -delete

echo "[$(date +"%Y-%m-%d %H:%M:%S")] Backup Harian SIKEMAS selesai dengan sukses!"
