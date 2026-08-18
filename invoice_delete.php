<?php
/**
 * Hapus invoice (POST).
 */
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_BASE . '/index.php');
}

$id = (int)($_POST['id'] ?? 0);
if ($id > 0) {
    $stmt = db()->prepare('DELETE FROM invoices WHERE id = ?');
    $stmt->execute([$id]);
    flash_set('Invoice berhasil dihapus.');
}
redirect(APP_BASE . '/index.php');
