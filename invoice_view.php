<?php
/**
 * Lihat / Print invoice — layout A4 seperti contoh.
 */
require_once __DIR__ . '/config.php';

$pdo = db();
$id  = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM invoices WHERE id = ?');
$stmt->execute([$id]);
$inv = $stmt->fetch();
if (!$inv) {
    http_response_code(404);
    exit('Invoice tidak ditemukan. <a href="' . APP_BASE . '/index.php">Kembali</a>');
}

$st = $pdo->prepare('SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY id');
$st->execute([$id]);
$items = $st->fetchAll();

$s   = get_settings();
$cur = $s['currency'] ?? 'Rp';

$logoUrl = '';
if (!empty($s['logo_path'])) {
    $logoFile = __DIR__ . '/uploads/' . basename($s['logo_path']);
    if (is_file($logoFile)) {
        $logoUrl = APP_BASE . '/uploads/' . rawurlencode(basename($s['logo_path']));
    }
}

$remaining = (float)$inv['remaining'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Invoice <?= e($inv['invoice_no']) ?></title>
<link rel="stylesheet" href="<?= e(APP_BASE) ?>/assets/style.css">
</head>
<body class="print-layout">

<div class="no-print toolbar">
    <div>
        <a href="index.php" class="btn btn-ghost">← Kembali</a>
        <a href="invoice_form.php?id=<?= (int)$inv['id'] ?>" class="btn">Edit</a>
    </div>
    <button onclick="window.print()" class="btn btn-primary">🖨 Print / Simpan PDF</button>
</div>

<div class="invoice-sheet">
    <?php if ($logoUrl && ((int)($s['logo_bg_opacity'] ?? 8)) > 0): ?>
        <div class="inv-watermark" style="opacity:<?= min(100, max(0, (int)($s['logo_bg_opacity'] ?? 8))) / 100 ?>;">
            <img src="<?= e($logoUrl) ?>" alt="">
        </div>
    <?php endif; ?>

    <!-- Header perusahaan -->
    <header class="inv-header">
        <div class="inv-logo">
            <?php if ($logoUrl): ?>
                <img src="<?= e($logoUrl) ?>" alt="Logo">
            <?php endif; ?>
        </div>
        <div class="inv-company">
            <h1><?= e($s['company_name'] ?? '') ?></h1>
            <?php if (!empty($s['company_address'])): ?>
                <p><?= nl2br(e($s['company_address'])) ?></p>
            <?php endif; ?>
            <p>
                <?php if (!empty($s['company_phone'])): ?>Telp: <?= e($s['company_phone']) ?><?php endif; ?>
                <?php if (!empty($s['company_email'])): ?><?= !empty($s['company_phone']) ? ' &nbsp;|&nbsp; ' : '' ?>Email: <?= e($s['company_email']) ?><?php endif; ?>
            </p>
        </div>
    </header>

    <div class="inv-title">INVOICE</div>

    <!-- Meta -->
    <div class="inv-meta">
        <div class="meta-left">
            <div class="meta-label">Invoice Kepada</div>
            <div class="meta-name"><?= e($inv['invoice_to']) ?></div>
            <?php if (!empty($inv['invoice_to_company'])): ?>
                <div><?= e($inv['invoice_to_company']) ?></div>
            <?php endif; ?>
            <?php if (!empty($inv['invoice_to_address'])): ?>
                <div><?= nl2br(e($inv['invoice_to_address'])) ?></div>
            <?php endif; ?>
            <?php if (!empty($inv['invoice_to_phone'])): ?>
                <div>Telp: <?= e($inv['invoice_to_phone']) ?></div>
            <?php endif; ?>
            <?php if (!empty($inv['invoice_to_email'])): ?>
                <div><?= e($inv['invoice_to_email']) ?></div>
            <?php endif; ?>
        </div>
        <table class="meta-right">
            <tr><td class="meta-label">No. Invoice</td><td>: <strong><?= e($inv['invoice_no']) ?></strong></td></tr>
            <tr><td class="meta-label">Tanggal</td><td>: <?= e(date('d F Y', strtotime($inv['date_invoice']))) ?></td></tr>
            <tr><td class="meta-label">Payment Terms</td><td>: <?= e($inv['payment_terms'] ?: '-') ?></td></tr>
        </table>
    </div>

    <!-- Tabel item -->
    <table class="inv-items">
        <thead>
            <tr>
                <th class="c">No</th>
                <th>Uraian / Item</th>
                <th class="c">Qty</th>
                <th class="r">Harga</th>
                <th class="r">Sub Total</th>
            </tr>
        </thead>
        <tbody>
        <?php $no = 1; foreach ($items as $it): ?>
            <tr>
                <td class="c"><?= $no++ ?></td>
                <td><?= e($it['item_name']) ?></td>
                <td class="c"><?= (int)$it['qty'] ?></td>
                <td class="r"><?= e(money((float)$it['price'], $cur)) ?></td>
                <td class="r"><?= e(money((float)$it['subtotal'], $cur)) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Ringkasan -->
    <div class="inv-summary">
        <table class="sum-table">
            <tr><td>Sub Total</td><td class="r"><?= e(money((float)$inv['subtotal'], $cur)) ?></td></tr>
            <tr><td>Diskon</td><td class="r"><?= $inv['discount'] > 0 ? '-' . e(money((float)$inv['discount'], $cur)) : '—' ?></td></tr>
            <tr><td>Terbayar</td><td class="r"><?= $inv['paid'] > 0 ? e(money((float)$inv['paid'], $cur)) : '—' ?></td></tr>
            <tr class="sum-total"><td>Sisa Tagihan</td><td class="r"><?= e(money($remaining, $cur)) ?></td></tr>
        </table>
    </div>

    <!-- Info pembayaran (rekening bank) -->
    <?php if (!empty($s['bank_account_number'])): ?>
    <div class="inv-bank">
        <strong>Pembayaran via Transfer Bank</strong>
        <div>
            <?= e($s['bank_name'] ?? 'Bank') ?> &nbsp;•&nbsp; <span class="bank-no"><?= e($s['bank_account_number']) ?></span>
            <?php if (!empty($s['bank_account_holder'])): ?>
                &nbsp;•&nbsp; a.n. <?= e($s['bank_account_holder']) ?>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Tanda tangan -->
    <div class="inv-sign">
        <p class="sign-greeting">Dengan hormat,</p>
        <div class="sign-space"></div>
        <p class="sign-name"><strong><?= e($inv['sign_name'] ?: '&nbsp;') ?></strong></p>
        <p class="sign-position"><?= e($inv['sign_position'] ?: '&nbsp;') ?></p>
    </div>
</div>

</body>
</html>
