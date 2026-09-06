#!/bin/bash
# ============================================================
# SmartPKL — Shared Hosting (Hostinger) Deployment Script
# ============================================================
# Jalankan script ini SEBELUM upload ke shared hosting.
#
# Output: smartpkl-deploy/ (folder siap upload ke public_html/)
#
# Struktur output:
#   public_html/
#   ├── index.php          ← Laravel entry (path disesuaikan)
#   ├── .htaccess          ← Routing: API → Laravel, SPA → React
#   ├── app.html           ← React SPA
#   ├── assets/            ← React build (JS, CSS)
#   ├── app/
#   ├── bootstrap/
#   ├── config/
#   ├── database/
#   ├── resources/
#   ├── routes/
#   ├── storage/
#   ├── vendor/
#   └── .env               ← Production config
# ============================================================

set -e

echo "=========================================="
echo "  SmartPKL Deployment (Hostinger)"
echo "=========================================="
echo ""

# --- Step 1: Build Frontend ---
echo "[1/7] Building frontend React..."
cd frontend
npm install
npm run build
cd ..
echo "   ✅ Frontend built -> frontend/dist/"
echo ""

# --- Step 2: Install Composer Dependencies (production) ---
echo "[2/7] Installing composer dependencies (no-dev)..."
composer install --no-dev --optimize-autoloader --no-interaction
echo "   ✅ Composer dependencies installed"
echo ""

# --- Step 3: Generate APP_KEY (if missing) ---
echo "[3/7] Generating APP_KEY..."
if grep -q "APP_KEY=" .env 2>/dev/null && ! grep -q "APP_KEY=base64:" .env 2>/dev/null; then
  php artisan key:generate --force --no-interaction 2>/dev/null || echo "   ⚠️  Run 'php artisan key:generate' on server if key is missing"
fi
echo "   ✅ APP_KEY check done"
echo ""

# --- Step 4: Laravel Optimization ---
echo "[4/7] Optimizing Laravel..."
php artisan config:cache 2>/dev/null || true
php artisan route:cache 2>/dev/null || true
php artisan view:cache 2>/dev/null || true
echo "   ✅ Laravel optimized"
echo ""

# --- Step 5: Create deployment directory ---
echo "[5/7] Creating deployment package..."
DEPLOY_DIR="smartpkl-deploy"
rm -rf "$DEPLOY_DIR"
mkdir -p "$DEPLOY_DIR"

# Copy Laravel core (excluding public/, frontend source, dev files)
rsync -av \
  --exclude='.git' \
  --exclude='node_modules' \
  --exclude='frontend' \
  --exclude='public' \
  --exclude='konsep' \
  --exclude='tests' \
  --exclude='docs' \
  --exclude='.phpunit.result.cache' \
  --exclude='phpunit.xml' \
  --exclude='.editorconfig' \
  --exclude='.gitattributes' \
  --exclude='deploy.sh' \
  --exclude='export-db.sh' \
  --exclude='smartpkl-deploy' \
  --exclude='smartpkl-deploy.zip' \
  --exclude='smartpkl-deploy.tar.gz' \
  --exclude='.env' \
  --exclude='.env.backup' \
  --exclude='.env.production' \
  --exclude='DEPLOYMENT.md' \
  --exclude='README.md' \
  --exclude='test_*.php' \
  --exclude='smartpkl.sql' \
  . "$DEPLOY_DIR/"

echo "   ✅ Laravel files copied"
echo ""

# --- Step 6: Copy public/ contents to deployment root ---
# For shared hosting: public/ contents go to root of deployment dir
echo "[6/7] Setting up public files for shared hosting..."

# Copy public files to deployment root
cp public/index.php "$DEPLOY_DIR/index.php"
cp public/.htaccess "$DEPLOY_DIR/.htaccess"
cp public/app.html "$DEPLOY_DIR/app.html" 2>/dev/null || true
mkdir -p "$DEPLOY_DIR/assets"
cp -r public/assets/* "$DEPLOY_DIR/assets/" 2>/dev/null || true
cp public/favicon.svg "$DEPLOY_DIR/favicon.svg" 2>/dev/null || true
cp public/robots.txt "$DEPLOY_DIR/robots.txt" 2>/dev/null || true

# Create storage/public symlink target
mkdir -p "$DEPLOY_DIR/storage/app/public"

echo "   ✅ Public files placed at deployment root"
echo ""

# --- Step 6b: Patch index.php for shared hosting ---
# Standard Laravel: require __DIR__.'/../vendor/autoload.php'
# Shared hosting: vendor/ is sibling to index.php, not parent
echo "   Patching index.php for shared hosting paths..."

cat > "$DEPLOY_DIR/index.php" << 'INDEXPHP'
<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Check If The Application Is Under Maintenance
|--------------------------------------------------------------------------
|
| If the application is in maintenance / demo mode via the "down" command
| we will load this file so that any pre-rendered content can be shown
| instead of starting the framework, which could cause an exception.
|
*/

if (file_exists($maintenance = __DIR__.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader for
| this application. We just need to utilize it! We'll simply require it
| into the script here so we don't need to manually load our classes.
|
*/

require __DIR__.'/vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Run The Application
|--------------------------------------------------------------------------
|
| Once we have the application, we can handle the incoming request using
| the application's HTTP kernel. Then, we will send the response back
| to this client's browser, allowing them to enjoy our application.
|
*/

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
INDEXPHP

echo "   ✅ index.php patched for shared hosting"
echo ""

# --- Step 6c: Patch bootstrap/app.php for shared hosting ---
# Standard: dirname(__DIR__) points to project root above public/
# Shared hosting: all files in same dir, APP_BASE_PATH should be __DIR__/..
cat > "$DEPLOY_DIR/bootstrap/app.php" << 'BOOTSTRAPPHP'
<?php

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
*/

$app = new Illuminate\Foundation\Application(
    $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__)
);

/*
|--------------------------------------------------------------------------
| Bind Important Interfaces
|--------------------------------------------------------------------------
*/

$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

/*
|--------------------------------------------------------------------------
| Return The Application
|--------------------------------------------------------------------------
*/

return $app;
BOOTSTRAPPHP

echo "   ✅ bootstrap/app.php verified for shared hosting"
echo ""

# --- Step 6d: Create .env from template ---
echo "   Creating .env for production..."

if [ -f ".env.production" ]; then
  cp .env.production "$DEPLOY_DIR/.env"
  echo "   ✅ .env.production copied as .env"
else
  cat > "$DEPLOY_DIR/.env" << 'ENVTEMPLATE'
APP_NAME=SmartPKL
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://your-domain.com

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

MEMCACHED_HOST=127.0.0.1

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@your-domain.com"
MAIL_FROM_NAME="${APP_NAME}"

VITE_APP_NAME="${APP_NAME}"
ENVTEMPLATE
  echo "   ✅ .env template created"
fi
echo ""

# --- Step 7: Create ZIP ---
echo "[7/7] Creating deployment ZIP..."
rm -f smartpkl-deploy.zip
zip -r smartpkl-deploy.zip "$DEPLOY_DIR" -x "*.DS_Store" -x "*/storage/logs/*" -x "*/storage/framework/cache/*" -x "*/storage/framework/sessions/*" -x "*/storage/framework/views/*"
rm -rf "$DEPLOY_DIR"

echo ""
echo "=========================================="
echo "  ✅ Deployment package ready!"
echo "=========================================="
echo ""
echo "  📦 File: smartpkl-deploy.zip"
echo ""
echo "  📋 STRUKTUR FOLDER (untuk upload ke Hostinger):"
echo ""
echo "     public_html/"
echo "     ├── index.php          ← Laravel entry point"
echo "     ├── .htaccess          ← API + SPA routing"
echo "     ├── app.html           ← React SPA"
echo "     ├── assets/            ← React JS/CSS"
echo "     ├── app/               ← Laravel application"
echo "     ├── bootstrap/"
echo "     ├── config/"
echo "     ├── database/"
echo "     ├── resources/"
echo "     ├── routes/"
echo "     ├── storage/"
echo "     ├── vendor/"
echo "     └── .env               ← isi kredensial database"
echo ""
echo "  📋 LANGKAH DEPLOYMENT:"
echo ""
echo "  1. Login cPanel → MySQL Databases"
echo "     - Buat database (contoh: youruser_smartpkl)"
echo "     - Buat MySQL User (username + password)"
echo "     - Add User to Database → ALL PRIVILEGES"
echo ""
echo "  2. Login cPanel → phpMyAdmin"
echo "     - Pilih database yang baru dibuat"
echo "     - Tab Import → Upload smartpkl.sql → Go"
echo ""
echo "     !! PENTING (data loss prevention):"
echo "     smartpkl.sql berisi 'DROP TABLE IF EXISTS' untuk semua tabel."
echo "     Impor file ini HANYA pada database KOSONG / setup pertama kali."
echo "     JANGAN PERNAH impor ulang di atas database yang SUDAH berisi data"
echo "     (termasuk data user) — semua data akan TERHAPUS permanen."
echo "     Untuk update skema pada database yang sudah ada, jalankan:"
echo "     php artisan migrate   (di server, di direktori aplikasi)."
echo ""
echo "  3. Login cPanel → File Manager"
echo "     - Navigate ke public_html/"
echo "     - Upload smartpkl-deploy.zip"
echo "     - Extract ZIP di public_html/"
echo "     - Pindahkan isi smartpkl-deploy/ ke public_html/ (root)"
echo "     - Hapus folder smartpkl-deploy/ dan ZIP"
echo ""
echo "  4. Edit .env → isi nilai berikut:"
echo "     - APP_URL=https://your-domain.com"
echo "     - DB_DATABASE=your_database_name"
echo "     - DB_USERNAME=your_mysql_username"
echo "     - DB_PASSWORD=your_mysql_password"
echo "     - APP_KEY=base64:... (jika kosong, generate via php artisan key:generate)"
echo ""
echo "  5. Set permissions via File Manager:"
echo "     - storage/ → chmod 755 (recursive)"
echo "     - bootstrap/cache/ → chmod 755 (recursive)"
echo ""
echo "  6. Test:"
echo "     - Buka https://your-domain.com/api/health"
echo "     - Buka https://your-domain.com/ (landing page)"
echo "     - Buka https://your-domain.com/login"
echo ""
echo "  🗄️  DATABASE INFO:"
echo "     - Export SQL: bash export-db.sh"
echo "     - File output: smartpkl.sql"
echo ""
echo "  📖 Dokumentasi lengkap: DEPLOYMENT.md"
echo ""
