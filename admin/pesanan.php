<?php
require 'auth_check.php';

$pending_orders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM orders WHERE status='pending_payment' OR status='pending'"))['n'] ?? 0;
$total_orders   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM orders"))['n'] ?? 0;

$pesan = "";

// ── Update status order (existing) ───────────────────────────
if (isset($_POST['update_status'])) {
    $id_order   = (int)$_POST['id_order'];
    $new_status = mysqli_real_escape_string($conn, $_POST['status']);
    mysqli_query($conn, "UPDATE orders SET status='$new_status' WHERE id='$id_order'");
    $pesan = "Status pesanan #$id_order berhasil diperbarui.";
}

// ── Konfirmasi pembayaran QRIS ────────────────────────────────
if (isset($_POST['konfirmasi_bayar'])) {
    $id_order = (int)$_POST['id_order'];

    // Update status_bayar jadi paid + ubah status order jadi diproses
    $stmt = mysqli_prepare($conn, "UPDATE orders SET status_bayar='paid', status='diproses' WHERE id=?");
    mysqli_stmt_bind_param($stmt, 'i', $id_order);
    mysqli_stmt_execute($stmt);

    if (mysqli_stmt_affected_rows($stmt) > 0) {
        $pesan = "Pembayaran QRIS order #$id_order berhasil dikonfirmasi. Status diubah ke Diproses.";
    }
}

// ── Hapus order (existing) ────────────────────────────────────
if (isset($_GET['hapus_order'])) {
    $id = (int)$_GET['hapus_order'];
    mysqli_query($conn, "DELETE FROM orders WHERE id='$id'");
    header("Location: pesanan.php?deleted=1");
    exit;
}

$orders = mysqli_query($conn, "
    SELECT o.*, u.nama_panggilan AS nama, u.no_telepon
    FROM orders o
    LEFT JOIN users u ON o.id_user = u.id_user
    ORDER BY o.created_at DESC LIMIT 50
");

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
    <title>Pesanan — STARWAVE Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
    <style>
        /* ── Badge pembayaran ── */
        .badge-bayar {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 500;
        }
        .badge-bayar.paid    { background: #eafaf3; color: #166534; }
        .badge-bayar.pending { background: #fef9ec; color: #92680a; }

        /* ── Tombol konfirmasi ── */
        .btn-konfirmasi {
            padding: 5px 10px;
            background: #1a1a1a;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            white-space: nowrap;
            transition: background .15s;
        }
        .btn-konfirmasi:hover { background: #333; }

        /* ── Modal ── */
        .modal-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.5);
            z-index: 999;
            align-items: center;
            justify-content: center;
        }
        .modal-backdrop.open { display: flex; }

        .modal-box {
            background: #fff;
            border-radius: 14px;
            padding: 2rem;
            width: 100%;
            max-width: 360px;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0,0,0,.12);
        }
        .modal-box h3 { font-size: 16px; font-weight: 600; margin-bottom: 8px; }
        .modal-box p  { font-size: 14px; color: #666; margin-bottom: 1.5rem; line-height: 1.5; }

        .modal-actions { display: flex; gap: 10px; }

        .btn-modal-cancel {
            flex: 1; padding: 10px;
            border: 1px solid #e0e0dc;
            background: #fff;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            color: #666;
        }
        .btn-modal-confirm {
            flex: 1; padding: 10px;
            background: #1a1a1a; color: #fff;
            border: none; border-radius: 8px;
            font-size: 14px; font-weight: 500;
            cursor: pointer;
        }
        .btn-modal-confirm:hover { background: #333; }
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
        <a class="nav-item active" href="pesanan.php">
            <span class="icon">📦</span> Pesanan
            <?php if ($pending_orders > 0): ?>
                <span style="margin-left:auto;background:#ef4444;color:#fff;font-size:10px;padding:2px 7px;border-radius:10px;"><?= $pending_orders ?></span>
            <?php endif; ?>
        </a>
        <a class="nav-item" href="produk.php"><span class="icon">👕</span> Produk</a>
        <a class="nav-item" href="pengguna.php"><span class="icon">👥</span> Pengguna</a>
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
        <div class="topbar-title">MANAJEMEN PESANAN</div>
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

        <div class="section">
            <div class="section-header">
                <div class="section-title">DAFTAR PESANAN</div>
                <div class="section-badge"><?= $total_orders ?> total</div>
            </div>
            <div class="actions-bar">
                <input type="text" class="search-input" id="search-orders" placeholder="Cari produk / pemesan..." oninput="filterOrders()">
                <button class="filter-btn active" onclick="filterStatus('semua', this)">Semua</button>
                <button class="filter-btn" onclick="filterStatus('belum_bayar', this)">Belum Bayar</button>
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
                            <th>Pembayaran</th>
                            <th>Ubah Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (mysqli_num_rows($orders) == 0): ?>
                        <tr class="empty-row"><td colspan="10">Belum ada pesanan</td></tr>
                    <?php else: while ($o = mysqli_fetch_assoc($orders)): ?>
                        <?php $is_paid = ($o['status_bayar'] ?? '') === 'paid'; ?>
                        <tr data-status="<?= $o['status'] ?>"
                            data-bayar="<?= $is_paid ? 'paid' : 'belum_bayar' ?>"
                            data-search="<?= strtolower($o['nama_produk'] . ' ' . ($o['nama'] ?? '') . ' ' . $o['nama_penerima']) ?>">

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
                            <td>
                                <span class="badge <?= statusClass($o['status']) ?>">
                                    <?= statusLabel($o['status']) ?>
                                </span>
                            </td>

                            <!-- ── Kolom Pembayaran QRIS ── -->
                            <td>
                                <?php if ($is_paid): ?>
                                    <span class="badge-bayar paid">✓ Lunas</span>
                                <?php else: ?>
                                    <div style="display:flex;flex-direction:column;gap:5px;align-items:flex-start;">
                                        <span class="badge-bayar pending">Belum Bayar</span>
                                        <button class="btn-konfirmasi"
                                            onclick="openModal(<?= $o['id'] ?>, '<?= htmlspecialchars($o['nama_produk']) ?>', 'Rp <?= number_format($o['total_harga'], 0, ',', '.') ?>')">
                                            Konfirmasi Bayar
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </td>

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

    </div>
</div>

<!-- ── Modal Konfirmasi Pembayaran ── -->
<div class="modal-backdrop" id="modal-konfirmasi">
    <div class="modal-box">
        <h3>Konfirmasi Pembayaran</h3>
        <p id="modal-desc">Tandai order ini sebagai lunas?</p>
        <form method="POST" id="form-konfirmasi">
            <input type="hidden" name="id_order" id="modal-order-id">
            <input type="hidden" name="konfirmasi_bayar" value="1">
            <div class="modal-actions">
                <button type="button" class="btn-modal-cancel" onclick="closeModal()">Batal</button>
                <button type="submit" class="btn-modal-confirm">Ya, Konfirmasi</button>
            </div>
        </form>
    </div>
</div>

<script>
let currentStatus = 'semua';

function filterStatus(status, btn) {
    currentStatus = status;
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    applyFilter();
}

function filterOrders() { applyFilter(); }

function applyFilter() {
    const q = document.getElementById('search-orders').value.toLowerCase();
    document.querySelectorAll('#orders-table tbody tr').forEach(row => {
        const rowStatus = row.getAttribute('data-status') || '';
        const rowBayar  = row.getAttribute('data-bayar') || '';
        const rowSearch = row.getAttribute('data-search') || '';

        let statusMatch = false;
        if (currentStatus === 'semua') {
            statusMatch = true;
        } else if (currentStatus === 'belum_bayar') {
            statusMatch = rowBayar === 'belum_bayar';
        } else if (currentStatus === 'pending') {
            statusMatch = rowStatus === 'pending' || rowStatus === 'pending_payment';
        } else {
            statusMatch = rowStatus === currentStatus;
        }

        const searchMatch = q === '' || rowSearch.includes(q);
        row.style.display = (statusMatch && searchMatch) ? '' : 'none';
    });
}

// ── Modal konfirmasi pembayaran ──
function openModal(orderId, namaProduk, total) {
    document.getElementById('modal-order-id').value = orderId;
    document.getElementById('modal-desc').innerHTML =
        `Konfirmasi pembayaran QRIS untuk:<br>
         <strong>#${orderId} — ${namaProduk}</strong><br>
         Total: <strong>${total}</strong><br><br>
         Status order akan otomatis berubah ke <strong>Diproses</strong>.`;
    document.getElementById('modal-konfirmasi').classList.add('open');
}

function closeModal() {
    document.getElementById('modal-konfirmasi').classList.remove('open');
}

document.getElementById('modal-konfirmasi').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>
</body>
</html>