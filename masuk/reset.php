<?php
// =======================================================
// 1. INISIALISASI SESSION & KONEKSI DATABASE
// =======================================================
session_start();
include('../config/koneksi.php');

$error = "";
$success = "";

// Ambil token dari URL (GET) atau dari form (POST)
$token = $_GET['token'] ?? ($_POST['token'] ?? "");
$validToken = false;

// =======================================================
// 2. CEK APAKAH TOKEN VALID & BELUM EXPIRED
// =======================================================
if ($token) {
    // Cari user yang punya reset_token ini DAN belum lewat waktu expired-nya
    $stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token = ? AND reset_expiry > NOW()");
    $stmt->execute([$token]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        // Token ketemu & masih berlaku -> boleh tampilkan form reset
        $validToken = true;
    } else {
        // Token tidak ketemu / sudah expired
        $error = "Link reset sudah tidak valid atau sudah expired.";
    }
} else {
    // User akses halaman ini tanpa token sama sekali
    $error = "Token tidak ditemukan.";
}

// =======================================================
// 3. PROSES SAAT USER SUBMIT FORM RESET PASSWORD
// =======================================================
if ($validToken && isset($_POST['reset'])) {

    $password = trim($_POST['password']);
    $confirm  = trim($_POST['confirm']);

    // --- Validasi 1: password & konfirmasi harus sama ---
    if ($password !== $confirm) {
        $error = "Konfirmasi password tidak sama.";
        $validToken = true; // form tetap ditampilkan lagi

    // --- Validasi 2: minimal 8 karakter ---
    } elseif (strlen($password) < 8) {
        $error = "Password minimal 8 karakter.";
        $validToken = true;

    // --- Validasi 3: harus ada kombinasi angka ---
    } elseif (!preg_match('/[0-9]/', $password)) {
        $error = "Password harus mengandung minimal 1 angka.";
        $validToken = true;

    // --- Semua validasi lolos -> update password ke database ---
    } else {
        // Password di-hash dulu, JANGAN simpan password polos ke DB
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        // Update password baru + kosongkan token (biar link tidak bisa dipakai lagi)
        $update = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expiry = NULL WHERE reset_token = ?");
        $update->execute([$hashed, $token]);

        $success = "Password berhasil diganti. Silakan login.";
        $validToken = false; // form disembunyikan, ganti tampilkan pesan sukses
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

    <!-- ================= HEADER / NAVBAR ================= -->
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

            <!-- Tampil kalau ada pesan error -->
            <?php if ($error): ?>
                <div class="auth-alert error">⚠ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <!-- Tampil kalau password berhasil diganti -->
            <?php if ($success): ?>
                <div class="auth-alert success"><?= htmlspecialchars($success) ?></div>
                <div class="create">
                    <a href="login.php">Ke halaman Login</a>
                </div>

            <!-- Tampil kalau token valid -> munculkan form isi password baru -->
            <?php elseif ($validToken): ?>
                <form action="" method="POST">
                    <!-- token dikirim ulang lewat hidden input -->
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                    <div class="form-group">
                        <div class="pw-wrap">
                            <input type="password" name="password" id="new-password"
                                   placeholder="Password Baru" required minlength="8"
                                   pattern="(?=.*[0-9]).{8,}"
                                   title="Password minimal 8 karakter dan mengandung minimal 1 angka">
                            <button type="button" class="pw-toggle" onclick="togglePw('new-password')">👁</button>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="pw-wrap">
                            <input type="password" name="confirm" id="confirm-password"
                                   placeholder="Konfirmasi Password" required minlength="8">
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