<?php
include('config/koneksi.php');

$keyword = isset($_GET['q']) ? $_GET['q'] : '';

$query = mysqli_query($conn,
    "SELECT * FROM produk
     WHERE nama_produk LIKE '%$keyword%'"
);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Search - STARWAVE</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="order.css">
</head>

<body class="page-search">

<!-- NAVBAR -->
<header>
    <nav>
        <h1>STARWAVE</h1>
        <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="man.php">Man</a></li>
            <li><a href="woman.php">Woman</a></li>
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

<!-- RESULT -->
<section class="products">
    <h2>Hasil Pencarian: "<?= htmlspecialchars($keyword) ?>"</h2>

    <div class="product-box">

        <?php if (mysqli_num_rows($query) == 0) { ?>
            <p>Produk tidak ditemukan</p>
        <?php } ?>

        <?php while($row = mysqli_fetch_assoc($query)) { ?>

            <div class="product">

                <a href="detail.php?id=<?= $row['id'] ?>">
                    <img src="<?= $row['gambar'] ?>">
                </a>

                <p><?= $row['nama_produk'] ?></p>
                <p>Rp <?= number_format($row['harga']) ?></p>

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