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

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid!";

    } else {

        // Cek ADMIN
        $stmt = $pdo->prepare("SELECT * FROM admin WHERE email = ?");
        $stmt->execute([$email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row && password_verify($password, $row['password'])) {
            $_SESSION['admin'] = $email;
            header("Location: ../admin/dashboard.php");
            exit;
        }

        // Cek USER biasa
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
            <h1><a href="../index.php">STARWAVE</a></h1>
            <ul>
                <li><a href="../index.php">Home</a></li>
                <li><a href="../man.php">Man</a></li>
                <li><a href="../woman.php">Woman</a></li>
                <li><a href="../accessories.php">Accessories</a></li>
                <li><a href="../order.php">Order</a></li>
                <li><a href="../keranjang.php">Keranjang</a></li>
            </ul>
            <a href="login.php" class="btn-login">Login</a>
        </nav>
    </header>

    <div class="login-page">
        <div class="login-container">
            <h2>LOGIN</h2>
            <p>Selamat datang kembali</p>

            <?php if ($error): ?>
                <div class="auth-alert error">⚠ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form action="" method="POST" novalidate onsubmit="return validateLogin(this)">
                <div class="form-group">
                    <label for="login-email">Email</label>
                    <input type="email" name="email" id="login-email" placeholder="email@gmail.com" autocomplete="email">
                </div>
                <div class="form-group">
                    <label for="login-password">Password</label>
                    <div class="pw-wrap">
                        <input type="password" name="password" id="login-password" placeholder="Password">
                        <button type="button" class="pw-toggle" onclick="togglePw('login-password')">👁</button>
                    </div>
                </div>
                <div style="text-align:right; margin-bottom:10px;">
                    <a href="lupa_password.php" style="font-size:13px; color:#000000;">Lupa password?</a>
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

    <script src="masuk.js"></script>

</body>
</html>