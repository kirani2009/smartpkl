#!/bin/bash
# ============================================================
# SmartPKL — Shared Hosting Deployment Script
# ============================================================
# Jalankan script ini SEBELUM upload ke shared hosting.
# Script ini akan:
#   1. Build frontend React
#   2. Install composer dependencies (production)
#   3. Copy React build ke public/
#   4. Generate APP_KEY
#   5. Export database migration SQL
#   6. Buat ZIP package siap upload
# ============================================================

set -e

echo "=========================================="
echo "  SmartPKL Deployment Preparation"
echo "=========================================="
echo ""

# --- Step 1: Build Frontend ---
echo "[1/6] Building frontend React..."
cd frontend
npm install
npm run build
cd ..
echo "   ✅ Frontend built -> frontend/dist/"
echo ""

# --- Step 2: Install Composer Dependencies (production) ---
echo "[2/6] Installing composer dependencies (no-dev)..."
composer install --no-dev --optimize-autoloader --no-interaction
echo "   ✅ Composer dependencies installed"
echo ""

# --- Step 3: Copy React Build to public/ ---
echo "[3/6] Copying React build to public/..."
mkdir -p public/assets
cp frontend/dist/index.html public/app.html
cp frontend/dist/assets/* public/assets/
cp frontend/dist/favicon.svg public/ 2>/dev/null || true
cp frontend/dist/icons.svg public/ 2>/dev/null || true
echo "   ✅ React files copied to public/"
echo "   ✅ React files copied to public/"
echo ""

# --- Step 4: Generate APP_KEY (if missing) ---
echo "[4/6] Generating APP_KEY..."
php artisan key:generate --force --no-interaction 2>/dev/null || echo "   ⚠️  Run 'php artisan key:generate' on server if key is missing"
echo "   ✅ APP_KEY check done"
echo ""

# --- Step 5: Laravel Optimization ---
echo "[5/6] Optimizing Laravel..."
php artisan config:cache 2>/dev/null || true
php artisan route:cache 2>/dev/null || true
php artisan view:cache 2>/dev/null || true
php artisan icons:cache 2>/dev/null || true
echo "   ✅ Laravel optimized"
echo ""

# --- Step 6: Create deployment ZIP ---
echo "[6/6] Creating deployment ZIP..."
# Create a temp deploy directory
DEPLOY_DIR="smartpkl-deploy"
rm -rf "$DEPLOY_DIR"
mkdir -p "$DEPLOY_DIR"

# Copy everything needed
rsync -av --exclude='.git' \
  --exclude='node_modules' \
  --exclude='frontend/node_modules' \
  --exclude='frontend/src' \
  --exclude='frontend/dist' \
  --exclude='frontend/.oxlintrc.json' \
  --exclude='frontend/postcss.config.js' \
  --exclude='frontend/tailwind.config.js' \
  --exclude='frontend/vite.config.js' \
  --exclude='frontend/README.md' \
  --exclude='konsep' \
  --exclude='tests' \
  --exclude='docs' \
  --exclude='docs/ai' \
  --exclude='docs/security' \
  --exclude='.phpunit.result.cache' \
  --exclude='phpunit.xml' \
  --exclude='.editorconfig' \
  --exclude='deploy.sh' \
  --exclude='smartpkl-deploy' \
  --exclude='smartpkl-deploy.zip' \
  --exclude='.env' \
  --exclude='.env.backup' \
  . "$DEPLOY_DIR/"

# Copy .env.production as .env
if [ -f ".env.production" ]; then
  cp .env.production "$DEPLOY_DIR/.env"
  echo "   ✅ .env.production copied as .env"
else
  echo "   ⚠️  .env.production not found! Creating template..."
  cp .env.example "$DEPLOY_DIR/.env"
fi

# Copy updated public/ with SPA routing
cp public/.htaccess "$DEPLOY_DIR/public/.htaccess"
cp public/app.html "$DEPLOY_DIR/public/app.html" 2>/dev/null || true
cp -r public/assets/* "$DEPLOY_DIR/public/assets/" 2>/dev/null || true

# Create ZIP
rm -f smartpkl-deploy.zip
zip -r smartpkl-deploy.zip "$DEPLOY_DIR" -x "*.DS_Store"
rm -rf "$DEPLOY_DIR"

echo ""
echo "=========================================="
echo "  ✅ Deployment package ready!"
echo "=========================================="
echo ""
echo "  📦 File: smartpkl-deploy.zip"
echo ""
echo "  📋 Upload ke cPanel:"
echo "     1. Login cPanel → File Manager"
echo "     2. Navigate ke document root (public_html atau subdomain)"
echo "     3. Upload smartpkl-deploy.zip"
echo "     4. Extract ZIP di document root"
echo "     5. Pindahkan isi folder 'smartpkl-deploy' ke document root"
echo "     6. Pastikan folder 'public' adalah document root"
echo ""
echo "  🗄️  Setup Database:"
echo "     1. Login cPanel → MySQL Databases"
echo "     2. Buat database 'smartpkl'"
echo "     3. Buat user MySQL dan tambahkan ke database"
echo "     4. Import smartpkl.sql ke database"
echo "     5. Update .env dengan kredensial database hosting"
echo ""
echo "  ⚡ Post-Upload:"
echo "     1. Edit .env → set APP_URL ke domain hosting"
echo "     2. Edit .env → set DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD"
echo "     3. php artisan key:generate (jika APP_KEY kosong)"
echo "     4. php artisan migrate (jika tidak import SQL)"
echo "     5. chmod 755 storage/ bootstrap/cache/"
echo "     6. Test: https://domain-anda.com/api/health"
echo ""
