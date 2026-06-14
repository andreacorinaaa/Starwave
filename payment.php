<?php
session_start();
include('config/koneksi.php');

if (!isset($_GET['id'])) {
    die("ID order tidak ditemukan");
}

$id_order = (int)$_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$id_order]);
$main_order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$main_order) {
    die("Pesanan tidak ditemukan");
}

$id_user       = (int)$main_order['id_user'];
$tanggal_order = $main_order['tanggal_order'];

$stmt = $pdo->prepare("
    SELECT o.*, p.gambar
    FROM orders o
    LEFT JOIN produk p ON o.nama_produk LIKE CONCAT(p.nama_produk, '%')
    WHERE o.id = ?
");
$stmt->execute([$id_order]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($orders)) {
    die("Pesanan tidak ditemukan");
}

$total = array_sum(array_column($orders, 'total_harga'));

// ── UPLOAD BUKTI BAYAR ──
$upload_success = '';
$upload_error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['bukti_bayar'])) {
    $file  = $_FILES['bukti_bayar'];
    $ftype = mime_content_type($file['tmp_name']);
    $allowed = ['image/jpeg', 'image/png', 'image/webp'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $upload_error = 'Gagal mengupload file.';
    } elseif (!in_array($ftype, $allowed)) {
        $upload_error = 'Format tidak didukung. Gunakan JPG, PNG, atau WEBP.';
    } elseif ($file['size'] > 3 * 1024 * 1024) {
        $upload_error = 'Ukuran file maksimal 3MB.';
    } else {
        $ext        = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename   = 'bukti_' . $id_order . '_' . time() . '.' . $ext;
        $upload_dir = __DIR__ . '/asset/bukti/';

        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

        if (!move_uploaded_file($file['tmp_name'], $upload_dir . $filename)) {
            $upload_error = 'Gagal menyimpan file.';
        } else {
            $path = 'asset/bukti/' . $filename;
            $stmt = $pdo->prepare("UPDATE orders SET bukti_bayar = ?, status_bayar = 'menunggu_konfirmasi' WHERE id = ?");
            $stmt->execute([$path, $id_order]);
            $upload_success = 'Bukti bayar berhasil dikirim! Pesanan kamu sedang diverifikasi admin.';

            $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
            $stmt->execute([$id_order]);
            $main_order = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }
}

// ── QRIS Config ──
define('QRIS_NMID',          '936009060600895');
define('QRIS_MERCHANT_NAME', 'Starwave Fashion');
define('QRIS_CITY',          'Mataram');

function qrisLen(string $v): string { return str_pad(strlen($v), 2, '0', STR_PAD_LEFT); }

function qrisCrc16(string $payload): string {
    $crc = 0xFFFF;
    for ($i = 0; $i < strlen($payload); $i++) {
        $crc ^= ord($payload[$i]) << 8;
        for ($j = 0; $j < 8; $j++) {
            $crc = ($crc & 0x8000) ? (($crc << 1) ^ 0x1021) & 0xFFFF : ($crc << 1) & 0xFFFF;
        }
    }
    return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
}

function generateQrisString(string $nmid, string $merchantName, string $city, int $amount): string {
    $merchantName = substr($merchantName, 0, 25);
    $city         = substr($city, 0, 15);
    $amountStr    = (string) $amount;
    $guid         = '0016A00000007750415';
    $sub01        = '01' . qrisLen($nmid) . $nmid;
    $sub02        = '02' . '15' . str_pad($nmid, 15, '0', STR_PAD_RIGHT);
    $sub03        = '0303UME';
    $inner        = $guid . $sub01 . $sub02 . $sub03;
    $field26      = '26' . qrisLen($inner) . $inner;
    $field54      = '54' . qrisLen($amountStr) . $amountStr;
    $add62inner   = '0503***';
    $field62      = '62' . qrisLen($add62inner) . $add62inner;
    $payload = implode('', [
        '000201', '010212', $field26, '52045963', '5303360', $field54,
        '5802ID', '59' . qrisLen($merchantName) . $merchantName,
        '60' . qrisLen($city) . $city, $field62, '6304',
    ]);
    return $payload . qrisCrc16($payload);
}

$qris_string    = generateQrisString(QRIS_NMID, QRIS_MERCHANT_NAME, QRIS_CITY, (int)$total);
$sudah_upload   = !empty($main_order['bukti_bayar']);
$status_bayar   = $main_order['status_bayar'] ?? 'unpaid';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran — Starwave</title>
    <link rel="stylesheet" href="order.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<!-- NAVBAR -->
<header>
    <nav>
        <h1>STARWAVE</h1>
        <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="man.php">Man</a></li>
            <li><a href="woman.php">Woman</a></li>
            <li><a href="accessories.php">Accessories</a></li>
            <li><a href="order.php" class="active">Order</a></li>
            <li><a href="keranjang.php">Keranjang</a></li>
        </ul>
        <form action="search.php" method="GET" style="display:inline;">
            <input type="text" name="q" placeholder="Search produk..." style="padding:5px;">
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
    <a href="masuk/login.php" style="margin-left:15px; text-decoration:none; color:#c9dde8; font-size:14px; font-weight:700;">Login</a>
<?php endif; ?>
    </nav>
</header>

<div class="ord-confirm-container">

    <div class="ord-confirm-header">
        <h2>Order Confirmation</h2>
        <p>Thank you for your order!</p>
    </div>

    <!-- STEP -->
    <div class="ord-steps">
        <div class="ord-step active"><span>1</span><p>Shipping</p></div>
        <div class="ord-step active"><span>2</span><p>Payment</p></div>
        <div class="ord-step active"><span>3</span><p>Summary</p></div>
        <div class="ord-step current"><span>4</span><p>Confirmation</p></div>
    </div>

    <!-- INFO ORDER -->
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
            <?php else: ?>
                <span class="status-badge unpaid">Belum Bayar</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- HEADER TABLE -->
    <div class="ord-table-head">
        <span>Product</span><span>Shipping</span><span>Quantity</span><span>Total</span>
    </div>

    <!-- ITEM -->
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

    <!-- SUMMARY -->
    <div class="ord-summary">
        <div class="ord-sum-box"><p>Discount</p><strong>Rp 0</strong></div>
        <div class="ord-sum-box"><p>Delivery</p><strong>Free</strong></div>
        <div class="ord-sum-box"><p>Total</p><strong>Rp <?= number_format($total, 0, ',', '.'); ?></strong></div>
    </div>

    <!-- QRIS SECTION — sembunyikan kalau sudah lunas -->
    <?php if ($status_bayar !== 'paid'): ?>
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
                <div class="qr-timer" id="qr-timer">
                    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                    </svg>
                    QR berlaku: <strong id="timer-count">15:00</strong>
                </div>
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
    <?php endif; ?>

    <!-- UPLOAD BUKTI BAYAR -->
    <div class="bukti-section">
        <h3>📎 Upload Bukti Pembayaran</h3>
        <p class="sub">Setelah transfer, upload screenshot atau foto bukti pembayaran kamu.</p>

        <?php if ($upload_success): ?>
            <div class="bukti-alert success">✓ <?= htmlspecialchars($upload_success) ?></div>
        <?php endif; ?>

        <?php if ($upload_error): ?>
            <div class="bukti-alert error">✗ <?= htmlspecialchars($upload_error) ?></div>
        <?php endif; ?>

        <?php if ($status_bayar === 'paid'): ?>
            <div class="bukti-alert paid">✓ Pembayaran kamu sudah dikonfirmasi oleh admin. Pesanan sedang diproses!</div>

        <?php elseif ($sudah_upload): ?>
            <div class="bukti-alert waiting">⏳ Bukti bayar sudah dikirim. Menunggu konfirmasi admin...</div>
            <div class="bukti-preview-existing">
                <img src="<?= htmlspecialchars($main_order['bukti_bayar']) ?>" alt="Bukti Bayar">
                <p>Bukti pembayaran Order #<?= $id_order ?></p>
            </div>
            <form method="POST" enctype="multipart/form-data" style="margin-top:16px;">
                <label class="upload-zone" for="bukti_input2" id="upload-zone2">
                    <input type="file" id="bukti_input2" name="bukti_bayar" accept="image/jpeg,image/png,image/webp">
                    <div id="placeholder2">
                        <div class="icon">🔄</div>
                        <div class="text"><strong>Ganti bukti bayar</strong><br><span style="font-size:11px;color:#aaa;">JPG, PNG, WEBP · Maks 3MB</span></div>
                    </div>
                    <img id="img-preview2" class="upload-preview-img" alt="Preview">
                </label>
                <button type="submit" class="btn-upload">Kirim Ulang Bukti</button>
            </form>

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

    <!-- BUTTON -->
    <div class="ord-button-group">
        <a href="index.php" class="ord-btn-back">Back to Shop</a>
        <a href="order.php" class="ord-btn-place">Lihat Pesanan</a>
    </div>

</div>

<!-- FOOTER -->
<footer>
    <div class="footer-box">
        <div><h3>Store</h3><p>Man</p><p>Woman</p><p>Accessories</p></div>
        <div><h3>Business</h3><p><a href="mailto:starwave@gmail.com">starwave@gmail.com</a></p><p>081836737367367</p></div>
        <div><h3>Social</h3><p><a href="https://instagram.com/starwave" target="_blank">Instagram : starwave.fashion</a></p></div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/qrcode/build/qrcode.min.js"></script>
<script>
const QRIS_STRING = <?= json_encode($qris_string); ?>;

<?php if ($status_bayar !== 'paid'): ?>
QRCode.toCanvas(document.getElementById('qr-canvas'), QRIS_STRING, {
    width: 190, margin: 1, color: { dark: '#1a1a1a', light: '#ffffff' }
});

const expiredAt = new Date(Date.now() + 15 * 60 * 1000);
const timerInterval = setInterval(() => {
    const remaining = Math.max(0, expiredAt - Date.now());
    const mins = String(Math.floor(remaining / 60000)).padStart(2, '0');
    const secs = String(Math.floor((remaining % 60000) / 1000)).padStart(2, '0');
    document.getElementById('timer-count').textContent = `${mins}:${secs}`;
    if (remaining === 0) clearInterval(timerInterval);
}, 1000);
<?php endif; ?>

// Preview gambar di dalam kotak upload — ganti placeholder dengan gambar
function setupPreview(inputId, previewId, placeholderId) {
    const input = document.getElementById(inputId);
    if (!input) return;
    input.addEventListener('change', function () {
        const file = this.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = e => {
            const img = document.getElementById(previewId);
            img.src = e.target.result;
            img.style.display = 'block';
            document.getElementById(placeholderId).style.display = 'none';
        };
        reader.readAsDataURL(file);
    });
}

setupPreview('bukti_input',  'img-preview',  'placeholder1');
setupPreview('bukti_input2', 'img-preview2', 'placeholder2');
</script>
</body>
</html>