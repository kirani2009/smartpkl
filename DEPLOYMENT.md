# 🚀 SmartPKL — Deployment Guide (Hostinger Shared Hosting)

## Arsitektur Deployment

```
public_html/                  ← Document Root (Hostinger)
├── index.php                 ← Laravel API entry point (paths disesuaikan)
├── .htaccess                 ← Routing: API → Laravel, SPA → React
├── app.html                  ← React SPA (client-side routing)
├── assets/                   ← React build assets (JS, CSS)
├── favicon.svg
├── app/                      ← Laravel application
├── bootstrap/
├── config/
├── database/
├── resources/
├── routes/
├── storage/                  ← Logs, cache, sessions, uploads
├── vendor/                   ← Composer dependencies
└── .env                      ← Production config (DI ISI MANUAL)
```

> ⚠️ **PENTING**: Tidak ada file rahasia (APP_KEY, password) di dalam repository.
> File `.env` harus diisi manual setelah upload ke hosting.

## Prasyarat

| Komponen | Versi Minimum |
|---|---|
| PHP | 8.1+ |
| PHP Extensions | pdo_mysql, mbstring, openssl, curl, xml, bcmath, json, fileinfo |
| MySQL/MariaDB | 5.7+ / 10.2+ |
| Node.js | 18+ (untuk build di local) |

---

## Langkah Deployment (Lengkap)

### 1. Build & Package di Local

```bash
# Export database dulu
bash export-db.sh
# → Output: smartpkl.sql

# Jalankan deploy script
bash deploy.sh
# → Output: smartpkl-deploy.zip
```

### 2. Setup Database di Hostinger

> ⚠️ **PENTING — Perlindungan Data (jangan sampai data hilang)**
>
> Berkas `smartpkl.sql` berisi `DROP TABLE IF EXISTS` untuk **seluruh tabel**
> (termasuk tabel `users`). Meng-import berkas ini akan **menghapus dan
> mengganti seluruh database** (semua data, termasuk akun user yang sudah
> terdaftar, akan hilang permanen).
>
> Gunakan impor `smartpkl.sql` **HANYA pada database yang KOSONG / saat
> setup pertama kali**. **JANGAN PERNAH** meng-import ulang `smartpkl.sql`
> di atas database yang sudah berisi data — ini penyebab akun "hilang /
> tidak bisa login".
>
> Untuk pembaruan skema pada database yang sudah berjalan, gunakan migrasi,
> **jangan** impor SQL:
> ```bash
> php artisan migrate
> ```

#### Via phpMyAdmin (cPanel) — hanya untuk setup pertama / database kosong:
1. Login **cPanel** → **phpMyAdmin**
2. Klik **New** di sidebar kiri
3. Buat database: `youruser_smartpkl` (sesuai naming Hostinger)
4. Klik tab **Import** di bagian atas
5. Klik **Choose File** → pilih `smartpkl.sql`
6. Klik **Go** / **Import**

#### Via MySQL Databases (cPanel):
1. Login **cPanel** → **MySQL® Databases**
2. Buat **New Database**: `youruser_smartpkl`
3. Buat **MySQL User**: username + password
4. Di bagian **Add User To Database**: pilih user & database → klik **Add**
5. Berikan **ALL PRIVILEGES** → klik **Make Changes**

> 📝 **Catatan Hostinger**: Nama database dan user biasanya otomatis diawali
> dengan username cPanel Anda (contoh: `u1234567_smartpkl`).

### 3. Upload File ke Hostinger

#### Via cPanel File Manager:
1. Login **cPanel** → **File Manager**
2. Navigate ke **`public_html/`** (document root)
3. **Upload** `smartpkl-deploy.zip`
   - Klik tombol **Upload** di toolbar
   - Pilih file dari komputer
4. **Extract** ZIP di `public_html/`
   - Klik kanan `smartpkl-deploy.zip` → **Extract**
   - Pastikan extract ke `/public_html/`
5. **Pindahkan isi** folder `smartpkl-deploy/` ke `public_html/` root
   - Buka folder `smartpkl-deploy/`
   - Select All → **Move** → pilih `/public_html/`
6. **Hapus** folder `smartpkl-deploy/` dan file ZIP

#### Via SSH (jika tersedia):
```bash
cd /home/yourusername/public_html/
scp user@local:~/path/to/smartpkl-deploy.zip .
unzip smartpkl-deploy.zip
mv smartpkl-deploy/* .
mv smartpkl-deploy/.* . 2>/dev/null
rm -rf smartpkl-deploy smartpkl-deploy.zip
```

### 4. Konfigurasi `.env`

Edit file `.env` di `public_html/` menggunakan File Manager atau SSH:

```bash
# Login cPanel → File Manager → klik .env → Edit
```

**Isi nilai berikut** (ganti placeholder dengan data sebenarnya):

```ini
# ===== DOMAIN =====
APP_URL=https://your-domain.com          # ← Ganti ke domain Hostinger Anda

# ===== DATABASE =====
DB_CONNECTION=mysql
DB_HOST=127.0.0.1                         # ← Biasanya 127.0.0.1 atau localhost
DB_PORT=3306
DB_DATABASE=youruser_smartpkl             # ← Nama database dari cPanel
DB_USERNAME=youruser_smartpkl_user        # ← Username MySQL dari cPanel
DB_PASSWORD=xxxxxx                        # ← Password MySQL dari cPanel

# ===== APP KEY =====
APP_KEY=                                  # ← Isi dengan key (lihat langkah 5)

# ===== LAINNYA (biarkan default) =====
APP_NAME=SmartPKL
APP_ENV=production
APP_DEBUG=false
SESSION_DRIVER=file
CACHE_DRIVER=file
QUEUE_CONNECTION=sync
```

### 5. Generate APP_KEY

**Cara 1 — Via SSH (disarankan):**
```bash
cd /home/yourusername/public_html/
php artisan key:generate
```
Key akan otomatis ditulis ke `.env` → `APP_KEY=base64:...`

**Cara 2 — Manual (tanpa SSH):**
```bash
# Di komputer local, jalankan:
php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
# Copy hasilnya ke .env → APP_KEY=
```

### 6. Set Permissions

Via **cPanel File Manager**:
1. Klik folder **`storage/`** → **Change Permissions** → centang semua → set **755**
   - Atau klik **Set Permissions** → ketik **755** → centang **Recurse into subdirectories**
2. Klik folder **`bootstrap/cache/`** → **Change Permissions** → set **755** (recursive)

Via **SSH**:
```bash
cd /home/yourusername/public_html/
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
```

### 7. Bersihkan Cache (opsional, via SSH)

```bash
cd /home/yourusername/public_html/
php artisan config:clear
php artisan route:clear
php artisan cache:clear
```

### 8. Test Deployment

Buka browser dan test:

| URL | Expected Result |
|---|---|
| `https://your-domain.com/` | Landing page SmartPKL |
| `https://your-domain.com/login` | Form login |
| `https://your-domain.com/register` | Form registrasi |
| `https://your-domain.com/api/health` | JSON: `{"success":true,...}` |

---

## Akun Default (Seeder)

| Role | Email | Password |
|---|---|---|
| Admin | admin@smartpkl.id | password123 |
| Guru | guru@smartpkl.id | password123 |
| Siswa | rina@smartpkl.id | password123 |
| Perusahaan | hrd@techcorp.id | password123 |

> ⚠️ **GANTI SEMUA PASSWORD** setelah deployment!

---

## Troubleshooting

### 500 Internal Server Error
```bash
# Via SSH:
tail -50 storage/logs/laravel.log

# Atau via cPanel → Metrics → Errors
```

### APP_KEY Missing
```bash
php artisan key:generate
```

### Database Connection Failed
1. Pastikan nama DB, user, password sesuai dengan yang di cPanel
2. Cek `DB_HOST` → biasanya `127.0.0.1`
3. Pastikan user MySQL punya akses ke database

### .htaccess Not Working
1. Pastikan **mod_rewrite** aktif (contact Hostinger support)
2. Di cPanel → **MultiPHP Manager** → pastikan PHP ≥ 8.1

### Frontend Blank Putih
1. Pastikan `app.html` ada di `public_html/`
2. Pastikan `assets/` folder ada di `public_html/`
3. Buka browser console (F12) → tab Console → cek error
4. Pastikan `APP_URL` di `.env` benar

### Storage Permission Denied
```bash
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
```

---

## Struktur File Hostinger

```
/home/yourusername/
├── public_html/              ← Document Root (Apache)
│   ├── index.php             ← Laravel entry point
│   ├── .htaccess             ← URL routing
│   ├── app.html              ← React SPA
│   ├── assets/               ← React JS/CSS
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── resources/
│   ├── routes/
│   ├── storage/
│   ├── vendor/
│   └── .env
└── logs/                     ← Apache logs (otomatis)
```

---

## Rollback

Jika ada masalah:
1. Restore file dari backup
2. Restore database dari backup SQL — **perhatian**: restore full-dump juga
   akan **menimpa seluruh data saat ini** (termasuk user). Gunakan hanya untuk
   pemulihan penuh pada database kosong, atau pastikan backup tersebut adalah
   yang paling mutakhir agar data yang ada tidak hilang.
3. Pastikan `.env` tetap benar

---

**Catatan**: Script `deploy.sh` sudah menyiapkan semua file yang diperlukan dengan struktur flat yang siap upload ke `public_html/`. Tinggal upload, isi `.env`, dan setup database.
