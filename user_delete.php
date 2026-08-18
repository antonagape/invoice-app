<?php
/**
 * Hapus user (POST only).
 */
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_BASE . '/users.php');
}

$pdo = db();
$id  = (int)post('id', '0');

if ($id <= 0) {
    flash_set('User tidak ditemukan.', 'error');
    redirect(APP_BASE . '/users.php');
}

if ($id === (int)($_SESSION['user_id'] ?? 0)) {
    flash_set('Tidak bisa menghapus akun yang sedang login.', 'error');
    redirect(APP_BASE . '/users.php');
}

$st = $pdo->prepare('DELETE FROM users WHERE id = ?');
$st->execute([$id]);

flash_set('User berhasil dihapus.');
redirect(APP_BASE . '/users.php');
