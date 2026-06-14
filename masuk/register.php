<?php
include('../config/koneksi.php');

$error = "";

if (isset($_POST['register'])) {

    $email         = trim($_POST['email']);
    $raw_password  = $_POST['password'];
    $tanggal_lahir = $_POST['tanggal_lahir'];

    if (strlen($raw_password) < 8) {
        $error = "Password minimal 8 karakter!";
    } elseif (!preg_match('/[0-9]/', $raw_password)) {
        $error = "Password harus mengandung minimal 1 angka!";
    } else {
        $password = password_hash($raw_password, PASSWORD_DEFAULT);

        // Cek email sudah terdaftar
        $stmt = $pdo->prepare("SELECT id_user FROM users WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $error = "Email sudah digunakan";
        } else {
            $stmt = $pdo->prepare("INSERT INTO users (email, password, tanggal_lahir) VALUES (?, ?, ?)");
            $ok   = $stmt->execute([$email, $password, $tanggal_lahir]);

            if ($ok) {
                header("Location: login.php");
                exit;
            } else {
                $error = "Registrasi gagal";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>register - STARWAVE</title>
    <link rel="stylesheet" href="masuk.css">
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../order.css">
    <style>
        .pw-wrap { position: relative; }
        .pw-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
            color: #888;
        }
        .pw-hint {
            font-size: 12px;
            color: #888;
            margin-top: 4px;
        }
    </style>
</head>
<body>

<div class="register-page">
    <div class="register-container">

        <h2>CREATE ACCOUNT</h2>

        <?php if (!empty($error)): ?>
            <div class="auth-alert error">
                ⚠ <?= htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="email" name="email" placeholder="Email Address" required>

            <div class="pw-wrap">
                <input type="password" name="password" id="reg-password" placeholder="Password" required>
                <button type="button" class="pw-toggle" onclick="togglePw('reg-password')">👁</button>
            </div>
            <p class="pw-hint">Minimal 8 karakter dan harus mengandung angka</p>

            <input type="date" name="tanggal_lahir" required>
            <button type="submit" name="register">REGISTER ACCOUNT</button>
        </form>

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

<script>
function togglePw(id) {
    const input = document.getElementById(id);
    input.type = input.type === 'password' ? 'text' : 'password';
}
</script>

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
            <li><a href="../keranjang.php">Keranjang</a></li>
        </ul>
        <form action="search.php" method="GET" style="display:inline;">
            <input type="text" name="q" placeholder="Search produk..." style="padding:5px;">
        </form>
        <a href="login.php" style="margin-left:15px; text-decoration:none; color:#c9dde8; font-size:14px; font-weight:700;" class="active">Login</a>
    </nav>
</header>
</html>