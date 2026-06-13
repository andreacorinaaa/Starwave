<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include('config/koneksi.php');

if (!isset($_SESSION['user'])) {
    $_SESSION['redirect_after_login'] = 'buat_ulasan.php';
    header("Location: masuk/login.php?msg=login_dulu");
    exit;
}

$user_email = $_SESSION['user'];
$user = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM users WHERE email='$user_email'"
));
$user_id = $user['id_user'];

if (!isset($_GET['id'])) {
    header("Location: order.php");
    exit;
}

$id_order = (int)$_GET['id'];

$order = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM orders
     WHERE id='$id_order'
     AND id_user='$user_id'
     AND status='selesai'"
));

if (!$order) {
    header("Location: order.php");
    exit;
}

$cek_ulasan = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM ulasan WHERE id_order='$id_order'"
));

$pesan = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bintang  = (int)$_POST['bintang'];
    $komentar = mysqli_real_escape_string($conn, trim($_POST['komentar']));

    if ($bintang < 1 || $bintang > 5) {
        $pesan = "error|Pilih bintang antara 1 sampai 5.";
    } elseif (empty($komentar)) {
        $pesan = "error|Komentar tidak boleh kosong.";
    } elseif ($cek_ulasan) {
        $pesan = "error|Kamu sudah memberikan ulasan untuk pesanan ini.";
    } else {
        $nama_produk_order = $order['nama_produk'];

        $cari_produk = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT id FROM produk 
             WHERE '" . mysqli_real_escape_string($conn, $nama_produk_order) . "' LIKE CONCAT(nama_produk, '%')
             LIMIT 1"
        ));

        $id_produk_order = $cari_produk ? (int)$cari_produk['id'] : 0;

        mysqli_query($conn,
            "INSERT INTO ulasan (id_order, id_user, id_produk, nama_produk, bintang, komentar, created_at)
             VALUES ('$id_order', '$user_id', '$id_produk_order', '" . mysqli_real_escape_string($conn, $nama_produk_order) . "', '$bintang', '$komentar', NOW())"
        );

        $pesan = "success|Terima kasih! Ulasan kamu berhasil disimpan.";
        $cek_ulasan = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT * FROM ulasan WHERE id_order='$id_order'"
        ));
    }
}

$pesan_type = $pesan_text = "";
if ($pesan) {
    [$pesan_type, $pesan_text] = explode('|', $pesan, 2);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ulasan Produk — STARWAVE</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="order.css">
</head>

<body>

<!-- NAVBAR -->
<header>
    <nav>
        <h1>STARWAVE</h1>
        <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="man.php">Man</a></li>
            <li><a href="woman.php">Woman</a></li>
            <li><a href="accessories.php">Accessories</a></li>
            <li><a href="order.php" class="active">Order</a></li>
            <li><a href="keranjang.php">Keranjang</a></li>
        </ul>
        <form action="search.php" method="GET" style="display:inline;">
            <input type="text" name="q" placeholder="Search produk..." style="padding:5px;">
        </form>
        <?php if (isset($_SESSION['user'])): ?>
    <a href="profile.php" style="margin-left:15px; text-decoration:none; color:#333; display:flex; align-items:center;" title="Profile">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="8" r="4"/>
            <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
        </svg>
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
    <a href="masuk/login.php" style="margin-left:15px; text-decoration:none; color:#333;">Login</a>
<?php endif; ?>
    </nav>
</header>

<!-- Breadcrumb -->
<div class="breadcrumb-bar">
    <h1>Ulasan Produk</h1>
    <div class="breadcrumb">
        <a href="index.php">Home</a>
        <span>›</span>
        <a href="order.php">Order</a>
        <span>›</span>
        Ulasan
    </div>
</div>

<div class="uls-section">

    <?php if ($pesan_text): ?>
        <div class="alert <?= $pesan_type ?>">
            <?= $pesan_type == 'success' ? '✅' : '❌' ?>
            <?= htmlspecialchars($pesan_text) ?>
        </div>
    <?php endif; ?>

    <!-- Info Pesanan -->
    <div class="uls-order-info-card">
        <div class="uls-order-info-row">
            <span class="uls-order-info-label">Produk</span>
            <span class="uls-order-info-value"><?= htmlspecialchars($order['nama_produk']) ?></span>
        </div>
        <div class="uls-order-info-row">
            <span class="uls-order-info-label">Qty</span>
            <span class="uls-order-info-value"><?= $order['qty'] ?> pcs</span>
        </div>
        <div class="uls-order-info-row">
            <span class="uls-order-info-label">Tanggal Order</span>
            <span class="uls-order-info-value"><?= $order['tanggal_order'] ?></span>
        </div>
        <div class="uls-order-info-row">
            <span class="uls-order-info-label">Status</span>
            <span class="uls-order-info-value" style="color:#3a9e5f;">✓ Selesai</span>
        </div>
    </div>

    <?php if ($cek_ulasan): ?>

        <!-- Ulasan sudah ada -->
        <div class="uls-sudah-ada">
            <p class="uls-sudah-judul">Ulasan Kamu</p>
            <div class="uls-bintang-display">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <span class="uls-bintang-icon <?= $i <= $cek_ulasan['bintang'] ? 'aktif' : '' ?>">★</span>
                <?php endfor; ?>
                <span class="uls-bintang-angka"><?= $cek_ulasan['bintang'] ?>/5</span>
            </div>
            <p class="uls-komentar-text">"<?= htmlspecialchars($cek_ulasan['komentar']) ?>"</p>
            <p class="uls-tanggal">Diulas pada <?= date('d M Y', strtotime($cek_ulasan['created_at'])) ?></p>
        </div>

        <div class="uls-form-actions">
            <a href="order.php" class="uls-btn-kembali">← Kembali ke Pesanan</a>
        </div>

    <?php else: ?>

        <!-- Form Ulasan -->
        <form method="POST" action="buat_ulasan.php?id=<?= $id_order ?>" class="uls-form" style="width:100%;box-sizing:border-box;">

            <div class="uls-form-group">
                <label class="uls-form-label">Beri Bintang</label>
                <div class="uls-bintang-pilih" id="bintang-container">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <span class="uls-bintang-btn" data-nilai="<?= $i ?>">★</span>
                    <?php endfor; ?>
                </div>
                <input type="hidden" name="bintang" id="input-bintang" value="0">
                <p class="uls-bintang-label-teks" id="bintang-label">Pilih bintang di atas</p>
            </div>

            <div class="uls-form-group">
                <label class="uls-form-label" for="komentar">Komentar</label>
                <textarea
                    name="komentar"
                    id="komentar"
                    class="uls-form-textarea"
                    placeholder="Ceritakan pengalamanmu dengan produk ini..."
                    rows="5"
                ></textarea>
            </div>

            <div class="uls-form-actions">
                <a href="order.php" class="uls-btn-kembali">← Kembali</a>
                <button type="submit" class="uls-btn-kirim">Kirim Ulasan</button>
            </div>

        </form>

    <?php endif; ?>

</div>

<!-- FOOTER -->
<footer>
    <div class="footer-box">
        <div>
            <h3>Store</h3>
            <p>Man</p>
            <p>Woman</p>
            <p>Accessories</p>
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

<script>
const bintangBtns = document.querySelectorAll('.uls-bintang-btn');
const inputBintang = document.getElementById('input-bintang');
const bintangLabel = document.getElementById('bintang-label');

const labelTeks = ['', 'Sangat Buruk', 'Buruk', 'Cukup', 'Bagus', 'Sangat Bagus'];

bintangBtns.forEach(btn => {
    btn.addEventListener('mouseover', () => {
        const nilai = parseInt(btn.dataset.nilai);
        bintangBtns.forEach((b, i) => {
            b.classList.toggle('hover', i < nilai);
        });
    });

    btn.addEventListener('mouseout', () => {
        const terpilih = parseInt(inputBintang.value);
        bintangBtns.forEach((b, i) => {
            b.classList.remove('hover');
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
</script>

</body>
</html>