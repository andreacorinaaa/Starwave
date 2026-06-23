<?php
session_start();
include('config/koneksi.php');

if (!isset($_GET['id'])) {
    die("ID order tidak ditemukan");
}

$id_order = (int)$_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$id_order]);
$main_order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$main_order) {
    die("Pesanan tidak ditemukan");
}

$kode_order = $main_order['kode_order'];

$status_bayar = $main_order['status_bayar'] ?? 'unpaid';
$is_expired   = false;

if ($main_order['status'] === 'pending_payment' && $status_bayar === 'unpaid') {
    if (!empty($main_order['qris_expired_at']) && strtotime($main_order['qris_expired_at']) < time()) {
        $stmt = $pdo->prepare("UPDATE orders SET status = 'qr_expired' WHERE kode_order = ?");
        $stmt->execute([$kode_order]);
        $main_order['status'] = 'qr_expired';
        $is_expired = true;
    }
}

$id_user       = (int)$main_order['id_user'];
$tanggal_order = $main_order['tanggal_order'];

function ambilSemuaItemOrder(PDO $pdo, string $kode_order): array {
    $stmt = $pdo->prepare("
        SELECT o.*, p.gambar, p.id AS id_produk
        FROM orders o
        LEFT JOIN produk p ON o.id_produk = p.id
        WHERE o.kode_order = ? AND o.status != 'batal'
    ");
    $stmt->execute([$kode_order]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$orders = ambilSemuaItemOrder($pdo, $kode_order);

if (empty($orders)) {
    die("Pesanan tidak ditemukan");
}

$total      = array_sum(array_column($orders, 'total_harga'));
$id_produk  = $orders[0]['id_produk'] ?? null;

$upload_success = '';
$upload_error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['bukti_bayar'])) {
    $file    = $_FILES['bukti_bayar'];
    $ftype   = mime_content_type($file['tmp_name']);
    $allowed = ['image/jpeg', 'image/png', 'image/webp'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $upload_error = 'Gagal mengupload file.';

    } elseif (!in_array($ftype, $allowed)) {
        $upload_error = 'Format tidak didukung. Gunakan JPG, PNG, atau WEBP.';

    } elseif ($file['size'] > 3 * 1024 * 1024) {
        $upload_error = 'Ukuran file maksimal 3MB.';

    } else {
        $map_ext    = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $ext        = $map_ext[$ftype];
        $filename   = 'bukti_' . $id_order . '_' . time() . '.' . $ext;
        $upload_dir = __DIR__ . '/asset/bukti/';

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        if (!move_uploaded_file($file['tmp_name'], $upload_dir . $filename)) {
            $upload_error = 'Gagal menyimpan file.';
        } else {
            $path = 'asset/bukti/' . $filename;


            $stmt = $pdo->prepare("
                UPDATE orders 
                SET bukti_bayar = ?, status_bayar = 'menunggu_konfirmasi' 
                WHERE kode_order = ?
            ");
            $stmt->execute([$path, $kode_order]);
            $upload_success = 'Bukti bayar berhasil dikirim! Pesanan kamu sedang diverifikasi admin.';

            $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
            $stmt->execute([$id_order]);
            $main_order   = $stmt->fetch(PDO::FETCH_ASSOC);
            $status_bayar = $main_order['status_bayar'] ?? 'unpaid';

            $orders = ambilSemuaItemOrder($pdo, $kode_order);
        }
    }
}

define('QRIS_NMID',          '936009060600895');
define('QRIS_MERCHANT_NAME', 'Starwave Fashion');
define('QRIS_CITY',          'Mataram');

function qrisLen(string $v): string {
    return str_pad(strlen($v), 2, '0', STR_PAD_LEFT);
}

function qrisCrc16(string $payload): string {
    $crc = 0xFFFF;
    for ($i = 0; $i < strlen($payload); $i++) {
        $crc ^= ord($payload[$i]) << 8;
        for ($j = 0; $j < 8; $j++) {
            $crc = ($crc & 0x8000) ? (($crc << 1) ^ 0x1021) & 0xFFFF : ($crc << 1) & 0xFFFF;
        }
    }
    return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
}

function generateQrisString(string $nmid, string $merchantName, string $city, int $amount): string {
    $merchantName = substr($merchantName, 0, 25);
    $city         = substr($city, 0, 15);
    $amountStr    = (string) $amount;
    $guid         = '0016A00000007750415';

    $sub01   = '01' . qrisLen($nmid) . $nmid;
    $sub02   = '02' . '15' . str_pad($nmid, 15, '0', STR_PAD_RIGHT);
    $sub03   = '0303UME';
    $inner   = $guid . $sub01 . $sub02 . $sub03;
    $field26 = '26' . qrisLen($inner) . $inner;
    $field54 = '54' . qrisLen($amountStr) . $amountStr;

    $add62inner = '0503***';
    $field62    = '62' . qrisLen($add62inner) . $add62inner;

    $payload = implode('', [
        '000201', '010212', $field26, '52045963', '5303360', $field54,
        '5802ID', '59' . qrisLen($merchantName) . $merchantName,
        '60' . qrisLen($city) . $city, $field62, '6304',
    ]);

    return $payload . qrisCrc16($payload);
}

$qris_string  = generateQrisString(QRIS_NMID, QRIS_MERCHANT_NAME, QRIS_CITY, (int)$total);
$sudah_upload = !empty($main_order['bukti_bayar']);