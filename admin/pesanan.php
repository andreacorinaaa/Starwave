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
    // update database atau ngubah status pesanan
    $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?")
        ->execute([$new_status, $id_order]);

    $pesan = "Status pesanan #$id_order berhasil diperbarui.";
}

// --- Aksi 2B: Admin konfirmasi pembayaran ---
if (isset($_POST['konfirmasi_bayar'])) {
    $id_order = (int)$_POST['id_order'];

    $cek = $pdo->prepare("SELECT kode_order FROM orders WHERE id = ?");
    $cek->execute([$id_order]);
    $kode_order = $cek->fetchColumn();

    if ($kode_order) {
        $ambil_ids = $pdo->prepare("
            SELECT id FROM orders 
            WHERE kode_order = ? AND status != 'batal'
        ");
        $ambil_ids->execute([$kode_order]);
        $semua_id = $ambil_ids->fetchAll(PDO::FETCH_COLUMN);

        $stmt = $pdo->prepare("
            UPDATE orders 
            SET status_bayar = 'paid', status = 'diproses' 
            WHERE kode_order = ? AND status != 'batal'
        ");
        $stmt->execute([$kode_order]);
    } else {
        $semua_id = [$id_order];
        $stmt = $pdo->prepare("
            UPDATE orders 
            SET status_bayar = 'paid', status = 'diproses' 
            WHERE id = ?
        ");
        $stmt->execute([$id_order]);
    }

    if ($stmt->rowCount() > 0) {
        $jumlah_item = count($semua_id);
        $daftar_id   = implode(', #', $semua_id); // "12, #13, #14"

        $pesan = $jumlah_item > 1
            ? "Pembayaran untuk $jumlah_item item (#{$daftar_id}) berhasil dikonfirmasi sekaligus. Status diubah ke Diproses."
            : "Pembayaran order #$id_order berhasil dikonfirmasi. Status diubah ke Diproses.";
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
    SELECT o.*, u.nama_panggilan AS nama, u.no_telepon,
        u.alamat, u.wilayah
    FROM orders o
    LEFT JOIN users u ON o.id_user = u.id_user
    ORDER BY o.created_at DESC 
    LIMIT 50
");
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$bundle_info = [];
$semua_kode_order = array_unique(array_filter(array_column($orders, 'kode_order')));

if (!empty($semua_kode_order)) {
    $placeholder = implode(',', array_fill(0, count($semua_kode_order), '?'));
    $stmt2 = $pdo->prepare("
        SELECT kode_order, SUM(total_harga) AS total_bundle, COUNT(*) AS jumlah_item
        FROM orders
        WHERE kode_order IN ($placeholder) AND status != 'batal'
        GROUP BY kode_order
    ");
    $stmt2->execute(array_values($semua_kode_order));

    foreach ($stmt2->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $bundle_info[$row['kode_order']] = $row;
    }
}

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

$opsi_status = [
    'pending_payment' => 'Belum Bayar',
    'pending'          => 'Pending',
    'diproses'         => 'Diproses',
    'dikirim'          => 'Dikirim',
    'selesai'          => 'Selesai',
    'batal'            => 'Dibatalkan',
];
require 'pesanan_view.php';