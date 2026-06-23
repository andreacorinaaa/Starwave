<?php
session_start();
include 'config/koneksi.php';

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if (!$id) {
    header("Location: man.php");
    exit;
}

if (!isset($_SESSION['user'])) {
    $_SESSION['redirect_after_login'] = "detail.php?id=" . $id;
    header("Location: masuk/login.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM produk WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    die("Produk tidak ditemukan");
}

$user_email = $_SESSION['user'];

$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$user_email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header("Location: masuk/login.php");
    exit;
}

$id_user = $user['id_user'];
$qty     = (int)($_POST['qty'] ?? 0);
$ukuran  = $_POST['ukuran'] ?? '-';
$aksi    = $_POST['aksi'] ?? 'beli'; 

if (strtolower($item['kategori']) !== 'accessories') {

    $kolom_stok    = 'stok_' . strtolower($ukuran); 
    $stok_tersedia = (int)($item[$kolom_stok] ?? 0);

    if ($qty < 1 || $qty > $stok_tersedia) {
        $_SESSION['error_stok'] = "Maaf, stok untuk ukuran " . strtoupper($ukuran) . " hanya tersisa {$stok_tersedia} pcs.";
        header("Location: detail.php?id=" . $id);
        exit;
    }
}

$harga       = $item['harga'];
$total_harga = $harga * $qty;

$nama_produk_order = (strtolower($item['kategori']) === 'accessories')
    ? $item['nama_produk']
    : $item['nama_produk'] . " - Size " . $ukuran;

if ($aksi === 'keranjang') {

    $stmt = $pdo->prepare("SELECT * FROM keranjang WHERE id_user = ? AND id_produk = ? AND ukuran = ?");
    $stmt->execute([$id_user, $id, $ukuran]);
    $cek = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cek) {
        $stmt = $pdo->prepare("UPDATE keranjang SET qty = qty + ? WHERE id = ?");
        $stmt->execute([$qty, $cek['id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO keranjang (id_user, id_produk, nama_produk, harga, qty, ukuran, gambar)
                                VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$id_user, $id, $item['nama_produk'], $harga, $qty, $ukuran, $item['gambar']]);
    }

    header("Location: keranjang.php");
    exit;
}


$no_telp = trim($user['no_telepon'] ?? '');
$wilayah = trim($user['wilayah'] ?? '');
$alamat  = trim($user['alamat'] ?? '');

if (empty($no_telp) || empty($wilayah) || empty($alamat)) {
    $_SESSION['peringatan_profil']     = "Lengkapi nomor HP, wilayah, dan alamat kamu dulu sebelum memesan.";
    $_SESSION['redirect_after_profil'] = "detail.php?id=" . $id;
    header("Location: profile.php?peringatan=1");
    exit;
}

$kode_order = uniqid('ORD-', true);

$stmt = $pdo->prepare("INSERT INTO orders
        (id_user, kode_order, nama_produk, id_produk, qty, harga, total_harga, nama_penerima, email, tanggal_order, status, qris_expired_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'pending_payment', DATE_ADD(NOW(), INTERVAL 15 MINUTE))");

$stmt->execute([
    $id_user,
    $kode_order,
    $nama_produk_order,
    $id,             
    $qty,
    $harga,
    $total_harga,
    $user['nama_panggilan'],
    $user_email
]);

$id_order = $pdo->lastInsertId();

header("Location: payment.php?id=" . $id_order);
exit;