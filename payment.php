<?php
require_once 'payment_logic.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran — Starwave</title>
    <link rel="stylesheet" href="order.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

</head>
<body>

<header>
    <nav>
        <h1><a href="index.php">STARWAVE</a></h1>
        <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="man.php">Man</a></li>
            <li><a href="woman.php">Woman</a></li>
            <li><a href="accessories.php">Accessories</a></li>
            <li><a href="order.php" class="active">Order</a></li>
            <li><a href="keranjang.php">Keranjang</a></li>
        </ul>
        <form action="search.php" method="GET" class="search-form" onsubmit="return validateSearch(this)">
            <input type="text" name="q" placeholder="Search produk..." class="search-input">
            <button type="submit" class="search-btn">
                <i class="fa fa-search"></i>
            </button>
        </form>
        <?php if (isset($_SESSION['user'])): ?>
            <a href="profile.php" style="margin-left:15px; text-decoration:none; display:flex; align-items:center;" title="Profile">
                <?php
                    $stmt2 = $pdo->prepare("SELECT foto_profil FROM users WHERE email = ?");
                    $stmt2->execute([$_SESSION['user']]);
                    $navUser = $stmt2->fetch(PDO::FETCH_ASSOC);
                ?>
                <?php if (!empty($navUser['foto_profil']) && file_exists($navUser['foto_profil'])): ?>
                    <img src="<?= htmlspecialchars($navUser['foto_profil']) ?>"
                         style="width:34px; height:34px; border-radius:50%; object-fit:cover; border:2px solid #2a7fa8;">
                <?php else: ?>
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#c9dde8" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="8" r="4"/>
                        <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
                    </svg>
                <?php endif; ?>
            </a>
        <?php elseif (isset($_SESSION['admin'])): ?>
            <a href="admin/dashboard.php" style="margin-left:15px; text-decoration:none; color:#4f6ef7; display:flex; align-items:center; gap:5px; font-size:12px; font-weight:700; letter-spacing:1px;" title="Admin Panel">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="8" r="4"/>
                    <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
                </svg>
                ADMIN
            </a>
        <?php else: ?>
            <a href="masuk/login.php" class="btn-login">Login</a>
        <?php endif; ?>
    </nav>
</header>

<div class="ord-confirm-container">

    <div class="ord-confirm-header">
        <h2>Order Confirmation</h2>
        <p>Thank you for your order!</p>
    </div>

    <div class="ord-steps">
        <div class="ord-step active"><span>1</span><p>Shipping</p></div>
        <div class="ord-step active"><span>2</span><p>Payment</p></div>
        <div class="ord-step active"><span>3</span><p>Summary</p></div>
        <div class="ord-step current"><span>4</span><p>Confirmation</p></div>
    </div>

    <div class="ord-order-info">
        <div><small>Delivery Date</small><strong><?= date('d M Y'); ?></strong></div>
        <div><small>Order ID</small><strong>#<?= $main_order['id']; ?></strong></div>
        <div><small>Payment Method</small><strong>QRIS</strong></div>
        <div>
            <small>Status</small>
            <?php if ($status_bayar === 'paid'): ?>
                <span class="status-badge paid">✓ Lunas</span>
            <?php elseif ($status_bayar === 'menunggu_konfirmasi'): ?>
                <span class="status-badge waiting">⏳ Menunggu Konfirmasi</span>
            <?php elseif ($main_order['status'] === 'qr_expired'): ?>
                <span class="status-badge" style="background:#fff8e1;color:#b45309;border:1px solid #fcd34d;">⏰ QR Kadaluarsa</span>
            <?php else: ?>
                <span class="status-badge unpaid">Belum Bayar</span>
            <?php endif; ?>
        </div>
    </div>

    <div class="ord-table-head">
        <span>Product</span><span>Shipping</span><span>Quantity</span><span>Total</span>
    </div>

    <?php foreach ($orders as $order): ?>
    <div class="ord-product-row">
        <div class="ord-product-info">
            <?php if (!empty($order['gambar'])): ?>
                <img src="<?= htmlspecialchars($order['gambar']); ?>" alt="<?= htmlspecialchars($order['nama_produk']); ?>">
            <?php endif; ?>
            <div>
                <h4><?= htmlspecialchars($order['nama_produk']); ?></h4>
                <span>Rp <?= number_format($order['harga'], 0, ',', '.'); ?></span>
            </div>
        </div>
        <div class="ord-shipping">Free</div>
        <div class="ord-qty-box"><?= $order['qty']; ?></div>
        <div class="ord-price">Rp <?= number_format($order['total_harga'], 0, ',', '.'); ?></div>
    </div>
    <?php endforeach; ?>

    <div class="ord-summary">
        <div class="ord-sum-box"><p>Discount</p><strong>Rp 0</strong></div>
        <div class="ord-sum-box"><p>Delivery</p><strong>Free</strong></div>
        <div class="ord-sum-box"><p>Total</p><strong>Rp <?= number_format($total, 0, ',', '.'); ?></strong></div>
    </div>

    <?php if ($main_order['status'] === 'qr_expired'): ?>
        <!-- EXPIRED -->
        <div style="background:#fff8e1;border-left:4px solid #f59e0b;padding:20px 24px;border-radius:8px;margin:24px 0;text-align:center;">
            <div style="font-size:32px;">⏰</div>
            <h3 style="color:#b45309;margin:8px 0 4px;">QR Kadaluarsa</h3>
            <p style="color:#92400e;margin:0;">Waktu pembayaran sudah habis. Silakan buat pesanan baru.</p>
            <?php if ($id_produk): ?>
                <a href="detail.php?id=<?= $id_produk ?>"
                   style="display:inline-block;margin-top:14px;padding:10px 24px;background:#b45309;color:#fff;border-radius:6px;text-decoration:none;font-weight:700;">
                    Beli Ulang
                </a>
            <?php endif; ?>
        </div>

    <?php elseif ($status_bayar !== 'paid'): ?>
        <!-- QRIS -->
        <div class="qris-section">
            <h3>Pembayaran QRIS</h3>
            <div class="qris-layout">
                <div style="display:flex; flex-direction:column; gap:10px; align-items:center;">
                    <div class="qr-frame">
                        <div class="qr-top">
                            <div class="qris-badge">QRIS</div>
                            <span class="qr-merchant"><?= QRIS_MERCHANT_NAME; ?></span>
                        </div>
                        <canvas id="qr-canvas" width="190" height="190"></canvas>
                        <div class="qr-nominal">Nominal: <strong>Rp <?= number_format($total, 0, ',', '.'); ?></strong></div>
                    </div>
                    <?php if (!$sudah_upload): ?>
                    <div class="qr-timer" id="qr-timer">
                        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                        </svg>
                        QR berlaku: <strong id="timer-count">15:00</strong>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="qris-steps">
                    <h4>Cara bayar</h4>
                    <div class="qstep"><div class="qstep-num">1</div><span class="qstep-text">Buka aplikasi e-wallet atau mobile banking kamu</span></div>
                    <div class="qstep"><div class="qstep-num">2</div><span class="qstep-text">Pilih menu <strong>Bayar</strong> atau <strong>Scan QR</strong></span></div>
                    <div class="qstep"><div class="qstep-num">3</div><span class="qstep-text">Scan QR di samping — nominal <strong>Rp <?= number_format($total, 0, ',', '.'); ?></strong> sudah otomatis terisi</span></div>
                    <div class="qstep"><div class="qstep-num">4</div><span class="qstep-text">Setelah bayar, upload bukti pembayaran di bawah</span></div>
                    <div class="supported-apps">
                        <p>Didukung oleh semua aplikasi berlogo QRIS:</p>
                        <div class="app-pills">
                            <span class="app-pill">GoPay</span><span class="app-pill">OVO</span>
                            <span class="app-pill">Dana</span><span class="app-pill">ShopeePay</span>
                            <span class="app-pill">BCA Mobile</span><span class="app-pill">Livin' Mandiri</span>
                            <span class="app-pill">BRImo</span><span class="app-pill">& lainnya</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- UPLOAD BUKTI -->
        <div class="bukti-section">
            <h3>📎 Upload Bukti Pembayaran</h3>
            <p class="sub">Setelah transfer, upload screenshot atau foto bukti pembayaran kamu.</p>

            <?php if ($upload_success): ?>
                <div class="bukti-alert success">✓ <?= htmlspecialchars($upload_success) ?></div>
            <?php endif; ?>
            <?php if ($upload_error): ?>
                <div class="bukti-alert error">✗ <?= htmlspecialchars($upload_error) ?></div>
            <?php endif; ?>

            <?php if ($sudah_upload): ?>
                <div class="bukti-alert waiting">⏳ Bukti bayar sudah dikirim. Menunggu konfirmasi admin...</div>
                <div class="bukti-preview-existing">
                    <img src="<?= htmlspecialchars($main_order['bukti_bayar']) ?>" alt="Bukti Bayar">
                    <p>Bukti pembayaran Order #<?= $id_order ?></p>
                </div>
            <?php else: ?>
                <form method="POST" enctype="multipart/form-data">
                    <label class="upload-zone" for="bukti_input" id="upload-zone">
                        <input type="file" id="bukti_input" name="bukti_bayar" accept="image/jpeg,image/png,image/webp">
                        <div id="placeholder1">
                            <div class="icon">📷</div>
                            <div class="text"><strong>Klik untuk upload</strong> atau drag & drop<br><span style="font-size:11px;color:#aaa;">JPG, PNG, WEBP · Maks 3MB</span></div>
                        </div>
                        <img id="img-preview" class="upload-preview-img" alt="Preview">
                    </label>
                    <button type="submit" class="btn-upload">Kirim Bukti Pembayaran</button>
                </form>
            <?php endif; ?>
        </div>

    <?php else: ?>
        <!-- SUDAH LUNAS -->
        <div class="bukti-section">
            <div class="bukti-alert paid">✓ Pembayaran kamu sudah dikonfirmasi oleh admin. Pesanan sedang diproses!</div>
        </div>
    <?php endif; ?>

    <div class="ord-button-group">
        <a href="index.php" class="ord-btn-back">Back to Shop</a>
        <a href="order.php" class="ord-btn-place">Lihat Pesanan</a>
    </div>

</div>

<footer>
    <div class="footer-box">
        <div>
            <h3>Store</h3>
            <p>Man</p><p>Woman</p><p>Accessories</p>
        </div>
        <div>
            <h3>Business</h3>
            <p><a href="mailto:starwave@gmail.com">starwave@gmail.com</a></p>
            <p>081836737367367</p>
        </div>
        <div>
            <h3>Social</h3>
            <p><a href="https://instagram.com/starwave" target="_blank">Instagram : starwave.fashion</a></p>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/qrcode/build/qrcode.min.js"></script>

<script>
    window.QRIS_DATA = {
        qrisString: <?= json_encode($qris_string); ?>,
        isExpired:  <?= json_encode($main_order['status'] === 'qr_expired'); ?>,
        isPaid:     <?= json_encode($status_bayar === 'paid'); ?>,
        sudahUpload: <?= json_encode($sudah_upload); ?>,
        expiredAtMs: <?= !empty($main_order['qris_expired_at'])
            ? strtotime($main_order['qris_expired_at']) * 1000
            : (time() + 900) * 1000; ?>
    };
</script>

<script src="order.js"></script>
<script src="pengguna.js"></script>
</body>
</html>