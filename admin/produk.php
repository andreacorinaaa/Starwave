<?php
require 'auth_check.php';

// ── Handle POST ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    if ($action === 'hapus') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) { echo json_encode(['success'=>false,'message'=>'ID tidak valid.']); exit; }

        $stmt = $pdo->prepare("SELECT gambar FROM produk WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) { echo json_encode(['success'=>false,'message'=>'Produk tidak ditemukan.']); exit; }

        $stmt = $pdo->prepare("DELETE FROM produk WHERE id = ?");
        if ($stmt->execute([$id])) {
            if (!empty($row['gambar'])) {
                $file = __DIR__ . '/../' . $row['gambar'];
                if (file_exists($file)) unlink($file);
            }
            echo json_encode(['success'=>true,'message'=>'Produk berhasil dihapus.']);
        } else {
            echo json_encode(['success'=>false,'message'=>'Gagal menghapus produk.']);
        }
        exit;

    } elseif ($action === 'update_harga') {
        $id    = (int)($_POST['id'] ?? 0);
        $harga = (int)($_POST['harga'] ?? 0);
        if ($id <= 0)    { echo json_encode(['success'=>false,'message'=>'ID tidak valid.']); exit; }
        if ($harga <= 0) { echo json_encode(['success'=>false,'message'=>'Harga harus lebih dari 0.']); exit; }

        $stmt = $pdo->prepare("UPDATE produk SET harga = ? WHERE id = ?");
        if ($stmt->execute([$harga, $id])) {
            echo json_encode(['success'=>true,'message'=>'Harga berhasil diperbarui.']);
        } else {
            echo json_encode(['success'=>false,'message'=>'Gagal memperbarui harga.']);
        }
        exit;

    } elseif ($action === 'update_stok') {
        $id      = (int)($_POST['id'] ?? 0);
        $ukuran  = strtolower($_POST['ukuran'] ?? '');
        $jumlah  = max(0, (int)($_POST['jumlah'] ?? 0));
        $allowed = ['s','m','l','xl','xxl'];

        if ($id <= 0 || !in_array($ukuran, $allowed)) {
            echo json_encode(['success'=>false,'message'=>'Data tidak valid.']); exit;
        }

        $col  = "stok_$ukuran";
        $stmt = $pdo->prepare("UPDATE produk SET $col = ? WHERE id = ?");
        $stmt->execute([$jumlah, $id]);
        echo json_encode(['success'=>true,'jumlah'=>$jumlah]);
        exit;

    } else {
        echo json_encode(['success'=>false,'message'=>'Aksi tidak dikenal.']); exit;
    }
}

// ── Query data halaman ───────────────────────────────────────
$pending_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status='pending_payment' OR status='pending'")->fetchColumn();
$total_produk   = $pdo->query("SELECT COUNT(*) FROM produk")->fetchColumn();

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

            <?php if (empty($produk_list)): ?>
                <div style="text-align:center;padding:60px;color:var(--muted);">Belum ada produk di database.</div>
            <?php else: ?>
            <div class="produk-grid">
                <?php foreach ($produk_list as $p): ?>
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

                        <?php if (strtolower($p['kategori']) !== 'accessories'): ?>
                        <div class="stok-sizes" style="margin-top:10px;">
                            <?php foreach(['S','M','L','XL','XXL'] as $sz):
                                $col    = 'stok_' . strtolower($sz);
                                $jumlah = (int)($p[$col] ?? 0);
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
                                    <?= $jumlah > 0 ? $jumlah.' pcs' : 'Habis' ?>
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

let toastTimer;
function showToast(msg, type = 'success') {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className   = `toast ${type} show`;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => t.classList.remove('show'), 3500);
}

async function simpanStok(id, ukuran, input) {
    const jumlah = Math.max(0, parseInt(input.value) || 0);
    input.value = jumlah;

    const fd = new FormData();
    fd.append('action', 'update_stok');
    fd.append('id', id);
    fd.append('ukuran', ukuran);
    fd.append('jumlah', jumlah);

    try {
        const res  = await fetch('produk.php', { method:'POST', body:fd });
        const data = await res.json();
        if (data.success) {
            const key   = id + '-' + ukuran.toLowerCase();
            const badge = document.getElementById('badge-' + key);
            const label = document.querySelector('#stok-' + key)
                            .closest('.stok-row').querySelector('.stok-label');
            const isAda = jumlah > 0;
            if (badge) {
                badge.textContent = isAda ? jumlah + ' pcs' : 'Habis';
                badge.className   = 'stok-badge ' + (isAda ? 'ada' : 'habis');
            }
            if (label) {
                label.className = 'stok-label ' + (isAda ? 'ada' : 'habis');
            }
            showToast('✓ Stok ' + ukuran + ' diperbarui: ' + jumlah, 'success');
        }
    } catch { showToast('Gagal menyimpan stok.', 'error'); }
}

function ubahStok(id, ukuran, btn, delta) {
    const input = document.getElementById('stok-' + id + '-' + ukuran.toLowerCase());
    input.value = Math.max(0, (parseInt(input.value) || 0) + delta);
    simpanStok(id, ukuran, input);
}
</script>
</body>
</html>