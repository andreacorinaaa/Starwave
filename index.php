<?php
session_start();

include 'config/koneksi.php';
$new_arrival = mysqli_query($conn, "SELECT * FROM produk ORDER BY created_at DESC LIMIT 3");
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
            <a href="profile.php" style="margin-left:15px; text-decoration:none; color:#333;">Profile</a>
        <?php else: ?>
            <a href="masuk/login.php" style="margin-left:15px; text-decoration:none; color:#333;">Login</a>
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

        <?php while($row = mysqli_fetch_assoc($new_arrival)) { ?>

            <div class="arrival-item">
                <img src="<?= $row['gambar'] ?>">
            </div>

        <?php } ?>

    </div>
</section>

<!-- PRODUCTS -->
<section class="trend">

    <h2>TREND COLLECTION</h2>

    <div class="product-box">

        <div class="product">
            <img src="asset\trendcl1.jpg">
            <p>Monologo Tee</p>
        </div>

        <div class="product">
            <img src="asset\trendcl2.jpg">
            <p>Classic Trucker Jacket</p>
        </div>

        <div class="product">
            <img src="asset\trendcl3.1.jpg">
            <p>90s Denim Trucker Jacket</p>
        </div>

        <div class="product">
            <img src="asset\trendcl4.jpg">
            <p>Cotton Crewneck T-Shirt</p>
        </div>

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