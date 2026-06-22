let currentStatus = 'semua';

function filterStatus(status, btn) {
    currentStatus = status;
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    applyFilter();
}

function togglePw() {
    const input = document.getElementById('password');
    input.type = input.type === 'password' ? 'text' : 'password';
}

function filterOrders() { applyFilter(); }

function applyFilter() {
    const q = document.getElementById('search-orders').value.toLowerCase();
    document.querySelectorAll('#orders-table tbody tr').forEach(row => {
        const rowStatus = row.getAttribute('data-status') || '';
        const rowBayar  = row.getAttribute('data-bayar') || '';
        const rowSearch = row.getAttribute('data-search') || '';

        let statusMatch = false;
        if (currentStatus === 'semua') statusMatch = true;
        else if (currentStatus === 'belum_bayar') statusMatch = rowBayar === 'belum_bayar';
        // FIX: 'pending_payment' itu artinya "Belum Bayar", BUKAN "Pending".
        // Sebelumnya disamain di sini, makanya item Belum Bayar ikut nyangkut
        // ke filter Pending. Sekarang cuma status 'pending' murni yang dicek.
        else if (currentStatus === 'pending') statusMatch = rowStatus === 'pending';
        else if (currentStatus === 'qr_expired') statusMatch = rowStatus === 'qr_expired';
        else statusMatch = rowStatus === currentStatus;

        const searchMatch = q === '' || rowSearch.includes(q);
        row.style.display = (statusMatch && searchMatch) ? '' : 'none';
    });
}

// Buka modal "lihat bukti bayar"
function openBukti(imgSrc, orderId, namaProduk, total, isPaid) {
    document.getElementById('bukti-img').src = imgSrc;
    document.getElementById('bukti-meta').innerHTML = `Order <strong>#${orderId}</strong> — ${namaProduk} — <strong>${total}</strong>`;

    const btnKonfirmasi = document.getElementById('btn-konfirmasi-dari-bukti');
    if (isPaid) {
        btnKonfirmasi.style.display = 'none';
    } else {
        btnKonfirmasi.style.display = '';
        btnKonfirmasi.onclick = () => { closeBukti(); openModal(orderId, namaProduk, total); };
    }
    document.getElementById('modal-bukti').classList.add('open');
}
function closeBukti() { document.getElementById('modal-bukti').classList.remove('open'); }

// Buka modal "konfirmasi pembayaran"
function openModal(orderId, namaProduk, total) {
    document.getElementById('modal-order-id').value = orderId;
    document.getElementById('modal-desc').innerHTML =
        `Konfirmasi pembayaran QRIS untuk:<br>
         <strong>#${orderId} — ${namaProduk}</strong><br>
         Total: <strong>${total}</strong><br><br>
         Jika pesanan ini berisi lebih dari 1 produk (checkout sekaligus dari keranjang),
         SEMUA item dalam 1 pesanan ini akan ikut dikonfirmasi lunas bersamaan.<br><br>
         Status order akan otomatis berubah ke <strong>Diproses</strong>.`;
    document.getElementById('modal-konfirmasi').classList.add('open');
}
function closeModal() { document.getElementById('modal-konfirmasi').classList.remove('open'); }

// Buka modal "konfirmasi hapus"
function openHapusModal(orderId) {
    document.getElementById('hapus-desc').textContent = `Hapus pesanan #${orderId}? Tidak bisa dikembalikan!`;
    document.getElementById('btn-hapus-ya').onclick = () => {
        window.location.href = `pesanan.php?hapus_order=${orderId}`;
    };
    document.getElementById('modal-hapus').classList.add('open');
}
function closeHapusModal() { document.getElementById('modal-hapus').classList.remove('open'); }

// Tutup modal kalau area gelap di luar kotak diklik
document.getElementById('modal-konfirmasi').addEventListener('click', function(e) { if (e.target === this) closeModal(); });
document.getElementById('modal-bukti').addEventListener('click', function(e) { if (e.target === this) closeBukti(); });
document.getElementById('modal-hapus').addEventListener('click', function(e) { if (e.target === this) closeHapusModal(); });