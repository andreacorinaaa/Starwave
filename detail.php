<?php
session_start();
include 'config/koneksi.php';

if (!isset($_GET['id'])) {
    header("Location: man.php");
    exit;
}

$id = (int)$_GET['id'];

$query = mysqli_query($conn, "SELECT * FROM produk WHERE id='$id'");
$item = mysqli_fetch_assoc($query);

if (!$item) {
    die("Produk tidak ditemukan");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_SESSION['user'])) {
        $_SESSION['redirect_after_login'] = "detail.php?id=" . $id;
        header("Location: masuk/login.php");
        exit;
    }

    $user_email = $_SESSION['user'];
    $user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE email='$user_email'"));
    $id_user = $user['id_user'];

    $qty            = (int)$_POST['qty'];
    $ukuran         = $_POST['ukuran'];
    $harga          = $item['harga'];
    $total_harga    = $harga * $qty;
    $nama_produk_order = $item['nama_produk'] . " - Size " . $ukuran;

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
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($item['nama_produk']) ?> – STARWAVE</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="detail.css">
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

<!-- BREADCRUMB -->
<div class="breadcrumb-bar">
    <h1>Detail Produk</h1>
    <div class="breadcrumb">
        <a href="index.php">Home</a><span>/</span>
        <a href="woman.php">Shop</a><span>/</span>
        <a href="woman.php"><?= htmlspecialchars($item['kategori'] ?? 'Produk') ?></a><span>/</span>
        <span style="color:#2b1a0e"><?= htmlspecialchars($item['nama_produk']) ?></span>
    </div>
</div>

<!-- DETAIL PRODUK -->
<section class="detail-section">
    <div class="detail-grid">

        <!-- GALERI GAMBAR -->
        <div class="gallery">
            <div class="gallery-main">
                <img id="mainImg" src="<?= htmlspecialchars($item['gambar']) ?>" alt="<?= htmlspecialchars($item['nama_produk']) ?>">
            </div>
        </div>

        <!-- INFO PRODUK -->
        <div class="product-info">

            <h1 class="product-title"><?= htmlspecialchars($item['nama_produk']) ?></h1>

            <!-- Rating -->
            <div class="rating-row">
                <div class="stars">
                    <span class="star">★</span>
                    <span class="star">★</span>
                    <span class="star">★</span>
                    <span class="star">★</span>
                    <span class="star" style="color:#ddd; position:relative;">
                        <span style="position:absolute;left:0;overflow:hidden;width:80%;color:#f0b96b;">★</span>★
                    </span>
                </div>
                <span class="rating-num">4.8</span>
                <span class="rating-count">(245 Ulasan)</span>
            </div>

            <!-- Harga -->
            <div class="price-row">
                <span class="price-now">Rp <?= number_format($item['harga'], 0, ',', '.') ?></span>
                <?php
                    $hargaCoret = $item['harga'] * 2;
                ?>
            </div>

            <p class="product-desc"><?= $item['deskripsi'] ?></p>

            <form method="POST" id="orderForm">

                <!-- Ukuran -->
                <?php if (strtolower($item['kategori']) !== 'accessories'): ?>
                <div>
                    <div class="size-buttons" style="margin-top:10px;">
                        <button type="button" class="size-btn" data-size="S" onclick="selectSize(this)">S</button>
                        <button type="button" class="size-btn active" data-size="M" onclick="selectSize(this)">M</button>
                        <button type="button" class="size-btn" data-size="L" onclick="selectSize(this)">L</button>
                        <button type="button" class="size-btn" data-size="XL" onclick="selectSize(this)">XL</button>
                        <button type="button" class="size-btn" data-size="XXL" onclick="selectSize(this)">XXL</button>
                    </div>
                    <input type="hidden" name="ukuran" id="ukuranInput" value="M">
                </div>
                <?php else: ?>
                    <input type="hidden" name="ukuran" id="ukuranInput" value="-">
                <?php endif; ?>

                <!-- Total harga -->
                <div class="total-row">
                    <span class="total-label">Total Harga</span>
                    <span class="total-price" id="totalHarga">Rp <?= number_format($item['harga'], 0, ',', '.') ?></span>
                </div>

                <!-- Qty + tombol -->
                <div class="order-row">
                    <div class="qty-control">
                        <button type="button" class="qty-btn" onclick="changeQty(-1)">−</button>
                        <input class="qty-input" type="number" name="qty" id="qty" min="1" value="1" readonly>
                        <button type="button" class="qty-btn" onclick="changeQty(1)">+</button>
                    </div>
                    <button type="submit" class="btn-cart">Keranjang</button>
                    <button type="submit" class="btn-buy">Beli</button>
                </div>

            </form>
        </div>
    </div>
</section>

<!-- ULASAN -->
<!-- ULASAN -->
<section class="tabs-section">
<div>

<?php
// Ambil ulasan berdasarkan id_produk
$ulasanQuery = mysqli_query($conn,
    "SELECT u.*, us.nama_panggilan FROM ulasan u
     LEFT JOIN users us ON u.id_user = us.id_user
     WHERE u.id_produk = '$id'
     ORDER BY u.created_at DESC"
);
$allUlasan   = mysqli_fetch_all($ulasanQuery, MYSQLI_ASSOC);
$totalUlasan = count($allUlasan);

$avgRating = 0;
$dist = [5=>0, 4=>0, 3=>0, 2=>0, 1=>0];
if ($totalUlasan > 0) {
    foreach ($allUlasan as $u) $dist[(int)$u['bintang']]++;
    $avgRating = round(array_sum(array_column($allUlasan, 'bintang')) / $totalUlasan, 1);
}
?>

<!-- Overview Rating -->
<div class="review-overview">
    <div class="rating-big">
        <div class="num"><?= $totalUlasan > 0 ? $avgRating : '-' ?></div>
        <div class="out">dari 5</div>
        <div class="stars-big">★★★★★</div>
        <div class="total-reviews">(<?= $totalUlasan ?> Ulasan)</div>
    </div>
    <div class="rating-bars">
        <?php for ($s = 5; $s >= 1; $s--):
            $pct = $totalUlasan > 0 ? round($dist[$s] / $totalUlasan * 100) : 0;
        ?>
        <div class="bar-row">
            <span class="bar-label"><?= $s ?></span>
            <div class="bar-track"><div class="bar-fill" style="width:<?= $pct ?>%"></div></div>
            <span class="bar-count"><?= $dist[$s] ?></span>
        </div>
        <?php endfor; ?>
    </div>
</div>

<!-- Daftar Ulasan -->
<?php if ($totalUlasan === 0): ?>
    <p style="text-align:center;color:#888;padding:30px 0;">Belum ada ulasan untuk produk ini.</p>
<?php else: ?>
    <?php foreach ($allUlasan as $u):
        $nama    = htmlspecialchars($u['nama_panggilan'] ?? 'User');
        $initial = strtoupper(substr($nama, 0, 1));
        $bintang = (int)$u['bintang'];
        $tgl     = date('d M Y', strtotime($u['created_at']));
    ?>
    <div class="review-card">
        <div class="review-card-header">
            <div class="reviewer">
                <div class="reviewer-avatar"><?= $initial ?></div>
                <div>
                    <div class="reviewer-name"><?= $nama ?></div>
                    <div class="reviewer-badge">✔ Terverifikasi</div>
                </div>
            </div>
            <div class="review-date"><?= $tgl ?></div>
        </div>
        <div class="review-stars">
            <?= str_repeat('★', $bintang) ?>
            <?= str_repeat('<span style="color:#ddd">★</span>', 5 - $bintang) ?>
            <span style="font-size:13px;color:#555;font-weight:700;"><?= number_format($bintang, 1) ?></span>
        </div>
        <div class="review-body" style="margin-top:8px;">
            <?= htmlspecialchars($u['komentar']) ?>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

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

<script>
    // ── Harga & qty ──
    const harga = <?= $item['harga'] ?>;

    function changeQty(delta) {
        const input = document.getElementById('qty');
        let val = parseInt(input.value) + delta;
        if (val < 1) val = 1;
        input.value = val;
        updateTotal(val);
    }

    function updateTotal(qty) {
        const total = harga * qty;
        document.getElementById('totalHarga').innerText = 'Rp ' + total.toLocaleString('id-ID');
    }

    // ── Ukuran ──
    function selectSize(el) {
        document.querySelectorAll('.size-btn').forEach(b => b.classList.remove('active'));
        el.classList.add('active');
        const size = el.getAttribute('data-size');
        document.getElementById('selectedSize').innerText = size;
        document.getElementById('ukuranInput').value = size;
    }

    // ── Warna ──
    function selectColor(el) {
        document.querySelectorAll('.swatch').forEach(s => s.classList.remove('active'));
        el.classList.add('active');
        document.getElementById('selectedColor').innerText = el.getAttribute('data-color');
    }

    // ── Gambar galeri ──
    const thumbs = document.querySelectorAll('.gallery-thumbs img');
    let currentImg = 0;

    function setImg(el) {
        thumbs.forEach(t => t.classList.remove('active'));
        el.classList.add('active');
        document.getElementById('mainImg').src = el.src;
        currentImg = Array.from(thumbs).indexOf(el);
    }

    function changeImg(dir) {
        currentImg = (currentImg + dir + thumbs.length) % thumbs.length;
        setImg(thumbs[currentImg]);
    }

    // ── Tabs ──
    function switchTab(name) {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        document.getElementById('tab-' + name).classList.add('active');
        event.target.classList.add('active');
    }
</script>

</body>
</html>