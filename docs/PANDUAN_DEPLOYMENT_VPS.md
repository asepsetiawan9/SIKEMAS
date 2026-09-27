# 🚀 PANDUAN DEPLOYMENT KE SERVER PRODUCTION (VPS UBUNTU 22.04 LTS)
## SISTEM INFORMASI KEUANGAN DAN ASET TERINTEGRASI (SIKEMAS)
### KECAMATAN CARINGIN — KABUPATEN GARUT

---

Panduan ini berisi instruksi menyeluruh untuk menginstal, mengonfigurasi, dan memelihara aplikasi **SIKEMAS** pada server produksi VPS berbasis Ubuntu 22.04 LTS.

---

## 🖥️ 1. Informasi Deployment Live Server VPS Production

| Parameter | Konfigurasi Live Server | Keterangan |
|---|---|---|
| **Host IP / Gateway** | `36.64.200.242:2020` | Server Multi-Tenant Host `initd` |
| **User SSH & Sudo** | `server-initd` | Sudo privileges aktif |
| **Target URL Utama** | `https://sikemas.initd.web.id` | Single-Domain Monolith (Inertia React + Laravel) |
| **Path Aplikasi** | `/var/www/sikemas` | Owner `server-initd:www-data` |
| **PHP Runtime** | PHP 8.5.4 (CLI & FPM) | Socket `/run/php/php-fpm.sock` |
| **Database Engine** | MySQL 8.0 | DB: `sikemas` \| User: `sikemas_user` |
| **SSL / TLS** | Let's Encrypt Wildcard/Dedicated | Otomatis via Certbot (Auto-renew) |
| **Backup Otomatis** | Daily 02:00 WIB via Scheduler | Crontab: `* * * * * cd /var/www/sikemas && php artisan schedule:run` |

---

## 📦 2. Langkah 1: Instalasi Paket & Dependensi Server

Login ke VPS via SSH sebagai user `root` atau `sudo`:

```bash
# Update repository sistem
sudo apt update && sudo apt upgrade -y

# Install tools pendukung
sudo apt install -y curl git unzip ufw software-properties-common rsync certbot python3-certbot-nginx

# Tambahkan PPA PHP Ondřej Surý
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Install PHP 8.2 dan ekstensi wajib SIKEMAS (GD untuk QR Code & DomPDF, BCMath, MySQL, dll.)
sudo apt install -y php8.2 php8.2-fpm php8.2-mysql php8.2-mbstring php8.2-xml \
    php8.2-bcmath php8.2-curl php8.2-zip php8.2-gd php8.2-intl php8.2-cli

# Install Nginx Web Server
sudo apt install -y nginx

# Install MySQL Server
sudo apt install -y mysql-server

# Install Composer 2.x
curl -sS https://getcomposer.org/installer -o composer-setup.php
sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer
rm composer-setup.php

# Install Node.js 20 LTS & NPM
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

---

## 🗄️ 3. Langkah 2: Konfigurasi Database MySQL

Amankan instalasi MySQL dan buat database serta pengguna khusus SIKEMAS:

```bash
sudo mysql_secure_installation
```

Masuk ke konsol MySQL:
```sql
sudo mysql -u root -p
```

Eksekusi perintah SQL berikut:
```sql
CREATE DATABASE sikemas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'sikemas_user'@'localhost' IDENTIFIED BY 'PasswordKuat_Caringin2026!#';
GRANT ALL PRIVILEGES ON sikemas.* TO 'sikemas_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

---

## 📂 4. Langkah 3: Deployment Source Code & Izin Akses

```bash
# Buat direktori aplikasi
sudo mkdir -p /var/www/sikemas
sudo chown -R $USER:www-data /var/www/sikemas

# Clone repository atau salin source code proyek
cd /var/www/sikemas
git clone https://github.com/organisasi/pkp-caringin.git .

# Salin konfigurasi environment production
cp .env.production .env

# Generate APP_KEY
php artisan key:generate

# Konfigurasi kredensial database pada .env
nano .env
# (Pastikan DB_DATABASE=sikemas, DB_USERNAME=sikemas_user, DB_PASSWORD=...)
```

Atur kepemilikan dan permission direktori:
```bash
sudo chown -R www-data:www-data /var/www/sikemas/storage /var/www/sikemas/bootstrap/cache /var/www/sikemas/backup
sudo chmod -R 775 /var/www/sikemas/storage /var/www/sikemas/bootstrap/cache /var/www/sikemas/backup
```

---

## ⚡ 5. Langkah 4: Instalasi Vendor, Migrasi & Build Assets

```bash
cd /var/www/sikemas

# Install dependency PHP tanpa dependensi development
composer install --no-dev --optimize-autoloader --prefer-dist

# Hubungkan symbolic link storage publik
php artisan storage:link

# Jalankan migrasi database dan seeding data awal
php artisan migrate --force
php artisan db:seed --class=RolePermissionSeeder --force
php artisan db:seed --class=UserSeeder --force
php artisan db:seed --class=KegiatanSeeder --force
php artisan db:seed --class=AsetSeeder --force
php artisan db:seed --class=PengaturanSeeder --force

# Install dependensi frontend dan kompilasi production bundle Vite
npm ci --omit=dev
npm run build

# Optimasi caching Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

---

## 🌐 6. Langkah 5: Konfigurasi Nginx & SSL HTTPS

Salin file konfigurasi Nginx dari proyek:
```bash
sudo cp /var/www/sikemas/deployment/nginx.conf /etc/nginx/sites-available/sikemas.caringin.go.id
sudo ln -s /etc/nginx/sites-available/sikemas.caringin.go.id /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default

# Uji konfigurasi Nginx
sudo nginx -t
sudo systemctl reload nginx
```

Pemasangan Sertifikat SSL Gratis via Certbot:
```bash
sudo certbot --nginx -d sikemas.caringin.go.id
```

---

## ⏰ 7. Langkah 6: Konfigurasi Cron Job Backup Database Harian

Sistem SIKEMAS dilengkapi perintah otomatis `sikemas:backup-database` yang melakukan dump database setiap jam **02:00 WIB** dan membersihkan file backup yang telah berusia lebih dari **30 hari**.

Tambahkan entri cron ke crontab server:
```bash
sudo crontab -e
```

Tambahkan baris berikut di baris paling bawah:
```cron
# Laravel Scheduler (Menjalankan backup harian jam 02:00 WIB & task berkala)
* * * * * cd /var/www/sikemas && php artisan schedule:run >> /dev/null 2>&1

# Eksekusi script sinkronisasi upload & backup fisik tambahan jam 02:00 WIB
0 2 * * * /var/www/sikemas/deployment/backup.sh >> /var/log/sikemas_backup.log 2>&1
```

---

## 🛡️ 8. Langkah 7: Konfigurasi Firewall & Pengamanan Server

```bash
# Aktifkan UFW Firewall
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow ssh
sudo ufw allow http
sudo ufw allow https
sudo ufw enable
```

---

## 🔍 9. Langkah 8: Prosedur Monitoring Log Aktivitas (2 Minggu Pertama)

Sesuai instruksi Fase 6, selama 2 minggu pertama masa *go-live*, administrator sistem wajib memantau tabel `log_aktivitas` untuk memastikan tidak ada anomali atau kegagalan operasional.

Jalankan perintah monitoring bawaan SIKEMAS:
```bash
cd /var/www/sikemas
php artisan sikemas:audit-summary --days=14
```

Perintah di atas akan menyajikan laporan:
1. Total transaksi sistem.
2. Distribusi frekuensi per aksi (SPJ diajukan, dikonsolidasi, diverifikasi, dsb.).
3. Peringatan dini jika ada penolakan SPJ abnormal (`catatan_penolakan`).
4. Peringatan dini jika ada aset yang dilaporkan `rusak_berat`.
5. 10 aktivitas pengguna terakhir beserta alamat IP.

---

## 🔄 10. Prosedur Pembaruan Aplikasi (One-Click Deploy)

Setiap ada rilis perbaikan bug atau update fitur, gunakan script deployment otomatis:

```bash
cd /var/www/sikemas
chmod +x ./deployment/deploy.sh
./deployment/deploy.sh
```
Script tersebut akan secara otomatis melakukan:
1. `php artisan down`
2. `git pull origin main`
3. `composer install --no-dev`
4. `php artisan migrate --force`
5. `npm run build`
6. `php artisan config:cache && php artisan route:cache && php artisan view:cache`
7. `systemctl reload php8.2-fpm`
8. `php artisan up`
