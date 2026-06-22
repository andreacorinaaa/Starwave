<?php
include('../config/koneksi.php');

$error = "";

if (isset($_POST['register'])) {

    $email          = trim($_POST['email']);
    $raw_password   = $_POST['password'];
    $tanggal_lahir  = $_POST['tanggal_lahir'];
    $nama_panggilan = trim($_POST['nama_panggilan']); // <-- field baru

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid! email@gmail.com";

    } elseif (empty($nama_panggilan)) {
        $error = "Nama panggilan wajib diisi!";

    } elseif (strlen($raw_password) < 8) {
        $error = "Password minimal 8 karakter!";

    } elseif (!preg_match('/[0-9]/', $raw_password)) {
        $error = "Password harus mengandung minimal 1 angka!";

    } else {
        $password = password_hash($raw_password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("SELECT id_user FROM users WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $error = "Email sudah digunakan";

        } else {

            $stmt = $pdo->prepare("INSERT INTO users (email, password, tanggal_lahir, nama_panggilan) VALUES (?, ?, ?, ?)");
            $ok   = $stmt->execute([$email, $password, $tanggal_lahir, $nama_panggilan]);

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

<div class="register-page">
    <div class="register-container">

        <h2>CREATE ACCOUNT</h2>

        <?php if (!empty($error)): ?>
            <div class="auth-alert error">
                ⚠ <?= htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" novalidate onsubmit="return validateRegister(this)">
            <div class="form-group">
                <label for="reg-nama">Nama Panggilan</label>
                <input type="text" name="nama_panggilan" id="reg-nama" placeholder="Nama panggilan kamu"
                       value="<?= htmlspecialchars($_POST['nama_panggilan'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="reg-email">Email</label>
                <input type="email" name="email" id="reg-email" placeholder="email@gmail.com"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="reg-password">Password</label>
                <div class="pw-wrap">
                    <input type="password" name="password" id="reg-password" placeholder="Password">
                    <button type="button" class="pw-toggle" onclick="togglePw('reg-password')">👁</button>
                </div>
            </div>

            <div class="form-group">
                <label for="reg-tanggal">Tanggal Lahir</label>
                <input type="date" name="tanggal_lahir" id="reg-tanggal"
                       value="<?= htmlspecialchars($_POST['tanggal_lahir'] ?? '') ?>">
            </div>

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

<script src="masuk.js"></script>

</body>
</html>