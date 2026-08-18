<?php
/**
 * Form buat/edit Template.
 */
require_once __DIR__ . '/config.php';

$pdo  = db();
$s    = get_settings();
$cur  = $s['currency'] ?? 'Rp';
$editing = false;
$tpl = [
    'id' => 0, 'name' => '', 'invoice_to' => '', 'invoice_to_company' => '',
    'invoice_to_address' => '', 'invoice_to_phone' => '', 'invoice_to_email' => '',
    'payment_terms' => '', 'sign_name' => '', 'sign_position' => '',
];
$items = [];

$id = (int)($_GET['id'] ?? 0);
if ($id > 0) {
    $st = $pdo->prepare('SELECT * FROM templates WHERE id = ?');
    $st->execute([$id]);
    $row = $st->fetch();
    if ($row) {
        $editing = true;
        $tpl = $row;
        $st2 = $pdo->prepare('SELECT * FROM template_items WHERE template_id = ? ORDER BY id');
        $st2->execute([$id]);
        $items = $st2->fetchAll();
    } else {
        redirect(APP_BASE . '/templates.php');
    }
}

$title = $editing ? 'Edit Template: ' . $tpl['name'] : 'Template Baru';
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
        <div class="brand"><?= e(APP_NAME) ?></div>
        <div class="nav-links">
            <a href="<?= e(APP_BASE) ?>/index.php">Dashboard</a>
            <a href="<?= e(APP_BASE) ?>/templates.php">Template</a>
            <a href="<?= e(APP_BASE) ?>/invoice_form.php" class="btn btn-primary btn-sm">+ Buat Invoice</a>
            <a href="<?= e(APP_BASE) ?>/settings.php">Pengaturan</a>
        </div>
    </div>
</nav>

<main class="container">
    <h1><?= e($title) ?></h1>

    <form method="post" action="template_save.php" class="card">
        <input type="hidden" name="id" value="<?= (int)$tpl['id'] ?>">

        <h2 class="section-title">Identitas Template</h2>
        <div class="grid-2">
            <div class="field">
                <label>Nama Template *</label>
                <input type="text" name="name" value="<?= e($tpl['name']) ?>" required placeholder="cth: PT Cantik Abadi">
                <small class="muted">Bisa nama pelanggan atau nama paket</small>
            </div>
            <div class="field">
                <label>Invoice Kepada (nama)</label>
                <input type="text" name="invoice_to" value="<?= e($tpl['invoice_to']) ?>">
            </div>
            <div class="field">
                <label>Perusahaan Invoice Kepada</label>
                <input type="text" name="invoice_to_company" value="<?= e($tpl['invoice_to_company']) ?>">
            </div>
            <div class="field">
                <label>Telepon</label>
                <input type="text" name="invoice_to_phone" value="<?= e($tpl['invoice_to_phone']) ?>">
            </div>
            <div class="field">
                <label>Email</label>
                <input type="email" name="invoice_to_email" value="<?= e($tpl['invoice_to_email']) ?>">
            </div>
            <div class="field">
                <label>Payment Terms</label>
                <input type="text" name="payment_terms" value="<?= e($tpl['payment_terms']) ?>" placeholder="cth: 14 hari">
            </div>
            <div class="field field-full">
                <label>Alamat</label>
                <textarea name="invoice_to_address" rows="3"><?= e($tpl['invoice_to_address']) ?></textarea>
            </div>
        </div>

        <h2 class="section-title">Item / Jasa</h2>
        <div class="table-wrap">
            <table class="table items-table">
                <thead>
                    <tr>
                        <th>Item / Jasa</th>
                        <th style="width:100px">Qty</th>
                        <th style="width:180px">Harga</th>
                        <th style="width:180px" class="num">Sub Total</th>
                        <th style="width:60px"></th>
                    </tr>
                </thead>
                <tbody id="items-body">
                <?php if ($items): ?>
                    <?php foreach ($items as $it): ?>
                        <tr class="item-row">
                            <td><input type="text" name="item_name[]" placeholder="Nama item / jasa" value="<?= e($it['item_name']) ?>"></td>
                            <td><input type="number" name="qty[]" value="<?= (int)$it['qty'] ?>" min="1" step="1" class="qty"></td>
                            <td><input type="text" name="price[]" class="price" inputmode="decimal" value="<?= e(rtrim(rtrim(number_format((float)$it['price'], 2, ',', '.'), '0'), ',')) ?>"></td>
                            <td class="num row-subtotal"></td>
                            <td><button type="button" class="btn btn-sm btn-danger remove-row" title="Hapus item">&times;</button></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr class="item-row">
                        <td><input type="text" name="item_name[]" placeholder="Nama item / jasa"></td>
                        <td><input type="number" name="qty[]" value="1" min="1" step="1" class="qty"></td>
                        <td><input type="text" name="price[]" class="price" inputmode="decimal" placeholder="0"></td>
                        <td class="num row-subtotal"></td>
                        <td><button type="button" class="btn btn-sm btn-danger remove-row" title="Hapus item">&times;</button></td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <button type="button" id="add-row" class="btn" style="margin-top:10px;">+ Tambah Item</button>

        <h2 class="section-title">Tanda Tangan</h2>
        <div class="grid-2">
            <div class="field">
                <label>Nama Terang</label>
                <input type="text" name="sign_name" value="<?= e($tpl['sign_name']) ?>">
            </div>
            <div class="field">
                <label>Jabatan Pemberi Invoice</label>
                <input type="text" name="sign_position" value="<?= e($tpl['sign_position']) ?>">
            </div>
        </div>

        <div class="form-actions">
            <a href="templates.php" class="btn btn-ghost">Batal</a>
            <button type="submit" class="btn btn-primary"><?= $editing ? 'Simpan Perubahan' : 'Simpan Template' ?></button>
        </div>
    </form>
</main>

<script>
window.CURRENCY_HINT = <?= json_encode($cur) ?>;
</script>
<script src="<?= e(APP_BASE) ?>/assets/app.js"></script>
</body>
</html>
