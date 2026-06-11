<?php
require 'auth_check.php';

$pending_orders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM orders WHERE status='pending_payment' OR status='pending'"))['n'] ?? 0;

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_produk = trim($_POST['nama_produk'] ?? '');
    $harga       = (int)($_POST['harga'] ?? 0);
    $kategori    = trim($_POST['kategori'] ?? '');
    $deskripsi   = trim($_POST['deskripsi'] ?? '');

    if ($nama_produk === '') {
        $error = 'Nama produk wajib diisi.';
    } elseif ($harga <= 0) {
        $error = 'Harga harus lebih dari 0.';
    } else {
        // Upload gambar
        $gambar_path = '';
        if (!empty($_FILES['gambar']['name'])) {
            $allowed = ['image/jpeg', 'image/png', 'image/webp'];
            $ftype   = mime_content_type($_FILES['gambar']['tmp_name']);

            if (!in_array($ftype, $allowed)) {
                $error = 'Format gambar tidak didukung (gunakan JPG/PNG/WEBP).';
            } elseif ($_FILES['gambar']['size'] > 3 * 1024 * 1024) {
                $error = 'Ukuran gambar maksimal 3MB.';
            } else {
                $ext        = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
                $filename   = 'produk_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $upload_dir = __DIR__ . '/../asset/';

                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

                if (!move_uploaded_file($_FILES['gambar']['tmp_name'], $upload_dir . $filename)) {
                    $error = 'Gagal mengupload gambar.';
                } else {
                    $gambar_path = 'asset/' . $filename;
                }
            }
        }

        if (!$error) {
            $stmt = $conn->prepare("INSERT INTO produk (nama_produk, harga, gambar, deskripsi, kategori) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sisss", $nama_produk, $harga, $gambar_path, $deskripsi, $kategori);

            if ($stmt->execute()) {
                $success = 'Produk berhasil ditambahkan!';
                // Kosongkan nilai form setelah berhasil
                $nama_produk = $harga = $kategori = $deskripsi = '';
            } else {
                $error = 'Gagal menyimpan ke database.';
            }
            $stmt->close();
        }
    }
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
    <style>
        .form-card {
            background: var(--surface, #1a1a1a);
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 12px;
            padding: 32px;
            max-width: 560px;
        }

        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            display: block;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--muted, #888);
            margin-bottom: 7px;
        }
        .form-label span.req { color: #ef4444; margin-left: 2px; }

        .form-input,
        .form-select,
        .form-textarea {
            width: 100%;
            background: rgba(255,255,255,.05);
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 8px;
            color: var(--text, #fff);
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            padding: 10px 14px;
            outline: none;
            transition: border-color .2s;
            box-sizing: border-box;
        }
        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            border-color: var(--accent, #e5c97e);
        }
        .form-textarea { resize: vertical; min-height: 90px; }
        .form-select option { background: #1a1a1a; }

        .input-prefix {
            display: flex;
            align-items: center;
            background: rgba(255,255,255,.05);
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 8px;
            overflow: hidden;
            transition: border-color .2s;
        }
        .input-prefix:focus-within { border-color: var(--accent, #e5c97e); }
        .input-prefix-label {
            padding: 10px 13px;
            font-size: 13px;
            color: var(--muted, #888);
            border-right: 1px solid rgba(255,255,255,.08);
            white-space: nowrap;
        }
        .input-prefix .form-input {
            border: none;
            border-radius: 0;
            background: transparent;
        }
        .input-prefix .form-input:focus { border-color: transparent; }

        /* Upload gambar */
        .upload-area {
            border: 2px dashed rgba(255,255,255,.15);
            border-radius: 8px;
            padding: 28px;
            text-align: center;
            cursor: pointer;
            transition: border-color .2s, background .2s;
            position: relative;
        }
        .upload-area:hover, .upload-area.dragover {
            border-color: var(--accent, #e5c97e);
            background: rgba(229,201,126,.04);
        }
        .upload-area input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
        }
        .upload-icon { font-size: 30px; margin-bottom: 8px; }
        .upload-text { font-size: 13px; color: var(--muted, #888); }
        .upload-text strong { color: var(--accent, #e5c97e); }
        .upload-preview {
            display: none;
            width: 100%;
            max-height: 180px;
            object-fit: cover;
            border-radius: 6px;
            margin-top: 12px;
        }

        /* Notif */
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-success {
            background: rgba(34,197,94,.1);
            border: 1px solid rgba(34,197,94,.3);
            color: #4ade80;
        }
        .alert-error {
            background: rgba(239,68,68,.1);
            border: 1px solid rgba(239,68,68,.3);
            color: #f87171;
        }

        /* Tombol aksi */
        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 28px;
        }
        .btn-simpan {
            padding: 11px 28px;
            background: var(--accent, #e5c97e);
            color: #111;
            border: none;
            border-radius: 8px;
            font-family: 'DM Sans', sans-serif;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: opacity .15s;
        }
        .btn-simpan:hover { opacity: .85; }
        .btn-kembali {
            padding: 11px 20px;
            background: none;
            color: var(--muted, #888);
            border: 1px solid rgba(255,255,255,.12);
            border-radius: 8px;
            font-family: 'DM Sans', sans-serif;
            font-size: 13px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: background .15s, color .15s;
        }
        .btn-kembali:hover { background: rgba(255,255,255,.06); color: #fff; }
    </style>
</head>
<body>

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
        <div class="nav-section">Lainnya</div>
        <a class="nav-item" href="../index.php" target="_blank"><span class="icon">🌐</span> Lihat Toko</a>
    </nav>
    <div class="sidebar-footer">
        <div class="admin-badge">Login sebagai <span><?= htmlspecialchars($_SESSION['admin']) ?></span></div>
        <a href="../masuk/logout.php" class="btn-logout">Keluar</a>
    </div>
</aside>

<div class="main">
    <div class="topbar">
        <div class="topbar-title">TAMBAH PRODUK</div>
        <div class="topbar-right">
            <span>📅 <?= date('d M Y, H:i') ?> WIB</span>
            <a href="../index.php" target="_blank">↗ Toko</a>
        </div>
    </div>

    <div class="content">
        <div class="section">
            <div class="section-header">
                <div class="section-title">PRODUK BARU</div>
            </div>

            <div class="form-card">

                <?php if ($success): ?>
                <div class="alert alert-success">
                    ✓ <?= htmlspecialchars($success) ?>
                    <a href="produk.php" style="margin-left:auto;color:inherit;font-size:12px;">← Lihat semua produk</a>
                </div>
                <?php endif; ?>

                <?php if ($error): ?>
                <div class="alert alert-error">✗ <?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">

                    <!-- Nama Produk -->
                    <div class="form-group">
                        <label class="form-label" for="inp-nama">
                            Nama Produk<span class="req">*</span>
                        </label>
                        <input class="form-input" id="inp-nama" name="nama_produk"
                               type="text"
                               placeholder="cth. Kaos Oversize STARWAVE"
                               value="<?= htmlspecialchars($nama_produk ?? '') ?>"
                               maxlength="255" required>
                    </div>

                    <!-- Harga -->
                    <div class="form-group">
                        <label class="form-label" for="inp-harga">
                            Harga<span class="req">*</span>
                        </label>
                        <div class="input-prefix">
                            <span class="input-prefix-label">Rp</span>
                            <input class="form-input" id="inp-harga" name="harga"
                                   type="number"
                                   placeholder="150000"
                                   value="<?= htmlspecialchars($harga ?? '') ?>"
                                   min="1" required>
                        </div>
                    </div>

                    <!-- Kategori -->
                    <div class="form-group">
                        <label class="form-label" for="inp-kategori">Kategori</label>
                        <select class="form-select" id="inp-kategori" name="kategori">
                            <option value="">— Pilih kategori —</option>
                            <?php
                            $kategori_list = ['man','woman','Accessories'];
                            foreach ($kategori_list as $k):
                            ?>
                            <option value="<?= $k ?>" <?= ($kategori ?? '') === $k ? 'selected' : '' ?>>
                                <?= $k ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Deskripsi -->
                    <div class="form-group">
                        <label class="form-label" for="inp-deskripsi">Deskripsi</label>
                        <textarea class="form-textarea" id="inp-deskripsi" name="deskripsi"
                                  placeholder="Deskripsi singkat produk..."
                                  rows="3"><?= htmlspecialchars($deskripsi ?? '') ?></textarea>
                    </div>

                    <!-- Gambar -->
                    <div class="form-group">
                        <label class="form-label">Gambar Produk</label>
                        <div class="upload-area" id="upload-area">
                            <input type="file" id="inp-gambar" name="gambar"
                                   accept="image/jpeg,image/png,image/webp">
                            <div id="upload-placeholder">
                                <div class="upload-icon">🖼️</div>
                                <div class="upload-text">
                                    <strong>Klik untuk upload</strong> atau drag & drop<br>
                                    <span style="font-size:11px;">JPG, PNG, WEBP · Maks 3MB</span>
                                </div>
                            </div>
                            <img id="img-preview" class="upload-preview" alt="Preview">
                        </div>
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

<script>
const inpGambar         = document.getElementById('inp-gambar');
const imgPreview        = document.getElementById('img-preview');
const uploadPlaceholder = document.getElementById('upload-placeholder');
const uploadArea        = document.getElementById('upload-area');

inpGambar.addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        imgPreview.src = e.target.result;
        imgPreview.style.display = 'block';
        uploadPlaceholder.style.display = 'none';
    };
    reader.readAsDataURL(file);
});

['dragover','dragleave','drop'].forEach(evt => {
    uploadArea.addEventListener(evt, e => e.preventDefault());
});
uploadArea.addEventListener('dragover',  () => uploadArea.classList.add('dragover'));
uploadArea.addEventListener('dragleave', () => uploadArea.classList.remove('dragover'));
uploadArea.addEventListener('drop', e => {
    uploadArea.classList.remove('dragover');
    const file = e.dataTransfer.files[0];
    if (file) {
        const dt = new DataTransfer();
        dt.items.add(file);
        inpGambar.files = dt.files;
        inpGambar.dispatchEvent(new Event('change'));
    }
});
</script>
</body>
</html>