# 🛡️ PANDUAN LENGKAP & PROTOKOL ISOLASI VPS MULTI-SISTEM
### Server: `36.64.200.242:2020` (Hostname: `initd` | User: `server-initd`)
### Dokumen Resmi: Audit VPS Live per 27 September 2026

---

> [!IMPORTANT]
> **SERVER INI ADALAH PRODUCTION MULTI-TENANT/MULTI-APP.**
> Di dalam server VPS ini telah berjalan **3 SISTEM PRODUKSI AKTIF** yang melayani pengguna riil. Setiap AI Agent atau DevOps Engineer yang akan menambahkan sistem baru **WAJIB MEMBACA & MEMATUHI PROTOKOL INI** agar tidak terjadi downtime, tabrakan port, OOM Killer, atau kerusakan data pada sistem yang sudah ada.

---

## 🗺️ 1. Peta Lengkap Aplikasi Eksisting di VPS (Hasil Audit Live)

Berdasarkan inspeksi langsung via SSH pada 27 September 2026, berikut inventaris resmi seluruh sistem yang sedang berjalan:

| No | Sistem / Aplikasi | Direktori `/var/www/` | Port Internal | Service Systemd | Database MySQL & User | Domain / Virtual Host |
|:---|:---|:---|:---|:---|:---|:---|
| **1** | **LENTERA Pajak Desa** | `/var/www/pajak-backend`<br>`/var/www/pajak-frontend` | **3000** (Node SSR)<br>Socket PHP-FPM | `pajak-frontend.service`<br>`php8.5-fpm.service`<br>`cloudflared-tunnel.service` | DB: `pajak_desa_db`<br>User: `pajak_user` | `pbb-p2.barudua.web.id`<br>`backend.barudua.web.id` |
| **2** | **SIKOS (Manajemen Kos)** | `/var/www/sikos-backend`<br>`/var/www/sikos-frontend` | **3001** (Next.js SSR)<br>Socket PHP-FPM | `sikos-frontend.service`<br>`sikos-queue.service` | DB: `db_sikos`<br>User: `sikos_user` | `sikos.initd.web.id`<br>`backend.sikos.initd.web.id` |
| **3** | **PKP Malangbong / SIPELAJAR** | `/var/www/pkp-malangbong` | Socket PHP-FPM | PHP-FPM via Nginx | DB: `pkp_malangbong_db`<br>User: `pkp_user` | `sipelajar.initd.web.id`<br>`pkpmalangbong.initd.web.id` |
| **4** | **Monitoring Server** | Core Ubuntu | **10050** | `zabbix-server`<br>`zabbix-agent` | DB: Internal | Monitoring Agent & Server |

---

## 🛑 2. ZONA TERLARANG (ABSOLUTE PROHIBITIONS / APA YANG TIDAK BOLEH DILAKUKAN)

### ❌ A. Port Terlarang (DILARANG Digunakan oleh Sistem Baru)
* **Port 3000**: Milik `pajak-frontend` (LENTERA SSR).
* **Port 3001**: Milik `sikos-frontend` (SIKOS Next.js).
* **Port 3306 & 33060**: Milik MySQL Engine.
* **Port 80 & 443**: Milik Nginx Global Reverse Proxy & Let's Encrypt SSL.
* **Port 22 & 2020**: Port SSH Administrasi Server.
* **Port 10050**: Milik Zabbix Agent.
* **Port 8000, 8080, 8826, 8827**: Digunakan oleh listener Nginx internal.

### ❌ B. Direktori Terlarang (DILARANG Dihapus, Dimodifikasi, atau Ditimpa)
* 🚫 `/var/www/pajak-backend/` & `/var/www/pajak-frontend/`
* 🚫 `/var/www/sikos-backend/` & `/var/www/sikos-frontend/`
* 🚫 `/var/www/pkp-malangbong/`
* 🚫 `/var/www/README_VPS_INFRASTRUCTURE.txt`

### ❌ C. Database Terlarang (DILARANG Disentuh Query DDL/DML Sembarangan)
* 🚫 Database `pajak_desa_db` (dan user `pajak_user`)
* 🚫 Database `db_sikos` (dan user `sikos_user`)
* 🚫 Database `pkp_malangbong_db` (dan user `pkp_user`)
* 🚫 **Dilarang keras**: `DROP DATABASE`, `FLUSH PRIVILEGES` tanpa grant baru, `migrate:fresh`, atau menjalankan seeder yang berpotensi merusak schema live.

### ❌ D. Service Systemd Terlarang (DILARANG Stop / Kill / Disable)
* 🚫 `systemctl stop/disable pajak-frontend.service`
* 🚫 `systemctl stop/disable sikos-frontend.service`
* 🚫 `systemctl stop/disable sikos-queue.service`
* 🚫 `systemctl stop/disable php8.5-fpm.service`
* 🚫 `systemctl stop/disable cloudflared-tunnel.service`
* 🚫 `systemctl stop/disable zabbix-server.service` atau `zabbix-agent.service`
* 🚫 `systemctl stop/disable mysql.service`
* 🚫 `systemctl stop/disable nginx.service` (hanya boleh `reload`)

### ❌ E. Konfigurasi Nginx Terlarang
* 🚫 **DILARANG MENGEDIT** berkas konfigurasi Nginx yang sudah ada:
  * `/etc/nginx/sites-available/pajak-backend`
  * `/etc/nginx/sites-available/sikos.initd.web.id`
  * `/etc/nginx/sites-available/backend.sikos.initd.web.id`
  * `/etc/nginx/sites-available/sipelajar.initd.web.id.conf`
  * `/etc/nginx/sites-available/pkpmalangbong.initd.web.id.conf`
* 🚫 **DILARANG MENGGUNAKAN** `systemctl restart nginx`. Jika ada salah syntax saat restart, seluruh website di server akan mati bersamaan!

---

## ✅ 3. STANDAR OPERASIONAL SISTEM BARU (APA YANG HARUS & BOLEH DILAKUKAN)

Jika ingin mendeploy sistem baru (misal: sistem X), ikuti standar isolasi berikut:

### 1. Alokasi Port Baru yang Direkomendasikan
Pilih salah satu port yang masih **FREE & AMAN**:
* **Rekomendasi Port Web / Node / Python**: `3002`, `3003`, `4000`, `5000`, `8081`, `8082`
* **Wajib cek ketersediaan sebelum bind**:
  ```bash
  ss -tulpn | grep :<PORT_PILIHAN>
  ```
  *(Pastikan output kosong sebelum mengonfigurasi aplikasi)*

### 2. Direktori Kerja Eksklusif
* Buat direktori baru di dalam `/var/www/`:
  ```bash
  sudo mkdir -p /var/www/<nama-sistem-baru>
  sudo chown -R www-data:www-data /var/www/<nama-sistem-baru>
  sudo chmod -R 775 /var/www/<nama-sistem-baru>
  ```

### 3. Database MySQL & User Eksklusif
* Buat database baru dan user baru dengan hak akses yang terisolasi HANYA pada database baru tersebut:
  ```sql
  CREATE DATABASE <nama_app>_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  CREATE USER '<nama_app>_user'@'localhost' IDENTIFIED BY '<PasswordKuatBaru2026!>';
  GRANT ALL PRIVILEGES ON <nama_app>_db.* TO '<nama_app>_user'@'localhost';
  FLUSH PRIVILEGES;
  ```
* *Dilarang menggunakan user `pajak_user`, `sikos_user`, atau `pkp_user` untuk aplikasi baru.*

### 4. Konfigurasi Nginx Terpisah & Graceful Reload
* Buat file virtual host baru di `/etc/nginx/sites-available/<nama-domain-baru>.conf`:
  ```nginx
  server {
      listen 80;
      server_name <domain-atau-subdomain-baru>;
      client_max_body_size 50M;

      # Kasus A: Jika aplikasi berbasis Node.js/Next.js/Python (Reverse Proxy)
      location / {
          proxy_pass http://127.0.0.1:<PORT_BARU>; # misal: 3002
          proxy_http_version 1.1;
          proxy_set_header Upgrade $http_upgrade;
          proxy_set_header Connection 'upgrade';
          proxy_set_header Host $host;
          proxy_set_header X-Real-IP $remote_addr;
          proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
          proxy_set_header X-Forwarded-Proto $scheme;
      }

      # Kasus B: Jika aplikasi berbasis PHP / Laravel
      # root /var/www/<nama-sistem-baru>/public;
      # index index.php index.html;
      # location / { try_files $uri $uri/ /index.php?$query_string; }
      # location ~ \.php$ {
      #     include snippets/fastcgi-php.conf;
      #     fastcgi_pass unix:/run/php/php-fpm.sock;
      #     fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
      #     include fastcgi_params;
      # }
  }
  ```
* Aktifkan symlink:
  ```bash
  sudo ln -s /etc/nginx/sites-available/<nama-domain-baru>.conf /etc/nginx/sites-enabled/
  ```
* **WAJIB UJI SYNTAX SEBELUM RELOAD**:
  ```bash
  sudo nginx -t
  ```
* Jika dan HANYA JIKA syntax OK, lakukan **graceful reload**:
  ```bash
  sudo systemctl reload nginx
  ```
  *(Reload tidak memutus koneksi aplikasi lain yang sedang berjalan).*

### 5. Systemd Service Mandiri untuk Aplikasi Baru
Jika aplikasi membutuhkan background process / daemon (Node.js, Queue Worker, Python):
* Buat unit service terpisah di `/etc/systemd/system/<nama-sistem-baru>.service`:
  ```ini
  [Unit]
  Description=<Nama Sistem Baru> Service
  After=network.target mysql.service

  [Service]
  Type=simple
  User=www-data
  WorkingDirectory=/var/www/<nama-sistem-baru>
  ExecStart=/usr/bin/node server.js
  Restart=always
  RestartSec=5
  Environment=NODE_ENV=production PORT=3002

  [Install]
  WantedBy=multi-user.target
  ```
* Aktifkan:
  ```bash
  sudo systemctl daemon-reload
  sudo systemctl enable --now <nama-sistem-baru>.service
  sudo systemctl status <nama-sistem-baru>.service --no-pager
  ```

### 6. Resource Guard (Pencegahan OOM Killer)
* Total RAM VPS adalah **3.3 GB**, dengan RAM tersedia sekitar **1.5 GB**.
* Fitur `systemd-oomd` aktif di server ini. Jika terjadi lonjakan pemakaian RAM (misal saat `npm run build` berat), kernel Linux akan secara otomatis **MEMBUNUH** proses dengan memori terbesar (yang bisa menimpa database MySQL atau proses LENTERA/SIKOS).
* **Aturan Emas**:
  * Untuk aplikasi frontend berat (Next.js, Vite SSR, Nuxt), **LAKUKAN BUILD DI KOMPUTER LOKAL**, lalu kompresi dan upload berkas hasil build (`.output` / `.next` / `dist`) ke VPS.
  * Hindari menjalankan `npm run build` berukuran besar secara langsung di dalam server VPS.

---

## 📋 4. TEMPLATE PROMPT UNTUK AI SISTEM BARU (TINGGAL SALIN)

Salin seluruh teks di bawah ini dan berikan ke AI yang akan melakukan deployment sistem baru:

```markdown
Halo AI, kamu bertindak sebagai DevOps Engineer untuk mendeploy sistem baru di server VPS Ubuntu produksi.
PERINGATAN KRITIS: Server ini adalah MULTI-APP SHARED PRODUCTION SERVER yang sudah menjalankan:
1. LENTERA Pajak Desa Pro (Port 3000, Laravel API, React SSR, DB: pajak_desa_db)
2. SIKOS Kost Management (Port 3001, Laravel API, Next.js SSR, DB: db_sikos)
3. PKP Malangbong / SIPELAJAR (Laravel API, DB: pkp_malangbong_db)
4. Zabbix Server & Agent (Port 10050)

Agar sistem yang sudah ada TIDAK TERGANGGU, kamu WAJIB mematuhi ATURAN ISOLASI KETAT berikut:

1. DAFTAR ZONA TERLARANG (JANGAN DISENTUH/DIUBAH/DIHAPUS):
   - Dilarang sentuh direktori: /var/www/pajak-*, /var/www/sikos-*, /var/www/pkp-malangbong/
   - Dilarang sentuh database: pajak_desa_db, db_sikos, pkp_malangbong_db
   - Dilarang bind port: 3000, 3001, 3306, 80, 443, 2020, 10050, 8000, 8080, 8826, 8827
   - Dilarang stop/restart service: pajak-frontend, sikos-frontend, sikos-queue, php8.5-fpm, cloudflared-tunnel, mysql, nginx
   - Dilarang mengedit file konfigurasi Nginx eksisting.

2. STANDAR DEPLOYMENT SISTEM BARU:
   - Direktori: Buat folder baru di `/var/www/<nama-app-baru>/`
   - Port: Pilih port internal baru yang BEBAS (misal: 3002, 3003, 4000, atau 5000). Cek dulu: `ss -tulpn | grep :<port>`
   - Database: Buat database baru terpisah (`<nama_app>_db`) dan user baru (`<nama_app>_user`)
   - Nginx: Buat file baru di `/etc/nginx/sites-available/<domain-baru>.conf`, simlink ke `sites-enabled/`, uji dengan `sudo nginx -t`, lalu jalankan `sudo systemctl reload nginx` (JANGAN restart)
   - Service: Buat unit systemd baru di `/etc/systemd/system/<nama-app-baru>.service`
   - Memory Guard: VPS memiliki RAM sisa ~1.5 GB. Untuk build frontend, build di lokal lalu upload berkas jadi untuk menghindari OOM Killer.

Konfirmasi pemahamanmu terhadap aturan isolasi ini sebelum mengeksekusi perintah apa pun di server.
```

---

## 🔍 5. Checklist Verifikasi Pasca-Deployment Sistem Baru

Setelah sistem baru dideploy, jalankan verifikasi independen untuk memastikan sistem lama tetap aman:

- [ ] LENTERA Pajak Web tetap `HTTP 200 OK`: `wget -q -T 3 --spider -S https://pbb-p2.barudua.web.id`
- [ ] LENTERA Backend API tetap `HTTP 200 OK`: `wget -q -T 3 --spider -S https://backend.barudua.web.id/api/v1/health`
- [ ] SIKOS Web tetap `HTTP 200 OK`: `wget -q -T 3 --spider -S http://sikos.initd.web.id`
- [ ] SIKOS Backend tetap `HTTP 200 OK`: `wget -q -T 3 --spider -S http://backend.sikos.initd.web.id`
- [ ] SIPELAJAR tetap responsif: `wget -q -T 3 --spider -S http://sipelajar.initd.web.id`
- [ ] Port baru terisolasi dan tidak konflik dengan 3000 / 3001 (`ss -tulpn`)
- [ ] Syntax Nginx tetap 100% valid (`sudo nginx -t`)
