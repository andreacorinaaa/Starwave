let editId  = null;
let hapusId = null;

function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}

function handleOverlay(event, id) {
    if (event.target === document.getElementById(id)) {
        closeModal(id);
    }
}

document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
        closeModal('modal-edit');
        closeModal('confirm-hapus');
    }
});

function openEditHarga(id, nama, harga) {
    editId = id; 

    document.getElementById('edit-nama').textContent = nama;
    document.getElementById('inp-harga-edit').value  = harga;

    document.getElementById('modal-edit').classList.add('active');

    setTimeout(function () {
        document.getElementById('inp-harga-edit').focus();
    }, 100);
}

async function simpanHarga() {
    const harga = parseInt(document.getElementById('inp-harga-edit').value);

    if (!harga || harga <= 0) {
        showToast('Harga harus lebih dari 0.', 'error');
        return;
    }

    const btn = document.getElementById('btn-save-harga');
    btn.disabled    = true;
    btn.textContent = 'Menyimpan...';

    const formData = new FormData();
    formData.append('action', 'update_harga');
    formData.append('id', editId);
    formData.append('harga', harga);

    try {
        const response = await fetch('produk.php', { method: 'POST', body: formData });
        const data     = await response.json();

        if (data.success) {
            const elHarga = document.getElementById('harga-' + editId);
            if (elHarga) {
                elHarga.textContent = 'Rp ' + harga.toLocaleString('id-ID');
            }
            showToast('✓ ' + data.message, 'success');
            closeModal('modal-edit');
        } else {
            showToast('✗ ' + data.message, 'error');
        }
    } catch (error) {
        showToast('Gagal terhubung ke server.', 'error');
    } finally {
        btn.disabled    = false;
        btn.textContent = 'Simpan';
    }
}

function openHapus(id, nama) {
    hapusId = id;

    document.getElementById('hapus-nama').textContent = nama;
    document.getElementById('confirm-hapus').classList.add('active');
}

async function eksekusiHapus() {
    const btn = document.getElementById('btn-hapus-ok');
    btn.disabled    = true;
    btn.textContent = 'Menghapus...';

    const formData = new FormData();
    formData.append('action', 'hapus');
    formData.append('id', hapusId);

    try {
        const response = await fetch('produk.php', { method: 'POST', body: formData });
        const data     = await response.json();

        if (data.success) {
            const card = document.getElementById('card-' + hapusId);
            if (card) card.remove();

            const badge = document.getElementById('badge-total');
            const sisa  = Math.max(0, (parseInt(badge.textContent) || 0) - 1);
            badge.textContent = sisa + ' produk';

            showToast('✓ ' + data.message, 'success');
            closeModal('confirm-hapus');
        } else {
            showToast('✗ ' + data.message, 'error');
        }
    } catch (error) {
        showToast('Gagal terhubung ke server.', 'error');
    } finally {
        btn.disabled    = false;
        btn.textContent = 'Ya, Hapus';
    }
}

async function simpanStok(id, ukuran, inputElement) {
    const jumlah = Math.max(0, parseInt(inputElement.value) || 0);
    inputElement.value = jumlah;

    const formData = new FormData();
    formData.append('action', 'update_stok');
    formData.append('id', id);
    formData.append('ukuran', ukuran);
    formData.append('jumlah', jumlah);

    try {
        const response = await fetch('produk.php', { method: 'POST', body: formData });
        const data     = await response.json();

        if (data.success) {
            const key   = id + '-' + ukuran.toLowerCase();
            const badge = document.getElementById('badge-' + key);
            const label = document
                .querySelector('#stok-' + key)
                .closest('.stok-row')
                .querySelector('.stok-label');

            const adaStok = jumlah > 0;

            if (badge) {
                badge.textContent = adaStok ? jumlah + ' pcs' : 'Habis';
                badge.className   = 'stok-badge ' + (adaStok ? 'ada' : 'habis');
            }
            if (label) {
                label.className = 'stok-label ' + (adaStok ? 'ada' : 'habis');
            }

            showToast('✓ Stok ' + ukuran + ' diperbarui: ' + jumlah, 'success');
        }
    } catch (error) {
        showToast('Gagal menyimpan stok.', 'error');
    }
}

function ubahStok(id, ukuran, btn, delta) {
    const input = document.getElementById('stok-' + id + '-' + ukuran.toLowerCase());

    input.value = Math.max(0, (parseInt(input.value) || 0) + delta);

    simpanStok(id, ukuran, input);
}
let toastTimer;

function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');

    toast.textContent = message;
    toast.className   = `toast ${type} show`;

    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () {
        toast.classList.remove('show');
    }, 3500);
}

function bukaModalHapusUlasan(el) {
    var link  = el.getAttribute('data-link');
    var nama  = el.getAttribute('data-nama');
    document.getElementById('modalHapusUlasanNama').textContent = nama;
    document.getElementById('modalHapusUlasanLink').setAttribute('href', link);
    document.getElementById('modalHapusUlasan').classList.add('open');
}

function tutupModalHapusUlasan() {
    document.getElementById('modalHapusUlasan').classList.remove('open');
}
document.addEventListener('DOMContentLoaded', function () {
    var modal = document.getElementById('modalHapusUlasan');
    if (modal) {
        // klik di luar box -> tutup modal
        modal.addEventListener('click', function (e) {
            if (e.target === this) tutupModalHapusUlasan();
        });
    }
});