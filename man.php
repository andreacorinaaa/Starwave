<?php
session_start();

include 'config/koneksi.php';
$stmt = $pdo->prepare("SELECT * FROM produk WHERE kategori = ?");

$stmt->execute(['man']);

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>STARWAVE - Man</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="order.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

</head>

<body class="page-man">

<header>
    <nav>
        <!-- Logo / nama brand → klik kembali ke halaman utama -->
        <h1><a href="index.php">STARWAVE</a></h1>

        <!-- Menu navigasi utama -->
        <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="man.php" class="active">Man</a></li>  
            <li><a href="woman.php">Woman</a></li>
            <li><a href="accessories.php">Accessories</a></li>
            <li><a href="order.php">Order</a></li>
            <li><a href="keranjang.php">Keranjang</a></li>
        </ul>

        <!-- Form pencarian produk → dikirim ke search.php pakai method GET (?q=...) -->
        <form action="search.php" method="GET" class="search-form" onsubmit="return validateSearch(this)">
            <input type="text" name="q" placeholder="Search produk..." class="search-input">
            <button type="submit" class="search-btn">
                <i class="fa fa-search"></i>
            </button>
        </form>

        <?php if (isset($_SESSION['user'])): ?>
            <!-- KONDISI 1: User biasa yang sudah login → tampilkan foto profil / ikon user -->
            <a href="profile.php" style="margin-left:15px; text-decoration:none; display:flex; align-items:center;" title="Profile">

                <?php
                    // Ambil foto profil user dari database berdasarkan email yang tersimpan di session
                    $stmt2 = $pdo->prepare("SELECT foto_profil FROM users WHERE email = ?");
                    $stmt2->execute([$_SESSION['user']]);
                    $navUser = $stmt2->fetch(PDO::FETCH_ASSOC);
                ?>

                <?php if (!empty($navUser['foto_profil']) && file_exists($navUser['foto_profil'])): ?>
                    <!-- Jika foto profil ada di database DAN file-nya benar-benar ada di server → tampilkan fotonya -->
                    <img src="<?= htmlspecialchars($navUser['foto_profil']) ?>"
                         style="width:34px; height:34px; border-radius:50%; object-fit:cover; border:2px solid #2a7fa8;">

                <?php else: ?>
                    <!-- Jika foto profil tidak ada → tampilkan ikon SVG default -->
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                         fill="none" stroke="#c9dde8" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="8" r="4"/>           <!-- kepala -->
                        <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/> <!-- bahu/badan -->
                    </svg>
                <?php endif; ?>

            </a>

        <?php elseif (isset($_SESSION['admin'])): ?>
            <!-- KONDISI 2: Admin yang login → tampilkan link ke dashboard admin -->
            <a href="admin/dashboard.php"
               style="margin-left:15px; text-decoration:none; color:#4f6ef7; display:flex; align-items:center; gap:5px; font-size:12px; font-weight:700; letter-spacing:1px;"
               title="Admin Panel">
                <!-- Ikon SVG untuk admin (sama bentuknya, beda warna) -->
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="8" r="4"/>
                    <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
                </svg>
                ADMIN
            </a>

        <?php else: ?>
            <!-- KONDISI 3: Belum login sama sekali → tampilkan tombol Login -->
            <a href="masuk/login.php" class="btn-login">Login</a>

        <?php endif; ?>

    </nav>
</header>

<section class="banner banner-category">

    <!-- Teks di sisi kiri banner -->
    <div class="banner-text">
        <p>STARWAVE</p>
        <h2>Choose Everything You Like</h2>
        <p>Various kinds of interesting clothes</p>
    </div>

    <!-- Gambar di sisi kanan banner -->
    <div class="banner-img">
        <img src="asset/posterPria.jpg">
    </div>

</section>

<section class="products">

    <h2>MAN COLLECTION</h2>

    <div class="product-box">

        <!-- Loop: tampilkan setiap produk dari array $products -->
        <?php foreach ($products as $row): ?>

            <div class="product">

                <!-- Klik gambar → pergi ke halaman detail produk -->
                <a href="detail.php?id=<?= $row['id']; ?>">

                    <!-- htmlspecialchars() → keamanan: ubah karakter berbahaya (<, >, ", &) jadi aman ditampilkan di HTML -->
                    <img src="<?= htmlspecialchars($row['gambar']); ?>">
                </a>

                <!-- Nama produk -->
                <p><?= htmlspecialchars($row['nama_produk']); ?></p>

                <!-- Harga produk -->
                <p>Rp. <?= number_format($row['harga']); ?></p>

            </div>

        <?php endforeach; ?>

    </div>

</section>

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

        <!-- Kolom 3: Media sosial -->
        <div>
            <h3>Social</h3>
            <p><a href="https://instagram.com/starwave" target="_blank">Instagram : starwave.fashion</a></p>
        </div>

    </div>
</footer>

<script src="order.js"></script>
</body>
</html>