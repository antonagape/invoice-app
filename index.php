<?php
/**
 * Dashboard — daftar invoice.
 */
require_once __DIR__ . '/layout.php';

$pdo = db();

$q = trim((string)($_GET['q'] ?? ''));
$sql = 'SELECT * FROM invoices';
$params = [];
if ($q !== '') {
    $sql .= ' WHERE invoice_no LIKE :q OR invoice_to LIKE :q OR invoice_to_company LIKE :q';
    $params['q'] = '%' . $q . '%';
}
$sql .= ' ORDER BY id DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$invoices = $stmt->fetchAll();

$s = get_settings();
$cur = $s['currency'] ?? 'Rp';

page_header('Daftar Invoice');
?>

<form method="get" action="index.php" class="searchbar">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Cari nomor invoice / nama pelanggan...">
    <button type="submit" class="btn">Cari</button>
    <?php if ($q !== ''): ?><a href="index.php" class="btn btn-ghost">Reset</a><?php endif; ?>
</form>

<?php if (!$invoices): ?>
    <div class="empty">
        <p>Belum ada invoice.</p>
        <a href="invoice_form.php" class="btn btn-primary">Buat Invoice Pertama</a>
    </div>
<?php else: ?>
<div class="table-wrap">
    <table class="table">
        <thead>
            <tr>
                <th>No. Invoice</th>
                <th>Invoice Kepada</th>
                <th>Tanggal</th>
                <th class="num">Sub Total</th>
                <th class="num">Diskon</th>
                <th class="num">Terbayar</th>
                <th class="num">Sisa Tagihan</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($invoices as $inv):
            $remaining = (float)$inv['remaining'];
            $lunas = $remaining <= 0;
        ?>
            <tr>
                <td><a href="invoice_view.php?id=<?= (int)$inv['id'] ?>" class="inv-no"><?= e($inv['invoice_no']) ?></a></td>
                <td>
                    <?= e($inv['invoice_to']) ?>
                    <?php if ($inv['invoice_to_company'] !== ''): ?>
                        <br><small><?= e($inv['invoice_to_company']) ?></small>
                    <?php endif; ?>
                </td>
                <td><?= e(date('d M Y', strtotime($inv['date_invoice']))) ?></td>
                <td class="num"><?= e(money((float)$inv['subtotal'], $cur)) ?></td>
                <td class="num"><?= $inv['discount'] > 0 ? '-' . e(money((float)$inv['discount'], $cur)) : '—' ?></td>
                <td class="num"><?= $inv['paid'] > 0 ? e(money((float)$inv['paid'], $cur)) : '—' ?></td>
                <td class="num"><strong><?= e(money($remaining, $cur)) ?></strong></td>
                <td>
                    <?php if ($lunas): ?>
                        <span class="badge badge-green">LUNAS</span>
                    <?php elseif ((float)$inv['paid'] > 0): ?>
                        <span class="badge badge-amber">SEBAGIAN</span>
                    <?php else: ?>
                        <span class="badge badge-red">BELUM LUNAS</span>
                    <?php endif; ?>
                </td>
                <td class="actions">
                    <a href="invoice_view.php?id=<?= (int)$inv['id'] ?>" title="Lihat / Print" class="btn btn-sm">Lihat</a>
                    <a href="invoice_form.php?id=<?= (int)$inv['id'] ?>" title="Edit" class="btn btn-sm btn-ghost">Edit</a>
                    <form method="post" action="invoice_delete.php" class="inline"
                          onsubmit="return confirm('Hapus invoice <?= e($inv['invoice_no']) ?> beserta itemnya?');">
                        <input type="hidden" name="id" value="<?= (int)$inv['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php page_footer(); ?>
