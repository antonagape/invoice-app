<?php
/**
 * Daftar Template perusahaan.
 */
require_once __DIR__ . '/layout.php';

$pdo = db();
$templates = $pdo->query(
    'SELECT t.*, (SELECT COUNT(*) FROM template_items ti WHERE ti.template_id = t.id) AS item_count
     FROM templates t ORDER BY t.name'
)->fetchAll();

page_header('Template');
?>

<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; gap:10px; flex-wrap:wrap;">
        <p class="muted" style="margin:0;">Simpan data pelanggan + item yang sering dipakai. Invoice baru bisa langsung diisi otomatis dari template.</p>
        <a href="template_form.php" class="btn btn-primary">+ Template Baru</a>
    </div>

    <?php if (!$templates): ?>
        <div class="empty">Belum ada template. <a href="template_form.php">Buat template</a> atau buat invoice baru — otomatis tersimpan sebagai template.</div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nama Template</th>
                        <th>Kepada</th>
                        <th>Perusahaan</th>
                        <th class="num">Item</th>
                        <th>Payment Terms</th>
                        <th>Diperbarui</th>
                        <th class="actions">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($templates as $t): ?>
                    <tr>
                        <td><strong><?= e($t['name']) ?></strong></td>
                        <td><?= e($t['invoice_to']) ?: '—' ?></td>
                        <td><?= e($t['invoice_to_company']) ?: '—' ?></td>
                        <td class="num"><?= (int)$t['item_count'] ?></td>
                        <td><?= e($t['payment_terms']) ?: '—' ?></td>
                        <td><?= e(date('d M Y', strtotime($t['updated_at']))) ?></td>
                        <td class="actions">
                            <a class="btn btn-sm" href="invoice_form.php?tpl=<?= (int)$t['id'] ?>" title="Buka form invoice dengan data ini">Pakai</a>
                            <a class="btn btn-sm" href="template_form.php?id=<?= (int)$t['id'] ?>">Edit</a>
                            <form class="inline" method="post" action="template_delete.php" onsubmit="return confirm('Hapus template &quot;<?= e($t['name']) ?>&quot;?');">
                                <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php page_footer(); ?>
