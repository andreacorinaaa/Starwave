<?php
session_start();
include('../config/koneksi.php');

if (isset($_SESSION['user'])) {
    header("Location: ../profile.php");
    exit; // exit WAJIB biar kode di bawah ga ikut jalan
}

// Variabel buat nampung pesan error (kalau email ga ketemu)
$error = "";

if (isset($_POST['kirim'])) {
    $email = trim($_POST['email']);

    // Cari user berdasarkan email yang diketik
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $token = bin2hex(random_bytes(32));

        // Token ini cuma berlaku 1 jam dari sekarang.
        // Setelah lewat 1 jam, token dianggap basi/tidak valid lagi.
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Simpan token + waktu kadaluarsanya ke baris user yang bersangkutan
        $update = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expiry = ? WHERE email = ?");
        $update->execute([$token, $expiry, $email]);

        header("Location: reset.php?token=" . $token);
        exit;
    } else {

        $error = "Email tidak ditemukan.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Lupa Password — STARWAVE</title>
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

    <!-- ===================== FORM LUPA PASSWORD ===================== -->
    <div class="login-page">
        <div class="login-container">
            <h2>LUPA PASSWORD</h2>
            <p>Masukkan email kamu, kamu akan diarahkan ke halaman reset password</p>

            <!-- Pesan error cuma muncul kalau $error ada isinya -->
            <?php if ($error): ?>
                <div class="auth-alert error">⚠ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <!-- action="" artinya form submit ke dirinya sendiri (lupa_password.php) -->
            <form action="" method="POST" novalidate onsubmit="return validateLupaPassword(this)">
                <div class="form-group">
                    <label for="lupa-email">Email</label>
                    <input type="email" name="email" id="lupa-email" placeholder="Email" autocomplete="email">
                </div>
                <!-- name="kirim" inilah yang dicek di PHP: isset($_POST['kirim']) -->
                <button type="submit" name="kirim">KIRIM LINK RESET</button>
            </form>

            <div class="create">
                <a href="login.php">← Kembali ke Login</a>
            </div>
        </div>
    </div>

    <!-- ===================== FOOTER ===================== -->
    <footer>
        <div class="footer-box">
            <div><h3>Store</h3><p>Man</p><p>Woman</p><p>Accessories</p></div>
            <div><h3>Business</h3><p>starwave@gmail.com</p><p>081836737367367</p></div>
            <div><h3>Social</h3><p>Instagram : starwave.fashion</p></div>
        </div>
    </footer>

    <script src="masuk.js"></script>
</body>
</html>