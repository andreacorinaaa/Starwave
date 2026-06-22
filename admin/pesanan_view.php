<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan — STARWAVE Admin</title>
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
        <a class="nav-item active" href="pesanan.php">
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
        <div class="admin-badge">Login sebagai <span><?= htmlspecialchars($_SESSION['nama_admin'] ?? $_SESSION['admin']) ?></span></div>
        <a href="../masuk/logout.php" class="btn-logout">Keluar</a>
    </div>
</aside>

<div class="main">
    <div class="topbar">
        <div class="topbar-title">MANAJEMEN PESANAN</div>
        <div class="topbar-right">
            <span>📅 <?= date('d M Y, H:i') ?> WITA</span>
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
                <button class="filter-btn" onclick="filterStatus('qr_expired', this)">QR Expired</button>
            </div>

            <div class="table-wrap">
                <table id="orders-table">
                    <thead>
                        <tr>
                            <th>#ID</th>
                            <th>Produk</th>
                            <th>Qty</th>
                            <th>Pemesan</th>
                            <th>Tgl Order</th>
                            <th>Status</th>
                            <th>Bukti Bayar</th>
                            <th>Pembayaran</th>
                            <th>Ubah Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($orders)): ?>

                        <tr class="empty-row">
                            <td colspan="10">Belum ada pesanan</td>
                        </tr>

                    <?php else: ?>
                        <?php foreach ($orders as $o): ?>

                            <?php
                            $is_paid    = ($o['status_bayar'] ?? '') === 'paid';
                            $is_waiting = ($o['status_bayar'] ?? '') === 'menunggu_konfirmasi';
                            $is_expired = $o['status'] === 'qr_expired';

                            $ada_bukti = !empty($o['bukti_bayar']);

                            $nama_produk_aman = htmlspecialchars($o['nama_produk']);

                            $info_bundle        = $bundle_info[$o['kode_order']] ?? null;
                            $jumlah_item_bundle = $info_bundle['jumlah_item'] ?? 1;
                            $is_bundle          = $jumlah_item_bundle > 1;

                            $harga_baris_ini = $is_bundle
                                ? (float)$info_bundle['total_bundle']
                                : (float)$o['total_harga'];
                            $harga_format    = 'Rp ' . number_format($harga_baris_ini, 0, ',', '.');

                            $label_modal = $is_bundle
                                ? $nama_produk_aman . " (+" . ($jumlah_item_bundle - 1) . " item lain dalam 1 pesanan)"
                                : $nama_produk_aman;

                            $class_status = ambilStatusClass($o['status'], $kamus_class);
                            $label_status = ambilStatusLabel($o['status'], $kamus_label);

                            $onclick_bukti = $ada_bukti ? sprintf(
                                "openBukti('../%s', %d, '%s', '%s', %s)",
                                htmlspecialchars($o['bukti_bayar'], ENT_QUOTES),
                                $o['id'],
                                $label_modal,
                                $harga_format,
                                $is_paid ? 'true' : 'false'
                            ) : '';

                            $onclick_konfirmasi = sprintf(
                                "openModal(%d, '%s', '%s')",
                                $o['id'],
                                $label_modal,
                                $harga_format
                            );

                            // Alamat: gabung alamat + wilayah, potong kalau terlalu panjang
                            $alamat_parts = array_filter([
                                $o['alamat']  ?? '',
                                $o['wilayah'] ?? '',
                            ]);
                            $alamat_full = implode(', ', $alamat_parts);
                            ?>

                            <tr data-status="<?= $o['status'] ?>"
                                data-bayar="<?= $is_paid ? 'paid' : 'belum_bayar' ?>"
                                data-search="<?= strtolower($o['nama_produk'] . ' ' . ($o['nama'] ?? '')) ?>">

                                <td class="order-id">#<?= $o['id'] ?></td>
                                <td><?= $nama_produk_aman ?></td>
                                <td><?= $o['qty'] ?></td>

                                <!-- Kolom Pemesan: nama + telepon + alamat -->
                                <td style="color:var(--muted); max-width:180px; white-space:normal; word-break:break-word;">
                                    <span style="font-weight:500; color:var(--text);"><?= htmlspecialchars($o['nama'] ?? '-') ?></span>
                                    <?php if (!empty($o['no_telepon'])): ?>
                                        <br><span style="font-size:11px;"><?= htmlspecialchars($o['no_telepon']) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($alamat_full)): ?>
                                        <br><span style="font-size:11px; display:block; margin-top:2px;">
                                            📍 <?= htmlspecialchars($alamat_full) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td style="color:var(--muted);font-size:12px;white-space:nowrap;">
                                    <?= htmlspecialchars($o['tanggal_order']) ?>
                                </td>
                                <td>
                                    <span class="badge <?= $class_status ?>"><?= $label_status ?></span>
                                </td>

                                <!-- Bukti bayar -->
                                <td>
                                    <?php if ($ada_bukti): ?>
                                        <img src="../<?= htmlspecialchars($o['bukti_bayar']) ?>"
                                             class="bukti-thumb"
                                             onclick="<?= $onclick_bukti ?>"
                                             title="Lihat bukti bayar">
                                    <?php else: ?>
                                        <span class="no-bukti">Belum upload</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Pembayaran -->
                                <td>
                                    <?php if ($is_expired): ?>
                                        <span style="color:#b45309;font-weight:600;">⏰ Expired</span>
                                    <?php elseif ($is_paid): ?>
                                        <span class="badge-bayar paid">✓ Lunas</span>
                                    <?php elseif ($is_waiting): ?>
                                        <div style="display:flex;flex-direction:column;gap:5px;align-items:flex-start;">
                                            <span class="badge-bayar waiting">⏳ Menunggu</span>
                                            <button class="btn-konfirmasi" onclick="<?= $onclick_konfirmasi ?>">
                                                Konfirmasi Bayar
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <span class="badge-bayar pending">Belum Bayar</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Ubah status -->
                                <td>
                                    <?php if ($is_expired): ?>
                                        <span style="color:#b45309;font-size:12px;font-weight:600;">— Expired —</span>
                                    <?php else: ?>
                                        <form method="POST" style="display:flex;gap:6px;align-items:center;">
                                            <input type="hidden" name="id_order" value="<?= $o['id'] ?>">
                                            <select name="status" class="select-status">
                                                <?php foreach ($opsi_status as $value => $label): ?>
                                                    <option value="<?= $value ?>" <?= $o['status'] === $value ? 'selected' : '' ?>>
                                                        <?= $label ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" name="update_status" class="btn-save">✓</button>
                                        </form>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <button type="button" class="btn-hapus-order" onclick="openHapusModal(<?= $o['id'] ?>)">
                                        Hapus
                                    </button>
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

<!-- Modal lihat bukti bayar -->
<div class="modal-bukti-backdrop" id="modal-bukti">
    <div class="modal-bukti-box">
        <h3>Bukti Pembayaran</h3>
        <img id="bukti-img" src="" alt="Bukti Bayar">
        <div class="modal-bukti-meta" id="bukti-meta"></div>
        <div class="modal-bukti-actions">
            <button class="btn-modal-bukti-close" onclick="closeBukti()">Tutup</button>
            <button class="btn-modal-bukti-confirm" id="btn-konfirmasi-dari-bukti">Konfirmasi Lunas</button>
        </div>
    </div>
</div>

<!-- Modal konfirmasi pembayaran -->
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

<!-- Modal konfirmasi hapus pesanan -->
<div class="modal-hapus-backdrop" id="modal-hapus">
    <div class="modal-hapus-box">
        <h3>STARWAVE</h3>
        <p id="hapus-desc">Hapus pesanan ini? Tidak bisa dikembalikan!</p>
        <div class="modal-hapus-actions">
            <button type="button" class="btn-hapus-ya" id="btn-hapus-ya">Ya</button>
            <button type="button" class="btn-hapus-tidak" onclick="closeHapusModal()">Tidak</button>
        </div>
    </div>
</div>

<script src="admin.js"></script>
</body>
</html>