<?php
session_start();
include('config/koneksi.php'); // pastikan $pdo sudah didefinisikan di sini

$stmt = $pdo->prepare("SELECT * FROM produk WHERE kategori = ?");
$stmt->execute(['accessories']);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>STARWAVE - Accessories</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="order.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body class="page-accessories">

<!-- ================= NAVBAR ================= -->
<header>
    <nav>
        <h1><a href="index.php">STARWAVE</a></h1>
        <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="man.php">Man</a></li>
            <li><a href="woman.php">Woman</a></li>
            <li><a href="accessories.php"  class="active">Accessories</a></li>
            <li><a href="order.php">Order</a></li>
            <li><a href="keranjang.php">Keranjang</a></li>
        </ul>

        <!-- Form search produk -->
        <form action="search.php" method="GET" class="search-form" onsubmit="return validateSearch(this)">
            <input type="text" name="q" placeholder="Search produk..." class="search-input">
            <button type="submit" class="search-btn">
                <i class="fa fa-search"></i>
            </button>
        </form>

        <?php if (isset($_SESSION['user'])): ?>
            <!-- ===== Kondisi 1: User biasa sudah login -> tampilkan foto profil ===== -->
            <a href="profile.php" style="margin-left:15px; text-decoration:none; display:flex; align-items:center;" title="Profile">
                <?php
                    // Ambil foto profil user yang sedang login
                    $stmt2 = $pdo->prepare("SELECT foto_profil FROM users WHERE email = ?");
                    $stmt2->execute([$_SESSION['user']]);
                    $navUser = $stmt2->fetch(PDO::FETCH_ASSOC);
                ?>
                <?php if (!empty($navUser['foto_profil']) && file_exists($navUser['foto_profil'])): ?>
                    <!-- Kalau punya foto profil dan filenya ada -> tampilkan foto -->
                    <img src="<?= htmlspecialchars($navUser['foto_profil']) ?>"
                         style="width:34px; height:34px; border-radius:50%; object-fit:cover; border:2px solid #2a7fa8;">
                <?php else: ?>
                    <!-- Kalau belum punya foto -> tampilkan ikon default -->
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#c9dde8" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="8" r="4"/>
                        <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
                    </svg>
                <?php endif; ?>
            </a>

        <?php elseif (isset($_SESSION['admin'])): ?>
            <!-- ===== Kondisi 2: Yang login adalah admin -> tampilkan tombol ke dashboard admin ===== -->
            <a href="admin/dashboard.php" style="margin-left:15px; text-decoration:none; color:#4f6ef7; display:flex; align-items:center; gap:5px; font-size:12px; font-weight:700; letter-spacing:1px;" title="Admin Panel">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="8" r="4"/>
                    <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
                </svg>
                ADMIN
            </a>

        <?php else: ?>
            <!-- ===== Kondisi 3: Belum login sama sekali -> tampilkan tombol Login ===== -->
            <a href="masuk/login.php" class="btn-login">Login</a>
        <?php endif; ?>
    </nav>
</header>

<!-- ================= BANNER ================= -->
<section class="banner banner-category">
    <div class="banner-text">
        <p>STARWAVE</p>
        <h2>Choose Everything You Like</h2>
        <p>Various kinds of interesting clothes</p>
    </div>
    <div class="banner-img">
        <img src="asset/posterAc.jpg">
    </div>
</section>

<!-- ================= DAFTAR PRODUK ================= -->
<section class="products">
    <h2>ACCESSORIES COLLECTION</h2>
    <div class="product-box">
        <?php foreach ($products as $row): ?>
            <!-- Loop: cetak satu kartu produk untuk setiap baris data dari database -->
            <div class="product">
                <a href="detail.php?id=<?= htmlspecialchars($row['id']); ?>">
                    <img src="<?= htmlspecialchars($row['gambar']); ?>">
                </a>
                <p><?= htmlspecialchars($row['nama_produk']); ?></p>
                <p>Rp. <?= number_format($row['harga']); ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- ================= FOOTER ================= -->
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

<script src="order.js"></script>
</body>
</html>