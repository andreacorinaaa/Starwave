<?php
session_start();
include('config/koneksi.php');

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$success = "";
$email = $_SESSION['user'];

$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email = ?");
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if (isset($_POST['update_profile'])) {
    $nama_panggilan = trim($_POST['nama_panggilan']);
    $no_telepon     = trim($_POST['no_telepon']);
    $alamat         = trim($_POST['alamat']);
    $tanggal_lahir  = $_POST['tanggal_lahir'];
    $jenis_kelamin  = $_POST['jenis_kelamin'];

    $stmt = mysqli_prepare($conn,
        "UPDATE users SET nama_panggilan=?, no_telepon=?, alamat=?, tanggal_lahir=?, jenis_kelamin=? WHERE email=?"
    );
    mysqli_stmt_bind_param($stmt, "ssssss",
        $nama_panggilan, $no_telepon, $alamat, $tanggal_lahir, $jenis_kelamin, $email
    );
    mysqli_stmt_execute($stmt);
    $success = "Profil berhasil diperbarui!";

    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>My Account — STARWAVE</title>
    <link rel="stylesheet" href="style.css">
</head>

<header>
    <nav>
        <h1>STARWAVE</h1>
        <ul>
            <li><a href="index.php">Home</a></li>
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
    <a href="profile.php" style="margin-left:15px; text-decoration:none; color:#333; display:flex; align-items:center;" title="Profile">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="8" r="4"/>
            <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
        </svg>
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
    <a href="masuk/login.php" style="margin-left:15px; text-decoration:none; color:#333;">Login</a>
<?php endif; ?>
    </nav>
</header>

<body>
<div class="profile-page">
    <div class="profile-card">
        <h2>My Account</h2>

        <?php if ($success): ?>
            <div class="auth-alert success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label>Email</label>
                <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled>
            </div>
            <div class="form-group">
                <label>Nama Panggilan</label>
                <input type="text" name="nama_panggilan" value="<?= htmlspecialchars($user['nama_panggilan'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Nomor Telepon</label>
                <input type="text" name="no_telepon" value="<?= htmlspecialchars($user['no_telepon'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Alamat</label>
                <textarea name="alamat"><?= htmlspecialchars($user['alamat'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label>Tanggal Lahir</label>
                <input type="date" name="tanggal_lahir" value="<?= htmlspecialchars($user['tanggal_lahir'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Jenis Kelamin</label>
                <select name="jenis_kelamin">
                    <option value="">Pilih</option>
                    <option value="Laki-laki" <?= ($user['jenis_kelamin'] ?? '') === 'Laki-laki' ? 'selected' : '' ?>>Laki-laki</option>
                    <option value="Perempuan" <?= ($user['jenis_kelamin'] ?? '') === 'Perempuan' ? 'selected' : '' ?>>Perempuan</option>
                </select>
            </div>
            <button type="submit" name="update_profile">Simpan Perubahan</button>
        </form>

        <div class="logout">
            <a href="masuk/logout.php">LOGOUT</a>
        </div>
    </div>
</div>

<footer>
    <div class="footer-box">
        <div><h3>Store</h3><p>Man</p><p>Woman</p><p>Accessories</p></div>
        <div><h3>Business</h3><p>starwave@gmail.com</p><p>081836737367367</p></div>
        <div><h3>Social</h3><p>Instagram : starwave.fashion</p></div>
    </div>
</footer>
</body>
</html>