<?php
session_start();           
include('config/koneksi.php'); 

if (!isset($_SESSION['user'])) {
   
    $_SESSION['redirect_after_login'] = 'order.php';

    // Redirect ke halaman login dengan pesan peringatan
    header("Location: masuk/login.php?msg=login_dulu");
    exit; 
}

$user_email = $_SESSION['user']; 

$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$user_email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC); // Ambil satu baris data user

if (!$user) {
    session_destroy();           
    header("Location: masuk/login.php");
    exit;
}

$user_id = $user['id_user']; 
$pesan   = "";               

if (isset($_GET['batal'])) {
    // (int) → paksa jadi angka bulat, mencegah injeksi lewat URL
    $id_order = (int)$_GET['batal'];

    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND id_user = ? AND status = 'pending_payment' AND (status_bayar IS NULL OR status_bayar = 'unpaid')");
    $stmt->execute([$id_order, $user_id]);
    $cek = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cek) {
        // Order valid → ubah status jadi 'batal'
        $stmt = $pdo->prepare("UPDATE orders SET status = 'batal' WHERE id = ?");
        $stmt->execute([$id_order]);
        $pesan = "success|Pesanan berhasil dibatalkan.";
    } else {
        $pesan = "error|Pesanan tidak bisa dibatalkan.";
    }
}

if (isset($_GET['hapus'])) {
    $id_order = (int)$_GET['hapus'];

    // Verifikasi: pastikan order ini benar-benar milik user yang login
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND id_user = ?");
    $stmt->execute([$id_order, $user_id]);
    $cek = $stmt->fetch(PDO::FETCH_ASSOC);

    // in_array() → cek apakah status ada di dalam daftar yang diizinkan
    if ($cek && in_array($cek['status'], ['selesai', 'batal', 'qr_expired'])) {
        $stmt = $pdo->prepare("DELETE FROM orders WHERE id = ? AND id_user = ?");
        $stmt->execute([$id_order, $user_id]);
        $pesan = "success|Riwayat pesanan berhasil dihapus.";
    } else {
        $pesan = "error|Riwayat tidak bisa dihapus.";
    }
}

$stmt = $pdo->prepare("
    SELECT o.*,
        -- Subquery 1: cek apakah order ini sudah diberi ulasan (ambil id ulasan jika ada)
        (SELECT id FROM ulasan WHERE id_order = o.id LIMIT 1) AS sudah_ulasan,

        -- Subquery 2: cari id produk berdasarkan nama produk yang ada di order
        -- Dipakai untuk tombol 'Beli Ulang' jika QR kadaluarsa
        (SELECT id FROM produk WHERE o.nama_produk LIKE CONCAT(nama_produk, '%') LIMIT 1) AS id_produk_ref

    FROM orders o
    WHERE o.id_user = ?
    ORDER BY o.created_at DESC  -- Urutkan dari yang terbaru
");
$stmt->execute([$user_id]);
$riwayat = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pesan_type = $pesan_text = ""; // Default kosong
if ($pesan) {
    // Limit 2 → ['success', 'Teks pesan'] (aman jika teks mengandung '|')
    [$pesan_type, $pesan_text] = explode('|', $pesan, 2);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Riwayat — STARWAVE</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="order.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>

<header>
    <nav>
        <h1><a href="index.php">STARWAVE</a></h1>
        <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="man.php">Man</a></li>
            <li><a href="woman.php">Woman</a></li>
            <li><a href="accessories.php">Accessories</a></li>
            <li><a href="order.php" class="active">Order</a></li>
            <li><a href="keranjang.php">Keranjang</a></li>
        </ul>
        <form action="search.php" method="GET" class="search-form" onsubmit="return validateSearch(this)">
            <input type="text" name="q" placeholder="Search produk..." class="search-input">
            <button type="submit" class="search-btn">
                <i class="fa fa-search"></i>
            </button>
        </form>

        <!-- 3 kondisi pojok kanan navbar: user / admin / belum login -->
        <?php if (isset($_SESSION['user'])): ?>
            <a href="profile.php" style="margin-left:15px; text-decoration:none; display:flex; align-items:center;" title="Profile">
                <?php
                    $stmt2 = $pdo->prepare("SELECT foto_profil FROM users WHERE email = ?");
                    $stmt2->execute([$_SESSION['user']]);
                    $navUser = $stmt2->fetch(PDO::FETCH_ASSOC);
                ?>
                <?php if (!empty($navUser['foto_profil']) && file_exists($navUser['foto_profil'])): ?>
                    <img src="<?= htmlspecialchars($navUser['foto_profil']) ?>"
                         style="width:34px; height:34px; border-radius:50%; object-fit:cover; border:2px solid #2a7fa8;">
                <?php else: ?>
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#c9dde8" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="8" r="4"/>
                        <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
                    </svg>
                <?php endif; ?>
            </a>
        <?php elseif (isset($_SESSION['admin'])): ?>
            <a href="admin/dashboard.php" style="margin-left:15px; text-decoration:none; color:#4f6ef7; display:flex; align-items:center; gap:5px; font-size:12px; font-weight:700; letter-spacing:1px;" title="Admin Panel">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="8" r="4"/>
                    <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
                </svg>
                ADMIN
            </a>
        <?php else: ?>
            <a href="masuk/login.php" class="btn-login">Login</a>
        <?php endif; ?>
    </nav>
</header>

<div class="breadcrumb-bar">
    <h1>Riwayat Pesanan</h1>
    <div class="breadcrumb">
        <a href="index.php">Home</a><span>/</span>
        <span style="color:#2b1a0e">Order</span>
    </div>
</div>

<main class="ord-container">

    <?php if ($pesan_text): ?>
        <div class="alert <?= $pesan_type ?>">
            <!-- Ternary operator: jika sukses tampilkan ✅, selain itu tampilkan ❌ -->
            <?= $pesan_type == 'success' ? '✅' : '❌' ?>
            <?= htmlspecialchars($pesan_text) ?>
        </div>
    <?php endif; ?>

    <?php if (empty($riwayat)): ?>
        <p class="ord-no-order">Belum ada pesanan.</p>

    <?php else: ?>
        <!-- Tabel riwayat pesanan -->
        <table class="ord-table">
            <tr>
                <th>#</th>
                <th>Produk</th>
                <th>Qty</th>
                <th>Penerima</th>
                <th>Tanggal</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>

            <!-- Loop setiap pesanan, $no = nomor urut baris -->
            <?php $no = 1; foreach ($riwayat as $row): ?>
                <?php
                // Ambil status pembayaran, pakai '' jika null (operator ??)
                $status_bayar = $row['status_bayar'] ?? '';

                // Tentukan kondisi tampilan berdasarkan kombinasi status order + status bayar
                $is_waiting = ($row['status'] == 'pending_payment' && $status_bayar === 'menunggu_konfirmasi');
                $is_unpaid  = ($row['status'] == 'pending_payment' && $status_bayar !== 'menunggu_konfirmasi');
                ?>
                <tr>
                    <td><?= $no++ ?></td>  <!-- $no++ → tampilkan dulu, baru tambah 1 -->
                    <td><?= htmlspecialchars($row['nama_produk']) ?></td>
                    <td><?= $row['qty'] ?></td>
                    <td><?= htmlspecialchars($row['nama_penerima']) ?></td>
                    <td><?= $row['tanggal_order'] ?></td>

                    <td>
                        <?php if ($is_waiting): ?>
                            <!-- Sudah bayar, menunggu konfirmasi admin -->
                            <span class="status-badge status-waiting">Menunggu Konfirmasi</span>

                        <?php elseif ($is_unpaid): ?>
                            <!-- Belum bayar sama sekali -->
                            <span class="status-badge status-pending">Belum Bayar</span>

                        <?php elseif ($row['status'] == 'qr_expired'): ?>
                            <!-- QR Code pembayaran sudah kadaluarsa -->
                            <span class="status-badge status-qr_expired">⏰ QR Kadaluarsa</span>

                        <?php else: ?>
                            <!-- Status lain: diproses, dikirim, selesai, batal -->
                            <!-- ucfirst() → huruf pertama jadi kapital, misal: "diproses" → "Diproses" -->
                            <span class="status-badge status-<?= $row['status'] ?>"><?= ucfirst($row['status']) ?></span>

                        <?php endif; ?>
                    </td>

                    <td class="ord-td-aksi">

                        <?php if ($is_waiting): ?>
                            <!-- Sudah bayar → bisa lihat bukti pembayaran -->
                            <a href="payment.php?id=<?= $row['id'] ?>" class="ord-btn-edit">Lihat Bukti</a>

                        <?php elseif ($is_unpaid): ?>
                            <!-- Belum bayar → bisa bayar atau batalkan -->
                            <a href="payment.php?id=<?= $row['id'] ?>" class="ord-btn-edit">Belum Bayar</a>
                            <!-- onclick → panggil showModal() di JS, return false → cegah link langsung diarahkan -->
                            <a href="#" class="ord-btn-batal"
                               onclick="showModal('Yakin batalkan pesanan ini?', 'order.php?batal=<?= $row['id'] ?>'); return false;">
                               Batal
                            </a>

                        <?php elseif ($row['status'] == 'selesai'): ?>
                            <!-- Selesai → bisa beri ulasan atau hapus riwayat -->
                            <?php if ($row['sudah_ulasan']): ?>
                                <!-- Sudah pernah memberi ulasan -->
                                <span class="ord-btn-ulasan-done">✓ Diulas</span>
                            <?php else: ?>
                                <!-- Belum ulasan → tampilkan tombol ulasan -->
                                <a href="buat_ulasan.php?id=<?= $row['id'] ?>" class="ord-btn-ulasan">Beri Ulasan</a>
                            <?php endif; ?>
                            <a href="#" class="ord-btn-hapus"
                               onclick="showModal('Hapus riwayat ini? Tidak bisa dikembalikan!', 'order.php?hapus=<?= $row['id'] ?>'); return false;">
                               Hapus
                            </a>

                        <?php elseif ($row['status'] == 'batal'): ?>
                            <!-- Dibatalkan → hanya bisa hapus riwayat -->
                            <a href="#" class="ord-btn-hapus"
                               onclick="showModal('Hapus riwayat ini? Tidak bisa dikembalikan!', 'order.php?hapus=<?= $row['id'] ?>'); return false;">
                               Hapus
                            </a>

                        <?php elseif ($row['status'] == 'qr_expired'): ?>
                            <!-- QR kadaluarsa → bisa beli ulang produk yang sama, atau hapus -->
                            <?php if (!empty($row['id_produk_ref'])): ?>
                                <!-- id_produk_ref didapat dari subquery di atas -->
                                <a href="detail.php?id=<?= $row['id_produk_ref'] ?>" class="ord-btn-edit">Beli Ulang</a>
                            <?php endif; ?>
                            <a href="#" class="ord-btn-hapus"
                               onclick="showModal('Hapus riwayat ini? Tidak bisa dikembalikan!', 'order.php?hapus=<?= $row['id'] ?>'); return false;">
                               Hapus
                            </a>

                        <?php else: ?>
                            <!-- Status lain yang tidak punya aksi (misal: sedang diproses) -->
                            <span class="ord-no-aksi">—</span>
                        <?php endif; ?>

                    </td>
                </tr>
            <?php endforeach; ?>

        </table>
    <?php endif; ?>

</main>

<footer>
    <div class="footer-box">
        <div>
            <h3>Store</h3>
            <p>Man</p><p>Woman</p><p>Accessories</p>
        </div>
        <div>
            <h3>Business</h3>
            <p><a href="mailto:starwave@gmail.com">starwave@gmail.com</a></p>
            <p>081836737367367</p>
        </div>
        <div>
            <h3>Social</h3>
            <p><a href="https://instagram.com/starwave" target="_blank">Instagram : starwave.fashion</a></p>
        </div>
    </div>
</footer>

<div id="ord-modal">
    <div id="ord-modal-inner">
        <h3>STARWAVE</h3>
        <p id="ord-modal-msg"></p>          
        <div id="ord-modal-buttons">
            <a id="ord-modal-confirm" href="#">Ya</a>             
            <button id="ord-modal-btn-tidak" onclick="closeModal()">Tidak</button>
        </div>
    </div>
</div>

<script src="order.js"></script>

</body>
</html>