@echo off
rem ============================================================
rem  SmartPKL dev stack (since 2026-09-06)
rem  API/login/register dilayani oleh XAMPP Apache
rem  multi-thread di http://127.0.0.1:8000 (vhost project untuk
rem  mengatasi timeout "Maximum execution time exceeded" pada
rem  `php artisan serve` yang single-threaded).
rem
rem  BACKEND (Apache, port 8000):  jalankan lewat XAMPP Control
rem  Panel / `C:\xampp\apache\bin\httpd.exe`. Sudah terpasang
rem  vhost E:/smartpkl/public. JANGAN jalankan `php artisan serve`
rem  bersamaan (bentrok port 8000).
rem
rem  FRONTEND (Vite, port 5173): npm run dev di folder frontend/
rem
rem  fallback lama (tanpa Apache):
rem    set PHP_INI_SCAN_DIR=%~dp0phpconf
rem    php artisan serve --host=127.0.0.1 --port=8010
rem ============================================================
if not exist "frontend\node_modules" (
  echo [serve.bat] frontend\node_modules belum ada - jalankan: npm install
  exit /b 1
)
echo [serve.bat] Memulai Vite dev server (http://localhost:5173)...
cd frontend
call npm run dev