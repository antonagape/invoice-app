<?php
/**
 * Pengaturan admin — logo, data perusahaan, penomoran, tanda tangan.
 */
require_once __DIR__ . '/layout.php';

$s = get_settings();
page_header('Pengaturan');
?>

<form method="post" action="settings_save.php" enctype="multipart/form-data" class="card">
    <h2 class="section-title">Logo Perusahaan</h2>
    <div class="grid-2">
        <div class="field">
            <label>Logo (PNG/JPG, maks 2MB)</label>
            <input type="file" name="logo" accept="image/png,image/jpeg,image/webp,image/gif">
            <?php if (!empty($s['logo_path'])): ?>
                <label class="check"><input type="checkbox" name="remove_logo" value="1"> Hapus logo saat ini</label>
            <?php endif; ?>
        </div>
        <div class="field">
            <label>Pratinjau Logo</label>
            <div class="logo-preview">
                <?php if (!empty($s['logo_path']) && is_file(__DIR__ . '/uploads/' . basename($s['logo_path']))): ?>
                    <img src="<?= e(APP_BASE) ?>/uploads/<?= e(rawurlencode(basename($s['logo_path']))) ?>" alt="Logo saat ini">
                <?php else: ?>
                    <span class="muted">Belum ada logo</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="field">
            <label>Opacity Logo di Background Invoice (0–100%)</label>
            <input type="range" name="logo_bg_opacity" id="logo_bg_opacity" min="0" max="100" step="1"
                   value="<?= (int)($s['logo_bg_opacity'] ?? 8) ?>">
            <small class="muted">Nilai: <span id="opacity-val"><?= (int)($s['logo_bg_opacity'] ?? 8) ?>%</span> — 0 = tanpa watermark</small>
        </div>
    </div>
    <script>
    (function () {
        var r = document.getElementById('logo_bg_opacity');
        var v = document.getElementById('opacity-val');
        if (r && v) r.addEventListener('input', function () { v.textContent = r.value + '%'; });
    })();
    </script>

    <h2 class="section-title">Perusahaan Pembuat Invoice</h2>
    <div class="grid-2">
        <div class="field">
            <label>Nama Perusahaan</label>
            <input type="text" name="company_name" value="<?= e($s['company_name'] ?? '') ?>">
        </div>
        <div class="field">
            <label>Mata Uang</label>
            <input type="text" name="currency" value="<?= e($s['currency'] ?? 'Rp') ?>" placeholder="Rp">
        </div>
        <div class="field field-full">
            <label>Alamat Perusahaan</label>
            <textarea name="company_address" rows="3"><?= e($s['company_address'] ?? '') ?></textarea>
        </div>
        <div class="field">
            <label>Email Perusahaan</label>
            <input type="email" name="company_email" value="<?= e($s['company_email'] ?? '') ?>">
        </div>
        <div class="field">
            <label>Nomor Telepon Perusahaan</label>
            <input type="text" name="company_phone" value="<?= e($s['company_phone'] ?? '') ?>">
        </div>
    </div>

    <h2 class="section-title">Bank / Pembayaran</h2>
    <div class="grid-3">
        <div class="field">
            <label>Nama Bank</label>
            <input type="text" name="bank_name" value="<?= e($s['bank_name'] ?? '') ?>" placeholder="cth: BCA">
        </div>
        <div class="field">
            <label>Nomor Rekening</label>
            <input type="text" name="bank_account_number" value="<?= e($s['bank_account_number'] ?? '') ?>" placeholder="cth: 1234567890">
        </div>
        <div class="field">
            <label>Atas Nama</label>
            <input type="text" name="bank_account_holder" value="<?= e($s['bank_account_holder'] ?? '') ?>" placeholder="cth: Carus Solution">
        </div>
    </div>

    <h2 class="section-title">Penomoran Invoice</h2>
    <div class="grid-3">
        <div class="field">
            <label>Prefix</label>
            <input type="text" name="invoice_prefix" value="<?= e($s['invoice_prefix'] ?? 'INV-') ?>" placeholder="INV-">
        </div>
        <div class="field">
            <label>Nomor Berikutnya</label>
            <input type="number" name="invoice_next_number" value="<?= (int)($s['invoice_next_number'] ?? 1) ?>" min="1">
        </div>
        <div class="field">
            <label>Jumlah Digit</label>
            <input type="number" name="invoice_digits" value="<?= (int)($s['invoice_digits'] ?? 4) ?>" min="1" max="8">
            <small class="muted">cth: 4 → INV-0001</small>
        </div>
    </div>

    <h2 class="section-title">Tanda Tangan (default)</h2>
    <div class="grid-2">
        <div class="field">
            <label>Nama Terang</label>
            <input type="text" name="default_sign_name" value="<?= e($s['default_sign_name'] ?? '') ?>">
        </div>
        <div class="field">
            <label>Jabatan Pemberi Invoice</label>
            <input type="text" name="default_sign_position" value="<?= e($s['default_sign_position'] ?? '') ?>">
        </div>
    </div>

    <div class="form-actions">
        <a href="index.php" class="btn btn-ghost">Batal</a>
        <button type="submit" class="btn btn-primary">Simpan Pengaturan</button>
    </div>
</form>

<?php page_footer(); ?>
