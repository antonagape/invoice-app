<?php
/**
 * Simpan invoice baru / update (POST).
 * Menghitung ulang subtotal, diskon, terbayar, sisa tagihan di sisi server.
 */
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_BASE . '/index.php');
}

$pdo = db();
$id = (int)($_POST['id'] ?? 0);

// ===== Validasi =====
$invoice_no   = post('invoice_no');
$date_invoice = post('date_invoice');
$invoice_to   = post('invoice_to');
if ($invoice_no === '' || $date_invoice === '' || $invoice_to === '') {
    flash_set('No. invoice, tanggal, dan nama penerima wajib diisi.', 'error');
    redirect(APP_BASE . '/invoice_form.php' . ($id ? '?id=' . $id : ''));
}
if (!strtotime($date_invoice)) {
    flash_set('Format tanggal tidak valid.', 'error');
    redirect(APP_BASE . '/invoice_form.php' . ($id ? '?id=' . $id : ''));
}

// ===== Item =====
$names  = $_POST['item_name'] ?? [];
$qtys   = $_POST['qty'] ?? [];
$prices = $_POST['price'] ?? [];

$rows = [];
foreach ($names as $i => $name) {
    $name = trim((string)$name);
    if ($name === '') continue;
    $qty = max(1, (int)($qtys[$i] ?? 1));
    $price = (float)str_replace(['.', ' '], '', (string)($prices[$i] ?? '0'));
    $price = str_replace(',', '.', (string)$price);
    $price = max(0.0, (float)$price);
    $rows[] = [
        'name'     => $name,
        'qty'      => $qty,
        'price'    => $price,
        'subtotal' => round($qty * $price, 2),
    ];
}
if (!$rows) {
    flash_set('Minimal satu item wajib diisi.', 'error');
    redirect(APP_BASE . '/invoice_form.php' . ($id ? '?id=' . $id : ''));
}

// ===== Angka =====
$subtotal = round(array_sum(array_column($rows, 'subtotal')), 2);
$discount = round(post_float('discount'), 2);
$paid     = round(post_float('paid'), 2);
if ($discount > $subtotal) $discount = $subtotal;
if ($paid > $subtotal - $discount) $paid = $subtotal - $discount;
$remaining = round($subtotal - $discount - $paid, 2);

$data = [
    'invoice_no'          => $invoice_no,
    'invoice_to'          => $invoice_to,
    'invoice_to_company'  => post('invoice_to_company'),
    'invoice_to_address'  => post('invoice_to_address'),
    'invoice_to_phone'    => post('invoice_to_phone'),
    'invoice_to_email'    => post('invoice_to_email'),
    'date_invoice'        => $date_invoice,
    'payment_terms'       => post('payment_terms'),
    'subtotal'            => $subtotal,
    'discount'            => $discount,
    'paid'                => $paid,
    'remaining'           => $remaining,
    'sign_name'           => post('sign_name'),
    'sign_position'       => post('sign_position'),
];

try {
    $pdo->beginTransaction();

    if ($id > 0) {
        // Cek duplikat nomor selain invoice ini
        $st = $pdo->prepare('SELECT id FROM invoices WHERE invoice_no = ? AND id <> ?');
        $st->execute([$invoice_no, $id]);
        if ($st->fetch()) {
            $pdo->rollBack();
            flash_set('Nomor invoice "' . $invoice_no . '" sudah dipakai invoice lain.', 'error');
            redirect(APP_BASE . '/invoice_form.php?id=' . $id);
        }

        $sql = 'UPDATE invoices SET invoice_no=:invoice_no, invoice_to=:invoice_to,
                invoice_to_company=:invoice_to_company, invoice_to_address=:invoice_to_address,
                invoice_to_phone=:invoice_to_phone, invoice_to_email=:invoice_to_email,
                date_invoice=:date_invoice, payment_terms=:payment_terms,
                subtotal=:subtotal, discount=:discount, paid=:paid, remaining=:remaining,
                sign_name=:sign_name, sign_position=:sign_position
                WHERE id=:id';
        $pdo->prepare($sql)->execute($data + ['id' => $id]);
        $pdo->prepare('DELETE FROM invoice_items WHERE invoice_id = ?')->execute([$id]);
        $invoiceId = $id;
    } else {
        // Cek duplikat nomor
        $st = $pdo->prepare('SELECT id FROM invoices WHERE invoice_no = ?');
        $st->execute([$invoice_no]);
        if ($st->fetch()) {
            $pdo->rollBack();
            flash_set('Nomor invoice "' . $invoice_no . '" sudah dipakai. Gunakan nomor lain.', 'error');
            redirect(APP_BASE . '/invoice_form.php');
        }

        $sql = 'INSERT INTO invoices (invoice_no, invoice_to, invoice_to_company, invoice_to_address,
                invoice_to_phone, invoice_to_email, date_invoice, payment_terms,
                subtotal, discount, paid, remaining, sign_name, sign_position)
                VALUES (:invoice_no, :invoice_to, :invoice_to_company, :invoice_to_address,
                :invoice_to_phone, :invoice_to_email, :date_invoice, :payment_terms,
                :subtotal, :discount, :paid, :remaining, :sign_name, :sign_position)';
        $pdo->prepare($sql)->execute($data);
        $invoiceId = (int)$pdo->lastInsertId();

        // Naikkan nomor berikutnya hanya jika memakai nomor otomatis
        $orig = post('orig_number');
        $s = get_settings();
        $suggested = ($s['invoice_prefix'] ?? 'INV-') . str_pad(
            (string)(int)($s['invoice_next_number'] ?? 1),
            max(1, (int)($s['invoice_digits'] ?? 4)), '0', STR_PAD_LEFT
        );
        if ($orig === $suggested && $invoice_no === $suggested) {
            $pdo->prepare('UPDATE settings SET invoice_next_number = invoice_next_number + 1 WHERE id = 1')
                ->execute();
        }
    }

    // Simpan item
    $ins = $pdo->prepare('INSERT INTO invoice_items (invoice_id, item_name, qty, price, subtotal)
                          VALUES (?, ?, ?, ?, ?)');
    foreach ($rows as $r) {
        $ins->execute([$invoiceId, $r['name'], $r['qty'], $r['price'], $r['subtotal']]);
    }

    // Auto-create template (hanya invoice baru)
    if ($id === 0) {
        autoCreateTemplate($pdo, $data, $rows);
    }

    $pdo->commit();
} catch (Throwable $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    flash_set('Gagal menyimpan: ' . $ex->getMessage(), 'error');
    redirect(APP_BASE . '/invoice_form.php' . ($id ? '?id=' . $id : ''));
}

flash_set('Invoice ' . $invoice_no . ' berhasil disimpan.');
redirect(APP_BASE . '/invoice_view.php?id=' . $invoiceId);

/**
 * Auto-simpan invoice baru menjadi template agar tidak perlu
 * menulis ulang data pelanggan + item. Dilewati jika sudah ada
 * template dengan identitas & item yang sama persis.
 */
function autoCreateTemplate(PDO $pdo, array $data, array $rows): void
{
    // 1) Cek template sejenis (invoice_to + company sama) dengan item identik
    $st = $pdo->prepare('SELECT id FROM templates WHERE invoice_to = ? AND invoice_to_company = ?');
    $st->execute([$data['invoice_to'], $data['invoice_to_company']]);
    foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $tid) {
        $st2 = $pdo->prepare('SELECT item_name, qty, price FROM template_items WHERE template_id = ? ORDER BY id');
        $st2->execute([$tid]);
        $existing = $st2->fetchAll();
        $match = count($existing) === count($rows);
        if ($match) {
            foreach ($existing as $i => $e) {
                if ($e['item_name'] !== $rows[$i]['name']
                    || (int)$e['qty'] !== (int)$rows[$i]['qty']
                    || abs((float)$e['price'] - (float)$rows[$i]['price']) > 0.001) {
                    $match = false;
                    break;
                }
            }
        }
        if ($match) return; // sudah ada template identik → jangan duplikat
    }

    // 2) Nama template dari no. invoice + penerima
    $name = trim(($data['invoice_no'] ?? '') . ' – ' . $data['invoice_to']);
    if ($name === '' || $name === '–') {
        $name = 'Template ' . date('Y-m-d H:i');
    }
    $st = $pdo->prepare('SELECT id FROM templates WHERE name = ?');
    $st->execute([$name]);
    if ($st->fetch()) return; // nama sudah dipakai → lewati

    // 3) Simpan template + item
    $pdo->prepare(
        'INSERT INTO templates (name, invoice_to, invoice_to_company, invoice_to_address,
         invoice_to_phone, invoice_to_email, payment_terms, sign_name, sign_position)
         VALUES (:name, :invoice_to, :invoice_to_company, :invoice_to_address,
         :invoice_to_phone, :invoice_to_email, :payment_terms, :sign_name, :sign_position)'
    )->execute([
        'name' => $name,
        'invoice_to'         => $data['invoice_to'],
        'invoice_to_company' => $data['invoice_to_company'],
        'invoice_to_address' => $data['invoice_to_address'],
        'invoice_to_phone'   => $data['invoice_to_phone'],
        'invoice_to_email'   => $data['invoice_to_email'],
        'payment_terms'      => $data['payment_terms'],
        'sign_name'          => $data['sign_name'],
        'sign_position'      => $data['sign_position'],
    ]);
    $tid = (int)$pdo->lastInsertId();

    $ins = $pdo->prepare('INSERT INTO template_items (template_id, item_name, qty, price) VALUES (?, ?, ?, ?)');
    foreach ($rows as $r) {
        $ins->execute([$tid, $r['name'], $r['qty'], $r['price']]);
    }
}
