<?php
session_start();
include('../config/koneksi.php');

// Kalau sudah login, langsung ke dashboard
if (isset($_SESSION['admin'])) {
    header("Location: dashboard.php");
    exit;
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = $_POST['password'];

    $result = mysqli_query($conn, "SELECT * FROM admin WHERE email='$email'");
    $admin  = mysqli_fetch_assoc($result);

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin'] = $admin['email'];
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Email atau password salah.";
    }
}
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

        <?php if ($error): ?>
            <div class="error-box">⚠ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="login_admin.php">
            <div class="form-group">
                <label class="form-label" for="email">Email</label>
                <input class="form-input" type="email" id="email" name="email"
                    placeholder="Email admin"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
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

    <a href="../index.php" class="back-link">← Kembali ke toko</a>
</div>

<script>
function togglePw() {
    const input = document.getElementById('password');
    input.type = input.type === 'password' ? 'text' : 'password';
}
</script>

</body>
</html>