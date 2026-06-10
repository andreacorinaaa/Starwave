<?php
session_start();
include('config/koneksi.php');

if (!isset($_GET['id'])) {
    die("ID order tidak ditemukan");
}

$id_order = (int)$_GET['id'];

$order = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT o.*, p.gambar
FROM orders o
LEFT JOIN produk p
ON o.nama_produk LIKE CONCAT(p.nama_produk,'%')
WHERE o.id='$id_order'
"));

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

<div class="confirm-container">

    <div class="confirm-header">
        <h2>Order Confirmation</h2>
        <p>Thank you for your order!</p>
    </div>

    <!-- STEP -->
    <div class="steps">

        <div class="step active">
            <span>1</span>
            <p>Shipping</p>
        </div>

        <div class="step active">
            <span>2</span>
            <p>Payment</p>
        </div>

        <div class="step active">
            <span>3</span>
            <p>Summary</p>
        </div>

        <div class="step current">
            <span>4</span>
            <p>Confirmation</p>
        </div>

    </div>

    <!-- INFO ORDER -->
    <div class="order-info">

        <div>
            <small>Delivery Date</small>
            <strong><?= date('d M Y'); ?></strong>
        </div>

        <div>
            <small>Order ID</small>
            <strong>#<?= $order['id']; ?></strong>
        </div>

        <div>
            <small>Payment Method</small>
            <strong>BCA Transfer</strong>
        </div>

        <div>
            <small>Status</small>
            <strong>Pending</strong>
        </div>

    </div>

    <!-- HEADER TABLE -->
    <div class="table-head">
        <span>Product</span>
        <span>Shipping</span>
        <span>Quantity</span>
        <span>Total</span>
    </div>

<!-- ITEM -->
    <div class="product-row">

        <div class="product-info">
            <img src="<?= $order['gambar']; ?>" alt="<?= $order['nama_produk']; ?>">
            <div>
                <h4><?= $order['nama_produk']; ?></h4>
                <span>Rp <?= number_format($order['harga'],0,',','.'); ?></span>
            </div>
        </div>

        <div class="shipping">Free</div>

        <div class="qty-box">
            <?= $order['qty']; ?>
        </div>

        <div class="price">
            Rp <?= number_format($order['total_harga'],0,',','.'); ?>
        </div>

    </div>  <!-- ✅ tutup product-row di sini -->

    <!-- SUMMARY -->
    <div class="summary">
        <div class="sum-box">
            <p>Discount</p>
            <strong>Rp 0</strong>
        </div>
        <div class="sum-box">
            <p>Delivery</p>
            <strong>Free</strong>
        </div>
        <div class="sum-box">
            <p>Total</p>
            <strong>Rp <?= number_format($order['total_harga'],0,',','.'); ?></strong>
        </div>
    </div>

    <!-- BUTTON -->
    <div class="button-group">
        <a href="index.php" class="btn-back">Back to Shop</a>
        <a href="order.php" class="btn-place">Place Order</a>
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