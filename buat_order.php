<?php
session_start();
include('config/koneksi.php');

// agar data login tetap tersimpan walaupun pindah halaman
if (!isset($_SESSION['user'])) { //Mengecek apakah data ada
    $_SESSION['redirect_after_login'];
    header("Location: masuk/login.php");
    exit;
}

$user_email = $_SESSION['user'];
// Digunakan untuk mengambil data user dari database.
$user = mysqli_fetch_assoc(mysqli_query($conn, // Mengambil hasil query menjadi array associative
    "SELECT * FROM users WHERE email='$user_email'"
));

$user_id = $user['id_user'];

// ambil produk dari URL
$produk = mysqli_real_escape_string($conn, $_GET['produk']);

// insert order otomatis
$query = "INSERT INTO orders (id_user, nama_produk, qty, nama_penerima, email, tanggal_order, status)
          VALUES ('$user_id', '$produk', '1', '".$user['nama']."', '$user_email', NOW(), 'pending')";

mysqli_query($conn, $query);

// redirect ke order.php
header("Location: order.php");
exit;
?>