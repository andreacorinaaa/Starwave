<?php
require 'auth_check.php';

$total_produk   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM produk"))['n']  ?? 0;
$total_users    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM users"))['n']   ?? 0;
$total_orders   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM orders"))['n'] ?? 0;
$pending_orders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM orders WHERE status='pending_payment' OR status='pending'"))['n'] ?? 0;

function statusClass($s) {
    return match($s) {
        'selesai'                    => 'done',
        'diproses'                   => 'process',
        'dikirim'                    => 'ship',
        'pending_payment', 'pending' => 'pending',
        'batal'                      => 'cancel',
        default                      => 'pending'
    };
}
function statusLabel($s) {
    return match($s) {
        'pending_payment' => 'Belum Bayar',
        'pending'         => 'Pending',
        'diproses'        => 'Diproses',
        'dikirim'         => 'Dikirim',
        'selesai'         => 'Selesai',
        'batal'           => 'Dibatalkan',
        default           => ucfirst($s)
    };
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
        <a class="nav-item active" href="dashboard.php"><span class="icon">▤</span> Dashboard</a>
        <a class="nav-item" href="pesanan.php">
            <span class="icon">📦</span> Pesanan
            <?php if ($pending_orders > 0): ?>
                <span style="margin-left:auto;background:#ef4444;color:#fff;font-size:10px;padding:2px 7px;border-radius:10px;"><?= $pending_orders ?></span>
            <?php endif; ?>
        </a>
        <a class="nav-item" href="produk.php"><span class="icon">👕</span> Produk</a>
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
        <div class="topbar-title">DASHBOARD</div>
        <div class="topbar-right">
            <span>📅 <?= date('d M Y, H:i') ?> WIB</span>
            <a href="../index.php" target="_blank">↗ Toko</a>
        </div>
    </div>

    <div class="content">

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

        <div class="section">
            <div class="section-header">
                <div class="section-title">PESANAN TERBARU</div>
                <a href="pesanan.php" style="color:var(--accent);font-size:13px;font-weight:500;text-decoration:none;">Lihat semua →</a>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>#ID</th><th>Produk</th><th>Pemesan</th><th>Tgl Order</th><th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $recent = mysqli_query($conn, "
                        SELECT o.*, u.nama_panggilan AS nama FROM orders o
                        LEFT JOIN users u ON o.id_user = u.id_user
                        ORDER BY o.created_at DESC LIMIT 8
                    ");
                    if (mysqli_num_rows($recent) == 0): ?>
                        <tr class="empty-row"><td colspan="5">Belum ada pesanan</td></tr>
                    <?php else: while ($r = mysqli_fetch_assoc($recent)): ?>
                        <tr>
                            <td class="order-id">#<?= $r['id'] ?></td>
                            <td><?= htmlspecialchars($r['nama_produk']) ?></td>
                            <td><?= htmlspecialchars($r['nama'] ?? $r['nama_penerima']) ?></td>
                            <td style="color:var(--muted);font-size:12px;"><?= $r['tanggal_order'] ?></td>
                            <td><span class="badge <?= statusClass($r['status']) ?>"><?= statusLabel($r['status']) ?></span></td>
                        </tr>
                    <?php endwhile; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
</body>
</html>
