<?php
require 'auth_check.php';

$stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE status='pending_payment' OR status='pending'");
$pending_orders = $stmt->fetchColumn() ?? 0;

$success = '';   
$errors  = [];   

$nama_produk = '';
$harga       = '';
$kategori    = '';
$deskripsi   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nama_produk = trim($_POST['nama_produk'] ?? '');
    $harga       = trim($_POST['harga'] ?? '');
    $kategori    = trim($_POST['kategori'] ?? '');
    $deskripsi   = trim($_POST['deskripsi'] ?? '');

    if ($nama_produk === '') {
        $errors['nama_produk'] = 'Nama produk wajib diisi.';
    }

    if ($harga === '') {
        $errors['harga'] = 'Harga wajib diisi.';
    } elseif (!is_numeric($harga) || (int)$harga <= 0) {
        $errors['harga'] = 'Harga harus berupa angka lebih dari 0.';
    }

    if ($kategori === '') {
        $errors['kategori'] = 'Kategori wajib dipilih.';
    }

    if ($deskripsi === '') {
        $errors['deskripsi'] = 'Deskripsi wajib diisi.';
    }

    if (empty($_FILES['gambar']['name'])) {
        $errors['gambar'] = 'Gambar produk wajib diupload.';
    } else {
        $format_diizinkan = ['image/jpeg', 'image/png', 'image/webp'];
        $format_file      = mime_content_type($_FILES['gambar']['tmp_name']);

        if (!in_array($format_file, $format_diizinkan)) {
            $errors['gambar'] = 'Format gambar tidak didukung (gunakan JPG/PNG/WEBP).';
        } elseif ($_FILES['gambar']['size'] > 3 * 1024 * 1024) { // 3MB
            $errors['gambar'] = 'Ukuran gambar maksimal 3MB.';
        }
    }

    if (empty($errors)) {
        $map_ekstensi  = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $ekstensi_file = $map_ekstensi[$format_file];
        $nama_file     = 'produk_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ekstensi_file;
        $folder_upload = __DIR__ . '/../asset/';

        if (!is_dir($folder_upload)) {
            mkdir($folder_upload, 0755, true);
        }

        if (!move_uploaded_file($_FILES['gambar']['tmp_name'], $folder_upload . $nama_file)) {
            $errors['gambar'] = 'Gagal mengupload gambar.';
        } else {

            $path_gambar = 'asset/' . $nama_file; 
            $stmt = $pdo->prepare(
                "INSERT INTO produk (nama_produk, harga, gambar, deskripsi, kategori) VALUES (?, ?, ?, ?, ?)"
            );
            $berhasil_simpan = $stmt->execute([$nama_produk, (int)$harga, $path_gambar, $deskripsi, $kategori]);

            if ($berhasil_simpan) {
                header("Location: produk.php?success=1");
                exit;
            } else {
                $errors['umum'] = 'Gagal menyimpan ke database.';
                @unlink($folder_upload . $nama_file);
            }
        }
    }
}

function has_error($field, $errors) {
    return isset($errors[$field]);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Produk — STARWAVE Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
</head>
<body>

<!-- ===================== SIDEBAR MENU ===================== -->
<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-name">STARWAVE</div>
        <div class="brand-label">Admin Panel</div>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section">Menu</div>
        <a class="nav-item" href="dashboard.php"><span class="icon">▤</span> Dashboard</a>
        <a class="nav-item" href="pesanan.php">
            <span class="icon">📦</span> Pesanan
            <?php if ($pending_orders > 0): ?>
                <span style="margin-left:auto;background:#ef4444;color:#fff;font-size:10px;padding:2px 7px;border-radius:10px;"><?= $pending_orders ?></span>
            <?php endif; ?>
        </a>
        <a class="nav-item active" href="produk.php"><span class="icon">👕</span> Produk</a>
        <a class="nav-item" href="pengguna.php"><span class="icon">👥</span> Pengguna</a>
        <a class="nav-item" href="ulasan.php"><span class="icon">⭐</span> Ulasan</a>
        <div class="nav-section">Lainnya</div>
        <a class="nav-item" href="../index.php"><span class="icon">🌐</span> Lihat Toko</a>
    </nav>
    <div class="sidebar-footer">
        <div class="admin-badge">Login sebagai <span><?= htmlspecialchars($_SESSION['nama_admin'] ?? $_SESSION['admin']) ?></span></div>
        <a href="../masuk/logout.php" class="btn-logout">Keluar</a>
    </div>
</aside>

<!-- ===================== KONTEN UTAMA ===================== -->
<div class="main">
    <div class="topbar">
        <div class="topbar-title">TAMBAH PRODUK</div>
        <div class="topbar-right">
            <span>📅 <?= date('d M Y, H:i') ?> WITA</span>
        </div>
    </div>

    <div class="content">
        <div class="section">
            <div class="section-header">
                <div class="section-title">PRODUK BARU</div>
            </div>

            <div class="form-card">

                <!-- Notifikasi sukses -->
                <?php if ($success): ?>
                <div class="alert alert-success">
                    ✓ <?= htmlspecialchars($success) ?>
                    <a href="produk.php" style="margin-left:auto;color:inherit;font-size:12px;">← Lihat semua produk</a>
                </div>
                <?php endif; ?>

                <!-- Notifikasi error (daftar semua pesan error) -->
                <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    ✗ Mohon lengkapi bagian yang masih kosong:
                    <ul>
                        <?php foreach ($errors as $msg): ?>
                            <li><?= htmlspecialchars($msg) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <!-- ===================== FORM TAMBAH PRODUK ===================== -->
                <form method="POST" enctype="multipart/form-data" id="form-produk" novalidate>

                    <!-- Nama produk -->
                    <div class="form-group">
                        <label class="form-label" for="inp-nama">Nama Produk<span class="req">*</span></label>
                        <input class="form-input <?= has_error('nama_produk', $errors) ? 'input-error' : '' ?>"
                               id="inp-nama" name="nama_produk"
                               type="text" placeholder="cth. Kaos Oversize"
                               value="<?= htmlspecialchars($nama_produk) ?>"
                               maxlength="255" required>
                        <?php if (has_error('nama_produk', $errors)): ?>
                            <span class="field-error-msg"><?= htmlspecialchars($errors['nama_produk']) ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Harga -->
                    <div class="form-group">
                        <label class="form-label" for="inp-harga">Harga<span class="req">*</span></label>
                        <div class="input-prefix">
                            <span class="input-prefix-label">Rp</span>
                            <input class="form-input <?= has_error('harga', $errors) ? 'input-error' : '' ?>"
                                   id="inp-harga" name="harga"
                                   type="number" placeholder="150000"
                                   value="<?= htmlspecialchars($harga) ?>"
                                   min="1" required>
                        </div>
                        <?php if (has_error('harga', $errors)): ?>
                            <span class="field-error-msg"><?= htmlspecialchars($errors['harga']) ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Kategori -->
                    <div class="form-group">
                        <label class="form-label" for="inp-kategori">Kategori<span class="req">*</span></label>
                        <select class="form-select <?= has_error('kategori', $errors) ? 'input-error' : '' ?>"
                                id="inp-kategori" name="kategori" required>
                            <option value="" disabled <?= $kategori === '' ? 'selected' : '' ?>>— Pilih kategori —</option>
                            <?php foreach (['man','woman','Accessories'] as $k): ?>
                            <option value="<?= $k ?>" <?= $kategori === $k ? 'selected' : '' ?>><?= $k ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (has_error('kategori', $errors)): ?>
                            <span class="field-error-msg"><?= htmlspecialchars($errors['kategori']) ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Deskripsi -->
                    <div class="form-group">
                        <label class="form-label" for="inp-deskripsi">Deskripsi<span class="req">*</span></label>
                        <textarea class="form-textarea <?= has_error('deskripsi', $errors) ? 'input-error' : '' ?>"
                                  id="inp-deskripsi" name="deskripsi"
                                  placeholder="Deskripsi singkat produk..." rows="3" required><?= htmlspecialchars($deskripsi) ?></textarea>
                        <?php if (has_error('deskripsi', $errors)): ?>
                            <span class="field-error-msg"><?= htmlspecialchars($errors['deskripsi']) ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Upload gambar -->
                    <div class="form-group">
                        <label class="form-label">Gambar Produk<span class="req">*</span></label>
                        <div class="upload-area <?= has_error('gambar', $errors) ? 'input-error' : '' ?>" id="upload-area">
                            <input type="file" id="inp-gambar" name="gambar" accept="image/jpeg,image/png,image/webp" required>
                            <div id="upload-placeholder">
                                <div class="upload-icon">🖼️</div>
                                <div class="upload-text">
                                    <strong>Klik untuk upload</strong> atau drag & drop<br>
                                    <span style="font-size:11px;">JPG, PNG, WEBP · Maks 3MB</span>
                                </div>
                            </div>
                            <img id="img-preview" class="upload-preview" alt="Preview">
                        </div>
                        <?php if (has_error('gambar', $errors)): ?>
                            <span class="field-error-msg"><?= htmlspecialchars($errors['gambar']) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-actions">
                        <a href="produk.php" class="btn-kembali">← Kembali</a>
                        <button type="submit" class="btn-simpan">Simpan Produk</button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<script src="tambah_produk.js"></script>
</body>
</html>