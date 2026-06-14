<?php
require 'auth_check.php';

$stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE status='pending_payment' OR status='pending'");
$pending_orders = $stmt->fetchColumn() ?? 0;

// ── HAPUS ULASAN ──
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $pdo->prepare("DELETE FROM ulasan WHERE id = ?")->execute([$id]);
    header("Location: ulasan.php?deleted=1");
    exit;
}

// ── FILTER ──
$filter_bintang = isset($_GET['bintang']) ? (int)$_GET['bintang'] : 0;
$filter_produk  = isset($_GET['produk'])  ? (int)$_GET['produk']  : 0;

$where  = "WHERE 1=1";
$params = [];
if ($filter_bintang) { $where .= " AND u.bintang = ?";   $params[] = $filter_bintang; }
if ($filter_produk)  { $where .= " AND u.id_produk = ?"; $params[] = $filter_produk; }

// ── DATA ──
$stmt = $pdo->prepare("
    SELECT u.*, us.nama_panggilan AS nama_user, p.nama_produk, p.gambar
    FROM ulasan u
    LEFT JOIN users  us ON u.id_user   = us.id_user
    LEFT JOIN produk p  ON u.id_produk = p.id
    $where
    ORDER BY u.created_at DESC
");
$stmt->execute($params);
$ulasan_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_ulasan = $pdo->query("SELECT COUNT(*) FROM ulasan")->fetchColumn() ?? 0;
$avg_bintang  = $pdo->query("SELECT ROUND(AVG(bintang),1) FROM ulasan")->fetchColumn() ?? 0;
$produk_list  = $pdo->query("SELECT id, nama_produk FROM produk ORDER BY nama_produk")->fetchAll(PDO::FETCH_ASSOC);

// Hitung per bintang
$counts = [];
for ($i = 1; $i <= 5; $i++) {
    $s = $pdo->prepare("SELECT COUNT(*) FROM ulasan WHERE bintang = ?");
    $s->execute([$i]);
    $counts[$i] = $s->fetchColumn() ?? 0;
}

function stars($n) { $n = (int)$n; return str_repeat('★', $n) . str_repeat('☆', 5 - $n); }
function starClass($n) { if ($n >= 4) return 'done'; if ($n == 3) return 'process'; return 'pending'; }
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
        <a class="nav-item" href="../index.php" target="_blank"><span class="icon">🌐</span> Lihat Toko</a>
    </nav>
    <div class="sidebar-footer">
        <div class="admin-badge">Login sebagai <span><?= htmlspecialchars($_SESSION['admin']) ?></span></div>
        <a href="../masuk/logout.php" class="btn-logout">Keluar</a>
    </div>
</aside>

<div class="main">
    <div class="topbar">
        <div class="topbar-title">ULASAN & RATING</div>
        <div class="topbar-right">
            <span>📅 <?= date('d M Y, H:i') ?> WIB</span>
            <a href="../index.php" target="_blank">↗ Toko</a>
        </div>
    </div>

    <div class="content">

        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert">✓ Ulasan berhasil dihapus.</div>
        <?php endif; ?>

        <!-- STAT MINI -->
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
                <div class="value"><?= $counts[5] ?></div>
            </div>
        </div>

        <div class="section">
            <div class="section-header">
                <div class="section-title">SEMUA ULASAN</div>
                <div class="section-badge"><?= count($ulasan_list) ?> ulasan</div>
            </div>

            <!-- RATING BAR -->
            <div class="rating-bar-wrap">
                <?php for ($i = 5; $i >= 1; $i--): ?>
                <div class="rating-bar-row">
                    <div class="rating-bar-label">
                        <span style="color:#f59e0b;"><?= str_repeat('★', $i) ?></span>
                    </div>
                    <div class="rating-bar-bg">
                        <div class="rating-bar-fill" style="width:<?= $total_ulasan ? round($counts[$i] / $total_ulasan * 100) : 0 ?>%"></div>
                    </div>
                    <div class="rating-bar-count"><?= $counts[$i] ?></div>
                </div>
                <?php endfor; ?>
            </div>

            <!-- FILTER -->
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

            <!-- LIST ULASAN -->
            <?php if (empty($ulasan_list)): ?>
                <div class="empty-ulasan">
                    <div class="icon">⭐</div>
                    Belum ada ulasan<?= ($filter_bintang || $filter_produk) ? ' untuk filter ini' : '' ?>.
                </div>
            <?php else: foreach ($ulasan_list as $u): ?>
            <div class="review-card">

                <?php if ($u['gambar']): ?>
                    <img src="../<?= htmlspecialchars($u['gambar']) ?>" class="review-prod-img" onerror="this.src='../asset/posterutama.png'">
                <?php else: ?>
                    <div class="review-prod-placeholder">👕</div>
                <?php endif; ?>

                <div class="review-body">
                    <div class="review-meta">
                        <span class="review-user"><?= htmlspecialchars($u['nama_user'] ?? 'Pengguna') ?></span>
                        <span class="badge <?= starClass($u['bintang']) ?>" style="font-size:11px;"><?= $u['bintang'] ?>★</span>
                        <span class="review-prod">· <?= htmlspecialchars($u['nama_produk'] ?? '-') ?></span>
                        <span class="review-date"><?= date('d M Y, H:i', strtotime($u['created_at'])) ?></span>
                    </div>
                    <div class="star-display">
                        <span class="star-gold"><?= str_repeat('★', (int)$u['bintang']) ?></span><span class="star-muted"><?= str_repeat('★', 5 - (int)$u['bintang']) ?></span>
                    </div>
                    <div class="review-comment <?= empty($u['komentar']) ? 'empty' : '' ?>">
                        <?= empty($u['komentar']) ? 'Tidak ada komentar.' : htmlspecialchars($u['komentar']) ?>
                    </div>
                </div>

                <a href="?hapus=<?= $u['id'] ?><?= $filter_bintang ? '&bintang='.$filter_bintang : '' ?><?= $filter_produk ? '&produk='.$filter_produk : '' ?>"
                   class="btn-hapus-ulasan"
                   onclick="return confirm('Hapus ulasan ini?')">Hapus</a>

            </div>
            <?php endforeach; endif; ?>

        </div>
    </div>
</div>
</body>
</html>