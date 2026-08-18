<?php
/**
 * Simpan user (tambah/edit).
 */
require_once __DIR__ . '/config.php';

$pdo = db();
$id        = (int)post('id', '0');
$username  = post('username');
$name      = post('name');
$role      = post('role') !== '' ? post('role') : 'admin';
$isActive  = isset($_POST['is_active']) ? 1 : 0;
$password  = (string)($_POST['password'] ?? '');
$password2 = (string)($_POST['password_confirm'] ?? '');
$backUrl   = APP_BASE . '/user_form.php' . ($id > 0 ? '?id=' . $id : '');

// Validasi
if ($username === '') {
    flash_set('Username wajib diisi.', 'error');
    redirect($backUrl);
}
if (!preg_match('/^[A-Za-z0-9_.-]+$/', $username)) {
    flash_set('Username hanya boleh huruf, angka, titik, garis bawah, strip.', 'error');
    redirect($backUrl);
}

// Username unik (kecuali dirinya sendiri saat edit)
if ($id > 0) {
    $st = $pdo->prepare('SELECT id FROM users WHERE username = ? AND id != ?');
    $st->execute([$username, $id]);
} else {
    $st = $pdo->prepare('SELECT id FROM users WHERE username = ?');
    $st->execute([$username]);
}
if ($st->fetch()) {
    flash_set('Username sudah dipakai. Pilih username lain.', 'error');
    redirect($backUrl);
}

// Password
if ($password !== '' || $password2 !== '') {
    if (strlen($password) < 6) {
        flash_set('Password minimal 6 karakter.', 'error');
        redirect($backUrl);
    }
    if ($password !== $password2) {
        flash_set('Konfirmasi password tidak cocok.', 'error');
        redirect($backUrl);
    }
}

// Jangan izinkan menonaktifkan akun sendiri (biar tidak terkunci tanpa sengaja)
$isMe = $id > 0 && $id === (int)($_SESSION['user_id'] ?? 0);
if ($isMe) {
    $isActive = 1;
}

if ($id > 0) {
    if ($password !== '') {
        $pdo->prepare('UPDATE users SET username = ?, name = ?, role = ?, is_active = ?, password_hash = ? WHERE id = ?')
            ->execute([$username, $name, $role, $isActive, password_hash($password, PASSWORD_DEFAULT), $id]);
    } else {
        $pdo->prepare('UPDATE users SET username = ?, name = ?, role = ?, is_active = ? WHERE id = ?')
            ->execute([$username, $name, $role, $isActive, $id]);
    }
    flash_set('User berhasil diperbarui.');
} else {
    $pdo->prepare('INSERT INTO users (username, password_hash, name, role, is_active) VALUES (?, ?, ?, ?, ?)')
        ->execute([$username, password_hash($password, PASSWORD_DEFAULT), $name, $role, $isActive]);
    flash_set('User berhasil ditambahkan.');
}

redirect(APP_BASE . '/users.php');
