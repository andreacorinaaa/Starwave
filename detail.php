<?php
session_start();
include 'config/koneksi.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}
$id = (int)$_GET['id'];

// --- Ambil data produk dari database -----
$stmt = $pdo->prepare("SELECT * FROM produk WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    die("Produk tidak ditemukan");
}

$kategori = strtolower($item['kategori'] ?? '');

$kategori_link = match ($kategori) {
    'man'         => 'man.php',
    'woman'       => 'woman.php',
    'accessories' => 'accessories.php',
    default       => 'index.php'
};
$kategori_label = ucfirst($kategori);

if ($kategori === 'accessories') {
    $stok_accessories = (int)($item['stok'] ?? 0);
    $semua_habis = $stok_accessories <= 0;
} else {
    $semua_habis =
        ($item['stok_s']   <= 0) &&
        ($item['stok_m']   <= 0) &&
        ($item['stok_l']   <= 0) &&
        ($item['stok_xl']  <= 0) &&
        ($item['stok_xxl'] <= 0);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($item['nama_produk']) ?> – STARWAVE</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="order.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

</head>
<body>

<header>
    <nav>
        <h1><a href="index.php">STARWAVE</a></h1>
        <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="man.php" <?= $kategori === 'man' ? 'class="active"' : '' ?>>Man</a></li>
            <li><a href="woman.php" <?= $kategori === 'woman' ? 'class="active"' : '' ?>>Woman</a></li>
            <li><a href="accessories.php" <?= $kategori === 'accessories' ? 'class="active"' : '' ?>>Accessories</a></li>
            <li><a href="order.php">Order</a></li>
            <li><a href="keranjang.php">Keranjang</a></li>
        </ul>
        <!-- mengirim keyword pencarian -->
        <form action="search.php" method="GET" class="search-form" onsubmit="return validateSearch(this)">
            <input type="text" name="q" placeholder="Search produk..." class="search-input">
            <button type="submit" class="search-btn">
                <i class="fa fa-search"></i>
            </button>
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
            <a href="masuk/login.php" class="btn-login">Login</a>
        <?php endif; ?>
    </nav>
</header>

<div class="breadcrumb-bar">
    <h1>Detail Produk</h1>
    <div class="breadcrumb">
        <a href="index.php">Home</a><span>/</span>
        <a href="<?= $kategori_link ?>">Shop</a><span>/</span>
        <a href="<?= $kategori_link ?>"><?= htmlspecialchars($kategori_label) ?></a><span>/</span>
        <span style="color:#2b1a0e"><?= htmlspecialchars($item['nama_produk']) ?></span>
    </div>
</div>

<section class="dtl-section">
    <div class="dtl-grid">

        <div class="dtl-gallery">
            <div class="dtl-gallery-main">
                <img id="mainImg"
                     src="<?= htmlspecialchars($item['gambar']) ?>"
                     alt="<?= htmlspecialchars($item['nama_produk']) ?>">
            </div>
        </div>

        <!-- Info produk + form order -->
        <div class="dtl-product-info">

            <h1 class="dtl-product-title"><?= htmlspecialchars($item['nama_produk']) ?></h1>

            <?php
            $stmtRating = $pdo->prepare("SELECT COUNT(*) as total, AVG(bintang) as avg_bintang FROM ulasan WHERE id_produk = ?");
            $stmtRating->execute([$id]);
            $ratingData  = $stmtRating->fetch(PDO::FETCH_ASSOC);
            $totalReal   = (int)$ratingData['total'];
            $avgReal     = $totalReal > 0 ? round((float)$ratingData['avg_bintang'], 1) : 0;
            $avgPct      = $totalReal > 0 ? ($avgReal / 5) * 100 : 0;
            ?>

            <div class="dtl-rating-row">
                <?php if ($totalReal > 0): ?>
                    <div class="dtl-stars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <?php if ($i <= floor($avgReal)): ?>
                                <!-- Bintang penuh -->
                                <span class="dtl-star" style="color:#f0b96b;">★</span>
                            <?php elseif ($i == ceil($avgReal) && $avgReal != floor($avgReal)): ?>
                                <!-- Bintang sebagian (misal 4.8 → 80% terisi) -->
                                <?php $sisa = ($avgReal - floor($avgReal)) * 100; ?>
                                <span class="dtl-star" style="color:#ddd; position:relative;">
                                    <span style="position:absolute;left:0;overflow:hidden;width:<?= $sisa ?>%;color:#f0b96b;">★</span>★
                                </span>
                            <?php else: ?>
                                <!-- Bintang kosong -->
                                <span class="dtl-star" style="color:#ddd;">★</span>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </div>
                    <span class="dtl-rating-num"><?= $avgReal ?></span>
                    <span class="dtl-rating-count">(<?= $totalReal ?> Ulasan)</span>
                <?php else: ?>
                    <div class="dtl-stars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="dtl-star" style="color:#ddd;">★</span>
                        <?php endfor; ?>
                    </div>
                    <span class="dtl-rating-num" style="color:#aaa;">-</span>
                    <span class="dtl-rating-count">(Belum ada ulasan)</span>
                <?php endif; ?>
            </div>
            <!-- nampilkan Harga -->
            <div class="dtl-price-row">
                <span class="dtl-price-now">Rp <?= number_format($item['harga'], 0, ',', '.') ?></span>
            </div>
            <!-- nampilin deskripsi -->
            <p class="dtl-product-desc"><?= $item['deskripsi'] ?></p>

            <?php if (isset($_SESSION['error_stok'])): ?>
            <!-- pesan error stok dikirim dari proses_order.php -->
            <div style="background:#fdecea;border-left:4px solid #e05555;padding:14px 18px;font-size:14px;color:#c0392b;font-weight:600;margin-bottom:10px;">
                ⚠️ <?= htmlspecialchars($_SESSION['error_stok']) ?>
            </div>
            <?php unset($_SESSION['error_stok']); ?>
            <?php endif; ?>

            <?php if ($semua_habis): ?>
            <div style="background:#fdecea;border-left:4px solid #e05555;padding:14px 18px;font-size:14px;color:#c0392b;font-weight:600;">
                ⚠️ Produk ini sedang habis stok. Silakan cek kembali nanti.
            </div>
            <?php endif; ?>


            <form method="POST" action="proses_beli.php" id="dtl-orderForm"
                  data-harga="<?= (int)$item['harga'] ?>"
                  data-stok-max="<?= $kategori === 'accessories' ? $stok_accessories : '' ?>">
                <input type="hidden" name="id" value="<?= $id ?>">

                <?php if ($kategori !== 'accessories'): ?>
                <div>
                    <div class="dtl-size-buttons" style="margin-top:10px;">
                        <?php foreach (['S', 'M', 'L', 'XL', 'XXL'] as $sz):
                            $col    = 'stok_' . strtolower($sz);
                            $jumlah = (int)($item[$col] ?? 0);
                            $habis  = $jumlah <= 0;
                        ?>
                        <div style="display:flex;flex-direction:column;align-items:center;gap:4px;">
                            <button type="button"
                                class="dtl-size-btn <?= $habis ? 'habis' : '' ?>"
                                data-size="<?= $sz ?>"
                                data-stok="<?= $jumlah ?>"
                                <?= $habis ? 'disabled' : 'onclick="selectSize(this)"' ?>
                                title="<?= $habis ? 'Stok habis' : 'Sisa ' . $jumlah . ' pcs' ?>">
                                <?= $sz ?>
                            </button>
                            <span style="font-size:10px;color:<?= $habis ? '#e05555' : '#c0773a' ?>;">
                                <?= $habis ? 'Habis' : ($jumlah <= 2 ? 'Sisa ' . $jumlah : '') ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" name="ukuran" id="ukuranInput" value="">
                </div>
                <?php else: ?>
                    <!-- ====== ACCESSORIES: tampilkan info stok total (tanpa pilihan ukuran) ====== -->
                    <input type="hidden" name="ukuran" id="ukuranInput" value="-">
                    <div style="margin-top:10px;font-size:12px;font-weight:600;
                                color:<?= $stok_accessories > 0 ? '#c0773a' : '#e05555' ?>;">
                        <?= $stok_accessories > 0
                            ? 'Stok tersedia: ' . $stok_accessories . ' pcs'
                            : 'Stok habis' ?>
                    </div>
                <?php endif; ?>

                <div class="dtl-total-row">
                    <span class="dtl-total-label">Total Harga</span>
                    <span class="dtl-total-price" id="totalHarga">
                        Rp <?= number_format($item['harga'], 0, ',', '.') ?>
                    </span>
                </div>

                <div class="dtl-order-row">
                    <div class="dtl-qty-control">
                        <button type="button" class="dtl-qty-btn" onclick="changeQty(-1)">−</button>
                        <input class="dtl-qty-input" type="number" name="qty" id="qty" min="1" value="1" readonly>
                        <button type="button" class="dtl-qty-btn" onclick="changeQty(1)">+</button>
                    </div>
                    <button type="submit" name="aksi" value="keranjang" class="dtl-btn-cart"
                        <?= $semua_habis ? 'disabled style="opacity:.5;cursor:not-allowed;"' : '' ?>>
                        Keranjang
                    </button>
                    <button type="submit" name="aksi" value="beli" class="dtl-btn-buy"
                        <?= $semua_habis ? 'disabled style="opacity:.5;cursor:not-allowed;"' : '' ?>>
                        Beli
                    </button>
                </div>
            </form>
        </div>

    </div>

    <section>
        <?php
        $stmt = $pdo->prepare("SELECT u.*, us.nama_panggilan, us.foto_profil
                               FROM ulasan u
                               LEFT JOIN users us ON u.id_user = us.id_user
                               WHERE u.id_produk = ?
                               ORDER BY u.created_at DESC");
        $stmt->execute([$id]);
        $allUlasan   = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $totalUlasan = count($allUlasan);

        $avgRating = 0;
        $dist = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
        if ($totalUlasan > 0) {
            foreach ($allUlasan as $u) {
                $dist[(int)$u['bintang']]++;
            }
            $avgRating = round(array_sum(array_column($allUlasan, 'bintang')) / $totalUlasan, 1);
        }
        ?>

        <div class="dtl-review-overview">
            <div class="dtl-rating-big">
                <div class="num"><?= $totalUlasan > 0 ? $avgRating : '-' ?> / 5</div>
                <div class="stars-big">★★★★★</div>
                <div class="total-reviews">(<?= $totalUlasan ?> Ulasan)</div>
            </div>
            <div class="dtl-rating-bars">
                <?php for ($s = 5; $s >= 1; $s--):
                    $pct = $totalUlasan > 0 ? round($dist[$s] / $totalUlasan * 100) : 0;
                ?>
                <div class="dtl-bar-row">
                    <span class="dtl-bar-label"><?= $s ?></span>
                    <div class="dtl-bar-track">
                        <div class="dtl-bar-fill" style="width:<?= $pct ?>%"></div>
                    </div>
                    <span class="dtl-bar-count"><?= $dist[$s] ?></span>
                </div>
                <?php endfor; ?>
            </div>
        </div>

        <?php if ($totalUlasan === 0): ?>
            <p style="text-align:center; color:#888; padding:30px 0;">
                Belum ada ulasan untuk produk ini.
            </p>
        <?php else: ?>
            <!-- nampilkan Semua Review -->
            <?php foreach ($allUlasan as $u):
                $nama    = htmlspecialchars($u['nama_panggilan'] ?? 'User');
                $initial = strtoupper(substr($nama, 0, 1));
                $bintang = (int)$u['bintang'];
                $tgl     = date('d M Y', strtotime($u['created_at']));
            ?>
            <div class="dtl-review-card">
                <div class="dtl-review-card-header">
                    <div class="dtl-reviewer">
                        <div class="dtl-reviewer-avatar" style="overflow:hidden;">
                            <!-- nampilkan Avatar User -->
                            <?php if (!empty($u['foto_profil']) && file_exists($u['foto_profil'])): ?>
                                <img src="<?= htmlspecialchars($u['foto_profil']) ?>"
                                     style="width:100%; height:100%; object-fit:cover; border-radius:50%;">
                            <?php else: ?>
                                <?= $initial ?>
                            <?php endif; ?>
                        </div>
                        <div>
                            <div class="dtl-reviewer-name"><?= $nama ?></div>
                            <div class="dtl-reviewer-badge">✔ Terverifikasi</div>
                        </div>
                    </div>
                    <div class="dtl-review-date"><?= $tgl ?></div>
                </div>
                <div class="dtl-review-stars">
                    <!-- nampilkan Bintang Review -->
                    <?= str_repeat('★', $bintang) ?>
                    <?= str_repeat('<span style="color:#ddd">★</span>', 5 - $bintang) ?>
                    <span style="font-size:13px; color:#555; font-weight:700;">
                        <?= number_format($bintang, 1) ?>
                    </span>
                </div>
                <div class="dtl-review-body" style="margin-top:8px;">
                    <!-- Menampilkan Komentar -->
                    <?= htmlspecialchars($u['komentar']) ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

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

<script src="pengguna.js"></script>
<script src="order.js"></script>

</body>
</html>