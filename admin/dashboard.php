<?php
require 'auth_check.php';

// Hitung total produk
$total_produk = $pdo->query("SELECT COUNT(*) FROM produk")->fetchColumn();

// Hitung total pengguna
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

// Hitung total pesanan
$total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();

// Hitung pesanan yang belum dibayar / masih pending
$pending_orders = $pdo->query("
    SELECT COUNT(*) FROM orders 
    WHERE status = 'pending_payment' OR status = 'pending'
")->fetchColumn();

// Cek: ada pesanan pending atau tidak?
$ada_pending = $pending_orders > 0;

// Ambil 8 pesanan terbaru, sekalian nama pemesannya
$stmt = $pdo->query("
    SELECT o.*, u.nama_panggilan AS nama 
    FROM orders o
    LEFT JOIN users u ON o.id_user = u.id_user
    ORDER BY o.created_at DESC 
    LIMIT 8
");
$daftar_pesanan = $stmt->fetchAll(PDO::FETCH_ASSOC);

$kamus_class = [
    'selesai'         => 'done',
    'diproses'        => 'process',
    'dikirim'         => 'ship',
    'pending_payment' => 'pending',
    'pending'         => 'pending',
    'batal'           => 'cancel',
];

$kamus_label = [
    'pending_payment' => 'Belum Bayar',
    'pending'         => 'Pending',
    'diproses'        => 'Diproses',
    'dikirim'         => 'Dikirim',
    'selesai'         => 'Selesai',
    'batal'           => 'Dibatalkan',
];

// Fungsi: cari class CSS untuk status tertentu.
function ambilStatusClass($status, $kamus) {
    if (isset($kamus[$status])) {
        return $kamus[$status];
    }
    return 'pending';
}

// Fungsi: cari label (tulisan) untuk status tertentu.
function ambilStatusLabel($status, $kamus) {
    if (isset($kamus[$status])) {
        return $kamus[$status];
    }
    return ucfirst($status);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — STARWAVE Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-name">STARWAVE</div>
        <div class="brand-label">Admin Panel</div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section">Menu</div>

        <a class="nav-item active" href="dashboard.php">
            <span class="icon">▤</span> Dashboard
        </a>

        <a class="nav-item" href="pesanan.php">
            <span class="icon">📦</span> Pesanan
            <?php if ($ada_pending): ?>
                <span style="margin-left:auto;background:#ef4444;color:#fff;font-size:10px;padding:2px 7px;border-radius:10px;">
                    <?= $pending_orders ?>
                </span>
            <?php endif; ?>
        </a>

        <a class="nav-item" href="produk.php"><span class="icon">👕</span> Produk</a>
        <a class="nav-item" href="pengguna.php"><span class="icon">👥</span> Pengguna</a>
        <a class="nav-item" href="ulasan.php"><span class="icon">⭐</span> Ulasan</a>

        <div class="nav-section">Lainnya</div>
        <a class="nav-item" href="../index.php"><span class="icon">🌐</span> Lihat Toko</a>
    </nav>

    <div class="sidebar-footer">
        <div class="admin-badge">
            Login sebagai <span><?= htmlspecialchars($_SESSION['admin']) ?></span>
        </div>
        <a href="../masuk/logout.php" class="btn-logout">Keluar</a>
    </div>
</aside>

<div class="main">

    <div class="topbar">
        <div class="topbar-title">DASHBOARD</div>
        <div class="topbar-right">
            <span>📅 <?= date('d M Y, H:i') ?> WIB</span>
        </div>
    </div>

    <div class="content">

        <!-- Kartu-kartu angka ringkasan -->
        <div class="stats-grid">
            <div class="stat-card blue">
                <div class="stat-label">Total Produk</div>
                <div class="stat-number"><?= $total_produk ?></div>
                <div class="stat-icon">👕</div>
            </div>

            <div class="stat-card gold">
                <div class="stat-label">Total Pengguna</div>
                <div class="stat-number"><?= $total_users ?></div>
                <div class="stat-icon">👥</div>
            </div>

            <div class="stat-card green">
                <div class="stat-label">Total Pesanan</div>
                <div class="stat-number"><?= $total_orders ?></div>
                <div class="stat-icon">📦</div>
            </div>

            <div class="stat-card red">
                <div class="stat-label">Pesanan Pending</div>
                <div class="stat-number"><?= $pending_orders ?></div>
                <div class="stat-icon">⏳</div>
            </div>
        </div>

        <!-- Tabel pesanan terbaru -->
        <div class="section">
            <div class="section-header">
                <div class="section-title">PESANAN TERBARU</div>
                <a href="pesanan.php" style="color:var(--accent);font-size:13px;font-weight:500;text-decoration:none;">
                    Lihat semua →
                </a>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>#ID</th>
                            <th>Produk</th>
                            <th>Pemesan</th>
                            <th>Tgl Order</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($daftar_pesanan)): ?>

                        <tr class="empty-row">
                            <td colspan="5">Belum ada pesanan</td>
                        </tr>

                    <?php else: ?>
                        <?php foreach ($daftar_pesanan as $pesanan): ?>

                            <?php
                            // Siapkan dulu nilai-nilai yang dibutuhkan baris ini,
                            $nama_pemesan = $pesanan['nama'] ?? $pesanan['nama_penerima'];
                            $class_status = ambilStatusClass($pesanan['status'], $kamus_class);
                            $label_status = ambilStatusLabel($pesanan['status'], $kamus_label);
                            ?>

                            <tr>
                                <td class="order-id">#<?= $pesanan['id'] ?></td>
                                <td><?= htmlspecialchars($pesanan['nama_produk']) ?></td>
                                <td><?= htmlspecialchars($nama_pemesan) ?></td>
                                <td style="color:var(--muted);font-size:12px;">
                                    <?= htmlspecialchars($pesanan['tanggal_order']) ?>
                                </td>
                                <td>
                                    <span class="badge <?= $class_status ?>"><?= $label_status ?></span>
                                </td>
                            </tr>

                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
</body>
</html>