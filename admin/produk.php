<?php
require 'auth_check.php';

$pending_orders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM orders WHERE status='pending_payment' OR status='pending'"))['n'] ?? 0;
$total_produk   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM produk"))['n'] ?? 0;
$produk_list    = mysqli_query($conn, "SELECT * FROM produk ORDER BY created_at DESC");
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
        <div class="topbar-title">DAFTAR PRODUK</div>
        <div class="topbar-right">
            <span>📅 <?= date('d M Y, H:i') ?> WIB</span>
            <a href="../index.php" target="_blank">↗ Toko</a>
        </div>
    </div>

    <div class="content">
        <div class="section">
            <div class="section-header">
                <div class="section-title">SEMUA PRODUK</div>
                <div class="section-badge"><?= $total_produk ?> produk</div>
            </div>
            <?php if (mysqli_num_rows($produk_list) == 0): ?>
                <div style="text-align:center;padding:60px;color:var(--muted);">Belum ada produk di database.</div>
            <?php else: ?>
            <div class="produk-grid">
                <?php while ($p = mysqli_fetch_assoc($produk_list)): ?>
                <div class="produk-card">
                    <img src="../<?= htmlspecialchars($p['gambar'] ?? 'asset/posterutama.png') ?>"
                         alt="<?= htmlspecialchars($p['nama_produk'] ?? $p['nama'] ?? '') ?>"
                         onerror="this.src='../asset/posterutama.png'">
                    <div class="produk-info">
                        <div class="produk-name"><?= htmlspecialchars($p['nama_produk'] ?? $p['nama'] ?? 'Produk') ?></div>
                        <div class="produk-cat"><?= htmlspecialchars($p['kategori'] ?? '') ?></div>
                        <?php if (!empty($p['harga'])): ?>
                        <div style="font-size:13px;color:var(--accent);margin-top:6px;font-weight:600;">
                            Rp <?= number_format($p['harga'],0,',','.') ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
