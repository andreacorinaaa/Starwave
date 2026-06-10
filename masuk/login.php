<?php
session_start();
include('../config/koneksi.php');

if (isset($_SESSION['user'])) {
    header("Location: profile.php");
    exit;
}
if (isset($_SESSION['admin'])) {
    header("Location: ../admin/dashboard.php");
    exit;
}

$error = "";
$admin_user = "admin@gmail.com";
$admin_pass = "starwave2024";

if (isset($_POST['login'])) {
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);

    if ($email === $admin_user && $password === $admin_pass) {
        $_SESSION['admin'] = $email;
        header("Location: ../admin/dashboard.php");
        exit;
    }

    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        if ($password === $row['password']) {
            $_SESSION['user'] = $email;
            if (isset($_SESSION['redirect_after_login'])) {
                $redirect = $_SESSION['redirect_after_login'];
                unset($_SESSION['redirect_after_login']);
                header("Location: ../" . $redirect);
            } else {
                header("Location: ../index.php");
            }
            exit;
        }
    }
    $error = "Email atau password salah!";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login — STARWAVE</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="masuk.css">
</head>
<body>
<div class="login-page">
    <div class="login-container">
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
<header>
    <nav>
    <h1>STARWAVE</h1>
    <ul>
        <li><a href="../index.php">Home</a></li>
        <li><a href="../man.php">Man</a></li>
        <li><a href="../woman.php">Woman</a></li>
        <li><a href="../accessories.php">Accessories</a></li>
        <li><a href="../order.php">Order</a></li>
    </ul>
    <!-- Search & Login dipisah dari ul, biar tetap di kanan -->
    <form action="search.php" method="GET" style="display:inline;">
        <input type="text" name="q" placeholder="Search produk..." style="padding:5px;">
    </form>
    <a href="login.php" style="margin-left:15px; text-decoration:none; color:#333;" class="active">Login</a>
</nav>
</header>
</html>