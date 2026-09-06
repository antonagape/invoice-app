#!/usr/bin/env bash
#
# install.sh — Install database Invoice App
# ------------------------------------------------------------
# Cara pakai:
#   ./install.sh
#
# Konfigurasi bisa di-override lewat environment variable:
#   DB_HOST=127.0.0.1 DB_PORT=8889 DB_USER=root DB_PASS=root \
#   DB_NAME=dbinvoice ./install.sh
#
# Yang dilakukan:
#   1. Cek koneksi ke MySQL
#   2. Buat database (CREATE DATABASE IF NOT EXISTS)
#   3. Impor db_schema.sql (tabel + baris settings)
#
# Catatan: akun admin/admin123 dibuat otomatis oleh aplikasi
# saat pertama kali dibuka (lihat config.php → ensureSchema).
# ============================================================
set -euo pipefail

# ===== Konfigurasi (default: MySQL lokal gaya MAMP) =====
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-8889}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-root}"
DB_NAME="${DB_NAME:-dbinvoice}"

DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
SCHEMA_FILE="$DIR/db_schema.sql"

echo "==> Invoice App — Installer Database"
echo "    Target : mysql://$DB_USER@$DB_HOST:$DB_PORT/$DB_NAME"
echo ""

# ===== Prasyarat =====
if ! command -v mysql >/dev/null 2>&1; then
    echo "❌ Client 'mysql' tidak ditemukan. Install dulu: sudo apt install mysql-client"
    exit 1
fi
if [[ ! -f "$SCHEMA_FILE" ]]; then
    echo "❌ File '$SCHEMA_FILE' tidak ditemukan."
    echo "   Pastikan db_schema.sql berada satu folder dengan install.sh."
    exit 1
fi

# Password lewat env (MYSQL_PWD) supaya tidak bocor ke daftar proses (ps).
export MYSQL_PWD="$DB_PASS"
MYSQL=(mysql --host="$DB_HOST" --port="$DB_PORT" --user="$DB_USER" --batch --skip-column-names)

# ===== 1. Cek koneksi =====
if ! "${MYSQL[@]}" -e "SELECT 1" >/dev/null 2>&1; then
    echo "❌ Tidak bisa konek ke MySQL $DB_HOST:$DB_PORT sebagai '$DB_USER'."
    echo "   Periksa: MySQL aktif? Port benar? Kredensial benar?"
    echo "   Uji manual: mysql -h$DB_HOST -P$DB_PORT -u$DB_USER -p"
    exit 1
fi
echo "✅ Koneksi MySQL OK"

# ===== 2. Buat database =====
"${MYSQL[@]}" -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
echo "✅ Database '$DB_NAME' siap (atau sudah ada)"

# ===== 3. Impor skema =====
"${MYSQL[@]}" "$DB_NAME" < "$SCHEMA_FILE"
echo "✅ Skema db_schema.sql berhasil diimpor"

# ===== Verifikasi =====
TABLE_COUNT=$("${MYSQL[@]}" -e "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = '$DB_NAME'")
SETTINGS_COUNT=$("${MYSQL[@]}" -e "SELECT COUNT(*) FROM \`$DB_NAME\`.settings WHERE id = 1")

echo ""
echo "✅ Install selesai — database '$DB_NAME' siap dipakai."
echo "   Tabel dibuat       : $TABLE_COUNT"
echo "   Baris settings id=1: $SETTINGS_COUNT"
echo ""
echo "   Langkah berikutnya: buka aplikasi di browser, akun awal"
echo "   admin/admin123 dibuat otomatis saat halaman pertama diakses."
echo "   ⚠️  WAJIB ganti password admin setelah login pertama."
