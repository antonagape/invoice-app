<?php
/**
 * Manajemen Pengguna — daftar user.
 */
require_once __DIR__ . '/layout.php';

$pdo = db();
$users = $pdo->query('SELECT * FROM users ORDER BY id')->fetchAll();

page_header('Manajemen Pengguna');
?>
<div class="page-head">
    <h1>Manajemen Pengguna</h1>
    <a href="user_form.php" class="btn btn-primary">+ Tambah User</a>
</div>

<div class="card">
<table class="table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Nama</th>
            <th>Role</th>
            <th>Status</th>
            <th>Dibuat</th>
            <th class="th-actions">Aksi</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($users as $u): $isMe = (int)$u['id'] === (int)($_SESSION['user_id'] ?? 0); ?>
        <tr>
            <td><?= (int)$u['id'] ?></td>
            <td>
                <?= e($u['username']) ?>
                <?php if ($isMe): ?><span class="badge badge-me">Anda</span><?php endif; ?>
            </td>
            <td><?= e($u['name']) ?></td>
            <td><?= e($u['role']) ?></td>
            <td>
                <?php if ((int)$u['is_active'] === 1): ?>
                    <span class="badge badge-ok">Aktif</span>
                <?php else: ?>
                    <span class="badge badge-off">Nonaktif</span>
                <?php endif; ?>
            </td>
            <td><?= e(date('d M Y', strtotime($u['created_at']))) ?></td>
            <td class="row-actions">
                <a href="user_form.php?id=<?= (int)$u['id'] ?>" class="btn btn-sm">Edit</a>
                <?php if (!$isMe): ?>
                <form method="post" action="user_delete.php" class="inline"
                      onsubmit="return confirm('Hapus user ini?')">
                    <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php
page_footer();
