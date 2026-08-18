/**
 * Invoice App — interaksi form: item dinamis + kalkulasi otomatis.
 */
(function () {
    'use strict';

    var currency = 'Rp';

    // ===== Utilitas angka =====
    function parseAmount(str) {
        if (typeof str !== 'string') str = String(str || '');
        var s = str.replace(/[^\d,.-]/g, '');      // buang simbol & spasi
        s = s.replace(/\./g, '');                  // buang ribuan
        s = s.replace(',', '.');                   // koma -> titik desimal
        var n = parseFloat(s);
        return isNaN(n) ? 0 : Math.max(0, n);
    }

    function formatAmount(n) {
        n = Math.max(0, Math.round(n * 100) / 100);
        var parts = n.toFixed(2).split('.');
        var intPart = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        var decPart = parts[1].replace(/0+$/, '');
        return decPart ? intPart + ',' + decPart : intPart;
    }

    function money(n) {
        return currency + ' ' + formatAmount(n);
    }

    function readCurrency() {
        if (window.CURRENCY_HINT) {
            currency = window.CURRENCY_HINT;
            return;
        }
        var el = document.getElementById('currency-hint');
        if (el && el.textContent) currency = el.textContent.trim();
    }

    // ===== Kalkulasi =====
    function recalc() {
        var tbody = document.getElementById('items-body');
        if (!tbody) return;

        var sum = 0;
        var rows = tbody.querySelectorAll('tr.item-row');
        rows.forEach(function (row) {
            var qtyEl = row.querySelector('input.qty');
            var priceEl = row.querySelector('input.price');
            var subEl = row.querySelector('.row-subtotal');
            var qty = Math.max(0, parseInt(qtyEl.value, 10) || 0);
            var price = parseAmount(priceEl.value);
            var sub = qty * price;
            sum += sub;
            subEl.textContent = money(sub);
        });

        var discount = parseAmount(document.getElementById('discount') ? document.getElementById('discount').value : '0');
        var paid = parseAmount(document.getElementById('paid') ? document.getElementById('paid').value : '0');
        if (discount > sum) discount = sum;
        if (paid > sum - discount) paid = sum - discount;
        var remaining = sum - discount - paid;

        var el = document.querySelector('.sum-subtotal');
        if (el) el.textContent = money(sum);
        el = document.querySelector('.sum-remaining');
        if (el) el.textContent = money(remaining);
    }

    // ===== Input uang: format ribuan saat blur =====
    function attachMoneyInputs(root) {
        var els = (root || document).querySelectorAll('input.price, input.amount-input');
        els.forEach(function (input) {
            if (input.dataset.moneyBound) return;
            input.dataset.moneyBound = '1';
            input.addEventListener('input', recalc);
            input.addEventListener('blur', function () {
                input.value = formatAmount(parseAmount(input.value));
            });
        });
    }

    // ===== Baris item =====
    function newRow() {
        var tbody = document.getElementById('items-body');
        var tr = document.createElement('tr');
        tr.className = 'item-row';
        tr.innerHTML =
            '<td><input type="text" name="item_name[]" required placeholder="Nama item / jasa"></td>' +
            '<td><input type="number" name="qty[]" value="1" min="1" step="1" class="qty" required></td>' +
            '<td><input type="text" name="price[]" class="price" inputmode="decimal" placeholder="0"></td>' +
            '<td class="num row-subtotal">' + money(0) + '</td>' +
            '<td><button type="button" class="btn btn-sm btn-danger remove-row" title="Hapus item">&times;</button></td>';
        tbody.appendChild(tr);
        attachMoneyInputs(tr);
        recalc();
        return tr;
    }

    function removeRow(btn) {
        var tbody = document.getElementById('items-body');
        if (tbody.querySelectorAll('tr.item-row').length <= 1) {
            var row = btn.closest('tr.item-row');
            var name = row.querySelector('input.item_name, input[name="item_name[]"]');
            if (name) name.value = '';
            row.querySelector('input.qty').value = '1';
            row.querySelector('input.price').value = '';
            recalc();
            return;
        }
        btn.closest('tr.item-row').remove();
        recalc();
    }

    // ===== Isi form dari template =====
    function setField(id, val) {
        var el = document.getElementById(id);
        if (el) el.value = (val === null || val === undefined) ? '' : String(val);
    }

    function fillFromTemplate(t) {
        setField('invoice_to', t.invoice_to);
        setField('invoice_to_company', t.invoice_to_company);
        setField('invoice_to_address', t.invoice_to_address);
        setField('invoice_to_phone', t.invoice_to_phone);
        setField('invoice_to_email', t.invoice_to_email);
        setField('payment_terms', t.payment_terms);
        setField('sign_name', t.sign_name);
        setField('sign_position', t.sign_position);
        setField('discount', '');
        setField('paid', '');

        var tbody = document.getElementById('items-body');
        if (tbody) {
            tbody.innerHTML = '';
            var items = (t.items && t.items.length) ? t.items : [{ item_name: '', qty: 1, price: '' }];
            items.forEach(function (it) {
                var tr = newRow();
                setField('', '');
                tr.querySelector('input[name="item_name[]"]').value = it.item_name || '';
                tr.querySelector('input.qty').value = it.qty || 1;
                tr.querySelector('input.price').value = it.price ? formatAmount(parseFloat(it.price)) : '';
            });
            recalc();
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        readCurrency();

        var tplSelect = document.getElementById('template-select');
        if (tplSelect) {
            tplSelect.addEventListener('change', function () {
                var id = parseInt(this.value, 10);
                if (!id) return;
                var t = (window.TEMPLATES || []).filter(function (x) { return x.id === id; })[0];
                if (t) fillFromTemplate(t);
            });
        }

        var addBtn = document.getElementById('add-row');
        if (addBtn) addBtn.addEventListener('click', newRow);

        var tbody = document.getElementById('items-body');
        if (tbody) {
            tbody.addEventListener('click', function (e) {
                if (e.target.classList.contains('remove-row')) removeRow(e.target);
            });
            tbody.addEventListener('input', function (e) {
                if (e.target.classList.contains('qty') || e.target.classList.contains('price')) recalc();
            });
            attachMoneyInputs(tbody);
            recalc();
        }
    });
})();
