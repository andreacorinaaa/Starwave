<?php
include('config/koneksi.php');
                      // variabel koneksi database 
$query = mysqli_query($conn, "SELECT * FROM produk WHERE kategori='accessories'"); // jalanin perintah sql ke database 
?>

<!DOCTYPE html>
<html>
<head>
    <title>STARWAVE -Accessories</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="order.css">
</head>

<body class="page-accessories">

<!-- NAVBAR -->
<header>
    <nav>
        <h1>STARWAVE</h1>

        <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="man.php">Man</a></li>
            <li><a href="woman.php">Woman</a></li>
            <li><a href="accessories.php" class="active">Accessories</a></li>
            <li><a href="order.php">Order</a></li>
            <li><a href="masuk/login.php">User</a></li>
        </ul>

        <form action="search.php" method="GET" style="display:inline;">
            <input type="text" name="q" placeholder="Search produk..." style="padding:5px;">
        </form>
    </nav>
</header>

<section class="banner">
    <div class="banner-text">
        <p>STARWAVE</p>
        <h2>Choose Everything You Like</h2>
        <p>Various kinds of interesting clothes</p>
    </div>

    <div class="banner-img">
        <img src="asset/posterAc.jpg">
    </div>
</section>

<!-- PRODUCTS -->
<section class="products">

    <h2>ACCESSORIES COLLECTION</h2>

    <div class="product-box">
                   <!-- mengambil satu baris data dari query -->
        <?php while($row = mysqli_fetch_assoc($query)) { ?> <!-- looping dari database -->

            <div class="product">
                <a href="detail.php?id=<?= $row['id']; ?>">
                    <img src="<?= $row['gambar']; ?>">
                </a>

                <p><?= $row['nama_produk']; ?></p>
                <p>Rp. <?= number_format($row['harga']); ?></p>
            </div>

        <?php } ?>

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