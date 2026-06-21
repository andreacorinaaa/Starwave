<?php
require 'auth_check.php';

$pending_orders = $pdo->query("
    SELECT COUNT(*) FROM orders 
    WHERE status = 'pending_payment' OR status = 'pending'
")->fetchColumn();
$ada_pending = $pending_orders > 0;

$total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();

$pesan = "";

// --- Aksi 2A: Admin ganti status pesanan lewat dropdown ---
if (isset($_POST['update_status'])) {
    $id_order   = (int)$_POST['id_order'];
    $new_status = $_POST['status'];

    $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?")
        ->execute([$new_status, $id_order]);

    $pesan = "Status pesanan #$id_order berhasil diperbarui.";
}

// --- Aksi 2B: Admin konfirmasi pembayaran (dari modal "Konfirmasi Bayar") ---
if (isset($_POST['konfirmasi_bayar'])) {
    $id_order = (int)$_POST['id_order'];

    $stmt = $pdo->prepare("
        UPDATE orders 
        SET status_bayar = 'paid', status = 'diproses' 
        WHERE id = ?
    ");
    $stmt->execute([$id_order]);

    if ($stmt->rowCount() > 0) {
        $pesan = "Pembayaran order #$id_order berhasil dikonfirmasi. Status diubah ke Diproses.";
    }
}

// --- Aksi 2C: Admin hapus pesanan (dari tombol "Hapus") ---
if (isset($_GET['hapus_order'])) {
    $id = (int)$_GET['hapus_order'];
    $pdo->prepare("DELETE FROM orders WHERE id = ?")->execute([$id]);
    header("Location: pesanan.php?deleted=1");
    exit;
}

$stmt = $pdo->query("
    SELECT o.*, u.nama_panggilan AS nama, u.no_telepon
    FROM orders o
    LEFT JOIN users u ON o.id_user = u.id_user
    ORDER BY o.created_at DESC 
    LIMIT 50
");
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$kamus_class = [
    'selesai'         => 'done',
    'diproses'        => 'process',
    'dikirim'         => 'ship',
    'pending_payment' => 'pending',
    'pending'         => 'pending',
    'batal'           => 'cancel',
    'qr_expired'      => 'cancel',
];

$kamus_label = [
    'pending_payment' => 'Belum Bayar',
    'pending'         => 'Pending',
    'diproses'        => 'Diproses',
    'dikirim'         => 'Dikirim',
    'selesai'         => 'Selesai',
    'batal'           => 'Dibatalkan',
    'qr_expired'      => 'QR Expired',
];

function ambilStatusClass($status, $kamus) {
    return $kamus[$status] ?? 'pending';
}
function ambilStatusLabel($status, $kamus) {
    return $kamus[$status] ?? ucfirst($status);
}

// Pilihan status buat dropdown "Ubah Status".
$opsi_status = [
    'pending_payment' => 'Belum Bayar',
    'pending'          => 'Pending',
    'diproses'         => 'Diproses',
    'dikirim'          => 'Dikirim',
    'selesai'          => 'Selesai',
    'batal'            => 'Dibatalkan',
];
require 'pesanan_view.php';