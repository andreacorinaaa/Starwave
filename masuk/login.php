<?php
session_start();
include('../config/koneksi.php');

if (isset($_SESSION['user'])) {
    header("Location: ../profile.php");
    exit;
}
if (isset($_SESSION['admin'])) {
    header("Location: ../admin/dashboard.php");
    exit;
}

$error = "";

if (isset($_POST['login'])) {
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Cek admin
    $stmt = $pdo->prepare("SELECT * FROM admin WHERE email = ?");
    $stmt->execute([$email]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row && password_verify($password, $row['password'])) {
        $_SESSION['admin'] = $email;
        header("Location: ../admin/dashboard.php");
        exit;
    }

    // Cek user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row && password_verify($password, $row['password'])) {
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
    <header>
        <nav>
            <h1>STARWAVE</h1>
            <ul>
                <li><a href="../index.php">Home</a></li>
                <li><a href="../man.php">Man</a></li>
                <li><a href="../woman.php">Woman</a></li>
                <li><a href="../accessories.php">Accessories</a></li>
                <li><a href="../order.php">Order</a></li>
                <li><a href="../keranjang.php">Keranjang</a></li>
            </ul>
            <form action="search.php" method="GET" style="display:inline;">
                <input type="text" name="q" placeholder="Search produk..." style="padding:5px;">
            </form>
            <a href="login.php" style="margin-left:15px; text-decoration:none; color:#c9dde8; font-size:14px; font-weight:700;" class="active">Login</a>
        </nav>
    </header>

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
                <div class="pw-wrap">
                    <input type="password" name="password" id="login-password" placeholder="Password" required>
                    <button type="button" class="pw-toggle" onclick="togglePw('login-password')">👁</button>
                </div>
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

<script>
function togglePw(id) {
    const input = document.getElementById(id);
    input.type = input.type === 'password' ? 'text' : 'password';
}
</script>

</body>
</html>