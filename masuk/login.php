<?php
session_start();
include('../config/koneksi.php');

$error   = "";
$success = "";

if (isset($_SESSION['admin'])) {
    header("Location: ../admin/dashboard.php");
    exit;
}

$admin_user = "admin@gmail.com";
$admin_pass = "starwave2024";

// ── Proses Login ────────────────────────────────────────────────────────────
if (isset($_POST['login'])) {
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);

    // 1. Cek admin dulu
    if ($email === $admin_user && $password === $admin_pass) {
        $_SESSION['admin'] = $email;
        header("Location: ../admin/dashboard.php");
        exit;
    }

    // 2. Cek user di database (prepared statement — aman dari SQL Injection)
    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        if ($password === $row['password']) {
            $_SESSION['user'] = $email;

            // FIX 2: Kalau ada redirect tersimpan, ke sana. Kalau tidak, ke index.php
            if (isset($_SESSION['redirect_after_login'])) {
                $redirect = $_SESSION['redirect_after_login'];
                unset($_SESSION['redirect_after_login']);
                header("Location: ../" . $redirect);
            } else {
                header("Location: ../index.php"); // <-- FIX: bukan login.php
            }
            exit;
        }
    }

    $error = "Email atau password salah!";
}

// ── Ambil data user kalau sudah login ───────────────────────────────────────
$user = null;
if (isset($_SESSION['user'])) {
    $email = $_SESSION['user'];
    $stmt  = mysqli_prepare($conn, "SELECT * FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user   = mysqli_fetch_assoc($result);
}

// ── Update Profile ──────────────────────────────────────────────────────────
if (isset($_POST['update_profile']) && isset($_SESSION['user'])) {
    $email          = $_SESSION['user'];
    $nama_panggilan = trim($_POST['nama_panggilan']);
    $no_telepon     = trim($_POST['no_telepon']);
    $alamat         = trim($_POST['alamat']);
    $tanggal_lahir  = $_POST['tanggal_lahir'];
    $jenis_kelamin  = $_POST['jenis_kelamin'];

    $stmt = mysqli_prepare($conn,
        "UPDATE users SET
            nama_panggilan = ?,
            no_telepon     = ?,
            alamat         = ?,
            tanggal_lahir  = ?,
            jenis_kelamin  = ?
         WHERE email = ?"
    );
    mysqli_stmt_bind_param($stmt, "ssssss",
        $nama_panggilan,
        $no_telepon,
        $alamat,
        $tanggal_lahir,
        $jenis_kelamin,
        $email
    );
    mysqli_stmt_execute($stmt);

    $success = "Profil berhasil diperbarui!";

    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user   = mysqli_fetch_assoc($result);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($_SESSION['user']) ? 'My Account' : 'Login' ?> — STARWAVE</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../order.css">
    <link rel="stylesheet" href="masuk.css">
</head>

<header>
    <nav>
        <h1>STARWAVE</h1>
        <ul>
            <li><a href="../index.php">Home</a></li>
            <li><a href="../man.php">Man</a></li>
            <li><a href="../woman.php">Woman</a></li>
            <li><a href="../accessories.php">Accessories</a></li>
            <li><a href="../order.php">Order</a></li>
            <li><a href="login.php" class="active">User</a></li>
        </ul>
        <form action="search.php" method="GET" style="display:inline;">
            <input type="text" name="q" placeholder="Search produk..." style="padding:5px;">
        </form>
    </nav>
</header>

<body>

<div class="login-page">
    <div class="login-container <?= isset($_SESSION['user']) ? 'account-layout' : '' ?>">

        <?php if (isset($_SESSION['user']) && $user): ?>

            <!-- ── MODE: SUDAH LOGIN (tampil profil) ── -->
            <div class="account-left">
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
                            <option value="Laki-laki"  <?= ($user['jenis_kelamin'] ?? '') === 'Laki-laki'  ? 'selected' : '' ?>>Laki-laki</option>
                            <option value="Perempuan"  <?= ($user['jenis_kelamin'] ?? '') === 'Perempuan'  ? 'selected' : '' ?>>Perempuan</option>
                        </select>
                    </div>
                    <button type="submit" name="update_profile">Simpan Perubahan</button>
                </form>

                <div class="logout">
                    <a href="logout.php">LOGOUT</a>
                </div>
            </div>

            <div class="account-right">
                <img src="../asset/model login.jpg" alt="Fashion Model">
            </div>

        <?php else: ?>

            <!-- ── MODE: BELUM LOGIN (tampil form) ── -->
            <h2>Login</h2>
            <p>Selamat datang kembali</p>

            <?php if ($error): ?>
                <div class="auth-alert error">⚠ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="form-group">
                    <input type="email" name="email" placeholder="Email" required autocomplete="email">
                </div>
                <div class="form-group">
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <button type="submit" name="login">MASUK</button>
            </form>

            <div class="create">
                Belum punya akun? <a href="register.php">BUAT AKUN</a>
            </div>

        <?php endif; ?>

    </div>
</div>

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