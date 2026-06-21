<?php
session_start();
include 'config/koneksi.php';

// --- Wajib login dulu ------------------------------------------
if (!isset($_SESSION['user'])) {
    $_SESSION['redirect_after_login'] = 'keranjang.php';
    header("Location: masuk/login.php");
    exit;
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

$id_user = (int)$user['id_user'];

function getStokMax(PDO $pdo, int $id_produk, ?string $ukuran): int {
    $stmt = $pdo->prepare("SELECT * FROM produk WHERE id = ?");
    $stmt->execute([$id_produk]);
    $p = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$p) return 0;

    if (strtolower($p['kategori'] ?? '') === 'accessories') {
        return isset($p['stok']) ? (int)$p['stok'] : 0;
    }

    $kolom = 'stok_' . strtolower($ukuran ?? '');
    return isset($p[$kolom]) ? (int)$p[$kolom] : 0;
}

if (isset($_GET['hapus'])) {
    $hapus_id = (int)$_GET['hapus'];

    $stmt = $pdo->prepare("DELETE FROM keranjang WHERE id = ? AND id_user = ?");
    $stmt->execute([$hapus_id, $id_user]);

    header("Location: keranjang.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_qty'])) {

    if (!empty($_POST['qty']) && is_array($_POST['qty'])) {
        $get_item = $pdo->prepare("SELECT * FROM keranjang WHERE id = ? AND id_user = ?");
        $update   = $pdo->prepare("UPDATE keranjang SET qty = ? WHERE id = ? AND id_user = ?");

        foreach ($_POST['qty'] as $kid => $qval) {
            $get_item->execute([(int)$kid, $id_user]);
            $kitem = $get_item->fetch(PDO::FETCH_ASSOC);
            if (!$kitem) continue;

            $stok_max  = getStokMax($pdo, (int)$kitem['id_produk'], $kitem['ukuran']);
            $qty_final = max(1, min((int)$qval, $stok_max));

            $update->execute([$qty_final, (int)$kid, $id_user]);
        }
    }

    header("Location: keranjang.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['beli_semua'])) {

    // update qty dulu (divalidasi ke stok) kalau ada perubahan terakhir
    if (!empty($_POST['qty']) && is_array($_POST['qty'])) {
        $get_item = $pdo->prepare("SELECT * FROM keranjang WHERE id = ? AND id_user = ?");
        $update   = $pdo->prepare("UPDATE keranjang SET qty = ? WHERE id = ? AND id_user = ?");

        foreach ($_POST['qty'] as $kid => $qval) {
            $get_item->execute([(int)$kid, $id_user]);
            $kitem = $get_item->fetch(PDO::FETCH_ASSOC);
            if (!$kitem) continue;

            $stok_max  = getStokMax($pdo, (int)$kitem['id_produk'], $kitem['ukuran']);
            $qty_final = max(1, min((int)$qval, $stok_max));

            $update->execute([$qty_final, (int)$kid, $id_user]);
        }
    }

    // ambil id item yang dicentang user di halaman keranjang
    $checked_ids = array_map('intval', $_POST['checked'] ?? []);

    if (empty($checked_ids)) {
        $_SESSION['error_keranjang'] = "Pilih minimal 1 produk yang ingin di-checkout.";
        header("Location: keranjang.php");
        exit;
    }

    $placeholders = implode(',', array_fill(0, count($checked_ids), '?'));
    $stmt = $pdo->prepare("SELECT * FROM keranjang WHERE id_user = ? AND id IN ($placeholders)");
    $stmt->execute(array_merge([$id_user], $checked_ids));
    $all_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- Validasi stok ulang sebelum checkout (anti race-condition) ---
    $stok_kurang = [];
    foreach ($all_items as $kitem) {
        $stok_max = getStokMax($pdo, (int)$kitem['id_produk'], $kitem['ukuran']);
        if ((int)$kitem['qty'] > $stok_max) {
            $stok_kurang[] = $kitem['nama_produk'] . (!empty($kitem['ukuran']) && $kitem['ukuran'] !== '-' ? ' (Size ' . $kitem['ukuran'] . ')' : '');
        }
    }

    if (!empty($stok_kurang)) {
        $_SESSION['error_keranjang'] = "Stok tidak mencukupi untuk: " . implode(', ', $stok_kurang) . ". Silakan sesuaikan qty-nya dulu.";
        header("Location: keranjang.php");
        exit;
    }

    // pastikan data no HP, wilayah & alamat sudah lengkap sebelum checkout
    $no_telp = trim($user['no_telepon'] ?? '');
    $wilayah = trim($user['wilayah'] ?? '');
    $alamat  = trim($user['alamat'] ?? '');

    if (empty($no_telp) || empty($wilayah) || empty($alamat)) {
        $_SESSION['peringatan_profil']     = "Lengkapi nomor HP, wilayah, dan alamat kamu dulu sebelum memesan.";
        $_SESSION['redirect_after_profil'] = "keranjang.php";
        header("Location: profile.php?peringatan=1");
        exit;
    }

    if (!empty($all_items)) {

        $kode_order    = uniqid('ORD-', true);
        $last_order_id = null;

        $insert_stmt = $pdo->prepare("INSERT INTO orders (id_user, kode_order, nama_produk, id_produk, qty, harga, total_harga, nama_penerima, email, tanggal_order, status)
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'pending_payment')");
        $delete_stmt = $pdo->prepare("DELETE FROM keranjang WHERE id = ? AND id_user = ?");

        foreach ($all_items as $kitem) {
            $nama_order = $kitem['nama_produk'];
            if (!empty($kitem['ukuran']) && $kitem['ukuran'] !== '-') {
                $nama_order .= " - Size " . $kitem['ukuran'];
            }
            $total_harga = (float)$kitem['harga'] * (int)$kitem['qty'];

            $insert_stmt->execute([
                $id_user,
                $kode_order,
                $nama_order,
                $kitem['id_produk'],
                $kitem['qty'],
                $kitem['harga'],
                $total_harga,
                $user['nama_panggilan'] ?? '',
                $user_email
            ]);

            $last_order_id = $pdo->lastInsertId();

            $delete_stmt->execute([$kitem['id'], $id_user]);
        }

        if ($last_order_id) {
            header("Location: payment.php?id=" . $last_order_id);
            exit;
        }
    }

    header("Location: keranjang.php");
    exit;
}

if (isset($_GET['beli'])) {
    $kid = (int)$_GET['beli'];

    $stmt = $pdo->prepare("SELECT * FROM keranjang WHERE id = ? AND id_user = ?");
    $stmt->execute([$kid, $id_user]);
    $kitem = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($kitem) {

        // --- Validasi stok ulang sebelum checkout ---
        $stok_max = getStokMax($pdo, (int)$kitem['id_produk'], $kitem['ukuran']);
        if ((int)$kitem['qty'] > $stok_max) {
            $_SESSION['error_keranjang'] = "Stok tidak mencukupi untuk " . $kitem['nama_produk'] . ". Silakan sesuaikan qty-nya dulu.";
            header("Location: keranjang.php");
            exit;
        }

        $no_telp = trim($user['no_telepon'] ?? '');
        $wilayah = trim($user['wilayah'] ?? '');
        $alamat  = trim($user['alamat'] ?? '');

        if (empty($no_telp) || empty($wilayah) || empty($alamat)) {
            $_SESSION['peringatan_profil']     = "Lengkapi nomor HP, wilayah, dan alamat kamu dulu sebelum memesan.";
            $_SESSION['redirect_after_profil'] = "keranjang.php";
            header("Location: profile.php?peringatan=1");
            exit;
        }

        $kode_order = uniqid('ORD-', true);

        $nama_order = $kitem['nama_produk'];
        if (!empty($kitem['ukuran']) && $kitem['ukuran'] !== '-') {
            $nama_order .= " - Size " . $kitem['ukuran'];
        }
        $total_harga = (float)$kitem['harga'] * (int)$kitem['qty'];

        $stmt = $pdo->prepare("INSERT INTO orders (id_user, kode_order, nama_produk, id_produk, qty, harga, total_harga, nama_penerima, email, tanggal_order, status)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'pending_payment')");

        $stmt->execute([
            $id_user,
            $kode_order,
            $nama_order,
            $kitem['id_produk'],
            $kitem['qty'],
            $kitem['harga'],
            $total_harga,
            $user['nama_panggilan'] ?? '',
            $user_email
        ]);

        $id_order = $pdo->lastInsertId();

        $stmt = $pdo->prepare("DELETE FROM keranjang WHERE id = ? AND id_user = ?");
        $stmt->execute([$kid, $id_user]);

        header("Location: payment.php?id=" . $id_order);
        exit;
    }

    header("Location: keranjang.php");
    exit;
}

header("Location: keranjang.php");
exit;