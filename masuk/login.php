<?php
session_start();
include('../config/koneksi.php');

$error   = "";
$success = "";

// proses login
if (isset($_POST['login'])) {
    $email    = $_POST['email'];
    $password = $_POST['password'];

    $query = mysqli_query($conn,
        "SELECT * FROM users
         WHERE email    = '$email'
         AND   password = '$password'"
    );

    if (mysqli_num_rows($query) > 0) {
        $_SESSION['user'] = $email;

        if (isset($_SESSION['redirect_after_login'])) {
            $redirect = $_SESSION['redirect_after_login'];
            unset($_SESSION['redirect_after_login']);
            header("Location: ../" . $redirect);
        } else {
            header("Location: login.php");
        }
        exit;
    } else {
        $error = "Email atau password salah!";
    }
}

// ambil data user
if (isset($_SESSION['user'])) {
    $email = $_SESSION['user'];

    $query = mysqli_query($conn,
        "SELECT * FROM users
         WHERE email = '$email'"
    );

    $user = mysqli_fetch_assoc($query);
}

// update profile
if (isset($_POST['update_profile'])) {
    $nama_panggilan = $_POST['nama_panggilan'];
    $no_telepon     = $_POST['no_telepon'];
    $alamat         = $_POST['alamat'];
    $tanggal_lahir  = $_POST['tanggal_lahir'];
    $jenis_kelamin  = $_POST['jenis_kelamin'];

    mysqli_query($conn,
        "UPDATE users SET
            nama_panggilan = '$nama_panggilan',
            no_telepon     = '$no_telepon',
            alamat         = '$alamat',
            tanggal_lahir  = '$tanggal_lahir',
            jenis_kelamin  = '$jenis_kelamin'
         WHERE email = '$email'"
    );

    $success = "Profile berhasil diperbarui";

    $query = mysqli_query($conn,
        "SELECT * FROM users
         WHERE email = '$email'"
    );

    $user = mysqli_fetch_assoc($query);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Login — STARWAVE</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../order.css">
    <link rel="stylesheet" href="masuk.css">
</head>

<header>
    <nav>
        <h1>STARWAVE</h1>

        <ul>
            <li><a href="../index.php">Home</a></li>
            <li><a href="../man.php">Man</a></li>
            <li><a href="../woman.php">Woman</a></li>
            <li><a href="../accessories.php">Accessories</a></li>
            <li><a href="../order.php">Order</a></li>
            <li><a href="login.php" class="active">User</a></li>
        </ul>

        <form action="search.php" method="GET" style="display:inline;">
            <input type="text" name="q" placeholder="Search produk..." style="padding:5px;">
        </form>
    </nav>
</header>

<body>

<div class="login-page">
    <div class="login-container <?= isset($_SESSION['user']) ? 'account-layout' : '' ?>">

        <?php if (isset($_SESSION['user'])): ?>

            <div class="account-left">

                <h2>My Account</h2>

                <?php if ($success): ?>
                    <div class="auth-alert success">
                        <?= $success ?>
                    </div>
                <?php endif; ?>

                <form action="" method="POST">

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" value="<?= $user['email'] ?>" disabled>
                    </div>

                    <div class="form-group">
                        <label>Nama Panggilan</label>
                        <input type="text" name="nama_panggilan" value="<?= $user['nama_panggilan'] ?>">
                    </div>

                    <div class="form-group">
                        <label>Nomor Telepon</label>
                        <input type="text" name="no_telepon" value="<?= $user['no_telepon'] ?>">
                    </div>

                    <div class="form-group">
                        <label>Alamat</label>
                        <textarea name="alamat"><?= $user['alamat'] ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Tanggal Lahir</label>
                        <input type="date" name="tanggal_lahir" value="<?= $user['tanggal_lahir'] ?>">
                    </div>

                    <div class="form-group">
                        <label>Jenis Kelamin</label>
                        <select name="jenis_kelamin">
                            <option value="">Pilih</option>
                            <option value="Laki-laki" <?= $user['jenis_kelamin'] == 'Laki-laki' ? 'selected' : '' ?>>Laki-laki</option>
                            <option value="Perempuan" <?= $user['jenis_kelamin'] == 'Perempuan' ? 'selected' : '' ?>>Perempuan</option>
                        </select>
                    </div>

                    <button type="submit" name="update_profile">Simpan Perubahan</button>

                </form>

                <div class="logout">
                    <a href="logout.php">LOGOUT</a>
                </div>

            </div>

            <div class="account-right">
                <img src="../asset/model login.jpg" alt="Fashion Model">
            </div>

        <?php else: ?>

            <h2>Login</h2>
            <p>Selamat datang kembali</p>

            <?php if ($error): ?>
                <div class="auth-alert error">
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST">

                <div class="form-group">
                    <input type="email" name="email" placeholder="Email" required>
                </div>

                <div class="form-group">
                    <input type="password" name="password" placeholder="Password" required>
                </div>

                <button type="submit" name="login">MASUK</button>

            </form>

            <div class="create">
                Belum punya akun?
                <a href="register.php">BUAT AKUN</a>
            </div>

        <?php endif; ?>

    </div>
</div>

<!-- FOOTER -->
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

</body>
</html>