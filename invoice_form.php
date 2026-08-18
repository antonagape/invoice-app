<?php
/**
 * Form buat / edit invoice.
 * ?id= untuk edit, tanpa id untuk baru.
 */
require_once __DIR__ . '/layout.php';

$pdo = db();
$s = get_settings();
$cur = $s['currency'] ?? 'Rp';

$editing = false;
$inv = [
    'id' => 0, 'invoice_no' => next_invoice_number(), 'invoice_to' => '',
    'invoice_to_company' => '', 'invoice_to_address' => '', 'invoice_to_phone' => '',
    'invoice_to_email' => '', 'date_invoice' => date('Y-m-d'), 'payment_terms' => '',
    'subtotal' => 0, 'discount' => 0, 'paid' => 0, 'remaining' => 0,
    'sign_name' => $s['default_sign_name'] ?? '', 'sign_position' => $s['default_sign_position'] ?? '',
];
$items = [];

$id = (int)($_GET['id'] ?? 0);
if ($id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM invoices WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if ($row) {
        $editing = true;
        $inv = $row;
        $st = $pdo->prepare('SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY id');
        $st->execute([$id]);
        $items = $st->fetchAll();
    }
}
if (!$items) {
    $items = [['item_name' => '', 'qty' => 1, 'price' => 0]];
}

// ===== Prefill dari template (?tpl=) — hanya untuk invoice baru =====
$selectedTpl = 0;
$tplId = (int)($_GET['tpl'] ?? 0);
if (!$editing && $tplId > 0) {
    $st = $pdo->prepare('SELECT * FROM templates WHERE id = ?');
    $st->execute([$tplId]);
    $tplRow = $st->fetch();
    if ($tplRow) {
        $selectedTpl = $tplId;
        foreach (['invoice_to', 'invoice_to_company', 'invoice_to_address', 'invoice_to_phone',
                  'invoice_to_email', 'payment_terms', 'sign_name', 'sign_position'] as $f) {
            $inv[$f] = $tplRow[$f];
        }
        $st2 = $pdo->prepare('SELECT * FROM template_items WHERE template_id = ? ORDER BY id');
        $st2->execute([$tplId]);
        $tplItems = $st2->fetchAll();
        if ($tplItems) {
            $items = array_map(static fn($it) => [
                'item_name' => $it['item_name'],
                'qty'       => (int)$it['qty'],
                'price'     => (float)$it['price'],
                'subtotal'  => (int)$it['qty'] * (float)$it['price'],
            ], $tplItems);
        }
    }
}

// ===== Data template untuk dropdown (JS) =====
$tplList  = $pdo->query('SELECT * FROM templates ORDER BY name')->fetchAll();
$tplItems = $pdo->query('SELECT template_id, item_name, qty, price FROM template_items ORDER BY id')->fetchAll();
$byTpl = [];
foreach ($tplItems as $ti) {
    $byTpl[$ti['template_id']][] = [
        'item_name' => $ti['item_name'],
        'qty'       => (int)$ti['qty'],
        'price'     => (float)$ti['price'],
    ];
}
$tplJson = [];
foreach ($tplList as $t) {
    $tplJson[] = [
        'id' => (int)$t['id'], 'name' => $t['name'],
        'invoice_to' => $t['invoice_to'], 'invoice_to_company' => $t['invoice_to_company'],
        'invoice_to_address' => $t['invoice_to_address'], 'invoice_to_phone' => $t['invoice_to_phone'],
        'invoice_to_email' => $t['invoice_to_email'], 'payment_terms' => $t['payment_terms'],
        'sign_name' => $t['sign_name'], 'sign_position' => $t['sign_position'],
        'items' => $byTpl[$t['id']] ?? [],
    ];
}

$paymentOptions = ['Cash', '7 hari', '14 hari', '30 hari', 'Tempo 30 hari', 'Tempo 60 hari'];

page_header($editing ? 'Edit Invoice' : 'Buat Invoice Baru');
?>

<form method="post" action="invoice_save.php" id="invoice-form" class="card">
    <input type="hidden" name="id" value="<?= (int)$inv['id'] ?>">
    <input type="hidden" name="orig_number" value="<?= e($inv['invoice_no']) ?>">

    <h2 class="section-title">Template</h2>
    <div class="field">
        <label>Pilih Template (isi otomatis)</label>
        <select id="template-select">
            <option value="">— Pilih Template —</option>
            <?php foreach ($tplJson as $t): ?>
                <option value="<?= (int)$t['id'] ?>" <?= $selectedTpl === (int)$t['id'] ? 'selected' : '' ?>>
                    <?= e($t['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <small class="muted">Memilih template akan mengisi data penerima, item, payment terms, dan tanda tangan secara otomatis. Template dibuat otomatis dari setiap invoice baru & bisa dikelola di menu <a href="templates.php">Template</a>.</small>
    </div>

    <div class="grid-2">
        <div class="field">
            <label>No. Invoice</label>
            <input type="text" name="invoice_no" value="<?= e($inv['invoice_no']) ?>" required>
        </div>
        <div class="field">
            <label>Tanggal Invoice</label>
            <input type="date" name="date_invoice" value="<?= e($inv['date_invoice']) ?>" required>
        </div>
        <div class="field">
            <label>Payment Terms</label>
            <input type="text" name="payment_terms" list="payment-terms" value="<?= e($inv['payment_terms']) ?>"
                   placeholder="cth: 14 hari">
            <datalist id="payment-terms">
                <?php foreach ($paymentOptions as $opt): ?>
                    <option value="<?= e($opt) ?>">
                <?php endforeach; ?>
            </datalist>
        </div>
    </div>

    <h2 class="section-title">Invoice Kepada</h2>
    <div class="grid-2">
        <div class="field">
            <label>Nama</label>
            <input type="text" name="invoice_to" value="<?= e($inv['invoice_to']) ?>" required>
        </div>
        <div class="field">
            <label>Perusahaan</label>
            <input type="text" name="invoice_to_company" value="<?= e($inv['invoice_to_company']) ?>">
        </div>
        <div class="field field-full">
            <label>Alamat</label>
            <textarea name="invoice_to_address" rows="2"><?= e($inv['invoice_to_address']) ?></textarea>
        </div>
        <div class="field">
            <label>Telepon</label>
            <input type="text" name="invoice_to_phone" value="<?= e($inv['invoice_to_phone']) ?>">
        </div>
        <div class="field">
            <label>Email</label>
            <input type="email" name="invoice_to_email" value="<?= e($inv['invoice_to_email']) ?>">
        </div>
    </div>

    <h2 class="section-title">Item Tagihan</h2>
    <div class="table-wrap">
        <table class="table items-table" id="items-table">
            <thead>
                <tr>
                    <th style="width:55%">Item</th>
                    <th style="width:10%">Qty</th>
                    <th style="width:20%">Harga (<?= e($cur) ?>)</th>
                    <th style="width:15%">Sub Total</th>
                    <th style="width:40px"></th>
                </tr>
            </thead>
            <tbody id="items-body">
            <?php foreach ($items as $i => $it): ?>
                <tr class="item-row">
                    <td><input type="text" name="item_name[]" value="<?= e($it['item_name']) ?>" required placeholder="Nama item / jasa"></td>
                    <td><input type="number" name="qty[]" value="<?= (int)$it['qty'] ?>" min="1" step="1" class="qty" required></td>
                    <td><input type="text" name="price[]" value="<?= number_format((float)$it['price'], 0, ',', '.') ?>" class="price" inputmode="decimal" placeholder="0"></td>
                    <td class="num row-subtotal"><?= e(money((float)$it['subtotal'], $cur)) ?></td>
                    <td><button type="button" class="btn btn-sm btn-danger remove-row" title="Hapus item">&times;</button></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <button type="button" class="btn btn-ghost" id="add-row">+ Tambah Item</button>

    <div class="summary-box">
        <div class="sum-row"><span>Sub Total</span><span class="sum-subtotal"><?= e(money((float)$inv['subtotal'], $cur)) ?></span></div>
        <div class="sum-row">
            <span>Diskon <small>(<?= e($cur) ?>)</small></span>
            <input type="text" name="discount" id="discount" class="amount-input" value="<?= number_format((float)$inv['discount'], 0, ',', '.') ?>" inputmode="decimal">
        </div>
        <div class="sum-row">
            <span>Terbayar <small>(<?= e($cur) ?>)</small></span>
            <input type="text" name="paid" id="paid" class="amount-input" value="<?= number_format((float)$inv['paid'], 0, ',', '.') ?>" inputmode="decimal">
        </div>
        <div class="sum-row total-row"><span>Sisa Tagihan</span><span class="sum-remaining"><?= e(money((float)$inv['remaining'], $cur)) ?></span></div>
    </div>

    <h2 class="section-title">Penandatangan</h2>
    <div class="grid-2">
        <div class="field">
            <label>Nama Terang</label>
            <input type="text" name="sign_name" value="<?= e($inv['sign_name']) ?>">
        </div>
        <div class="field">
            <label>Jabatan Pemberi Invoice</label>
            <input type="text" name="sign_position" value="<?= e($inv['sign_position']) ?>">
        </div>
    </div>

    <div class="form-actions">
        <a href="index.php" class="btn btn-ghost">Batal</a>
        <button type="submit" class="btn btn-primary"><?= $editing ? 'Simpan Perubahan' : 'Simpan Invoice' ?></button>
    </div>
</form>

<script>
window.TEMPLATES = <?= json_encode($tplJson, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?>;
window.CURRENCY_HINT = <?= json_encode($cur) ?>;
</script>

<?php page_footer(); ?>
