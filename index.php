<?php
session_start();
include 'config/koneksi.php';

// New Arrivals — 4 produk terbaru
$stmt = $pdo->query("SELECT * FROM produk ORDER BY created_at DESC LIMIT 3");
$new_arrivals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Trend Collection — 4 produk paling banyak dibeli (join orders by nama_produk)
$stmt = $pdo->query("
    SELECT p.*, COUNT(o.id) AS total_terjual
    FROM produk p
    LEFT JOIN orders o ON o.nama_produk = p.nama_produk
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
</head>
<body>

<!-- NAVBAR -->
<header>
    <nav>
        <h1>STARWAVE</h1>
        <ul>
            <li><a href="index.php" class="active">Home</a></li>
            <li><a href="man.php">Man</a></li>
            <li><a href="woman.php">Woman</a></li>
            <li><a href="accessories.php">Accessories</a></li>
            <li><a href="order.php">Order</a></li>
            <li><a href="keranjang.php">Keranjang</a></li>
        </ul>
        <form action="search.php" method="GET" style="display:inline;">
            <input type="text" name="q" placeholder="Search produk..." style="padding:5px;">
        </form>
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
    <a href="masuk/login.php" style="margin-left:15px; text-decoration:none; color:#c9dde8; font-size:14px; font-weight:700;">Login</a>
<?php endif; ?>
    </nav>
</header>

<!-- BANNER -->
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

<!-- NEW ARRIVAL -->
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

<!-- TREND COLLECTION -->
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