<?php
require 'auth_check.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method tidak valid.']);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'create') {
    $nama_produk = trim($_POST['nama_produk'] ?? '');
    $harga       = (int)($_POST['harga'] ?? 0);
    $kategori    = trim($_POST['kategori'] ?? '');
    $deskripsi   = trim($_POST['deskripsi'] ?? '');

    if ($nama_produk === '') {
        echo json_encode(['success' => false, 'message' => 'Nama produk wajib diisi.']);
        exit;
    }
    if ($harga <= 0) {
        echo json_encode(['success' => false, 'message' => 'Harga harus lebih dari 0.']);
        exit;
    }

    // Upload gambar
    $gambar_path = '';
    if (!empty($_FILES['gambar']['name'])) {
        $allowed  = ['image/jpeg', 'image/png', 'image/webp'];
        $ftype    = mime_content_type($_FILES['gambar']['tmp_name']);
        if (!in_array($ftype, $allowed)) {
            echo json_encode(['success' => false, 'message' => 'Format gambar tidak didukung (gunakan JPG/PNG/WEBP).']);
            exit;
        }
        if ($_FILES['gambar']['size'] > 3 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'Ukuran gambar maksimal 3MB.']);
            exit;
        }

        $ext         = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
        $filename    = 'produk_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $upload_dir  = __DIR__ . '/../asset/produk/';

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        if (!move_uploaded_file($_FILES['gambar']['tmp_name'], $upload_dir . $filename)) {
            echo json_encode(['success' => false, 'message' => 'Gagal mengupload gambar.']);
            exit;
        }
        $gambar_path = 'asset/produk/' . $filename;
    }

    $stmt = $conn->prepare("INSERT INTO produk (nama_produk, harga, gambar, deskripsi, kategori) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sisss", $nama_produk, $harga, $gambar_path, $deskripsi, $kategori);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Produk berhasil ditambahkan!', 'id' => $stmt->insert_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan ke database: ' . $conn->error]);
    }
    $stmt->close();

} else {
    echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal.']);
}
exit;