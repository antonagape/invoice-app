-- ============================================================
-- Invoice App — Skema Database (db_schema.sql)
-- ------------------------------------------------------------
-- Cara pakai manual:
--   mysql -uroot -p dbinvoice < db_schema.sql
-- (atau jalankan install.sh yang otomatis membuat database
--  lalu mengimpor file ini)
--
-- Idempoten: semua CREATE TABLE memakai IF NOT EXISTS,
-- aman dijalankan berulang kali.
--
-- Catatan: akun awal (admin/admin123) TIDAK di-seed di sini.
-- Aplikasi (config.php → ensureSchema) yang membuatnya otomatis
-- saat tabel users kosong pada koneksi pertama. Password admin
-- wajib diganti setelah login pertama.
-- ============================================================

-- settings — 1 baris konfigurasi aplikasi (id = 1)
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
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    -- Kolom bank ditambahkan di akhir (meniru ALTER TABLE ensureColumn)
    bank_name VARCHAR(100) NOT NULL DEFAULT '',
    bank_account_number VARCHAR(50) NOT NULL DEFAULT '',
    bank_account_holder VARCHAR(150) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Baris settings wajib ada (id=1); INSERT IGNORE agar idempoten.
INSERT IGNORE INTO settings (id, company_name) VALUES (1, '');

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
