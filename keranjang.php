<?php
session_start();
include 'config/koneksi.php';

if (!isset($_SESSION['user'])) {
    $_SESSION['redirect_after_login'] = 'keranjang.php';
    header("Location: masuk/login.php");
    exit;
}

$user_email = $_SESSION['user'];

// Ambil data user — jika tidak ditemukan, paksa logout
$result = mysqli_query($conn, "SELECT * FROM users WHERE email='" . mysqli_real_escape_string($conn, $user_email) . "'");
$user   = mysqli_fetch_assoc($result);

if (!$user) {
    session_destroy();
    header("Location: masuk/login.php");
    exit;
}

$id_user = (int)$user['id_user'];

// Hapus item
if (isset($_GET['hapus'])) {
    $hapus_id = (int)$_GET['hapus'];
    mysqli_query($conn, "DELETE FROM keranjang WHERE id='$hapus_id' AND id_user='$id_user'");
    header("Location: keranjang.php");
    exit;
}

// Update qty
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_qty'])) {
    if (!empty($_POST['qty']) && is_array($_POST['qty'])) {
        foreach ($_POST['qty'] as $kid => $qval) {
            $kid  = (int)$kid;
            $qval = max(1, (int)$qval);
            mysqli_query($conn, "UPDATE keranjang SET qty='$qval' WHERE id='$kid' AND id_user='$id_user'");
        }
    }
    header("Location: keranjang.php");
    exit;
}

// Beli semua item keranjang sekaligus
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['beli_semua'])) {
    // Update qty dulu sebelum checkout
    if (!empty($_POST['qty']) && is_array($_POST['qty'])) {
        foreach ($_POST['qty'] as $kid => $qval) {
            $kid  = (int)$kid;
            $qval = max(1, (int)$qval);
            mysqli_query($conn, "UPDATE keranjang SET qty='$qval' WHERE id='$kid' AND id_user='$id_user'");
        }
    }

    // Ambil semua item keranjang terbaru
    $all_items = mysqli_fetch_all(
        mysqli_query($conn, "SELECT * FROM keranjang WHERE id_user='$id_user'"),
        MYSQLI_ASSOC
    );

    if (!empty($all_items)) {
        $last_order_id = null;

        foreach ($all_items as $kitem) {
            $nama_order    = $kitem['nama_produk'] . " - Size " . $kitem['ukuran'];
            $total_harga   = (float)$kitem['harga'] * (int)$kitem['qty'];
            $nama_produk   = mysqli_real_escape_string($conn, $nama_order);
            $nama_penerima = mysqli_real_escape_string($conn, $user['nama_panggilan'] ?? '');
            $email_user    = mysqli_real_escape_string($conn, $user_email);

            $insert = mysqli_query($conn,
                "INSERT INTO orders (id_user, nama_produk, qty, harga, total_harga, nama_penerima, email, tanggal_order, status)
                 VALUES ('$id_user', '$nama_produk', '{$kitem['qty']}', '{$kitem['harga']}', '$total_harga', '$nama_penerima', '$email_user', NOW(), 'pending_payment')"
            );

            if ($insert) {
                $last_order_id = mysqli_insert_id($conn);
                mysqli_query($conn, "DELETE FROM keranjang WHERE id='{$kitem['id']}' AND id_user='$id_user'");
            }
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
    $kid  = (int)$_GET['beli'];
    $kresult = mysqli_query($conn, "SELECT * FROM keranjang WHERE id='$kid' AND id_user='$id_user'");
    $kitem   = mysqli_fetch_assoc($kresult);

    if ($kitem) {
        $nama_order  = $kitem['nama_produk'] . " - Size " . $kitem['ukuran'];
        $total_harga = (float)$kitem['harga'] * (int)$kitem['qty'];
        $nama_produk = mysqli_real_escape_string($conn, $nama_order);
        $nama_penerima = mysqli_real_escape_string($conn, $user['nama_panggilan'] ?? '');
        $email_user  = mysqli_real_escape_string($conn, $user_email);

        $insert = mysqli_query($conn,
            "INSERT INTO orders (id_user, nama_produk, qty, harga, total_harga, nama_penerima, email, tanggal_order, status)
             VALUES ('$id_user', '$nama_produk', '{$kitem['qty']}', '{$kitem['harga']}', '$total_harga', '$nama_penerima', '$email_user', NOW(), 'pending_payment')"
        );

        if ($insert) {
            $id_order = mysqli_insert_id($conn);
            mysqli_query($conn, "DELETE FROM keranjang WHERE id='$kid' AND id_user='$id_user'");
            header("Location: payment.php?id=" . $id_order);
            exit;
        }
    }
    header("Location: keranjang.php");
    exit;
}

// Ambil semua item keranjang
$items_result = mysqli_query($conn, "SELECT * FROM keranjang WHERE id_user='$id_user' ORDER BY created_at DESC");
$items = $items_result ? mysqli_fetch_all($items_result, MYSQLI_ASSOC) : [];

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
            <li><a href="keranjang.php" class="active">Keranjang</a></li>
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
    <h1>Keranjang Belanja</h1>
    <div class="breadcrumb">
        <a href="index.php">Home</a><span>/</span>
        <span style="color:#2b1a0e">Keranjang</span>
    </div>
</div>

<!-- KERANJANG -->
<section class="detail-section" style="padding: 40px 60px;">

<?php if (empty($items)): ?>
    <div style="text-align:center; padding:80px 0; color:#888;">
        <div style="font-size:60px; margin-bottom:16px;">🛒</div>
        <h2 style="margin-bottom:8px;">Keranjang kamu kosong</h2>
        <p style="margin-bottom:24px;">Yuk mulai belanja!</p>
        <a href="index.php" class="btn-buy" style="text-decoration:none; padding:12px 32px;">Mulai Belanja</a>
    </div>

<?php else: ?>

    <form method="POST" action="keranjang.php">
    <!-- Tabel Keranjang -->
    <table class="keranjang-table">
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
            <tr class="keranjang-row">
                <!-- Hapus -->
                <td>
                    <a href="keranjang.php?hapus=<?= $it['id'] ?>" class="btn-hapus" title="Hapus">✕</a>
                </td>

                <!-- Gambar + Nama -->
                <td>
                    <div class="keranjang-produk">
                        <img src="<?= htmlspecialchars($it['gambar']) ?>" alt="<?= htmlspecialchars($it['nama_produk']) ?>" class="keranjang-img">
                        <div>
                            <div class="keranjang-nama"><?= htmlspecialchars($it['nama_produk']) ?></div>
                            <?php if (!empty($it['ukuran']) && $it['ukuran'] !== '-'): ?>
                                <div class="keranjang-ukuran">Size: <?= htmlspecialchars($it['ukuran']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </td>

                <!-- Harga -->
                <td class="keranjang-harga">
                    Rp <?= number_format($it['harga'], 0, ',', '.') ?>
                </td>

                <!-- Qty -->
                <td>
                    <div class="qty-control" style="justify-content:center;">
                        <button type="button" class="qty-btn" onclick="ubahQty(this, -1)">−</button>
                        <input class="qty-input" type="number" name="qty[<?= $it['id'] ?>]" value="<?= $it['qty'] ?>" min="1" style="width:50px;" readonly>
                        <button type="button" class="qty-btn" onclick="ubahQty(this, 1)">+</button>
                    </div>
                </td>

                <!-- Subtotal -->
                <td class="keranjang-subtotal" data-harga="<?= $it['harga'] ?>">
                    Rp <?= number_format($subtotal, 0, ',', '.') ?>
                </td>


            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Tombol Update & Total -->
    <div class="keranjang-footer">
        <div class="keranjang-total-box">
            <div class="keranjang-total-label">Total Semua</div>
            <div class="keranjang-total-harga" id="grandTotal">
                Rp <?= number_format($total_semua, 0, ',', '.') ?>
            </div>
        </div>
        <button type="submit" name="beli_semua" value="1" class="btn-cart" style="padding:14px 40px; font-size:15px;">
            Checkout
        </button>
    </div>

    </form>

<?php endif; ?>

</section>

<!-- FOOTER -->
<footer>
    <div class="footer-box">
        <div>
            <h3>Store</h3>
            <p>Man</p><p>Woman</p><p>Accessories</p>
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
    const sub   = row.querySelector('.keranjang-subtotal');
    const harga = parseInt(sub.dataset.harga);

    let val = parseInt(input.value) + delta;
    if (val < 1) val = 1;
    input.value = val;

    // Update subtotal baris
    sub.innerText = 'Rp ' + (harga * val).toLocaleString('id-ID');

    // Update grand total
    let total = 0;
    document.querySelectorAll('.keranjang-subtotal').forEach(s => {
        const num = parseInt(s.innerText.replace(/[^0-9]/g, ''));
        total += num;
    });
    document.getElementById('grandTotal').innerText = 'Rp ' + total.toLocaleString('id-ID');
}
</script>

</body>
</html>