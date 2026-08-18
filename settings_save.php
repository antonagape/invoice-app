<?php
/**
 * Simpan pengaturan (POST, multipart).
 */
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_BASE . '/settings.php');
}

$pdo = db();
$s = get_settings();

$logoPath = $s['logo_path'] ?? null;

// ===== Hapus logo =====
if (!empty($_POST['remove_logo'])) {
    if ($logoPath) {
        $old = __DIR__ . '/uploads/' . basename($logoPath);
        if (is_file($old)) @unlink($old);
    }
    $logoPath = null;
}

// ===== Upload logo baru =====
if (!empty($_FILES['logo']['name']) && is_uploaded_file($_FILES['logo']['tmp_name'])) {
    $file = $_FILES['logo'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        flash_set('Gagal upload logo (kode ' . $file['error'] . ').', 'error');
        redirect(APP_BASE . '/settings.php');
    }
    if ($file['size'] > 2 * 1024 * 1024) {
        flash_set('Ukuran logo maksimal 2MB.', 'error');
        redirect(APP_BASE . '/settings.php');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = (string)$finfo->file($file['tmp_name']);
    $allowed = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    if (!isset($allowed[$mime])) {
        flash_set('File logo harus PNG/JPG/WebP/GIF.', 'error');
        redirect(APP_BASE . '/settings.php');
    }

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    $newName = 'logo_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
    $dest = UPLOAD_DIR . '/' . $newName;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        flash_set('Gagal menyimpan file logo.', 'error');
        redirect(APP_BASE . '/settings.php');
    }

    // Hapus logo lama
    if ($logoPath && is_file(__DIR__ . '/uploads/' . basename($logoPath))) {
        @unlink(__DIR__ . '/uploads/' . basename($logoPath));
    }
    $logoPath = $newName;
}

// ===== Update data =====
$data = [
    'logo_path'           => $logoPath,
    'logo_bg_opacity'     => min(100, max(0, (int)post('logo_bg_opacity', '8'))),
    'company_name'        => post('company_name'),
    'company_address'     => post('company_address'),
    'company_email'       => post('company_email'),
    'company_phone'       => post('company_phone'),
    'currency'            => post('currency') !== '' ? post('currency') : 'Rp',
    'invoice_prefix'      => post('invoice_prefix') !== '' ? post('invoice_prefix') : 'INV-',
    'invoice_next_number' => max(1, (int)post('invoice_next_number', '1')),
    'invoice_digits'      => min(8, max(1, (int)post('invoice_digits', '4'))),
    'default_sign_name'   => post('default_sign_name'),
    'default_sign_position' => post('default_sign_position'),
    'bank_name'           => post('bank_name'),
    'bank_account_number' => post('bank_account_number'),
    'bank_account_holder' => post('bank_account_holder'),
];

$pdo->prepare('UPDATE settings SET logo_path=:logo_path, logo_bg_opacity=:logo_bg_opacity,
               company_name=:company_name,
               company_address=:company_address, company_email=:company_email,
               company_phone=:company_phone, currency=:currency,
               invoice_prefix=:invoice_prefix, invoice_next_number=:invoice_next_number,
               invoice_digits=:invoice_digits,
               default_sign_name=:default_sign_name, default_sign_position=:default_sign_position,
               bank_name=:bank_name, bank_account_number=:bank_account_number,
               bank_account_holder=:bank_account_holder
               WHERE id = 1')->execute($data);

flash_set('Pengaturan berhasil disimpan.');
redirect(APP_BASE . '/settings.php');
