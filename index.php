<?php

session_start();
include 'config/koneksi.php';

// --- 1. NEW ARRIVALS ---
$stmt = $pdo->query("SELECT * FROM produk ORDER BY created_at DESC LIMIT 3");
$new_arrivals = $stmt->fetchAll(PDO::FETCH_ASSOC);
// trends collection dan left join buat gabungin tabel produk dan orders
$stmt = $pdo->query("
    SELECT p.*, COUNT(o.id) AS total_terjual
    FROM produk p
    LEFT JOIN orders o ON o.id_produk = p.id
    GROUP BY p.id
    ORDER BY total_terjual DESC
    LIMIT 4
");
$trend_collection = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>STARWAVE</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="order.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

</head>
<body>

<header>
    <nav>
        <h1><a href="index.php">STARWAVE</a></h1>
        <ul>
            <li><a href="index.php" class="active">Home</a></li>
            <li><a href="man.php">Man</a></li>
            <li><a href="woman.php">Woman</a></li>
            <li><a href="accessories.php">Accessories</a></li>
            <li><a href="order.php">Order</a></li>
            <li><a href="keranjang.php">Keranjang</a></li>
        </ul>
        <form action="search.php" method="GET" class="search-form" onsubmit="return validateSearch(this)">
            <input type="text" name="q" placeholder="Search produk..." class="search-input">
            <button type="submit" class="search-btn">
                <i class="fa fa-search"></i>
            </button>
        </form>

        <?php if (isset($_SESSION['user'])): ?>
            <!-- User biasa yang sudah login -->
            <a href="profile.php" style="margin-left:15px; text-decoration:none; display:flex; align-items:center;" title="Profile">
                <?php
                    // ambil foto profil user yang lagi login
                    $stmt2 = $pdo->prepare("SELECT foto_profil FROM users WHERE email = ?");
                    $stmt2->execute([$_SESSION['user']]);
                    $navUser = $stmt2->fetch(PDO::FETCH_ASSOC);
                ?>
                <?php if (!empty($navUser['foto_profil']) && file_exists($navUser['foto_profil'])): ?>
                    <img src="<?= htmlspecialchars($navUser['foto_profil']) ?>"
                         style="width:34px; height:34px; border-radius:50%; object-fit:cover; border:2px solid #2a7fa8;">
                <?php else: ?>
                    <!-- kalau belum punya foto profil, pakai icon default -->
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#c9dde8" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="8" r="4"/>
                        <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
                    </svg>
                <?php endif; ?>
            </a>
        <?php elseif (isset($_SESSION['admin'])): ?>
            <!-- Admin yang sedang login -->
            <a href="admin/dashboard.php" style="margin-left:15px; text-decoration:none; color:#4f6ef7; display:flex; align-items:center; gap:5px; font-size:12px; font-weight:700; letter-spacing:1px;" title="Admin Panel">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="8" r="4"/>
                    <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
                </svg>
                ADMIN
            </a>
        <?php else: ?>
            <!-- Belum login sama sekali -->
            <a href="masuk/login.php" class="btn-login">Login</a>
        <?php endif; ?>
    </nav>
</header>

<section class="banner banner-index">
    <div class="banner-text">
        <p>STARWAVE</p>
        <h2>Choose Everything You Like</h2>
        <p>Various kinds of interesting clothes</p>
    </div>
    <div class="banner-img">
        <img src="asset/posterutama (1).png">
    </div>
</section>

<section class="arrival">
    <h2>NEW ARRIVALS</h2>
    <div class="arrival-box">
        <?php foreach ($new_arrivals as $row): ?>
            <a href="detail.php?id=<?= $row['id'] ?>" class="arrival-item">
                <img src="<?= htmlspecialchars($row['gambar']) ?>" alt="<?= htmlspecialchars($row['nama_produk']) ?>">
                <p><?= htmlspecialchars($row['nama_produk']) ?></p>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<section class="trend">
    <h2>TREND COLLECTION</h2>
    <div class="product-box">
        <?php foreach ($trend_collection as $row): ?>
            <a href="detail.php?id=<?= $row['id'] ?>" class="product">
                <img src="<?= htmlspecialchars($row['gambar']) ?>" alt="<?= htmlspecialchars($row['nama_produk']) ?>">
                <p><?= htmlspecialchars($row['nama_produk']) ?></p>
            </a>
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
        <div>
            <h3>Social</h3>
            <p><a href="https://instagram.com/starwave" target="_blank">Instagram : starwave.fashion</a></p>
        </div>
    </div>
</footer>

<script src="order.js"></script>
</body>
</html>