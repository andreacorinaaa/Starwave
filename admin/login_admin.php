<?php
session_start();
include('../config/koneksi.php');

if (isset($_SESSION['admin'])) {
    header("Location: dashboard.php");
    exit;
}

// Tempat nampung pesan error (kosong dulu, isinya nanti
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    // Cari admin di database berdasarkan email yang diketik
    $stmt = $pdo->prepare("SELECT * FROM admin WHERE email = ?");
    $stmt->execute([$email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    $email_ditemukan = $admin !== false;
    $password_benar  = $email_ditemukan && password_verify($password, $admin['password']);

    if ($email_ditemukan && $password_benar) {
        // Lolos dua syarat -> simpan status login, masuk dashboard
        $_SESSION['admin'] = $admin['email'];
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Email atau password salah.";
    }
}

$email_lama = htmlspecialchars($_POST['email'] ?? '');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin — STARWAVE</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="admin.css">
</head>
<body>

<div class="login-wrap">
    <div class="brand">
        <div class="brand-name">STARWAVE</div>
        <div class="brand-label">Admin Panel</div>
    </div>

    <div class="card">
        <div class="card-title">LOGIN</div>
        <div class="card-sub">Masuk ke panel administrasi</div>

        <!-- Pesan error cuma muncul kalau $error ada isinya -->
        <?php if ($error): ?>
            <div class="error-box">⚠ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="login_admin.php">

            <div class="form-group">
                <label class="form-label" for="email">Email</label>
                <input class="form-input" type="email" id="email" name="email"
                    placeholder="Email admin"
                    value="<?= $email_lama ?>"
                    autocomplete="email" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <div class="pw-wrap">
                    <input class="form-input" type="password" id="password" name="password"
                        placeholder="••••••••"
                        autocomplete="current-password"
                        style="padding-right: 42px;"
                        required>
                    <button type="button" class="pw-toggle" onclick="togglePw()" title="Tampilkan password">👁</button>
                </div>
            </div>

            <button type="submit" class="btn-login">Masuk</button>
        </form>
    </div>

    <a href="../index.php">← Kembali ke toko</a>
</div>

<script src="admin.js"></script>

</body>
</html>