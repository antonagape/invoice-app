<?php
/**
 * Invoice App — Konfigurasi & Helper
 * PHP 8.4 / MySQL (MAMP)
 */
declare(strict_types=1);

session_start();

// ===== Konfigurasi Database (MAMP default) =====
const DB_HOST = '127.0.0.1';
const DB_PORT = 8889;
const DB_NAME = 'dbinvoice';
const DB_USER = 'root';
const DB_PASS = 'root';

const APP_NAME = 'Invoice App';
const APP_BASE = '/invoice-app';   // path dari docroot
const UPLOAD_DIR = __DIR__ . '/uploads';

date_default_timezone_set('Asia/Jakarta');

// ===== Koneksi PDO =====
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            exit('Koneksi database gagal: ' . htmlspecialchars($e->getMessage()));
        }
        ensureSchema($pdo);
    }
    return $pdo;
}

// ===== Auto-install schema (idempoten, jalan tiap konek) =====
function ensureSchema(PDO $pdo): void
{
    $sql = <<<'SQL'
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    logo_path VARCHAR(255) DEFAULT NULL,
    logo_bg_opacity TINYINT NOT NULL DEFAULT 8,
    company_name VARCHAR(255) NOT NULL DEFAULT '',
    company_address TEXT,
    company_email VARCHAR(255) DEFAULT '',
    company_phone VARCHAR(100) DEFAULT '',
    currency VARCHAR(20) NOT NULL DEFAULT 'Rp',
    invoice_prefix VARCHAR(20) NOT NULL DEFAULT 'INV-',
    invoice_next_number INT NOT NULL DEFAULT 1,
    invoice_digits INT NOT NULL DEFAULT 4,
    default_sign_name VARCHAR(255) DEFAULT '',
    default_sign_position VARCHAR(255) DEFAULT '',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_no VARCHAR(50) NOT NULL UNIQUE,
    invoice_to VARCHAR(255) NOT NULL,
    invoice_to_company VARCHAR(255) DEFAULT '',
    invoice_to_address TEXT,
    invoice_to_phone VARCHAR(100) DEFAULT '',
    invoice_to_email VARCHAR(255) DEFAULT '',
    date_invoice DATE NOT NULL,
    payment_terms VARCHAR(100) DEFAULT '',
    subtotal DECIMAL(15,2) NOT NULL DEFAULT 0,
    discount DECIMAL(15,2) NOT NULL DEFAULT 0,
    paid DECIMAL(15,2) NOT NULL DEFAULT 0,
    remaining DECIMAL(15,2) NOT NULL DEFAULT 0,
    sign_name VARCHAR(255) DEFAULT '',
    sign_position VARCHAR(255) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_invoice_no (invoice_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS invoice_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    qty INT NOT NULL DEFAULT 1,
    price DECIMAL(15,2) NOT NULL DEFAULT 0,
    subtotal DECIMAL(15,2) NOT NULL DEFAULT 0,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    invoice_to VARCHAR(255) NOT NULL DEFAULT '',
    invoice_to_company VARCHAR(255) DEFAULT '',
    invoice_to_address TEXT,
    invoice_to_phone VARCHAR(100) DEFAULT '',
    invoice_to_email VARCHAR(255) DEFAULT '',
    payment_terms VARCHAR(100) DEFAULT '',
    sign_name VARCHAR(255) DEFAULT '',
    sign_position VARCHAR(255) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_tpl_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS template_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_id INT NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    qty INT NOT NULL DEFAULT 1,
    price DECIMAL(15,2) NOT NULL DEFAULT 0,
    FOREIGN KEY (template_id) REFERENCES templates(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(150) NOT NULL DEFAULT '',
    role VARCHAR(30) NOT NULL DEFAULT 'admin',
    is_active TINYINT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

    $pdo->exec($sql);

    $pdo->prepare("INSERT INTO settings (id, company_name) VALUES (1, '') ON DUPLICATE KEY UPDATE id = id")
        ->execute();

    ensureColumn($pdo, 'settings', 'logo_bg_opacity', 'TINYINT NOT NULL DEFAULT 8');
    ensureColumn($pdo, 'settings', 'bank_name', 'VARCHAR(100) NOT NULL DEFAULT \'\'');
    ensureColumn($pdo, 'settings', 'bank_account_number', 'VARCHAR(50) NOT NULL DEFAULT \'\'');
    ensureColumn($pdo, 'settings', 'bank_account_holder', 'VARCHAR(150) NOT NULL DEFAULT \'\'');

    // Seed user admin pertama (hanya jika tabel users kosong)
    $st = $pdo->query('SELECT COUNT(*) FROM users');
    if ((int)$st->fetchColumn() === 0) {
        $pdo->prepare('INSERT INTO users (username, password_hash, name, role) VALUES (?, ?, ?, ?)')
            ->execute(['admin', password_hash('admin123', PASSWORD_DEFAULT), 'Administrator', 'admin']);
    }
}

// ===== Migrasi kolom kecil untuk DB lama =====
function ensureColumn(PDO $pdo, string $table, string $column, string $definition): void
{
    $st = $pdo->prepare(
        "SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?"
    );
    $st->execute([$table, $column]);
    if ((int)$st->fetchColumn() === 0) {
        $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
    }
}

// ===== Helper =====
function e(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function money(float $amount, string $currency = 'Rp'): string
{
    return $currency . ' ' . number_format($amount, 0, ',', '.');
}

function get_settings(): array
{
    static $s = null;
    if ($s === null) {
        $row = db()->query('SELECT * FROM settings WHERE id = 1')->fetch();
        $s = $row ?: [];
    }
    return $s;
}

function next_invoice_number(): string
{
    $s = get_settings();
    $prefix = $s['invoice_prefix'] ?? 'INV-';
    $num    = (int)($s['invoice_next_number'] ?? 1);
    $digits = max(1, (int)($s['invoice_digits'] ?? 4));
    return $prefix . str_pad((string)$num, $digits, '0', STR_PAD_LEFT);
}

function flash_set(string $msg, string $type = 'success'): void
{
    $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
}

function flash_get(): ?array
{
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function post(string $key, ?string $default = ''): string
{
    return trim((string)($_POST[$key] ?? $default));
}

function post_float(string $key): float
{
    $v = str_replace(['.', ' ', 'Rp', 'rp'], '', (string)($_POST[$key] ?? '0'));
    $v = str_replace(',', '.', $v);
    return max(0.0, (float)$v);
}

function money_parse(string $v): float
{
    $v = trim((string)$v);
    if ($v === '') return 0.0;
    // "Rp 1.250.000" / "1.250.000" / "1250000" / "1,250,000"
    $v = str_replace(['Rp', 'rp', ' '], '', $v);
    $v = str_replace('.', '', $v);
    $v = str_replace(',', '.', $v);
    return max(0.0, (float)$v);
}

// ===== Autentikasi =====
function current_user(): ?array
{
    static $u = false;
    if ($u === false) {
        $u = null;
        if (!empty($_SESSION['user_id'])) {
            $st = db()->prepare('SELECT * FROM users WHERE id = ? AND is_active = 1');
            $st->execute([(int)$_SESSION['user_id']]);
            $u = $st->fetch() ?: null;
            if ($u === null) {
                unset($_SESSION['user_id']); // user dinonaktifkan/dihapus
            }
        }
    }
    return $u;
}

function require_login(): void
{
    if (current_user() === null) {
        flash_set('Silakan login terlebih dahulu.', 'error');
        redirect(APP_BASE . '/login.php');
    }
}

function attempt_login(string $username, string $password): bool
{
    $st = db()->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
    $st->execute([$username]);
    $u = $st->fetch();
    if (!$u || (int)$u['is_active'] !== 1) {
        return false;
    }
    if (!password_verify($password, $u['password_hash'])) {
        return false;
    }
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$u['id'];
    return true;
}

// ===== Proteksi semua halaman kecuali login.php =====
if (basename($_SERVER['SCRIPT_NAME'] ?? '') !== 'login.php') {
    require_login();
}
