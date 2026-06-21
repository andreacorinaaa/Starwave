// =====================================================================
// order.js — JavaScript gabungan untuk beberapa halaman:
//   - order.php   (Riwayat Pesanan)
//   - payment_logic.php (Halaman pembayaran / QRIS)
//   - search.php & halaman lain yang punya form search di navbar
//
// File ini dipanggil di bagian bawah halaman, contoh:
//   <script src="order.js"></script>
//
// Fungsi utama di file ini:
//   1. showModal()      — tampilkan popup konfirmasi (dipakai di order.php)
//   2. closeModal()     — tutup popup konfirmasi
//   3. (QRIS block)     — gambar QR code + timer (HANYA jalan kalau
//                          window.QRIS_DATA ada, misal di payment_logic.php)
//   4. setupPreview()   — preview gambar sebelum upload bukti bayar
//   5. validateSearch() — validasi form search di navbar (dipakai di semua
//                          halaman yang punya kolom search)
//
// PENTING: karena file ini dipakai di banyak halaman, bagian QRIS
// dibungkus dengan "if (window.QRIS_DATA)" supaya halaman yang TIDAK
// punya data itu (order.php, search.php, dll) tidak ikut error dan
// menghentikan sisa script (termasuk validateSearch).
// =====================================================================


// =====================================================================
// showModal() — Tampilkan popup konfirmasi sebelum aksi berbahaya
//
// Parameter:
//   msg → teks pertanyaan yang muncul di popup
//         contoh: 'Yakin batalkan pesanan ini?'
//   url → URL tujuan jika user klik "Ya"
//         contoh: 'order.php?batal=5'
//
// Cara dipanggil dari HTML (di atribut onclick tombol):
//   onclick="showModal('Yakin batalkan?', 'order.php?batal=5'); return false;"
//   → return false → mencegah link <a href="#"> langsung berpindah halaman
// =====================================================================
function showModal(msg, url) {
    document.getElementById('ord-modal-msg').innerText = msg;
    document.getElementById('ord-modal-confirm').href = url;
    document.getElementById('ord-modal').style.display = 'flex';
}


// =====================================================================
// closeModal() — Tutup/sembunyikan popup konfirmasi
//
// Dipanggil saat user klik tombol "Tidak"
// Di HTML: <button onclick="closeModal()">Tidak</button>
// =====================================================================
function closeModal() {
    document.getElementById('ord-modal').style.display = 'none';
}


// =====================================================================
// BONUS: Tutup modal jika user klik di luar area popup (background gelap)
// =====================================================================
document.addEventListener('DOMContentLoaded', function () {
    var modal = document.getElementById('ord-modal');

    if (modal) {
        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                closeModal();
            }
        });
    }

    console.log('order.js berhasil dimuat ✓');
});

if (window.QRIS_DATA) {
    const { qrisString, isExpired, isPaid, sudahUpload, expiredAtMs } = window.QRIS_DATA;
    if (!isExpired && !isPaid) {
        QRCode.toCanvas(document.getElementById('qr-canvas'), qrisString, {
            width: 190,
            margin: 1,
            color: { dark: '#1a1a1a', light: '#ffffff' }
        });
        if (!sudahUpload) {
            const timerInterval = setInterval(() => {
                const remaining = Math.max(0, expiredAtMs - Date.now());
                const mins = String(Math.floor(remaining / 60000)).padStart(2, '0');
                const secs = String(Math.floor((remaining % 60000) / 1000)).padStart(2, '0');

                document.getElementById('timer-count').textContent = `${mins}:${secs}`;

                if (remaining <= 0) {
                    clearInterval(timerInterval);
                    location.reload(); // waktu habis -> reload biar status jadi expired
                }
            }, 1000);
        }
    }

    function setupPreview(inputId, previewId, placeholderId) {
        const input = document.getElementById(inputId);
        if (!input) return; // form upload tidak selalu ada (misal sudah lunas)

        input.addEventListener('change', function () {
            const file = this.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = (e) => {
                const img = document.getElementById(previewId);
                img.src = e.target.result;
                img.style.display = 'block';
                document.getElementById(placeholderId).style.display = 'none';
            };
            reader.readAsDataURL(file);
        });
    }

    setupPreview('bukti_input', 'img-preview', 'placeholder1');
}

function validateSearch(form) {
    const keyword = form.q.value.trim();
    if (keyword === '') {
        return false; 
    }
    return true;
}