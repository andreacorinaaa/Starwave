<?php
require 'auth_check.php';

$stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE status='pending_payment' OR status='pending'");
$pending_orders = $stmt->fetchColumn() ?? 0;

if (isset($_GET['hapus'])) {
    $id_ulasan = (int)$_GET['hapus'];
    $pdo->prepare("DELETE FROM ulasan WHERE id = ?")->execute([$id_ulasan]);
    header("Location: ulasan.php?deleted=1");
    exit;
}

$filter_bintang = isset($_GET['bintang']) ? (int)$_GET['bintang'] : 0;
$filter_produk  = isset($_GET['produk'])  ? (int)$_GET['produk']  : 0;

// Bangun kondisi WHERE secara dinamis sesuai filter yang aktif
$kondisi_where = "WHERE 1=1"; // "WHERE 1=1" supaya gampang nambah AND di belakang
$parameter     = [];

if ($filter_bintang) {
    $kondisi_where .= " AND u.bintang = ?";
    $parameter[] = $filter_bintang;
}
if ($filter_produk) {
    $kondisi_where .= " AND u.id_produk = ?";
    $parameter[] = $filter_produk;
}

$stmt = $pdo->prepare("
    SELECT u.*, us.nama_panggilan AS nama_user, p.nama_produk, p.gambar
    FROM ulasan u
    LEFT JOIN users  us ON u.id_user   = us.id_user
    LEFT JOIN produk p  ON u.id_produk = p.id
    $kondisi_where
    ORDER BY u.created_at DESC
");
$stmt->execute($parameter);
$ulasan_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_ulasan = $pdo->query("SELECT COUNT(*) FROM ulasan")->fetchColumn() ?? 0;
$avg_bintang  = $pdo->query("SELECT ROUND(AVG(bintang),1) FROM ulasan")->fetchColumn() ?? 0;

$produk_list = $pdo->query("SELECT id, nama_produk FROM produk ORDER BY nama_produk")->fetchAll(PDO::FETCH_ASSOC);

$jumlah_per_bintang = [];
for ($i = 1; $i <= 5; $i++) {
    $s = $pdo->prepare("SELECT COUNT(*) FROM ulasan WHERE bintang = ?");
    $s->execute([$i]);
    $jumlah_per_bintang[$i] = $s->fetchColumn() ?? 0;
}

function tampilkan_bintang($jumlah) {
    $jumlah = (int)$jumlah;
    return str_repeat('★', $jumlah) . str_repeat('☆', 5 - $jumlah);
}

function kelas_bintang($jumlah) {
    if ($jumlah >= 4) return 'done';
    if ($jumlah == 3) return 'process';
    return 'pending';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ulasan — STARWAVE Admin</title>
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
        <a class="nav-item" href="produk.php"><span class="icon">👕</span> Produk</a>
        <a class="nav-item" href="pengguna.php"><span class="icon">👥</span> Pengguna</a>
        <a class="nav-item active" href="ulasan.php"><span class="icon">⭐</span> Ulasan</a>
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
        <div class="topbar-title">ULASAN & RATING</div>
        <div class="topbar-right">
            <span>📅 <?= date('d M Y, H:i') ?> WIB</span>
        </div>
    </div>

    <div class="content">

        <!-- Notifikasi setelah berhasil hapus ulasan -->
        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert">✓ Ulasan berhasil dihapus.</div>
        <?php endif; ?>

        <!-- ===================== RINGKASAN STATISTIK (mini-stat) ===================== -->
        <div class="stat-row">
            <div class="mini-stat">
                <div class="label">Total Ulasan</div>
                <div class="value"><?= $total_ulasan ?></div>
            </div>
            <div class="mini-stat">
                <div class="label">Rata-rata Bintang</div>
                <div class="value gold"><?= $avg_bintang ?: '—' ?></div>
            </div>
            <div class="mini-stat">
                <div class="label">Bintang 5</div>
                <div class="value"><?= $jumlah_per_bintang[5] ?></div>
            </div>
        </div>

        <div class="section">
            <div class="section-header">
                <div class="section-title">SEMUA ULASAN</div>
                <div class="section-badge"><?= count($ulasan_list) ?> ulasan</div>
            </div>

            <!-- ===================== GRAFIK BATANG RATING (bintang 5 ke 1) ===================== -->
            <div class="rating-bar-wrap">
                <?php for ($i = 5; $i >= 1; $i--): ?>
                <div class="rating-bar-row">
                    <div class="rating-bar-label">
                        <span style="color:#f59e0b;"><?= str_repeat('★', $i) ?></span>
                    </div>
                    <div class="rating-bar-bg">
                        <?php
                            // Hitung persentase panjang bar = jumlah ulasan bintang ini / total ulasan
                            $persen = $total_ulasan ? round($jumlah_per_bintang[$i] / $total_ulasan * 100) : 0;
                        ?>
                        <div class="rating-bar-fill" style="width:<?= $persen ?>%"></div>
                    </div>
                    <div class="rating-bar-count"><?= $jumlah_per_bintang[$i] ?></div>
                </div>
                <?php endfor; ?>
            </div>

            <!-- ===================== FORM FILTER ===================== -->
            <div class="actions-bar">
                <form method="GET" class="filter-form">
                    <select name="bintang" class="filter-select">
                        <option value="">Semua Bintang</option>
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                        <option value="<?= $i ?>" <?= $filter_bintang == $i ? 'selected' : '' ?>>
                            <?= str_repeat('★', $i) ?> (<?= $i ?> bintang)
                        </option>
                        <?php endfor; ?>
                    </select>

                    <select name="produk" class="filter-select">
                        <option value="">Semua Produk</option>
                        <?php foreach ($produk_list as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= $filter_produk == $p['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['nama_produk']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit" class="btn-filter">Filter</button>
                    <a href="ulasan.php" class="btn-reset">Reset</a>
                </form>
            </div>

            <!-- ===================== DAFTAR ULASAN ===================== -->
            <?php if (empty($ulasan_list)): ?>

                <!-- Kalau tidak ada data ulasan sama sekali (atau hasil filter kosong) -->
                <div class="empty-ulasan">
                    <div class="icon">⭐</div>
                    Belum ada ulasan
                </div>

            <?php else: ?>

                <?php foreach ($ulasan_list as $u): ?>
                <div class="review-card">

                    <?php if ($u['gambar']): ?>
                        <img src="../<?= htmlspecialchars($u['gambar']) ?>" class="review-prod-img" onerror="this.src='../asset/posterutama.png'">
                    <?php else: ?>
                        <div class="review-prod-placeholder">👕</div>
                    <?php endif; ?>

                    <div class="review-body">
                        <!-- Baris info: nama user, badge bintang, nama produk, tanggal -->
                        <div class="review-meta">
                            <span class="review-user"><?= htmlspecialchars($u['nama_user'] ?? 'Pengguna') ?></span>
                            <span class="badge <?= kelas_bintang($u['bintang']) ?>" style="font-size:11px;"><?= $u['bintang'] ?>★</span>
                            <span class="review-prod">· <?= htmlspecialchars($u['nama_produk'] ?? '-') ?></span>
                            <span class="review-date"><?= date('d M Y, H:i', strtotime($u['created_at'])) ?></span>
                        </div>

                        <!-- Tampilan bintang penuh (★) dan kosong (★ abu-abu) -->
                        <div class="star-display">
                            <span class="star-gold"><?= str_repeat('★', (int)$u['bintang']) ?></span><span class="star-muted"><?= str_repeat('★', 5 - (int)$u['bintang']) ?></span>
                        </div>

                        <!-- Isi komentar, kalau kosong tampilkan teks default -->
                        <div class="review-comment <?= empty($u['komentar']) ? 'empty' : '' ?>">
                            <?= empty($u['komentar']) ? 'Tidak ada komentar.' : htmlspecialchars($u['komentar']) ?>
                        </div>
                    </div>

                    <!-- Tombol hapus, sambil tetap bawa filter yang sedang aktif -->
                    <?php
                        $link_hapus = '?hapus=' . $u['id'];
                        if ($filter_bintang) $link_hapus .= '&bintang=' . $filter_bintang;
                        if ($filter_produk)  $link_hapus .= '&produk=' . $filter_produk;
                        $nama_pengulas = $u['nama_user'] ?? 'Pengguna';
                    ?>
                    <a href="javascript:void(0)"
                       class="btn-hapus-ulasan"
                       data-link="<?= htmlspecialchars($link_hapus) ?>"
                       data-nama="<?= htmlspecialchars($nama_pengulas) ?>"
                       onclick="bukaModalHapusUlasan(this)">Hapus</a>

                </div>
                <?php endforeach; ?>

            <?php endif; ?>

        </div>
    </div>
</div>

<!-- ===================== MODAL KONFIRMASI HAPUS ULASAN ===================== -->
<div class="modal-hapus-backdrop" id="modalHapusUlasan">
    <div class="modal-hapus-box">
        <h3>HAPUS ULASAN?</h3>
        <p>Ulasan dari <span class="confirm-name" id="modalHapusUlasanNama"></span> akan dihapus permanen dan tidak bisa dikembalikan.</p>
        <div class="modal-hapus-actions">
            <button type="button" class="btn-hapus-tidak" onclick="tutupModalHapusUlasan()">Batal</button>
            <a href="javascript:void(0)" id="modalHapusUlasanLink" class="btn-hapus-ya" style="text-decoration:none; display:flex; align-items:center; justify-content:center;">Ya, Hapus</a>
        </div>
    </div>
</div>

<script src="produk.js"></script>

</body>
</html>