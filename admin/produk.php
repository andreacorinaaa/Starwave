<?php
require 'auth_check.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    // -- AKSI: HAPUS PRODUK --
    if ($action === 'hapus') {
        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID tidak valid.']);
            exit;
        }

        // Ambil dulu nama file gambar produk (supaya bisa dihapus dari folder)
        $stmt = $pdo->prepare("SELECT gambar FROM produk WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            echo json_encode(['success' => false, 'message' => 'Produk tidak ditemukan.']);
            exit;
        }

        // Hapus baris produk dari database
        $stmt = $pdo->prepare("DELETE FROM produk WHERE id = ?");
        $berhasil = $stmt->execute([$id]);

        if ($berhasil) {
            // Kalau ada file gambar, hapus juga file fisiknya dari server
            if (!empty($row['gambar'])) {
                $path_file = __DIR__ . '/../' . $row['gambar'];
                if (file_exists($path_file)) {
                    unlink($path_file);
                }
            }
            echo json_encode(['success' => true, 'message' => 'Produk berhasil dihapus.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus produk.']);
        }
        exit;
    }

    // -- AKSI: UPDATE HARGA --
    if ($action === 'update_harga') {
        $id    = (int)($_POST['id'] ?? 0);
        $harga = (int)($_POST['harga'] ?? 0);

        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID tidak valid.']);
            exit;
        }
        if ($harga <= 0) {
            echo json_encode(['success' => false, 'message' => 'Harga harus lebih dari 0.']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE produk SET harga = ? WHERE id = ?");
        $berhasil = $stmt->execute([$harga, $id]);

        if ($berhasil) {
            echo json_encode(['success' => true, 'message' => 'Harga berhasil diperbarui.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal memperbarui harga.']);
        }
        exit;
    }

    // -- AKSI: UPDATE STOK --
    if ($action === 'update_stok') {
        $id     = (int)($_POST['id'] ?? 0);
        $ukuran = strtolower($_POST['ukuran'] ?? '');
        $jumlah = max(0, (int)($_POST['jumlah'] ?? 0)); // tidak boleh minus

        $ukuran_diizinkan = ['s', 'm', 'l', 'xl', 'xxl', 'accessories'];

        if ($id <= 0 || !in_array($ukuran, $ukuran_diizinkan)) {
            echo json_encode(['success' => false, 'message' => 'Data tidak valid.']);
            exit;
        }

        // Nama kolom di database.
        $nama_kolom = ($ukuran === 'accessories') ? 'stok' : "stok_$ukuran";

        $stmt = $pdo->prepare("UPDATE produk SET $nama_kolom = ? WHERE id = ?");
        $stmt->execute([$jumlah, $id]);

        echo json_encode(['success' => true, 'jumlah' => $jumlah]);
        exit;
    }

    // -- AKSI TIDAK DIKENALI --
    echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal.']);
    exit;
}

// Hitung jumlah pesanan yang masih pending (buat badge merah di sidebar)
$pending_orders = $pdo->query(
    "SELECT COUNT(*) FROM orders WHERE status='pending_payment' OR status='pending'"
)->fetchColumn();

// Hitung total produk yang ada
$total_produk = $pdo->query("SELECT COUNT(*) FROM produk")->fetchColumn();

// Ambil semua data produk, urutkan dari yang terbaru
$stmt        = $pdo->query("SELECT * FROM produk ORDER BY created_at DESC");
$produk_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produk — STARWAVE Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
</head>
<body>

<!-- ============================= SIDEBAR ============================= -->
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
                <span style="margin-left:auto;background:#ef4444;color:#fff;font-size:10px;padding:2px 7px;border-radius:10px;">
                    <?= $pending_orders ?>
                </span>
            <?php endif; ?>
        </a>
        <a class="nav-item active" href="produk.php"><span class="icon">👕</span> Produk</a>
        <a class="nav-item" href="pengguna.php"><span class="icon">👥</span> Pengguna</a>
        <a class="nav-item" href="ulasan.php"><span class="icon">⭐</span> Ulasan</a>
        <div class="nav-section">Lainnya</div>
        <a class="nav-item" href="../index.php"><span class="icon">🌐</span> Lihat Toko</a>
    </nav>
    <div class="sidebar-footer">
        <div class="admin-badge">Login sebagai <span><?= htmlspecialchars($_SESSION['admin']) ?></span></div>
        <a href="../masuk/logout.php" class="btn-logout">Keluar</a>
    </div>
</aside>

<div class="main">

    <div class="topbar">
        <div class="topbar-title">DAFTAR PRODUK</div>
        <div class="topbar-right">
            <span>📅 <?= date('d M Y, H:i') ?> WIB</span>
        </div>
    </div>

    <div class="content">
        <div class="section">

            <div class="section-header">
                <div style="display:flex;align-items:center;gap:12px;">
                    <div class="section-title">SEMUA PRODUK</div>
                    <div class="section-badge" id="badge-total"><?= $total_produk ?> produk</div>
                </div>
                <a href="tambah_produk.php" class="btn-tambah">
                    <span style="font-size:16px;line-height:1;">+</span> Tambah Produk
                </a>
            </div>

            <?php if (empty($produk_list)): ?>

                <div style="text-align:center;padding:60px;color:var(--muted);">
                    Belum ada produk di database.
                </div>

            <?php else: ?>

                <div class="produk-grid">
                    <?php foreach ($produk_list as $p): ?>

                        <!-- ====== SATU KARTU PRODUK ====== -->
                        <div class="produk-card" id="card-<?= $p['id'] ?>">

                            <img src="../<?= htmlspecialchars($p['gambar'] ?? 'asset/posterutama.png') ?>"
                                 alt="<?= htmlspecialchars($p['nama_produk'] ?? $p['nama'] ?? '') ?>"
                                 onerror="this.src='../asset/posterutama.png'">

                            <!-- Tombol edit & hapus di pojok kartu -->
                            <div class="card-actions">
                                <button class="btn-card btn-edit-harga" title="Edit Harga"
                                    onclick="openEditHarga(<?= $p['id'] ?>, '<?= htmlspecialchars($p['nama_produk'] ?? '', ENT_QUOTES) ?>', <?= (int)$p['harga'] ?>)">
                                    ✏️
                                </button>
                                <button class="btn-card btn-hapus-card" title="Hapus Produk"
                                    onclick="openHapus(<?= $p['id'] ?>, '<?= htmlspecialchars($p['nama_produk'] ?? '', ENT_QUOTES) ?>')">
                                    🗑️
                                </button>
                            </div>

                            <div class="produk-info">
                                <div class="produk-name"><?= htmlspecialchars($p['nama_produk'] ?? $p['nama'] ?? 'Produk') ?></div>
                                <div class="produk-cat"><?= htmlspecialchars($p['kategori'] ?? '') ?></div>

                                <?php if (!empty($p['harga'])): ?>
                                    <div class="produk-harga" id="harga-<?= $p['id'] ?>"
                                         style="font-size:13px;color:var(--accent);margin-top:6px;font-weight:600;">
                                        Rp <?= number_format($p['harga'], 0, ',', '.') ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (strtolower($p['kategori']) === 'accessories'): ?>

                                    <!-- ====== KATEGORI ACCESSORIES: STOK 1 ANGKA TOTAL, TANPA UKURAN ====== -->
                                    <div class="stok-sizes" style="margin-top:10px;">
                                        <?php
                                            $jumlah = (int)($p['stok'] ?? 0);
                                            $status = $jumlah > 0 ? 'ada' : 'habis';
                                        ?>
                                        <div class="stok-row">
                                            <span class="stok-label <?= $status ?>">Stok</span>

                                            <div class="stok-input-group">
                                                <button type="button" class="stok-dec"
                                                    onclick="ubahStok(<?= $p['id'] ?>, 'accessories', this, -1)">−</button>

                                                <input type="number" class="stok-angka"
                                                    id="stok-<?= $p['id'] ?>-accessories"
                                                    value="<?= $jumlah ?>" min="0"
                                                    onchange="simpanStok(<?= $p['id'] ?>, 'accessories', this)">

                                                <button type="button" class="stok-inc"
                                                    onclick="ubahStok(<?= $p['id'] ?>, 'accessories', this, 1)">+</button>
                                            </div>

                                            <span class="stok-badge <?= $status ?>"
                                                  id="badge-<?= $p['id'] ?>-accessories">
                                                <?= $jumlah > 0 ? $jumlah . ' pcs' : 'Habis' ?>
                                            </span>
                                        </div>
                                    </div>

                                <?php else: ?>

                                    <!-- ====== KATEGORI LAIN: STOK PER UKURAN S/M/L/XL/XXL ====== -->
                                    <div class="stok-sizes" style="margin-top:10px;">
                                        <?php foreach (['S', 'M', 'L', 'XL', 'XXL'] as $sz):
                                            $kolom  = 'stok_' . strtolower($sz);
                                            $jumlah = (int)($p[$kolom] ?? 0);
                                            $status = $jumlah > 0 ? 'ada' : 'habis';
                                        ?>
                                            <div class="stok-row">
                                                <span class="stok-label <?= $status ?>"><?= $sz ?></span>

                                                <div class="stok-input-group">
                                                    <button type="button" class="stok-dec"
                                                        onclick="ubahStok(<?= $p['id'] ?>, '<?= $sz ?>', this, -1)">−</button>

                                                    <input type="number" class="stok-angka"
                                                        id="stok-<?= $p['id'] ?>-<?= strtolower($sz) ?>"
                                                        value="<?= $jumlah ?>" min="0"
                                                        onchange="simpanStok(<?= $p['id'] ?>, '<?= $sz ?>', this)">

                                                    <button type="button" class="stok-inc"
                                                        onclick="ubahStok(<?= $p['id'] ?>, '<?= $sz ?>', this, 1)">+</button>
                                                </div>

                                                <span class="stok-badge <?= $status ?>"
                                                      id="badge-<?= $p['id'] ?>-<?= strtolower($sz) ?>">
                                                    <?= $jumlah > 0 ? $jumlah . ' pcs' : 'Habis' ?>
                                                </span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>

                                <?php endif; ?>

                            </div>
                        </div>

                    <?php endforeach; ?>
                </div>

            <?php endif; ?>

        </div>
    </div>
</div>

<!-- ============================= MODAL EDIT HARGA ============================= -->
<div class="modal-overlay" id="modal-edit" onclick="handleOverlay(event, 'modal-edit')">
    <div class="modal">
        <button class="modal-close" onclick="closeModal('modal-edit')">✕</button>
        <div class="modal-title">EDIT HARGA</div>
        <div class="modal-subtitle" id="edit-nama">—</div>

        <label class="form-label">Harga Baru<span style="color:#ef4444;margin-left:2px;">*</span></label>
        <div class="input-prefix">
            <span class="input-prefix-label">Rp</span>
            <input class="inp-harga" id="inp-harga-edit" type="number" min="1" placeholder="150000">
        </div>

        <div class="modal-actions">
            <button class="btn-cancel" onclick="closeModal('modal-edit')">Batal</button>
            <button class="btn-save" id="btn-save-harga" onclick="simpanHarga()">Simpan</button>
        </div>
    </div>
</div>

<!-- ============================= MODAL KONFIRMASI HAPUS ============================= -->
<div class="confirm-overlay" id="confirm-hapus" onclick="handleOverlay(event, 'confirm-hapus')">
    <div class="confirm-box">
        <div class="confirm-icon">🗑️</div>
        <div class="confirm-title">HAPUS PRODUK?</div>
        <div class="confirm-desc">
            Yakin ingin menghapus<br>
            <span class="confirm-name" id="hapus-nama">—</span>?<br>
            <span style="font-size:11px;color:#ef4444;">Tindakan ini tidak bisa dibatalkan.</span>
        </div>
        <div class="confirm-actions">
            <button class="btn-cancel" onclick="closeModal('confirm-hapus')">Batal</button>
            <button class="btn-hapus-confirm" id="btn-hapus-ok" onclick="eksekusiHapus()">Ya, Hapus</button>
        </div>
    </div>
</div>

<!-- Notifikasi kecil di pojok (toast) -->
<div class="toast" id="toast"></div>

<script src="produk.js"></script>
</body>
</html>