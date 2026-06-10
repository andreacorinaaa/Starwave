<?php
session_start();
include('config/koneksi.php');

if (!isset($_GET['id'])) {
    die("ID order tidak ditemukan");
}

$id_order = (int)$_GET['id'];

$order = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM orders WHERE id='$id_order'"
));

if (!$order) {
    die("Pesanan tidak ditemukan");
}

$harga = $order['harga'];
$qty   = $order['qty'];
$total = $order['total_harga'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran</title>
    <link rel="stylesheet" href="order.css">
    <link rel="stylesheet" href="style.css">
</head>

<body>

<!-- NAVBAR -->
<!-- NAVBAR -->
<header>
    <nav>
        <h1>STARWAVE</h1>
        <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="man.php">Man</a></li>
            <li><a href="woman.php" class="active">Woman</a></li>
            <li><a href="accessories.php">Accessories</a></li>
            <li><a href="order.php">Order</a></li>
        </ul>
        <form action="search.php" method="GET" style="display:inline;">
            <input type="text" name="q" placeholder="Search produk..." style="padding:5px;">
        </form>
        <?php if (isset($_SESSION['user'])): ?>
            <a href="profile.php" style="margin-left:15px; text-decoration:none; color:#333;">Profile</a>
        <?php else: ?>
            <a href="masuk/login.php" style="margin-left:15px; text-decoration:none; color:#333;">Login</a>
        <?php endif; ?>
    </nav>
</header>

<div class="payment-container">
    <div class="payment-card">

        <!-- HEADER -->
        <div class="payment-header">
            <h2>Pembayaran Transfer</h2>
            <p>Selesaikan pembayaran pesanan kamu</p>
        </div>

        <!-- BOX WRAPPER -->
        <div class="payment-top">

            <!-- TOTAL -->
            <div class="total-box">
                <span>Total Pembayaran</span>
                <h1>Rp <?= number_format($total, 0, ',', '.'); ?></h1>
            </div>

            <!-- BANK -->
            <div class="bank-box">
                <div class="bank-title">Bank BCA</div>
                <div class="rekening">123-456-7890</div>
                <p>a.n STARWAVE</p>
            </div>

        </div>

        <!-- NOTE -->
        <div class="payment-note">
            Transfer sesuai total pembayaran lalu simpan bukti transfer.
        </div>

        <!-- DETAIL -->
        <div class="detail-order">

            <h3>Detail Pesanan</h3>

            <div class="detail-item">
                <span>Produk</span>
                <strong><?= $order['nama_produk']; ?></strong>
            </div>

            <div class="detail-item">
                <span>Quantity</span>
                <strong><?= $order['qty']; ?></strong>
            </div>

            <div class="detail-item">
                <span>Harga</span>
                <strong>Rp <?= number_format($harga, 0, ',', '.'); ?></strong>
            </div>

            <div class="detail-item">
                <span>Status</span>
                <span class="status-badge status-pending">Menunggu Pembayaran</span>
            </div>

        </div>

        <a href="order.php" class="btn-payment">Kembali ke Order</a>

    </div>
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

</body>
</html>