#!/bin/bash
# ============================================================
# SmartPKL — Database Export for Shared Hosting
# ============================================================
# Jalankan script ini untuk export database ke SQL file
# yang bisa di-import ke phpMyAdmin di cPanel.
#
# Prasyarat: MySQL/MariaDB berjalan, database smartpkl ada.
# ============================================================

set -e

DB_NAME="${DB_NAME:-smartpkl}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-}"
SQL_FILE="smartpkl.sql"

echo "=========================================="
echo "  SmartPKL Database Export"
echo "=========================================="
echo ""
echo "  Database: $DB_NAME"
echo "  Output:   $SQL_FILE"
echo ""

# Check if mysqldump is available
if ! command -v mysqldump &> /dev/null; then
    echo "❌ mysqldump not found! Install MySQL client tools."
    exit 1
fi

# Export with data
if [ -n "$DB_PASS" ]; then
    mysqldump -u "$DB_USER" -p"$DB_PASS" \
        --single-transaction \
        --routines \
        --triggers \
        --events \
        --add-drop-table \
        --complete-insert \
        --default-character-set=utf8mb4 \
        "$DB_NAME" > "$SQL_FILE"
else
    mysqldump -u "$DB_USER" \
        --single-transaction \
        --routines \
        --triggers \
        --events \
        --add-drop-table \
        --complete-insert \
        --default-character-set=utf8mb4 \
        "$DB_NAME" > "$SQL_FILE"
fi

echo "✅ Database exported to $SQL_FILE"
echo ""
echo "📋 Untuk import ke hosting:"
echo "   1. Login cPanel → phpMyAdmin"
echo "   2. Pilih/import database 'smartpkl'"
echo "   3. Upload $SQL_FILE"
echo "   4. Klik 'Import' / 'Go'"
echo ""
echo "Atau via command line di server:"
echo "   mysql -u USER -p DATABASE_NAME < $SQL_FILE"
echo ""
