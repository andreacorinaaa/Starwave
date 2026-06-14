<?php
require 'auth_check.php';

$pending_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status='pending_payment' OR status='pending'")->fetchColumn();
$total_users    = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

$stmt     = $pdo->query("
    SELECT u.*, COUNT(o.id) as total_order
    FROM users u
    LEFT JOIN orders o ON u.id_user = o.id_user
    GROUP BY u.id_user
    ORDER BY u.id_user DESC
");
$all_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengguna — STARWAVE Admin</title>
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
        <a class="nav-item" href="produk.php"><span class="icon">👕</span> Produk</a>
        <a class="nav-item active" href="pengguna.php"><span class="icon">👥</span> Pengguna</a>
        <a class="nav-item" href="ulasan.php"><span class="icon">⭐</span> Ulasan</a>
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
        <div class="topbar-title">DAFTAR PENGGUNA</div>
        <div class="topbar-right">
            <span>📅 <?= date('d M Y, H:i') ?> WIB</span>
            <a href="../index.php" target="_blank">↗ Toko</a>
        </div>
    </div>

    <div class="content">
        <div class="section">
            <div class="section-header">
                <div class="section-title">SEMUA PENGGUNA</div>
                <div class="section-badge"><?= $total_users ?> pengguna</div>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th><th>Nama</th><th>Email</th><th>Telepon</th>
                            <th>Alamat</th><th>Jenis Kelamin</th><th>Total Order</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($all_users)): ?>
                        <tr class="empty-row"><td colspan="7">Belum ada pengguna terdaftar</td></tr>
                    <?php else: foreach ($all_users as $u): ?>
                        <?php $nama = $u['nama_panggilan'] ?? ''; ?>
                        <tr>
                            <td class="order-id">#<?= $u['id_user'] ?></td>
                            <td>
                                <div class="user-cell">
    <div class="user-avatar" style="overflow:hidden; padding:0;">
        <?php if (!empty($u['foto_profil']) && file_exists('../' . $u['foto_profil'])): ?>
            <img src="../<?= htmlspecialchars($u['foto_profil']) ?>"
                 style="width:100%; height:100%; object-fit:cover; border-radius:50%;">
        <?php else: ?>
            <?= $nama !== '' ? mb_strtoupper(mb_substr($nama, 0, 1)) : '?' ?>
        <?php endif; ?>
    </div>
    <div>
        <div style="font-weight:600;">
            <?= $nama !== '' ? htmlspecialchars($nama) : '<span style="color:var(--muted);font-style:italic;">—</span>' ?>
        </div>
        <div style="font-size:11px;color:var(--muted);">
            <?= htmlspecialchars($u['email']) ?>
        </div>
    </div>
</div>
                            </td>
                            <td style="color:var(--muted);font-size:13px;"><?= htmlspecialchars($u['email']) ?></td>
                            <td style="font-size:13px;"><?= htmlspecialchars($u['no_telepon'] ?? '-') ?></td>
                            <td style="font-size:12px;color:var(--muted);max-width:160px;"><?= htmlspecialchars($u['alamat'] ?? '-') ?></td>
                            <td style="font-size:13px;"><?= htmlspecialchars($u['jenis_kelamin'] ?? '-') ?></td>
                            <td>
                                <span class="badge <?= $u['total_order'] > 0 ? 'done' : 'cancel' ?>">
                                    <?= $u['total_order'] ?> pesanan
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>