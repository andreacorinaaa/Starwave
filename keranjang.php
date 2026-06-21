<?php

session_start();
include 'config/koneksi.php';

// --- Wajib login dulu ---
if (!isset($_SESSION['user'])) {
    $_SESSION['redirect_after_login'] = 'keranjang.php';
    header("Location: masuk/login.php");
    exit;
}

$user_email = $_SESSION['user'];

$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$user_email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header("Location: masuk/login.php");
    exit;
}

$id_user = (int)$user['id_user'];

// --- Ambil semua item di keranjang milik user ini + data stok produk ----
$stmt = $pdo->prepare("
    SELECT k.*,
           p.stok, p.stok_s, p.stok_m, p.stok_l, p.stok_xl, p.stok_xxl, p.kategori AS kategori_produk
    FROM keranjang k
    LEFT JOIN produk p ON k.id_produk = p.id
    WHERE k.id_user = ?
    ORDER BY k.created_at DESC
");
$stmt->execute([$id_user]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_semua = 0;
foreach ($items as &$it) {
    $kategori = strtolower($it['kategori_produk'] ?? '');

    if ($kategori === 'accessories') {
        $it['stok_max'] = isset($it['stok']) ? (int)$it['stok'] : 0;
    } else {
        $kolom = 'stok_' . strtolower($it['ukuran'] ?? '');
        $it['stok_max'] = isset($it[$kolom]) ? (int)$it[$kolom] : 0;
    }

    if ($it['qty'] > $it['stok_max']) {
        $it['qty'] = max(1, $it['stok_max']);
    }

    if ($it['stok_max'] > 0) {
        $total_semua += $it['harga'] * $it['qty'];
    }
}
unset($it);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang – STARWAVE</title>
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
            <li><a href="man.php">Man</a></li>
            <li><a href="woman.php">Woman</a></li>
            <li><a href="accessories.php">Accessories</a></li>
            <li><a href="order.php">Order</a></li>
            <li><a href="keranjang.php" class="active">Keranjang</a></li>
        </ul>
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
    <h1>Keranjang Belanja</h1>
    <div class="breadcrumb">
        <a href="index.php">Home</a><span>/</span>
        <span style="color:#2b1a0e">Keranjang</span>
    </div>
</div>

<section class="ord-detail-section">

<?php if (isset($_SESSION['error_keranjang'])): ?>
    <div style="background:#fdecea;border-left:4px solid #e05555;padding:14px 18px;font-size:14px;color:#c0392b;font-weight:600;margin:0 auto 16px;max-width:900px;">
        ⚠️ <?= htmlspecialchars($_SESSION['error_keranjang']) ?>
    </div>
    <?php unset($_SESSION['error_keranjang']); ?>
<?php endif; ?>

<?php if (empty($items)): ?>

    <!-- Kalau keranjang kosong -->
    <div style="text-align:center; padding:80px 0; color:#888;">
        <div style="font-size:60px; margin-bottom:16px;">🛒</div>
        <h2 style="margin-bottom:8px;">Keranjang kamu kosong</h2>
        <p style="margin-bottom:24px;">Yuk mulai belanja!</p>
        <a href="index.php" class="ord-btn-buy" style="text-decoration:none; padding:12px 32px;">Mulai Belanja</a>
    </div>

<?php else: ?>

    <form method="POST" action="proses_keranjang.php">
        <div style="display:flex; flex-direction:column; align-items:center;">
            <table class="ord-keranjang-table">
                <thead>
                    <tr>
                        <th style="width:36px;">
                            <input type="checkbox" id="checkAllItems" checked onclick="toggleAllItems(this)" title="Pilih semua">
                        </th>
                        <th style="width:50px;"></th>
                        <th>Produk</th>
                        <th>Harga Satuan</th>
                        <th>Qty</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $it):
                    $subtotal = $it['harga'] * $it['qty'];
                    $stok_max = (int)$it['stok_max'];
                    $habis    = $stok_max <= 0;
                ?>
                    <tr class="ord-keranjang-row">
                        <td>
                            <!-- checkbox pilih item ini buat di-checkout -->
                            <input type="checkbox" name="checked[]" value="<?= $it['id'] ?>"
                                   class="item-checkbox" data-harga="<?= $it['harga'] ?>"
                                   <?= $habis ? '' : 'checked' ?> <?= $habis ? 'disabled' : '' ?>
                                   onchange="recalcTotal()">
                        </td>
                        <td>
                            <!-- link hapus item -> diproses di proses_keranjang.php -->
                            <a href="proses_keranjang.php?hapus=<?= $it['id'] ?>" class="ord-btn-hapus-item" title="Hapus">✕</a>
                        </td>
                        <td>
                            <div class="ord-keranjang-produk">
                                <img src="<?= htmlspecialchars($it['gambar']) ?>" alt="<?= htmlspecialchars($it['nama_produk']) ?>" class="ord-keranjang-img">
                                <div>
                                    <div class="ord-keranjang-nama"><?= htmlspecialchars($it['nama_produk']) ?></div>
                                    <?php if (!empty($it['ukuran']) && $it['ukuran'] !== '-'): ?>
                                        <div class="ord-keranjang-ukuran">Size: <?= htmlspecialchars($it['ukuran']) ?></div>
                                    <?php endif; ?>
                                    <?php if ($habis): ?>
                                        <div style="font-size:11px;color:#e05555;font-weight:700;margin-top:2px;">Stok habis</div>
                                    <?php elseif ($stok_max <= 2): ?>
                                        <div style="font-size:11px;color:#c0773a;font-weight:700;margin-top:2px;">Sisa <?= $stok_max ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td class="ord-keranjang-harga">
                            Rp <?= number_format($it['harga'], 0, ',', '.') ?>
                        </td>
                        <td>
                            <div class="ord-qty-control" style="justify-content:center;">
                                <button type="button" class="ord-qty-btn" onclick="ubahQty(this, -1)">−</button>
                                <input class="ord-qty-input" type="number" name="qty[<?= $it['id'] ?>]"
                                       value="<?= $it['qty'] ?>" min="1" max="<?= $stok_max ?>"
                                       data-max="<?= $stok_max ?>" style="width:50px;" readonly>
                                <button type="button" class="ord-qty-btn" onclick="ubahQty(this, 1)">+</button>
                            </div>
                        </td>
                        <td class="ord-keranjang-subtotal" data-harga="<?= $it['harga'] ?>">
                            Rp <?= number_format($subtotal, 0, ',', '.') ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <div class="ord-keranjang-footer">
                <div class="ord-keranjang-total-box">
                    <div class="ord-keranjang-total-label">Total (item dicentang)</div>
                    <div class="ord-keranjang-total-harga" id="grandTotal">
                        Rp <?= number_format($total_semua, 0, ',', '.') ?>
                    </div>
                </div>
                <button type="submit" name="beli_semua" value="1" class="ord-btn-cart" id="btnCheckout">
                    Checkout
                </button>
            </div>
        </div>
    </form>

<?php endif; ?>

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