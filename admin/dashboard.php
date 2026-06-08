<?php
require 'auth_check.php';

// ── STATISTIK ──────────────────────────────────────────────────
$total_produk   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM produk"))['n']           ?? 0;
$total_users    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM users"))['n']            ?? 0;
$total_orders   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM orders"))['n']           ?? 0;
$pending_orders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM orders WHERE status='pending_payment' OR status='pending'"))['n'] ?? 0;

// ── UPDATE STATUS ORDER ─────────────────────────────────────────
$pesan = "";
if (isset($_POST['update_status'])) {
    $id_order  = (int)$_POST['id_order'];
    $new_status = mysqli_real_escape_string($conn, $_POST['status']);
    mysqli_query($conn, "UPDATE orders SET status='$new_status' WHERE id='$id_order'");
    $pesan = "Status pesanan #$id_order berhasil diperbarui.";
}

// ── HAPUS ORDER ─────────────────────────────────────────────────
if (isset($_GET['hapus_order'])) {
    $id = (int)$_GET['hapus_order'];
    mysqli_query($conn, "DELETE FROM orders WHERE id='$id'");
    header("Location: dashboard.php?deleted=1");
    exit;
}

// ── AMBIL DATA ──────────────────────────────────────────────────
$orders = mysqli_query($conn, "
    SELECT o.*, u.nama_panggilan AS nama, u.no_telepon
    FROM orders o
    LEFT JOIN users u ON o.id_user = u.id_user
    ORDER BY o.created_at DESC
    LIMIT 50
");

$users = mysqli_query($conn, "SELECT * FROM users ORDER BY id_user DESC LIMIT 10");
$produk_list = mysqli_query($conn, "SELECT * FROM produk ORDER BY created_at DESC");

// ── STATUS WARNA ────────────────────────────────────────────────
function statusClass($s) {
    return match($s) {
        'selesai'         => 'done',
        'diproses'        => 'process',
        'dikirim'         => 'ship',
        'pending_payment',
        'pending'         => 'pending',
        'batal'           => 'cancel',
        default           => 'pending'
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
    <title>Dashboard Admin — STARWAVE</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;1,300&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --bg:        #0f0f1a;
            --surface:   #0f0f1a;
            --surface2:  #141425;
            --border:    #1c1c30;
            --accent:    #b8dded;
            --accent2:   #7cb9d6;
            --accent3:   #4a9cbf;
            --text:      #dde2ee;
            --muted:     #4a4a6a;
            --sidebar-w: 240px;

            --status-done:    #34d399;
            --status-process: #fbbf24;
            --status-ship:    #60a5fa;
            --status-pending: #f87171;
            --status-cancel:  #9ca3af;
        }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            min-height: 100vh;
            display: flex;
        }

        /* ── SIDEBAR ── */
        .sidebar {
            width: var(--sidebar-w);
            background: var(--surface);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0; bottom: 0;
            z-index: 100;
        }

        .sidebar-brand {
            padding: 32px 24px 24px;
            border-bottom: 1px solid var(--border);
        }

        .sidebar-brand .brand-name {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 28px;
            letter-spacing: 5px;
            color: var(--accent);
        }

        .sidebar-brand .brand-label {
            font-size: 10px;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: var(--muted);
            margin-top: 2px;
        }

        .sidebar-nav {
            flex: 1;
            padding: 24px 0;
        }

        .nav-section {
            font-size: 10px;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: var(--muted);
            padding: 0 24px;
            margin-bottom: 8px;
            margin-top: 20px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 24px;
            color: var(--muted);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
            border-left: 2px solid transparent;
            cursor: pointer;
        }

        .nav-item:hover, .nav-item.active {
            color: var(--text);
            background: rgba(184,221,237,0.05);
            border-left-color: var(--accent);
        }

        .nav-item .icon {
            font-size: 16px;
            width: 20px;
            text-align: center;
        }

        .sidebar-footer {
            padding: 20px 24px;
            border-top: 1px solid var(--border);
        }

        .admin-badge {
            font-size: 12px;
            color: var(--muted);
            margin-bottom: 12px;
        }

        .admin-badge span {
            color: var(--accent);
            font-weight: 600;
        }

        .btn-logout {
            display: block;
            text-align: center;
            background: rgba(248,113,113,0.1);
            border: 1px solid rgba(248,113,113,0.2);
            color: #f87171;
            padding: 10px;
            border-radius: 2px;
            text-decoration: none;
            font-size: 12px;
            letter-spacing: 2px;
            text-transform: uppercase;
            transition: all 0.2s;
        }

        .btn-logout:hover {
            background: rgba(248,113,113,0.2);
        }

        /* ── MAIN ── */
        .main {
            margin-left: var(--sidebar-w);
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* ── TOPBAR ── */
        .topbar {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 0 36px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .topbar-title {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 22px;
            letter-spacing: 3px;
            color: var(--accent);
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 16px;
            font-size: 13px;
            color: var(--muted);
        }

        .topbar-right a {
            color: var(--accent2);
            text-decoration: none;
            font-size: 13px;
        }

        /* ── CONTENT ── */
        .content {
            padding: 36px;
        }

        /* ── ALERT ── */
        .alert {
            background: rgba(52, 211, 153, 0.1);
            border: 1px solid rgba(52, 211, 153, 0.3);
            color: #34d399;
            padding: 14px 18px;
            border-radius: 2px;
            font-size: 14px;
            margin-bottom: 28px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* ── STAT CARDS ── */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 36px;
        }

        .stat-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 4px;
            padding: 24px;
            position: relative;
            overflow: hidden;
            transition: border-color 0.2s;
        }

        .stat-card:hover { border-color: var(--accent3); }

        .stat-card::after {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 2px;
        }

        .stat-card.blue::after  { background: var(--accent); }
        .stat-card.gold::after  { background: #fbbf24; }
        .stat-card.green::after { background: #34d399; }
        .stat-card.red::after   { background: #f87171; }

        .stat-label {
            font-size: 10px;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 12px;
        }

        .stat-number {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 48px;
            line-height: 1;
            color: var(--text);
            letter-spacing: 2px;
        }

        .stat-icon {
            position: absolute;
            bottom: 16px;
            right: 20px;
            font-size: 36px;
            opacity: 0.08;
        }

        /* ── SECTIONS ── */
        .section {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 4px;
            margin-bottom: 28px;
            overflow: hidden;
        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 24px;
            border-bottom: 1px solid var(--border);
        }

        .section-title {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 18px;
            letter-spacing: 3px;
            color: var(--accent);
        }

        .section-badge {
            font-size: 11px;
            background: rgba(184,221,237,0.1);
            color: var(--accent2);
            padding: 4px 10px;
            border-radius: 20px;
            letter-spacing: 1px;
        }

        /* ── TABLE ── */
        .table-wrap { overflow-x: auto; }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13.5px;
        }

        thead th {
            background: var(--surface2);
            padding: 12px 20px;
            text-align: left;
            font-size: 10px;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--muted);
            font-weight: 600;
            white-space: nowrap;
        }

        tbody tr {
            border-top: 1px solid var(--border);
            transition: background 0.15s;
        }

        tbody tr:hover { background: rgba(184,221,237,0.03); }

        td {
            padding: 14px 20px;
            color: var(--text);
            vertical-align: middle;
        }

        .order-id {
            font-family: 'DM Sans', sans-serif;
            font-weight: 600;
            color: var(--muted);
            font-size: 12px;
        }

        /* ── STATUS BADGES ── */
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .badge.done    { background: rgba(52,211,153,0.12); color: var(--status-done); }
        .badge.process { background: rgba(251,191,36,0.12); color: var(--status-process); }
        .badge.ship    { background: rgba(96,165,250,0.12); color: var(--status-ship); }
        .badge.pending { background: rgba(248,113,113,0.12); color: var(--status-pending); }
        .badge.cancel  { background: rgba(156,163,175,0.12); color: var(--status-cancel); }

        /* ── SELECT STATUS ── */
        .select-status {
            background: var(--surface2);
            border: 1px solid var(--border);
            color: var(--text);
            padding: 6px 10px;
            font-size: 12px;
            border-radius: 2px;
            font-family: 'DM Sans', sans-serif;
            cursor: pointer;
            outline: none;
        }

        .select-status:focus { border-color: var(--accent); }

        .btn-save {
            background: var(--accent);
            color: #080810;
            border: none;
            padding: 7px 14px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1px;
            cursor: pointer;
            border-radius: 2px;
            font-family: 'DM Sans', sans-serif;
            transition: background 0.2s;
        }

        .btn-save:hover { background: var(--accent2); }

        .btn-hapus-order {
            color: #f87171;
            font-size: 12px;
            text-decoration: none;
            padding: 6px 10px;
            border: 1px solid rgba(248,113,113,0.2);
            border-radius: 2px;
            transition: all 0.2s;
            white-space: nowrap;
        }

        .btn-hapus-order:hover {
            background: rgba(248,113,113,0.1);
        }

        /* ── PRODUK GRID ── */
        .produk-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 16px;
            padding: 20px 24px;
        }

        .produk-card {
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 4px;
            overflow: hidden;
            transition: border-color 0.2s;
        }

        .produk-card:hover { border-color: var(--accent3); }

        .produk-card img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            display: block;
        }

        .produk-info {
            padding: 12px;
        }

        .produk-name {
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .produk-cat {
            font-size: 11px;
            color: var(--muted);
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        /* ── USERS TABLE ── */
        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--accent3);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 700;
            color: #080810;
            text-transform: uppercase;
        }

        .user-cell {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* ── EMPTY ── */
        .empty-row td {
            text-align: center;
            padding: 48px;
            color: var(--muted);
            font-size: 14px;
        }

        /* ── TAB NAVIGATION ── */
        .tab-nav {
            display: flex;
            gap: 0;
            border-bottom: 1px solid var(--border);
        }

        .tab-btn {
            padding: 14px 24px;
            background: none;
            border: none;
            color: var(--muted);
            font-family: 'DM Sans', sans-serif;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all 0.2s;
            letter-spacing: 0.5px;
        }

        .tab-btn.active, .tab-btn:hover {
            color: var(--text);
            border-bottom-color: var(--accent);
        }

        .tab-content { display: none; }
        .tab-content.active { display: block; }

        /* ── QUICK ACTIONS ── */
        .actions-bar {
            display: flex;
            gap: 12px;
            padding: 16px 24px;
            border-bottom: 1px solid var(--border);
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 8px 16px;
            background: var(--surface2);
            border: 1px solid var(--border);
            color: var(--muted);
            font-size: 12px;
            cursor: pointer;
            border-radius: 2px;
            font-family: 'DM Sans', sans-serif;
            transition: all 0.2s;
        }

        .filter-btn:hover, .filter-btn.active {
            background: rgba(184,221,237,0.08);
            border-color: var(--accent);
            color: var(--text);
        }

        .search-input {
            background: var(--surface2);
            border: 1px solid var(--border);
            color: var(--text);
            padding: 8px 14px;
            font-size: 13px;
            border-radius: 2px;
            font-family: 'DM Sans', sans-serif;
            outline: none;
            width: 200px;
            transition: border-color 0.2s;
        }

        .search-input:focus { border-color: var(--accent); }
        .search-input::placeholder { color: var(--muted); }

        /* ── RESPONSIVE NOTE ── */
        @media (max-width: 1100px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>

<!-- ══════════════════════════════════
     SIDEBAR
══════════════════════════════════ -->
<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-name">STARWAVE</div>
        <div class="brand-label">Admin Panel</div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section">Menu</div>
        <a class="nav-item active" onclick="showTab('dashboard')">
            <span class="icon">▤</span> Dashboard
        </a>
        <a class="nav-item" onclick="showTab('orders')">
            <span class="icon">📦</span> Pesanan
            <?php if ($pending_orders > 0): ?>
                <span style="margin-left:auto;background:#f87171;color:#fff;font-size:10px;padding:2px 7px;border-radius:10px;"><?= $pending_orders ?></span>
            <?php endif; ?>
        </a>
        <a class="nav-item" onclick="showTab('produk')">
            <span class="icon">👕</span> Produk
        </a>
        <a class="nav-item" onclick="showTab('users')">
            <span class="icon">👥</span> Pengguna
        </a>

        <div class="nav-section">Lainnya</div>
        <a class="nav-item" href="../index.php" target="_blank">
            <span class="icon">🌐</span> Lihat Toko
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="admin-badge">Login sebagai <span><?= $_SESSION['admin'] ?></span></div>
        <a href="../masuk/logout.php" class="btn-logout">Keluar</a>
    </div>
</aside>

<!-- ══════════════════════════════════
     MAIN
══════════════════════════════════ -->
<div class="main">
    <div class="topbar">
        <div class="topbar-title" id="topbar-label">DASHBOARD</div>
        <div class="topbar-right">
            <span>📅 <?= date('d M Y, H:i') ?> WIB</span>
            <a href="../index.php" target="_blank">↗ Toko</a>
        </div>
    </div>

    <div class="content">

        <?php if ($pesan): ?>
            <div class="alert">✓ <?= htmlspecialchars($pesan) ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert">✓ Pesanan berhasil dihapus.</div>
        <?php endif; ?>

        <!-- ══════════════════
             TAB: DASHBOARD
        ══════════════════ -->
        <div class="tab-content active" id="tab-dashboard">

            <!-- STAT CARDS -->
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

            <!-- PESANAN TERBARU (preview 10) -->
            <div class="section">
                <div class="section-header">
                    <div class="section-title">PESANAN TERBARU</div>
                    <a onclick="showTab('orders')" style="color:var(--accent2);font-size:13px;cursor:pointer;">Lihat semua →</a>
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

        </div><!-- /tab-dashboard -->

        <!-- ══════════════════
             TAB: ORDERS
        ══════════════════ -->
        <div class="tab-content" id="tab-orders">
            <div class="section">
                <div class="section-header">
                    <div class="section-title">MANAJEMEN PESANAN</div>
                    <div class="section-badge"><?= $total_orders ?> total</div>
                </div>

                <div class="actions-bar">
                    <input type="text" class="search-input" id="search-orders" placeholder="Cari produk / pemesan..." oninput="filterOrders()">
                    <button class="filter-btn active" onclick="filterStatus('semua', this)">Semua</button>
                    <button class="filter-btn" onclick="filterStatus('pending', this)">Pending</button>
                    <button class="filter-btn" onclick="filterStatus('diproses', this)">Diproses</button>
                    <button class="filter-btn" onclick="filterStatus('dikirim', this)">Dikirim</button>
                    <button class="filter-btn" onclick="filterStatus('selesai', this)">Selesai</button>
                    <button class="filter-btn" onclick="filterStatus('batal', this)">Dibatalkan</button>
                </div>

                <div class="table-wrap">
                    <table id="orders-table">
                        <thead>
                            <tr>
                                <th>#ID</th>
                                <th>Produk</th>
                                <th>Qty</th>
                                <th>Pemesan</th>
                                <th>Penerima</th>
                                <th>Tgl Order</th>
                                <th>Status</th>
                                <th>Ubah Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (mysqli_num_rows($orders) == 0): ?>
                            <tr class="empty-row"><td colspan="9">Belum ada pesanan</td></tr>
                        <?php else: while ($o = mysqli_fetch_assoc($orders)): ?>
                            <tr data-status="<?= $o['status'] ?>" data-search="<?= strtolower($o['nama_produk'] . ' ' . ($o['nama'] ?? '') . ' ' . $o['nama_penerima']) ?>">
                                <td class="order-id">#<?= $o['id'] ?></td>
                                <td><?= htmlspecialchars($o['nama_produk']) ?></td>
                                <td><?= $o['qty'] ?></td>
                                <td style="color:var(--muted);">
                                    <?= htmlspecialchars($o['nama'] ?? '-') ?>
                                    <?php if (!empty($o['no_telepon'])): ?>
                                        <br><span style="font-size:11px;"><?= $o['no_telepon'] ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($o['nama_penerima']) ?></td>
                                <td style="color:var(--muted);font-size:12px;white-space:nowrap;"><?= $o['tanggal_order'] ?></td>
                                <td><span class="badge <?= statusClass($o['status']) ?>"><?= statusLabel($o['status']) ?></span></td>
                                <td>
                                    <form method="POST" style="display:flex;gap:6px;align-items:center;">
                                        <input type="hidden" name="id_order" value="<?= $o['id'] ?>">
                                        <select name="status" class="select-status">
                                            <option value="pending_payment"<?= $o['status']=='pending_payment'?' selected':'' ?>>Belum Bayar</option>
                                            <option value="pending"<?= $o['status']=='pending'?' selected':'' ?>>Pending</option>
                                            <option value="diproses"<?= $o['status']=='diproses'?' selected':'' ?>>Diproses</option>
                                            <option value="dikirim"<?= $o['status']=='dikirim'?' selected':'' ?>>Dikirim</option>
                                            <option value="selesai"<?= $o['status']=='selesai'?' selected':'' ?>>Selesai</option>
                                            <option value="batal"<?= $o['status']=='batal'?' selected':'' ?>>Dibatalkan</option>
                                        </select>
                                        <button type="submit" name="update_status" class="btn-save">✓</button>
                                    </form>
                                </td>
                                <td>
                                    <a href="?hapus_order=<?= $o['id'] ?>" class="btn-hapus-order"
                                       onclick="return confirm('Hapus pesanan #<?= $o['id'] ?>?')">Hapus</a>
                                </td>
                            </tr>
                        <?php endwhile; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div><!-- /tab-orders -->

        <!-- ══════════════════
             TAB: PRODUK
        ══════════════════ -->
        <div class="tab-content" id="tab-produk">
            <div class="section">
                <div class="section-header">
                    <div class="section-title">DAFTAR PRODUK</div>
                    <div class="section-badge"><?= $total_produk ?> produk</div>
                </div>

                <?php if (mysqli_num_rows($produk_list) == 0): ?>
                    <div style="text-align:center;padding:60px;color:var(--muted);">
                        Belum ada produk di database.
                    </div>
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
                            <div style="font-size:13px;color:var(--accent);margin-top:6px;">Rp <?= number_format($p['harga'],0,',','.') ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                <?php endif; ?>

            </div>
        </div><!-- /tab-produk -->

        <!-- ══════════════════
             TAB: USERS
        ══════════════════ -->
        <div class="tab-content" id="tab-users">
            <div class="section">
                <div class="section-header">
                    <div class="section-title">DAFTAR PENGGUNA</div>
                    <div class="section-badge"><?= $total_users ?> pengguna</div>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Telepon</th>
                                <th>Alamat</th>
                                <th>Jenis Kelamin</th>
                                <th>Total Order</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $all_users = mysqli_query($conn, "
                            SELECT u.*, COUNT(o.id) as total_order
                            FROM users u
                            LEFT JOIN orders o ON u.id_user = o.id_user
                            GROUP BY u.id_user
                            ORDER BY u.id_user DESC
                        ");
                        if (mysqli_num_rows($all_users) == 0): ?>
                            <tr class="empty-row"><td colspan="7">Belum ada pengguna terdaftar</td></tr>
                        <?php else: while ($u = mysqli_fetch_assoc($all_users)): ?>
                            <tr>
                                <td class="order-id">#<?= $u['id_user'] ?></td>
                                <td>
                                    <div class="user-cell">
                                        <div class="user-avatar"><?= mb_substr($u['nama'], 0, 1) ?></div>
                                        <div>
                                            <div style="font-weight:600;"><?= htmlspecialchars($u['nama']) ?></div>
                                            <?php if (!empty($u['nama_panggilan'])): ?>
                                            <div style="font-size:11px;color:var(--muted);"><?= htmlspecialchars($u['nama_panggilan']) ?></div>
                                            <?php endif; ?>
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
                        <?php endwhile; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div><!-- /tab-users -->

    </div><!-- /content -->
</div><!-- /main -->

<!-- ══════════════════════════════════
     JAVASCRIPT
══════════════════════════════════ -->
<script>
// Tab navigation
const tabLabels = {
    'dashboard': 'DASHBOARD',
    'orders': 'MANAJEMEN PESANAN',
    'produk': 'DAFTAR PRODUK',
    'users': 'DAFTAR PENGGUNA'
};

function showTab(name) {
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
    document.getElementById('topbar-label').textContent = tabLabels[name] || name.toUpperCase();

    const items = document.querySelectorAll('.nav-item');
    const map = { dashboard: 0, orders: 1, produk: 2, users: 3 };
    if (map[name] !== undefined) items[map[name]].classList.add('active');
}

// Filter orders by status
let currentStatus = 'semua';

function filterStatus(status, btn) {
    currentStatus = status;
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    applyFilter();
}

function filterOrders() {
    applyFilter();
}

function applyFilter() {
    const q = document.getElementById('search-orders').value.toLowerCase();
    document.querySelectorAll('#orders-table tbody tr').forEach(row => {
        const rowStatus = row.getAttribute('data-status') || '';
        const rowSearch = row.getAttribute('data-search') || '';

        const statusMatch = currentStatus === 'semua'
            || rowStatus === currentStatus
            || (currentStatus === 'pending' && (rowStatus === 'pending' || rowStatus === 'pending_payment'));

        const searchMatch = q === '' || rowSearch.includes(q);

        row.style.display = (statusMatch && searchMatch) ? '' : 'none';
    });
}
</script>
</body>
</html>
