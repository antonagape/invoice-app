<?php
/**
 * Simpan template (POST).
 */
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_BASE . '/templates.php');
}

$pdo = db();
$id  = (int)($_POST['id'] ?? 0);

$name = trim(post('name'));
if ($name === '') {
    flash_set('Nama template wajib diisi.', 'error');
    redirect(APP_BASE . '/template_form.php' . ($id ? '?id=' . $id : ''));
}

$data = [
    'name'                => $name,
    'invoice_to'          => post('invoice_to'),
    'invoice_to_company'  => post('invoice_to_company'),
    'invoice_to_address'  => post('invoice_to_address'),
    'invoice_to_phone'    => post('invoice_to_phone'),
    'invoice_to_email'    => post('invoice_to_email'),
    'payment_terms'       => post('payment_terms'),
    'sign_name'           => post('sign_name'),
    'sign_position'       => post('sign_position'),
];

// ===== Item =====
$names = $_POST['item_name'] ?? [];
$qtys  = $_POST['qty'] ?? [];
$prices = $_POST['price'] ?? [];
$rows = [];
foreach ($names as $i => $n) {
    $n = trim((string)$n);
    if ($n === '') continue;
    $rows[] = [
        'name'  => $n,
        'qty'   => max(1, (int)($qtys[$i] ?? 1)),
        'price' => money_parse($prices[$i] ?? '0'),
    ];
}

$pdo->beginTransaction();
try {
    if ($id > 0) {
        $st = $pdo->prepare('SELECT id FROM templates WHERE name = ? AND id != ?');
        $st->execute([$name, $id]);
        if ($st->fetch()) {
            throw new RuntimeException('Template dengan nama "' . $name . '" sudah ada.');
        }
        $pdo->prepare(
            'UPDATE templates SET name=:name, invoice_to=:invoice_to, invoice_to_company=:invoice_to_company,
             invoice_to_address=:invoice_to_address, invoice_to_phone=:invoice_to_phone,
             invoice_to_email=:invoice_to_email, payment_terms=:payment_terms,
             sign_name=:sign_name, sign_position=:sign_position WHERE id=:id'
        )->execute($data + ['id' => $id]);
        $pdo->prepare('DELETE FROM template_items WHERE template_id = ?')->execute([$id]);
        $tid = $id;
    } else {
        $st = $pdo->prepare('SELECT id FROM templates WHERE name = ?');
        $st->execute([$name]);
        if ($st->fetch()) {
            throw new RuntimeException('Template dengan nama "' . $name . '" sudah ada.');
        }
        $pdo->prepare(
            'INSERT INTO templates (name, invoice_to, invoice_to_company, invoice_to_address,
             invoice_to_phone, invoice_to_email, payment_terms, sign_name, sign_position)
             VALUES (:name, :invoice_to, :invoice_to_company, :invoice_to_address,
             :invoice_to_phone, :invoice_to_email, :payment_terms, :sign_name, :sign_position)'
        )->execute($data);
        $tid = (int)$pdo->lastInsertId();
    }

    $ins = $pdo->prepare('INSERT INTO template_items (template_id, item_name, qty, price) VALUES (?, ?, ?, ?)');
    foreach ($rows as $r) {
        $ins->execute([$tid, $r['name'], $r['qty'], $r['price']]);
    }

    $pdo->commit();
} catch (RuntimeException $e) {
    $pdo->rollBack();
    flash_set($e->getMessage(), 'error');
    redirect(APP_BASE . '/template_form.php' . ($id ? '?id=' . $id : ''));
} catch (Throwable $e) {
    $pdo->rollBack();
    flash_set('Gagal menyimpan template: ' . $e->getMessage(), 'error');
    redirect(APP_BASE . '/template_form.php' . ($id ? '?id=' . $id : ''));
}

flash_set('Template "' . $name . '" berhasil disimpan.');
redirect(APP_BASE . '/templates.php');
