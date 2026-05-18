<?php
session_start();
include 'config/koneksi.php';

if (!isset($_GET['id'])) {
    header("Location: man.php");
    exit;
}

$id = (int)$_GET['id'];

// ambil produk
$query = mysqli_query($conn, "SELECT * FROM produk WHERE id='$id'");
$item = mysqli_fetch_assoc($query);

if (!$item) {
    die("Produk tidak ditemukan");
}

// proses order
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if (!isset($_SESSION['user'])) {
        $_SESSION['redirect_after_login'] = "detail.php?id=" . $id;
        header("Location: masuk/login.php");
        exit;
    }

    $user_email = $_SESSION['user'];

    $user = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT * FROM users WHERE email='$user_email'"
    ));

    $id_user = $user['id_user'];

    $qty            = (int)$_POST['qty'];
    $ukuran         = $_POST['ukuran'];
    $harga          = $item['harga'];
    $total_harga    = $harga * $qty;
    $nama_produk_order = $item['nama_produk'] . " - Size " . $ukuran;

    // insert order
    $insert = mysqli_query($conn,
        "INSERT INTO orders (id_user, nama_produk, qty, harga, total_harga, nama_penerima, email, tanggal_order, status)
         VALUES ('$id_user', '$nama_produk_order', '$qty', '$harga', '$total_harga', '".$user['nama_panggilan']."', '$user_email', NOW(), 'pending_payment')"
    );

    if (!$insert) {
        die("Gagal insert: " . mysqli_error($conn));
    }

    $id_order = mysqli_insert_id($conn);

    header("Location: payment.php?id=" . $id_order);
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title><?= $item['nama_produk'] ?></title>
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
            <li><a href="woman.php">Woman</a></li>
            <li><a href="accessories.php">Accessories</a></li>
            <li><a href="order.php">Order</a></li>
            <li><a href="masuk/login.php">User</a></li>
        </ul>

        <form action="search.php" method="GET" style="display:inline;">
            <input type="text" name="q" placeholder="Search produk..." style="padding:5px;">
        </form>
    </nav>
</header>

<!-- DETAIL PRODUK -->
<div class="detail-wrapper">
    <div class="detail-container card-detail">

        <!-- GAMBAR -->
        <div class="detail-image">
            <img src="<?= $item['gambar'] ?>">
        </div>

        <!-- INFO -->
        <div class="detail-info">

            <h2><?= $item['nama_produk'] ?></h2>
            <h3>Rp <?= number_format($item['harga'], 0, ',', '.') ?></h3>
            <p><?= $item['deskripsi'] ?></p>

            <form method="POST">

                <label>Ukuran</label>
                <select name="ukuran" required>
                    <option value="S">S</option>
                    <option value="M">M</option>
                    <option value="L">L</option>
                    <option value="XL">XL</option>
                </select>

                <label>Quantity</label>
                <input type="number" name="qty" id="qty" min="1" value="1" required>

                <h3 class="total-harga">
                    Total: Rp <span id="total"><?= number_format($item['harga'], 0, ',', '.') ?></span>
                </h3>

                <button type="submit">Order Now</button>

            </form>

        </div>

    </div>
</div>

<script>
const harga = <?= $item['harga'] ?>;
const qtyInput = document.getElementById('qty');
const totalText = document.getElementById('total');

qtyInput.addEventListener('input', function() {
    let qty = parseInt(this.value);
    if (!qty || qty < 1) qty = 1;
    let total = harga * qty;
    totalText.innerText = total.toLocaleString('id-ID');
});
</script>

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