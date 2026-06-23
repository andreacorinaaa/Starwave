const orderForm = document.getElementById('dtl-orderForm');

let harga   = orderForm ? (parseInt(orderForm.dataset.harga) || 0) : 0;
let maxStok = (orderForm && orderForm.dataset.stokMax !== '')
    ? parseInt(orderForm.dataset.stokMax)
    : null; 

function changeQty(delta) {
    const input = document.getElementById('qty');
    let val = parseInt(input.value) + delta;

    if (val < 1) val = 1; 

    if (maxStok !== null && val > maxStok) {
        val = maxStok;      
        showStokWarning();
    } else {
        hideStokWarning();
    }

    input.value = val;
    updateTotal(val);
}

function updateTotal(qty) {
    const total = harga * qty;
    document.getElementById('totalHarga').innerText = 'Rp ' + total.toLocaleString('id-ID');
}

function ubahQty(btn, delta) {
    const row = btn.closest('tr');
    const qtyInput = row.querySelector('input[type="number"]');
    const maxStokRow = parseInt(qtyInput.dataset.max || '9999', 10);

    let qty = parseInt(qtyInput.value, 10) || 1;
    qty = Math.max(1, qty + delta);

    if (qty > maxStokRow) {
        qty = maxStokRow;
        showStokWarningKeranjang(row, maxStokRow);
    } else {
        hideStokWarningKeranjang(row);
    }

    qtyInput.value = qty;

    const hargaRow = parseFloat(row.querySelector('.item-checkbox').dataset.harga);
    const subtotalCell = row.querySelector('.ord-keranjang-subtotal');
    subtotalCell.textContent = 'Rp ' + (hargaRow * qty).toLocaleString('id-ID');

    recalcTotal();
}

function showStokWarningKeranjang(row, maxStokRow) {
    let warn = row.querySelector('.stok-warning-row');

    if (!warn) {
        warn = document.createElement('div');
        warn.className = 'stok-warning-row';
        warn.style.cssText =
            'color:#c0392b;font-size:11px;font-weight:700;margin-top:4px;';
        row.querySelector('.ord-qty-control').insertAdjacentElement('afterend', warn);
    }

    warn.textContent = 'Stok hanya tersisa ' + maxStokRow + ' pcs';
    warn.style.display = 'block';
}

function hideStokWarningKeranjang(row) {
    const warn = row.querySelector('.stok-warning-row');
    if (warn) warn.style.display = 'none';
}

function selectSize(el) {
    document.querySelectorAll('.dtl-size-btn').forEach(b => b.classList.remove('active'));
    el.classList.add('active');

    document.getElementById('ukuranInput').value = el.getAttribute('data-size');
    maxStok = parseInt(el.dataset.stok);

    const qtyInput = document.getElementById('qty');
    if (parseInt(qtyInput.value) > maxStok) {
        qtyInput.value = maxStok;
        updateTotal(maxStok);
    }

    hideStokWarning();
}

function showStokWarning() {
    let warn = document.getElementById('stokWarning');

    if (!warn) {
        warn = document.createElement('div');
        warn.id = 'stokWarning';
        warn.style.cssText =
            'background:#fdecea;border-left:4px solid #e05555;padding:10px 14px;' +
            'font-size:13px;color:#c0392b;font-weight:600;margin-top:10px;border-radius:4px;';
        document.querySelector('.dtl-order-row').insertAdjacentElement('beforebegin', warn);
    }

    warn.textContent = '⚠️ Jumlah melebihi stok yang tersedia (sisa ' + maxStok + ' pcs).';
    warn.style.display = 'block';
}

function hideStokWarning() {
    const warn = document.getElementById('stokWarning');
    if (warn) warn.style.display = 'none';
}

document.addEventListener('DOMContentLoaded', () => {

    const form = document.getElementById('dtl-orderForm');
    if (form) {
        form.addEventListener('submit', function (e) {
            const qty = parseInt(document.getElementById('qty').value);
            if (maxStok !== null && qty > maxStok) {
                e.preventDefault();
                showStokWarning();
            }
        });
    }

    const firstAvail = document.querySelector('.dtl-size-btn:not(.habis):not([disabled])');
    if (firstAvail) {
        firstAvail.classList.add('active');
        document.getElementById('ukuranInput').value = firstAvail.dataset.size;
        maxStok = parseInt(firstAvail.dataset.stok);
    }
});

const bintangBtns = document.querySelectorAll('.uls-bintang-btn');
const inputBintang = document.getElementById('input-bintang');
const bintangLabel = document.getElementById('bintang-label');

const labelTeks = ['', 'Sangat Buruk', 'Buruk', 'Cukup', 'Bagus', 'Sangat Bagus'];

bintangBtns.forEach(btn => {

    btn.addEventListener('mouseover', () => {
        const nilai = parseInt(btn.dataset.nilai);
        // Nyalakan (kasih class 'hover') bintang dari awal sampai nilai yang di-hover
        bintangBtns.forEach((b, i) => {
            b.classList.toggle('hover', i < nilai);
        });
    });

    btn.addEventListener('mouseout', () => {
        const terpilih = parseInt(inputBintang.value);
        bintangBtns.forEach((b, i) => {
            b.classList.remove('hover');
            // Balikin tampilan ke nilai yang SUDAH DIPILIH sebelumnya (kalau ada)
            b.classList.toggle('aktif', i < terpilih);
        });
    });

    btn.addEventListener('click', () => {
        const nilai = parseInt(btn.dataset.nilai);

        inputBintang.value = nilai;

        bintangBtns.forEach((b, i) => {
            b.classList.toggle('aktif', i < nilai);
        });

        bintangLabel.textContent = labelTeks[nilai];
    });
});

function toggleAllItems(master) {
    document.querySelectorAll('.item-checkbox').forEach(function (cb) {
        cb.checked = master.checked;
    });
    recalcTotal();
}

function recalcTotal() {
    let total = 0;
    let adaYangDicentang = false;

    document.querySelectorAll('.item-checkbox').forEach(function (cb) {
        if (cb.checked) {
            adaYangDicentang = true;
            const row   = cb.closest('tr');
            const harga = parseFloat(cb.dataset.harga);
            const qty   = parseInt(row.querySelector('input[type="number"]').value, 10) || 0;
            total += harga * qty;
        }
    });

    document.getElementById('grandTotal').textContent = 'Rp ' + total.toLocaleString('id-ID');

    const btn = document.getElementById('btnCheckout');
    if (btn) {
        btn.disabled = !adaYangDicentang;
        btn.style.opacity = adaYangDicentang ? '1' : '.5';
        btn.style.cursor  = adaYangDicentang ? 'pointer' : 'not-allowed';
    }
}

document.addEventListener('DOMContentLoaded', recalcTotal);

const fotoInput     = document.getElementById('foto_profil');
const fotoPreview   = document.getElementById('avatar-preview');
const fotoDefSvg    = document.getElementById('avatar-default-svg');
const fotoHint      = document.getElementById('file-name-hint');
const fotoSaveBtn   = document.getElementById('save-photo-btn');

if (fotoInput) {
    fotoInput.addEventListener('change', function () {
        if (!this.files || !this.files[0]) return;

        const file = this.files[0];

        fotoHint.textContent = file.name.length > 24
            ? file.name.slice(0, 22) + '…'
            : file.name;

        const reader = new FileReader();
        reader.onload = function (e) {
            fotoPreview.src = e.target.result;
            fotoPreview.style.display = 'block';
            if (fotoDefSvg) fotoDefSvg.style.display = 'none';
        };
        reader.readAsDataURL(file);

        fotoSaveBtn.style.display = 'inline-block';
    });
}