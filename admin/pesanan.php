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

    // Ambil kode_order dari baris yang diklik dulu, supaya kalau order ini
    // adalah bagian dari "bundle" (checkout beberapa item sekaligus dari
    // keranjang), SEMUA item dengan kode_order yang sama ikut dikonfirmasi.
    // Ini bikin behavior-nya konsisten sama upload bukti bayar di payment_logic.php
    // yang juga update berdasarkan kode_order, bukan id satu baris doang.
    $cek = $pdo->prepare("SELECT kode_order FROM orders WHERE id = ?");
    $cek->execute([$id_order]);
    $kode_order = $cek->fetchColumn();

    if ($kode_order) {
        // Tambah "AND status != 'batal'" agar item yang sudah dibatalkan user
        // tidak ikut "dihidupkan lagi" jadi diproses saat item lain dalam
        // bundle yang sama dikonfirmasi pembayarannya.
        $stmt = $pdo->prepare("
            UPDATE orders 
            SET status_bayar = 'paid', status = 'diproses' 
            WHERE kode_order = ? AND status != 'batal'
        ");
        $stmt->execute([$kode_order]);
    } else {
        // fallback (seharusnya tidak terjadi, tapi jaga-jaga kalau kode_order kosong)
        $stmt = $pdo->prepare("
            UPDATE orders 
            SET status_bayar = 'paid', status = 'diproses' 
            WHERE id = ?
        ");
        $stmt->execute([$id_order]);
    }

    if ($stmt->rowCount() > 0) {
        $jumlah_item = $stmt->rowCount();
        $pesan = $jumlah_item > 1
            ? "Pembayaran untuk $jumlah_item item dalam pesanan #$id_order berhasil dikonfirmasi sekaligus. Status diubah ke Diproses."
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
    SELECT o.*, u.nama_panggilan AS nama, u.no_telepon
    FROM orders o
    LEFT JOIN users u ON o.id_user = u.id_user
    ORDER BY o.created_at DESC 
    LIMIT 50
");
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Hitung total harga & jumlah item per "bundle" (kode_order) ---
// Dipakai biar modal konfirmasi & modal bukti bayar bisa nampilin TOTAL
// gabungan semua item dalam 1 checkout, bukan cuma harga 1 baris produk.
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