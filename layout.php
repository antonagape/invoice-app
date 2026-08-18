<?php
/**
 * Layout bersama: header & footer halaman admin.
 */
require_once __DIR__ . '/config.php';

function page_header(string $title): void
{
    $s = get_settings();
    $company = $s['company_name'] ?? '';
    $flash = flash_get();
    $user = current_user();
    ?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($title) ?> — <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="<?= e(APP_BASE) ?>/assets/style.css">
</head>
<body>
<nav class="topnav">
    <div class="nav-inner">
        <span class="brand"><?= e($company !== '' ? $company : APP_NAME) ?></span>
        <div class="nav-links">
            <a href="<?= e(APP_BASE) ?>/index.php">Dashboard</a>
            <a href="<?= e(APP_BASE) ?>/templates.php">Template</a>
            <a href="<?= e(APP_BASE) ?>/users.php">Pengguna</a>
            <a href="<?= e(APP_BASE) ?>/invoice_form.php" class="btn-primary">+ Buat Invoice</a>
            <a href="<?= e(APP_BASE) ?>/settings.php">Pengaturan</a>
        </div>
        <div class="nav-user">
            <span class="nav-user-name"><?= e($user['name'] !== '' ? $user['name'] : $user['username']) ?></span>
            <a href="<?= e(APP_BASE) ?>/logout.php" class="nav-logout">Logout</a>
        </div>
    </div>
</nav>
<main class="container">
    <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div>
    <?php endif; ?>
    <h1><?= e($title) ?></h1>
<?php
}

function page_footer(): void
{
    ?>
</main>
<script src="<?= e(APP_BASE) ?>/assets/app.js"></script>
</body>
</html>
<?php
}
