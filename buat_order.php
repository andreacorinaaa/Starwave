<?php
session_start();
include('config/koneksi.php');

if (!isset($_SESSION['user'])) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: masuk/login.php");
    exit;
}

$user_email = $_SESSION['user'];

// Ambil data user
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$user_email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header("Location: masuk/login.php");
    exit;
}

$user_id = $user['id_user'];

// Ambil produk dari URL
$produk = $_GET['produk'] ?? '';

if (empty($produk)) {
    header("Location: index.php");
    exit;
}

// Insert order
$stmt = $pdo->prepare("INSERT INTO orders (id_user, nama_produk, qty, nama_penerima, email, tanggal_order, status)
                       VALUES (?, ?, 1, ?, ?, NOW(), 'pending')");
$stmt->execute([$user_id, $produk, $user['nama'], $user_email]);

header("Location: order.php");
exit;
?>