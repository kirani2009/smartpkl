# 🚀 SmartPKL — Deployment Guide (Shared Hosting)

## Arsitektur

```
Shared Hosting (cPanel)
├── public_html/              ← Document Root
│   ├── index.php             ← Laravel API entry point
│   ├── .htaccess             ← Routing: API → Laravel, SPA → React
│   ├── app.html              ← React SPA (client-side routing)
│   ├── assets/               ← React build assets (JS, CSS)
│   ├── favicon.svg
│   ├── robots.txt
│   ├── .env                  ← Production config
│   ├── app/                  ← Laravel application
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── public/               ← Laravel public (storage link)
│   ├── resources/
│   ├── routes/
│   ├── storage/              ← Logs, cache, sessions, uploads
│   └── vendor/               ← Composer dependencies
```

## Prasyarat

| Komponen | Versi Minimum |
|---|---|
| PHP | 8.1+ |
| PHP Extensions | pdo_mysql, mbstring, openssl, curl, xml, bcmath, json, fileinfo |
| MySQL/MariaDB | 5.7+ / 10.2+ |
| Node.js | 18+ (untuk build di local) |

## Langkah Deployment

### 1. Siapkan di Local (sudah dilakukan deploy.sh)

```bash
# Jalankan script deploy preparation
bash deploy.sh

# Output: smartpkl-deploy.zip
```

### 2. Export Database (di local)

```bash
# Export database ke SQL
bash export-db.sh

# Output: smartpkl.sql
```

### 3. Upload ke cPanel

#### Via File Manager:
1. Login **cPanel** → **File Manager**
2. Navigate ke **document root** (`public_html/` atau `/home/username/subdomain/`)
3. **Upload** `smartpkl-deploy.zip`
4. **Extract** ZIP di document root
5. **Pindahkan** isi folder `smartpkl-deploy/` ke document root
6. **Hapus** folder `smartpkl-deploy/` dan ZIP file

#### Via SSH (jika tersedia):
```bash
cd /home/username/public_html/
scp user@local:/path/to/smartpkl-deploy.zip .
unzip smartpkl-deploy.zip
mv smartpkl-deploy/* .
mv smartpkl-deploy/.* . 2>/dev/null
rm -rf smartpkl-deploy smartpkl-deploy.zip
```

### 4. Setup Database di cPanel

#### Via phpMyAdmin:
1. Login cPanel → **phpMyAdmin**
2. Klik **New** di sidebar
3. Buat database: `smartpkl` (atau sesuai naming cPanel: `username_smartpkl`)
4. Klik tab **Import**
5. Upload `smartpkl.sql`
6. Klik **Go** / **Import**

#### Via MySQL Databases (cPanel):
1. Buat database: `smartpkl`
2. Buat MySQL User (username + password)
3. **Add User to Database** → berikan **ALL PRIVILEGES**

### 5. Konfigurasi `.env`

Edit file `.env` di document root:

```ini
# ===== PENTING: Ganti semua placeholder ini =====

APP_NAME=SmartPKL
APP_ENV=production
APP_KEY=                    # Isi dengan key dari 'php artisan key:generate'
APP_DEBUG=false
APP_URL=https://smartpkl.example.com    # ← Ganti ke domain Anda

# Database (sesuaikan dengan yang dibuat di cPanel)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1           # Biasanya localhost atau 127.0.0.1
DB_PORT=3306
DB_DATABASE=username_smartpkl           # ← Nama database di cPanel
DB_USERNAME=username_smartpkl_user      # ← Username MySQL
DB_PASSWORD=xxxxxx                      # ← Password MySQL

# Session & Cache (file-based, kompatibel shared hosting)
SESSION_DRIVER=file
CACHE_DRIVER=file
QUEUE_CONNECTION=sync
```

**Cara dapat APP_KEY:**
```bash
# Via SSH di server
php artisan key:generate
```
Atau generate manual:
```bash
# Di local
php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
```
Copy hasilnya ke `APP_KEY=` di `.env`.

### 6. Setup Storage & Permissions

```bash
# Via SSH di server
cd /home/username/public_html/

# Buat storage link (agar Laravel bisa serve uploaded files)
php artisan storage:link

# Set permissions
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
chmod 644 .env

# Jika permission error pada .env:
chmod 600 .env
```

Via **cPanel File Manager**:
1. Klik folder `storage` → **Change Permissions** → 755
2. Klik folder `bootstrap/cache` → **Change Permissions** → 755

### 7. Bersihkan Cache Laravel

```bash
# Via SSH di server
cd /home/username/public_html/
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### 8. Test Deployment

```bash
# Test API Health Check
curl https://smartpkl.example.com/api/health

# Expected response:
# {"success":true,"message":"SmartPKL API is running.","data":{...}}
```

Test di browser:
1. Buka `https://smartpkl.example.com/` → Halaman Landing Page React
2. Buka `https://smartpkl.example.com/login` → Form Login React
3. Buka `https://smartpkl.example.com/api/health` → JSON response Laravel

## Troubleshooting

### 500 Internal Server Error
```bash
# Cek error log di cPanel → Metrics → Errors
# Atau via SSH:
tail -50 storage/logs/laravel.log
```

### APP_KEY Missing
```bash
php artisan key:generate
# Copy key ke .env → APP_KEY=
```

### Database Connection Failed
1. Pastikan nama database, username, password benar
2. Pastikan user MySQL punya akses ke database
3. Cek `DB_HOST` (biasanya `127.0.0.1` atau `localhost`)

### .htaccess Not Working
1. Pastikan mod_rewrite aktif di Apache
2. Di cPanel → **MultiPHP Manager** → pastikan PHP version ≥ 8.1
3. Buat file `info.php` berisi `<?php phpinfo();` untuk cek extensions

### Frontend Not Loading (blank page)
1. Pastikan `app.html` ada di document root
2. Pastikan `.htaccess` routing benar
3. Cek browser console untuk error
4. Pastikan `VITE_API_URL=/api` di `.env`

### Storage Permission Denied
```bash
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
```

## Akun Default (Seeder)

| Role | Email | Password |
|---|---|---|
| Admin | admin@smartpkl.id | password123 |
| Guru | guru@smartpkl.id | password123 |
| Siswa | rina@smartpkl.id | password123 |
| Perusahaan | hrd@techcorp.id | password123 |

> ⚠️ **PENTING**: Ganti semua password default setelah deployment!

## Rollback

Jika ada masalah, restore dari backup:
1. Restore file dari backup ZIP
2. Restore database dari backup SQL
3. Pastikan `.env` tetap benar

---

**Catatan**: Script `deploy.sh` sudah menyiapkan semua file yang diperlukan. Tinggal upload, konfigurasi `.env`, dan setup database.
