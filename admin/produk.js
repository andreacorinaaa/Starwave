// Menyimpan ID produk yang sedang diedit / akan dihapus
let editId  = null;
let hapusId = null;

// Tutup modal berdasarkan id elemen-nya
function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}

// Kalau user klik area gelap di luar kotak modal, modal ikut tertutup
function handleOverlay(event, id) {
    if (event.target === document.getElementById(id)) {
        closeModal(id);
    }
}

// Tombol "Esc" di keyboard juga bisa menutup modal yang sedang terbuka
document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
        closeModal('modal-edit');
        closeModal('confirm-hapus');
    }
});

// Dipanggil saat tombol ✏️ di kartu produk diklik
function openEditHarga(id, nama, harga) {
    editId = id; // simpan id produk yang mau diedit

    document.getElementById('edit-nama').textContent = nama;
    document.getElementById('inp-harga-edit').value  = harga;

    document.getElementById('modal-edit').classList.add('active');

    // fokus otomatis ke kolom input harga
    setTimeout(function () {
        document.getElementById('inp-harga-edit').focus();
    }, 100);
}

// Dipanggil saat tombol "Simpan" di modal edit harga diklik
async function simpanHarga() {
    const harga = parseInt(document.getElementById('inp-harga-edit').value);

    // validasi sederhana di sisi browser
    if (!harga || harga <= 0) {
        showToast('Harga harus lebih dari 0.', 'error');
        return;
    }

    const btn = document.getElementById('btn-save-harga');
    btn.disabled    = true;
    btn.textContent = 'Menyimpan...';

    // Siapkan data yang mau dikirim ke produk.php
    const formData = new FormData();
    formData.append('action', 'update_harga');
    formData.append('id', editId);
    formData.append('harga', harga);

    try {
        const response = await fetch('produk.php', { method: 'POST', body: formData });
        const data     = await response.json();

        if (data.success) {
            // Update tampilan harga di kartu tanpa reload halaman
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

// Dipanggil saat tombol 🗑️ di kartu produk diklik (membuka konfirmasi)
function openHapus(id, nama) {
    hapusId = id; // simpan id produk yang mau dihapus

    document.getElementById('hapus-nama').textContent = nama;
    document.getElementById('confirm-hapus').classList.add('active');
}

// Dipanggil saat tombol "Ya, Hapus" di kotak konfirmasi diklik
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
            // Hapus kartu produk dari tampilan tanpa reload halaman
            const card = document.getElementById('card-' + hapusId);
            if (card) card.remove();

            // Kurangi angka "X produk" di badge total
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

// Dipanggil saat angka di kolom stok diubah manual lalu pindah fokus (onchange)
async function simpanStok(id, ukuran, inputElement) {
    // pastikan jumlah tidak negatif
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

// Dipanggil saat tombol "+" atau "−" di sebelah kolom stok diklik
function ubahStok(id, ukuran, btn, delta) {
    const input = document.getElementById('stok-' + id + '-' + ukuran.toLowerCase());

    // delta = -1 (tombol kurang) atau +1 (tombol tambah)
    input.value = Math.max(0, (parseInt(input.value) || 0) + delta);

    // langsung simpan ke database setiap kali ditekan
    simpanStok(id, ukuran, input);
}

let toastTimer;

function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');

    toast.textContent = message;
    toast.className   = `toast ${type} show`;

    // otomatis hilang setelah 3.5 detik
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