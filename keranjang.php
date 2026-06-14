<?php
session_start();
include 'config/koneksi.php';

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

// Hapus item
if (isset($_GET['hapus'])) {
    $hapus_id = (int)$_GET['hapus'];
    $stmt = $pdo->prepare("DELETE FROM keranjang WHERE id = ? AND id_user = ?");
    $stmt->execute([$hapus_id, $id_user]);
    header("Location: keranjang.php");
    exit;
}

// Update qty saja
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_qty'])) {
    if (!empty($_POST['qty']) && is_array($_POST['qty'])) {
        $stmt = $pdo->prepare("UPDATE keranjang SET qty = ? WHERE id = ? AND id_user = ?");
        foreach ($_POST['qty'] as $kid => $qval) {
            $stmt->execute([max(1, (int)$qval), (int)$kid, $id_user]);
        }
    }
    header("Location: keranjang.php");
    exit;
}

// Checkout semua item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['beli_semua'])) {
    if (!empty($_POST['qty']) && is_array($_POST['qty'])) {
        $stmt = $pdo->prepare("UPDATE keranjang SET qty = ? WHERE id = ? AND id_user = ?");
        foreach ($_POST['qty'] as $kid => $qval) {
            $stmt->execute([max(1, (int)$qval), (int)$kid, $id_user]);
        }
    }

    $stmt = $pdo->prepare("SELECT * FROM keranjang WHERE id_user = ?");
    $stmt->execute([$id_user]);
    $all_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($all_items)) {
        $last_order_id = null;

        $insert_stmt = $pdo->prepare("INSERT INTO orders (id_user, nama_produk, qty, harga, total_harga, nama_penerima, email, tanggal_order, status)
                                      VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 'pending_payment')");
        $delete_stmt = $pdo->prepare("DELETE FROM keranjang WHERE id = ? AND id_user = ?");

        foreach ($all_items as $kitem) {
            $nama_order  = $kitem['nama_produk'] . " - Size " . $kitem['ukuran'];
            $total_harga = (float)$kitem['harga'] * (int)$kitem['qty'];

            $insert_stmt->execute([
                $id_user,
                $nama_order,
                $kitem['qty'],
                $kitem['harga'],
                $total_harga,
                $user['nama_panggilan'] ?? '',
                $user_email
            ]);

            $last_order_id = $pdo->lastInsertId();
            $delete_stmt->execute([$kitem['id'], $id_user]);
        }

        if ($last_order_id) {
            header("Location: payment.php?id=" . $last_order_id);
            exit;
        }
    }

    header("Location: keranjang.php");
    exit;
}

// Beli satu item dari keranjang
if (isset($_GET['beli'])) {
    $kid = (int)$_GET['beli'];

    $stmt = $pdo->prepare("SELECT * FROM keranjang WHERE id = ? AND id_user = ?");
    $stmt->execute([$kid, $id_user]);
    $kitem = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($kitem) {
        $nama_order  = $kitem['nama_produk'] . " - Size " . $kitem['ukuran'];
        $total_harga = (float)$kitem['harga'] * (int)$kitem['qty'];

        $stmt = $pdo->prepare("INSERT INTO orders (id_user, nama_produk, qty, harga, total_harga, nama_penerima, email, tanggal_order, status)
                               VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 'pending_payment')");
        $stmt->execute([
            $id_user,
            $nama_order,
            $kitem['qty'],
            $kitem['harga'],
            $total_harga,
            $user['nama_panggilan'] ?? '',
            $user_email
        ]);

        $id_order = $pdo->lastInsertId();

        $stmt = $pdo->prepare("DELETE FROM keranjang WHERE id = ? AND id_user = ?");
        $stmt->execute([$kid, $id_user]);

        header("Location: payment.php?id=" . $id_order);
        exit;
    }

    header("Location: keranjang.php");
    exit;
}

// Ambil semua item keranjang
$stmt = $pdo->prepare("SELECT * FROM keranjang WHERE id_user = ? ORDER BY created_at DESC");
$stmt->execute([$id_user]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_semua = 0;
foreach ($items as $it) $total_semua += $it['harga'] * $it['qty'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang – STARWAVE</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="order.css">
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
            <li><a href="keranjang.php" class="active">Keranjang</a></li>
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

<!-- BREADCRUMB -->
<div class="breadcrumb-bar">
    <h1>Keranjang Belanja</h1>
    <div class="breadcrumb">
        <a href="index.php">Home</a><span>/</span>
        <span style="color:#2b1a0e">Keranjang</span>
    </div>
</div>

<!-- KERANJANG -->
<section class="ord-detail-section">

<?php if (empty($items)): ?>
    <div style="text-align:center; padding:80px 0; color:#888;">
        <div style="font-size:60px; margin-bottom:16px;">🛒</div>
        <h2 style="margin-bottom:8px;">Keranjang kamu kosong</h2>
        <p style="margin-bottom:24px;">Yuk mulai belanja!</p>
        <a href="index.php" class="ord-btn-buy" style="text-decoration:none; padding:12px 32px;">Mulai Belanja</a>
    </div>

<?php else: ?>

    <form method="POST" action="keranjang.php">
        <div style="display:flex; flex-direction:column; align-items:center;">
            <table class="ord-keranjang-table">
                <thead>
                    <tr>
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
                ?>
                    <tr class="ord-keranjang-row">
                        <td>
                            <a href="keranjang.php?hapus=<?= $it['id'] ?>" class="ord-btn-hapus-item" title="Hapus">✕</a>
                        </td>
                        <td>
                            <div class="ord-keranjang-produk">
                                <img src="<?= htmlspecialchars($it['gambar']) ?>" alt="<?= htmlspecialchars($it['nama_produk']) ?>" class="ord-keranjang-img">
                                <div>
                                    <div class="ord-keranjang-nama"><?= htmlspecialchars($it['nama_produk']) ?></div>
                                    <?php if (!empty($it['ukuran']) && $it['ukuran'] !== '-'): ?>
                                        <div class="ord-keranjang-ukuran">Size: <?= htmlspecialchars($it['ukuran']) ?></div>
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
                                <input class="ord-qty-input" type="number" name="qty[<?= $it['id'] ?>]" value="<?= $it['qty'] ?>" min="1" style="width:50px;" readonly>
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
                    <div class="ord-keranjang-total-label">Total Semua</div>
                    <div class="ord-keranjang-total-harga" id="grandTotal">
                        Rp <?= number_format($total_semua, 0, ',', '.') ?>
                    </div>
                </div>
                <button type="submit" name="beli_semua" value="1" class="ord-btn-cart">
                    Checkout
                </button>
            </div>
        </div>
    </form>

<?php endif; ?>

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
function ubahQty(btn, delta) {
    const row   = btn.closest('tr');
    const input = row.querySelector('input[type=number]');
    const sub   = row.querySelector('.ord-keranjang-subtotal');
    const harga = parseInt(sub.dataset.harga);

    let val = parseInt(input.value) + delta;
    if (val < 1) val = 1;
    input.value = val;

    sub.innerText = 'Rp ' + (harga * val).toLocaleString('id-ID');

    let total = 0;
    document.querySelectorAll('.ord-keranjang-subtotal').forEach(s => {
        const num = parseInt(s.innerText.replace(/[^0-9]/g, ''));
        total += num;
    });
    document.getElementById('grandTotal').innerText = 'Rp ' + total.toLocaleString('id-ID');
}
</script>

</body>
</html>