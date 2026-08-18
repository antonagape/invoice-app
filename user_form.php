<?php
/**
 * Form Tambah/Edit User.
 */
require_once __DIR__ . '/layout.php';

$pdo = db();
$id = (int)($_GET['id'] ?? 0);
$user = null;
if ($id > 0) {
    $st = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $st->execute([$id]);
    $user = $st->fetch();
    if (!$user) {
        flash_set('User tidak ditemukan.', 'error');
        redirect(APP_BASE . '/users.php');
    }
}

page_header($id > 0 ? 'Edit User' : 'Tambah User');
?>
<div class="form-wrap card">
<form method="post" action="user_save.php" autocomplete="off">
    <input type="hidden" name="id" value="<?= $id ?>">

    <div class="field">
        <label>Username</label>
        <input type="text" name="username" value="<?= e($user['username'] ?? '') ?>"
               pattern="[A-Za-z0-9_.-]+" required>
        <small>Hanya huruf, angka, titik, garis bawah, strip.</small>
    </div>

    <div class="field">
        <label>Nama Lengkap</label>
        <input type="text" name="name" value="<?= e($user['name'] ?? '') ?>">
    </div>

    <div class="field">
        <label>Role</label>
        <select name="role">
            <option value="admin" <?= ($user['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
        </select>
        <small>Hak akses per role akan menyusul.</small>
    </div>

    <div class="field">
        <label>Password <?= $id > 0 ? '(kosongkan jika tidak diubah)' : '' ?></label>
        <input type="password" name="password" minlength="6" <?= $id > 0 ? '' : 'required' ?>>
        <small>Minimal 6 karakter.</small>
    </div>

    <div class="field">
        <label>Konfirmasi Password</label>
        <input type="password" name="password_confirm">
    </div>

    <label class="checkbox">
        <input type="checkbox" name="is_active" value="1"
               <?= !$user || (int)$user['is_active'] === 1 ? 'checked' : '' ?>>
        Aktif (bisa login)
    </label>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="users.php" class="btn btn-ghost">Batal</a>
    </div>
</form>
</div>
<?php
page_footer();
