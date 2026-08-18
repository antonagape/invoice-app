<?php
/**
 * Halaman Login
 */
require_once __DIR__ . '/config.php';

if (current_user() !== null) {
    redirect(APP_BASE . '/index.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = post('username');
    $password = (string)($_POST['password'] ?? '');
    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } elseif (attempt_login($username, $password)) {
        redirect(APP_BASE . '/index.php');
    } else {
        $error = 'Username atau password salah.';
    }
}
$flash = flash_get();
$s     = get_settings();
$company = $s['company_name'] ?? '';
$hasLogo = !empty($s['logo_path']) && is_file(UPLOAD_DIR . '/' . basename($s['logo_path']));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Masuk — <?= e($company !== '' ? $company : APP_NAME) ?></title>
<link rel="stylesheet" href="<?= e(APP_BASE) ?>/assets/style.css">
</head>
<body class="auth-body">
    <div class="auth-card">
        <div class="auth-brand">
            <?php if ($hasLogo): ?>
                <img src="<?= e(APP_BASE) ?>/uploads/<?= e(basename($s['logo_path'])) ?>"
                     alt="Logo" class="auth-logo">
            <?php endif; ?>
            <div class="auth-company"><?= e($company !== '' ? $company : APP_NAME) ?></div>
        </div>

        <h1>Masuk</h1>
        <p class="auth-sub">Silakan masuk dengan akun Anda untuk mengelola invoice.</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>
        <?php if ($flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div>
        <?php endif; ?>

        <form method="post" action="login.php" autocomplete="off" class="auth-form">
            <div class="field">
                <label>Username</label>
                <input type="text" name="username" value="<?= e(post('username')) ?>" required autofocus>
            </div>
            <div class="field">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Login</button>
        </form>

        <p class="auth-hint">Lupa password? Hubungi admin.</p>
    </div>
    <p class="auth-footer">© <?= date('Y') ?> <?= e($company !== '' ? $company : APP_NAME) ?></p>
</body>
</html>
