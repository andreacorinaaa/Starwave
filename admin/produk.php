<?php
require 'auth_check.php';

// ── Handle POST (hapus & update harga) ──────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    if ($action === 'hapus') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) { echo json_encode(['success'=>false,'message'=>'ID tidak valid.']); exit; }

        $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT gambar FROM produk WHERE id=$id"));
        if (!$row) { echo json_encode(['success'=>false,'message'=>'Produk tidak ditemukan.']); exit; }

        $stmt = $conn->prepare("DELETE FROM produk WHERE id=?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            if (!empty($row['gambar'])) {
                $file = __DIR__ . '/../' . $row['gambar'];
                if (file_exists($file)) unlink($file);
            }
            echo json_encode(['success'=>true,'message'=>'Produk berhasil dihapus.']);
        } else {
            echo json_encode(['success'=>false,'message'=>'Gagal menghapus produk.']);
        }
        $stmt->close(); exit;

    } elseif ($action === 'update_harga') {
        $id    = (int)($_POST['id'] ?? 0);
        $harga = (int)($_POST['harga'] ?? 0);
        if ($id <= 0)    { echo json_encode(['success'=>false,'message'=>'ID tidak valid.']); exit; }
        if ($harga <= 0) { echo json_encode(['success'=>false,'message'=>'Harga harus lebih dari 0.']); exit; }

        $stmt = $conn->prepare("UPDATE produk SET harga=? WHERE id=?");
        $stmt->bind_param("ii", $harga, $id);
        if ($stmt->execute()) {
            echo json_encode(['success'=>true,'message'=>'Harga berhasil diperbarui.']);
        } else {
            echo json_encode(['success'=>false,'message'=>'Gagal memperbarui harga.']);
        }
        $stmt->close(); exit;

    } else {
        echo json_encode(['success'=>false,'message'=>'Aksi tidak dikenal.']); exit;
    }
}

// ── Query data halaman ───────────────────────────────────────
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
    <style>
        .btn-tambah {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            background: var(--accent, #e5c97e);
            color: #111;
            border-radius: 8px;
            font-family: 'DM Sans', sans-serif;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: opacity .15s;
        }
        .btn-tambah:hover { opacity: .85; }

        .produk-card { position: relative; overflow: hidden; }
        .produk-card .card-actions {
            position: absolute;
            top: 8px; right: 8px;
            display: flex;
            gap: 6px;
            opacity: 0;
            transition: opacity .2s;
        }
        .produk-card:hover .card-actions { opacity: 1; }
        .btn-card {
            width: 30px; height: 30px;
            border-radius: 6px; border: none;
            cursor: pointer; font-size: 14px;
            display: flex; align-items: center; justify-content: center;
            transition: transform .1s;
        }
        .btn-card:hover { transform: scale(1.1); }
        .btn-edit-harga { background: rgba(0,0,0,.7); }
        .btn-hapus-card { background: rgba(239,68,68,.85); }

        /* Modal */
        .modal-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,.65); backdrop-filter: blur(4px);
            z-index: 1000; align-items: center; justify-content: center;
        }
        .modal-overlay.active { display: flex; }
        .modal {
            background: var(--surface, #1a1a1a);
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 12px; width: 100%; max-width: 380px;
            padding: 28px; position: relative;
            animation: modalIn .2s ease;
        }
        @keyframes modalIn {
            from { opacity:0; transform:translateY(12px) scale(.97); }
            to   { opacity:1; transform:none; }
        }
        .modal-close {
            position: absolute; top: 14px; right: 14px;
            background: none; border: none; color: var(--muted, #888);
            font-size: 18px; cursor: pointer; padding: 4px 8px; border-radius: 6px;
        }
        .modal-close:hover { background: rgba(255,255,255,.08); color: #fff; }
        .modal-title {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 20px; letter-spacing: .06em; margin-bottom: 6px;
        }
        .modal-subtitle { font-size: 12px; color: var(--muted, #888); margin-bottom: 20px; }
        .form-label {
            display: block; font-size: 11px; font-weight: 600;
            letter-spacing: .08em; text-transform: uppercase;
            color: var(--muted, #888); margin-bottom: 6px;
        }
        .input-prefix {
            display: flex; align-items: center;
            background: rgba(255,255,255,.05);
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 8px; overflow: hidden; transition: border-color .2s;
        }
        .input-prefix:focus-within { border-color: var(--accent, #e5c97e); }
        .input-prefix-label {
            padding: 10px 12px; font-size: 13px; color: var(--muted, #888);
            border-right: 1px solid rgba(255,255,255,.08);
        }
        .inp-harga {
            flex: 1; background: transparent; border: none;
            color: var(--text, #fff); font-family: 'DM Sans', sans-serif;
            font-size: 14px; padding: 10px 14px; outline: none; width: 100%;
        }
        .modal-actions { display: flex; gap: 10px; margin-top: 20px; justify-content: flex-end; }
        .btn-cancel {
            padding: 9px 18px; border-radius: 8px;
            border: 1px solid rgba(255,255,255,.12);
            background: none; color: var(--muted, #888);
            font-family: 'DM Sans', sans-serif; font-size: 13px; cursor: pointer;
            transition: background .15s, color .15s;
        }
        .btn-cancel:hover { background: rgba(255,255,255,.06); color: #fff; }
        .btn-save {
            padding: 9px 22px; border-radius: 8px; border: none;
            background: var(--accent, #e5c97e); color: #111;
            font-family: 'DM Sans', sans-serif; font-size: 13px;
            font-weight: 600; cursor: pointer; transition: opacity .15s;
        }
        .btn-save:hover { opacity: .85; }
        .btn-save:disabled { opacity: .5; cursor: not-allowed; }

        /* Konfirmasi Hapus */
        .confirm-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,.65); backdrop-filter: blur(4px);
            z-index: 1000; align-items: center; justify-content: center;
        }
        .confirm-overlay.active { display: flex; }
        .confirm-box {
            background: var(--surface, #1a1a1a);
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 12px; width: 100%; max-width: 340px;
            padding: 28px; text-align: center; animation: modalIn .2s ease;
        }
        .confirm-icon { font-size: 36px; margin-bottom: 12px; }
        .confirm-title {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 20px; letter-spacing: .06em; margin-bottom: 8px;
        }
        .confirm-desc { font-size: 13px; color: var(--muted, #888); margin-bottom: 24px; }
        .confirm-name { color: var(--text, #fff); font-weight: 600; }
        .confirm-actions { display: flex; gap: 10px; justify-content: center; }
        .btn-hapus-confirm {
            padding: 9px 22px; border-radius: 8px; border: none;
            background: #ef4444; color: #fff;
            font-family: 'DM Sans', sans-serif; font-size: 13px;
            font-weight: 600; cursor: pointer; transition: opacity .15s;
        }
        .btn-hapus-confirm:hover { opacity: .85; }
        .btn-hapus-confirm:disabled { opacity: .5; cursor: not-allowed; }

        /* Toast */
        .toast {
            position: fixed; bottom: 28px; right: 28px;
            background: #1a1a1a; border: 1px solid rgba(255,255,255,.1);
            border-radius: 10px; padding: 13px 18px;
            font-size: 13px; color: #fff; z-index: 2000;
            box-shadow: 0 8px 32px rgba(0,0,0,.4);
            transform: translateY(20px); opacity: 0;
            transition: opacity .25s, transform .25s; pointer-events: none;
        }
        .toast.show { opacity: 1; transform: translateY(0); }
        .toast.success { border-left: 3px solid #22c55e; }
        .toast.error   { border-left: 3px solid #ef4444; }
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
        <div class="topbar-title">DAFTAR PRODUK</div>
        <div class="topbar-right">
            <span>📅 <?= date('d M Y, H:i') ?> WIB</span>
            <a href="../index.php" target="_blank">↗ Toko</a>
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

            <?php if (mysqli_num_rows($produk_list) == 0): ?>
                <div style="text-align:center;padding:60px;color:var(--muted);">Belum ada produk di database.</div>
            <?php else: ?>
            <div class="produk-grid">
                <?php while ($p = mysqli_fetch_assoc($produk_list)): ?>
                <div class="produk-card" id="card-<?= $p['id'] ?>">
                    <img src="../<?= htmlspecialchars($p['gambar'] ?? 'asset/posterutama.png') ?>"
                         alt="<?= htmlspecialchars($p['nama_produk'] ?? $p['nama'] ?? '') ?>"
                         onerror="this.src='../asset/posterutama.png'">

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

<!-- Modal Edit Harga -->
<div class="modal-overlay" id="modal-edit" onclick="handleOverlay(event,'modal-edit')">
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

<!-- Konfirmasi Hapus -->
<div class="confirm-overlay" id="confirm-hapus" onclick="handleOverlay(event,'confirm-hapus')">
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

<div class="toast" id="toast"></div>

<script>
let editId  = null;
let hapusId = null;

function closeModal(id) { document.getElementById(id).classList.remove('active'); }
function handleOverlay(e, id) { if (e.target === document.getElementById(id)) closeModal(id); }
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') { closeModal('modal-edit'); closeModal('confirm-hapus'); }
});

// Edit Harga
function openEditHarga(id, nama, harga) {
    editId = id;
    document.getElementById('edit-nama').textContent = nama;
    document.getElementById('inp-harga-edit').value  = harga;
    document.getElementById('modal-edit').classList.add('active');
    setTimeout(() => document.getElementById('inp-harga-edit').focus(), 100);
}

async function simpanHarga() {
    const harga = parseInt(document.getElementById('inp-harga-edit').value);
    if (!harga || harga <= 0) { showToast('Harga harus lebih dari 0.', 'error'); return; }

    const btn = document.getElementById('btn-save-harga');
    btn.disabled = true; btn.textContent = 'Menyimpan...';

    const fd = new FormData();
    fd.append('action', 'update_harga');
    fd.append('id', editId);
    fd.append('harga', harga);

    try {
        const res  = await fetch('produk.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            const el = document.getElementById('harga-' + editId);
            if (el) el.textContent = 'Rp ' + harga.toLocaleString('id-ID');
            showToast('✓ ' + data.message, 'success');
            closeModal('modal-edit');
        } else {
            showToast('✗ ' + data.message, 'error');
        }
    } catch { showToast('Gagal terhubung ke server.', 'error'); }
    finally { btn.disabled = false; btn.textContent = 'Simpan'; }
}

// Hapus
function openHapus(id, nama) {
    hapusId = id;
    document.getElementById('hapus-nama').textContent = nama;
    document.getElementById('confirm-hapus').classList.add('active');
}

async function eksekusiHapus() {
    const btn = document.getElementById('btn-hapus-ok');
    btn.disabled = true; btn.textContent = 'Menghapus...';

    const fd = new FormData();
    fd.append('action', 'hapus');
    fd.append('id', hapusId);

    try {
        const res  = await fetch('produk.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            const card = document.getElementById('card-' + hapusId);
            if (card) card.remove();
            const badge = document.getElementById('badge-total');
            badge.textContent = Math.max(0, (parseInt(badge.textContent) || 0) - 1) + ' produk';
            showToast('✓ ' + data.message, 'success');
            closeModal('confirm-hapus');
        } else {
            showToast('✗ ' + data.message, 'error');
        }
    } catch { showToast('Gagal terhubung ke server.', 'error'); }
    finally { btn.disabled = false; btn.textContent = 'Ya, Hapus'; }
}

// Toast
let toastTimer;
function showToast(msg, type = 'success') {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className   = `toast ${type} show`;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => t.classList.remove('show'), 3500);
}
</script>
</body>
</html>