<?php
/**
 * Hapus template (POST).
 */
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_BASE . '/templates.php');
}

$pdo = db();
$id  = (int)($_POST['id'] ?? 0);
if ($id > 0) {
    $st = $pdo->prepare('SELECT name FROM templates WHERE id = ?');
    $st->execute([$id]);
    $t = $st->fetch();
    $pdo->prepare('DELETE FROM templates WHERE id = ?')->execute([$id]);
    flash_set('Template "' . ($t['name'] ?? '') . '" dihapus.');
}

redirect(APP_BASE . '/templates.php');
