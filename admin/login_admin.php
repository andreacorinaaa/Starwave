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
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $password = $_POST['password'];

    $result = mysqli_query($conn, "SELECT * FROM admin WHERE username='$username'");
    $admin  = mysqli_fetch_assoc($result);

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin'] = $admin['username'];
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Username atau password salah.";
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
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: #f7f8fc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-wrap {
            width: 100%;
            max-width: 400px;
            padding: 24px;
        }

        .brand {
            text-align: center;
            margin-bottom: 36px;
        }

        .brand-name {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 38px;
            letter-spacing: 8px;
            color: #4f6ef7;
        }

        .brand-label {
            font-size: 11px;
            letter-spacing: 4px;
            text-transform: uppercase;
            color: #8b90a7;
            margin-top: 4px;
        }

        .card {
            background: #fff;
            border: 1px solid #e8eaf2;
            border-radius: 16px;
            padding: 36px 32px;
            box-shadow: 0 4px 24px rgba(79,110,247,0.07);
        }

        .card-title {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 22px;
            letter-spacing: 3px;
            color: #1a1d2e;
            margin-bottom: 6px;
        }

        .card-sub {
            font-size: 13px;
            color: #8b90a7;
            margin-bottom: 28px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #8b90a7;
            margin-bottom: 7px;
        }

        .form-input {
            width: 100%;
            background: #f0f2f8;
            border: 1.5px solid #e8eaf2;
            border-radius: 8px;
            color: #1a1d2e;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            padding: 11px 14px;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
        }

        .form-input:focus {
            border-color: #4f6ef7;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(79,110,247,0.1);
        }

        .error-box {
            background: #fef2f2;
            border: 1px solid #fdd;
            color: #ef4444;
            border-radius: 8px;
            padding: 11px 14px;
            font-size: 13px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-login {
            width: 100%;
            padding: 13px;
            background: #4f6ef7;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            letter-spacing: 0.5px;
            transition: background 0.15s, transform 0.1s;
            margin-top: 4px;
        }

        .btn-login:hover  { background: #3a56e8; }
        .btn-login:active { transform: scale(0.99); }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            font-size: 13px;
            color: #8b90a7;
            text-decoration: none;
            transition: color 0.15s;
        }
        .back-link:hover { color: #4f6ef7; }

        .pw-wrap { position: relative; }
        .pw-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #8b90a7;
            font-size: 16px;
            padding: 4px;
            line-height: 1;
            transition: color 0.15s;
        }
        .pw-toggle:hover { color: #4f6ef7; }
    </style>
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
                <label class="form-label" for="username">Username</label>
                <input class="form-input" type="text" id="username" name="username"
                    placeholder="Username admin"
                    value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                    autocomplete="username" required>
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