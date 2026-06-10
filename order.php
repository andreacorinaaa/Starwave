<?php
session_start();
include('config/koneksi.php');

if (!isset($_SESSION['user'])) {
    $_SESSION['redirect_after_login'] = 'order.php';
    header("Location: masuk/login.php?msg=login_dulu");
    exit;
}

$user_email = $_SESSION['user'];

$user = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM users WHERE email='$user_email'"
));

$user_id = $user['id_user'];

$pesan = "";

if (isset($_GET['batal'])) {
    $id_order = (int)$_GET['batal'];
    $cek = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT * FROM orders
         WHERE id='$id_order'
         AND id_user='$user_id'
         AND status='pending_payment'"
    ));
    if ($cek) {
        mysqli_query($conn, "UPDATE orders SET status='batal' WHERE id='$id_order'");
        $pesan = "success|Pesanan berhasil dibatalkan.";
    } else {
        $pesan = "error|Pesanan tidak bisa dibatalkan.";
    }
}

if (isset($_GET['hapus'])) {
    $id_order = (int)$_GET['hapus'];
    $cek = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT * FROM orders
         WHERE id='$id_order'
         AND id_user='$user_id'"
    ));
    if ($cek && ($cek['status'] == 'selesai' || $cek['status'] == 'batal')) {
        mysqli_query($conn, "DELETE FROM orders WHERE id='$id_order' AND id_user='$user_id'");
        $pesan = "success|Riwayat pesanan berhasil dihapus.";
    } else {
        $pesan = "error|Riwayat tidak bisa dihapus.";
    }
}

$riwayat = mysqli_query($conn,
    "SELECT * FROM orders WHERE id_user='$user_id' ORDER BY created_at DESC"
);

$pesan_type = $pesan_text = "";
if ($pesan) {
    [$pesan_type, $pesan_text] = explode('|', $pesan, 2);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Riwayat — STARWAVE</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="order.css">
</head>

<body style="background: #d8e9f0;">

<!-- NAVBAR -->
<!-- NAVBAR -->
<header>
    <nav>
        <h1>STARWAVE</h1>
        <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="man.php">Man</a></li>
            <li><a href="woman.php">Woman</a></li>
            <li><a href="accessories.php">Accessories</a></li>
            <li><a href="order.php" class="active">Order</a></li>
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

<main class="container">

    <?php if ($pesan_text): ?>
        <div class="alert <?= $pesan_type ?>">
            <?= $pesan_type == 'success' ? '✅' : '❌' ?>
            <?= htmlspecialchars($pesan_text) ?>
        </div>
    <?php endif; ?>

    <h2 class="section-title">Riwayat Pesanan</h2>

    <?php if (mysqli_num_rows($riwayat) == 0): ?>
        <p class="no-order">Belum ada pesanan.</p>

    <?php else: ?>
        <table>
            <tr>
                <th>#</th>
                <th>Produk</th>
                <th>Qty</th>
                <th>Penerima</th>
                <th>Tanggal</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>

            <?php $no = 1; while ($row = mysqli_fetch_assoc($riwayat)): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= htmlspecialchars($row['nama_produk']) ?></td>
                    <td><?= $row['qty'] ?></td>
                    <td><?= htmlspecialchars($row['nama_penerima']) ?></td>
                    <td><?= $row['tanggal_order'] ?></td>
                    <td>
                        <?php if ($row['status'] == 'pending_payment'): ?>
                            <span class="status-badge status-pending">Belum Bayar</span>
                        <?php else: ?>
                            <span class="status-badge status-<?= $row['status'] ?>"><?= ucfirst($row['status']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="td-aksi">
                        <?php if ($row['status'] == 'pending_payment'): ?>
                            <a href="payment.php?id=<?= $row['id'] ?>" class="btn-edit">Belum Bayar</a>
                            <a href="#" class="btn-batal" onclick="showModal('Yakin batalkan pesanan ini?', 'order.php?batal=<?= $row['id'] ?>'); return false;">Batal</a>
                        <?php elseif ($row['status'] == 'selesai' || $row['status'] == 'batal'): ?>
                            <a href="#" class="btn-hapus" onclick="showModal('Hapus riwayat ini? Tidak bisa dikembalikan!', 'order.php?hapus=<?= $row['id'] ?>'); return false;">Hapus</a>
                        <?php else: ?>
                            <span class="no-aksi">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>

        </table>
    <?php endif; ?>

</main>

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

<!-- MODAL -->
<div id="modal">
    <div id="modal-inner">
        <h3>STARWAVE</h3>
        <p id="modal-msg"></p>
        <div id="modal-buttons">
            <a id="modal-confirm" href="#">Ya</a>
            <button id="modal-btn-tidak" onclick="closeModal()">Tidak</button>
        </div>
    </div>
</div>

<script>
function showModal(msg, url) {
    document.getElementById('modal-msg').innerText = msg;
    document.getElementById('modal-confirm').href = url;
    document.getElementById('modal').style.display = 'flex';
}
function closeModal() {
    document.getElementById('modal').style.display = 'none';
}
</script>

</body>
</html>