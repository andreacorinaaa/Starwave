<?php
include('../config/koneksi.php');

$error = "";

if (isset($_POST['register'])) {

    $email          = $_POST['email'];
    $password       = $_POST['password'];
    $tanggal_lahir  = $_POST['tanggal_lahir'];

    $cek = mysqli_query($conn,
        "SELECT * FROM users WHERE email='$email'"
    );

    if (mysqli_num_rows($cek) > 0) {

        $error = "Email sudah digunakan";

    } else {

        $query = mysqli_query($conn,
            "INSERT INTO users(email, password, tanggal_lahir)
             VALUES('$email', '$password', '$tanggal_lahir')"
        );

        if ($query) {
            header("Location: login.php");
            exit;
        } else {
            $error = "Registrasi gagal";
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

<header>
    <nav>
        <h1>STARWAVE</h1>

        <ul>
            <li><a href="../index.php">Home</a></li>
            <li><a href="../man.php">Man</a></li>
            <li><a href="../woman.php">Woman</a></li>
            <li><a href="../accessories.php">Accessories</a></li>
            <li><a href="../order.php">Order</a></li>
            <li><a href="login.php">User</a></li>
        </ul>

        <form action="search.php" method="GET" style="display:inline;">
            <input type="text" name="q" placeholder="Search produk..." style="padding:5px;">
        </form>
    </nav>
</header>

<body>

    <div class="register-page">
        <div class="register-container">

            <h2>CREATE ACCOUNT</h2>

            <?php if (!empty($error)): ?>
                <div class="auth-alert error">
                    <?= $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST">

                <input type="email" name="email" placeholder="Email Address" required>
                <input type="password" name="password" placeholder="Password" required>
                <input type="date" name="tanggal_lahir" required>

                <button type="submit" name="register">REGISTER ACCOUNT</button>

            </form>

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