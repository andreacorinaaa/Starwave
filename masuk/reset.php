<?php
session_start();
include('../config/koneksi.php');

$error = "";
$success = "";

$token = $_GET['token'] ?? ($_POST['token'] ?? "");
$validToken = false;

if ($token) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token = ? AND reset_expiry > NOW()");
    $stmt->execute([$token]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $validToken = true;
    } else {
        $error = "Link reset sudah tidak valid atau sudah expired.";
    }
} else {
    $error = "Token tidak ditemukan.";
}
if ($validToken && isset($_POST['reset'])) {
    $password = trim($_POST['password']);
    $confirm  = trim($_POST['confirm']);
    if ($password !== $confirm) {
        $error = "Konfirmasi password tidak sama.";
        $validToken = true;

    } elseif (strlen($password) < 8) {
        $error = "Password minimal 8 karakter.";
        $validToken = true;
    
    } elseif (!preg_match('/[0-9]/', $password)) {
        $error = "Password harus mengandung minimal 1 angka.";
        $validToken = true;

    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $update = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expiry = NULL WHERE reset_token = ?");
        $update->execute([$hashed, $token]);

        $success = "Password berhasil diganti. Silakan login.";
        $validToken = false;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Reset Password — STARWAVE</title>
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
            <h2>Reset Password</h2>

            <?php if ($error): ?>
                <div class="auth-alert error">⚠ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="auth-alert success"><?= htmlspecialchars($success) ?></div>
                <div class="create">
                    <a href="login.php">Ke halaman Login</a>
                </div>

            <?php elseif ($validToken): ?>
                <form action="" method="POST" novalidate onsubmit="return validateResetPassword(this)">
                    <!-- Menyimpan token secara tersembunyi saat form dikirim. User tidak melihatnya -->
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                    <div class="form-group">
                        <label for="new-password">Password Baru</label>
                        <div class="pw-wrap">
                            <input type="password" name="password" id="new-password"
                                   placeholder="Password Baru">
                            <button type="button" class="pw-toggle" onclick="togglePw('new-password')">👁</button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm-password">Konfirmasi Password</label>
                        <div class="pw-wrap">
                            <input type="password" name="confirm" id="confirm-password"
                                   placeholder="Konfirmasi Password">
                            <button type="button" class="pw-toggle" onclick="togglePw('confirm-password')">👁</button>
                        </div>
                    </div>

                    <button type="submit" name="reset">RESET PASSWORD</button>
                </form>
            <?php endif; ?>
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