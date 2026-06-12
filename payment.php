<?php
session_start();
include('config/koneksi.php');

if (!isset($_GET['id'])) {
    die("ID order tidak ditemukan");
}

$id_order = (int)$_GET['id'];

// Ambil order utama untuk dapat id_user & tanggal_order
$stmt = mysqli_prepare($conn, "SELECT * FROM orders WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id_order);
mysqli_stmt_execute($stmt);
$main_order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$main_order) {
    die("Pesanan tidak ditemukan");
}

$id_user       = (int)$main_order['id_user'];
$tanggal_order = $main_order['tanggal_order'];

// Ambil SEMUA order dari user yang diinsert di waktu yang sama (dalam 5 detik)
$stmt2 = mysqli_prepare($conn, "
    SELECT o.*, p.gambar
    FROM orders o
    LEFT JOIN produk p ON o.nama_produk LIKE CONCAT(p.nama_produk,'%')
    WHERE o.id = ?
");
mysqli_stmt_bind_param($stmt2, 'i', $id_order);
mysqli_stmt_execute($stmt2);
$orders = mysqli_fetch_all(mysqli_stmt_get_result($stmt2), MYSQLI_ASSOC);

if (empty($orders)) {
    die("Pesanan tidak ditemukan");
}

// Total semua item
$total = array_sum(array_column($orders, 'total_harga'));

// ── QRIS Config ──────────────────────────────────────────────
define('QRIS_NMID',          '936009060600895');
define('QRIS_MERCHANT_NAME', 'Starwave Fashion');
define('QRIS_CITY',          'Mataram');

// ── Generate QRIS String (EMVCo) ─────────────────────────────
function qrisLen(string $v): string {
    return str_pad(strlen($v), 2, '0', STR_PAD_LEFT);
}

function qrisCrc16(string $payload): string {
    $crc = 0xFFFF;
    for ($i = 0; $i < strlen($payload); $i++) {
        $crc ^= ord($payload[$i]) << 8;
        for ($j = 0; $j < 8; $j++) {
            $crc = ($crc & 0x8000)
                ? (($crc << 1) ^ 0x1021) & 0xFFFF
                : ($crc << 1) & 0xFFFF;
        }
    }
    return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
}

function generateQrisString(string $nmid, string $merchantName, string $city, int $amount): string {
    $merchantName = substr($merchantName, 0, 25);
    $city         = substr($city, 0, 15);
    $amountStr    = (string) $amount;

    $guid      = '0016A00000007750415';
    $sub01     = '01' . qrisLen($nmid) . $nmid;
    $sub02     = '02' . '15' . str_pad($nmid, 15, '0', STR_PAD_RIGHT);
    $sub03     = '0303UME';
    $inner     = $guid . $sub01 . $sub02 . $sub03;
    $field26   = '26' . qrisLen($inner) . $inner;

    $field54   = '54' . qrisLen($amountStr) . $amountStr;

    $terminalRef = '***';
    $add62inner  = '0503' . $terminalRef;
    $field62     = '62' . qrisLen($add62inner) . $add62inner;

    $payload = implode('', [
        '000201',
        '010212',
        $field26,
        '52045963',
        '5303360',
        $field54,
        '5802ID',
        '59' . qrisLen($merchantName) . $merchantName,
        '60' . qrisLen($city) . $city,
        $field62,
        '6304',
    ]);

    return $payload . qrisCrc16($payload);
}

$qris_string = generateQrisString(QRIS_NMID, QRIS_MERCHANT_NAME, QRIS_CITY, (int)$total);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran — Starwave</title>
    <link rel="stylesheet" href="order.css">
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/qrcode/build/qrcode.min.js"></script>
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
            <a href="profile.php" style="margin-left:15px; text-decoration:none; color:#333;">Profile</a>
        <?php else: ?>
            <a href="masuk/login.php" style="margin-left:15px; text-decoration:none; color:#333;">Login</a>
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
        <div>
            <small>Delivery Date</small>
            <strong><?= date('d M Y'); ?></strong>
        </div>
        <div>
            <small>Order ID</small>
            <strong>#<?= $main_order['id']; ?></strong>
        </div>
        <div>
            <small>Payment Method</small>
            <strong>QRIS</strong>
        </div>
        <div>
            <small>Status</small>
            <strong id="order-status-label">Pending</strong>
        </div>
    </div>

    <!-- HEADER TABLE -->
    <div class="ord-table-head">
        <span>Product</span>
        <span>Shipping</span>
        <span>Quantity</span>
        <span>Total</span>
    </div>

    <!-- SEMUA ITEM -->
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
        <div class="ord-sum-box">
            <p>Total</p>
            <strong>Rp <?= number_format($total, 0, ',', '.'); ?></strong>
        </div>
    </div>

    <!-- QRIS PAYMENT SECTION -->
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
                    <div class="qr-nominal">
                        Nominal: <strong>Rp <?= number_format($total, 0, ',', '.'); ?></strong>
                    </div>
                </div>

                <div class="qr-status pending" id="qr-status">
                    <div class="status-dot pending" id="status-dot"></div>
                    <span id="status-text">Menunggu pembayaran...</span>
                </div>

                <div class="qr-timer" id="qr-timer">
                    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                    </svg>
                    QR berlaku: <strong id="timer-count">15:00</strong>
                </div>

                <div class="paid-overlay" id="paid-overlay">
                    <div class="paid-icon">✅</div>
                    <p style="font-size:15px;font-weight:600;">Pembayaran Berhasil!</p>
                    <p style="font-size:13px;color:#666;">Order #<?= $main_order['id']; ?> telah terkonfirmasi</p>
                </div>
            </div>

            <div class="qris-steps" id="qris-instructions">
                <h4>Cara bayar</h4>
                <div class="qstep">
                    <div class="qstep-num">1</div>
                    <span class="qstep-text">Buka aplikasi e-wallet atau mobile banking kamu</span>
                </div>
                <div class="qstep">
                    <div class="qstep-num">2</div>
                    <span class="qstep-text">Pilih menu <strong>Bayar</strong> atau <strong>Scan QR</strong></span>
                </div>
                <div class="qstep">
                    <div class="qstep-num">3</div>
                    <span class="qstep-text">Scan QR di samping — nominal <strong>Rp <?= number_format($total, 0, ',', '.'); ?></strong> sudah otomatis terisi</span>
                </div>
                <div class="qstep">
                    <div class="qstep-num">4</div>
                    <span class="qstep-text">Konfirmasi pembayaran — halaman ini otomatis update</span>
                </div>

                <div class="supported-apps">
                    <p>Didukung oleh semua aplikasi berlogo QRIS:</p>
                    <div class="app-pills">
                        <span class="app-pill">GoPay</span>
                        <span class="app-pill">OVO</span>
                        <span class="app-pill">Dana</span>
                        <span class="app-pill">ShopeePay</span>
                        <span class="app-pill">BCA Mobile</span>
                        <span class="app-pill">Livin' Mandiri</span>
                        <span class="app-pill">BRImo</span>
                        <span class="app-pill">& lainnya</span>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- BUTTON -->
    <div class="ord-button-group">
        <a href="index.php" class="ord-btn-back">Back to Shop</a>
        <a href="order.php" class="ord-btn-place">Place Order</a>
    </div>

</div>

<!-- FOOTER -->
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
    const QRIS_STRING = <?= json_encode($qris_string); ?>;
    const ORDER_ID    = <?= json_encode((string) $main_order['id']); ?>;
    const TOTAL       = <?= json_encode((int)$total); ?>;

    let pollInterval  = null;
    let timerInterval = null;
    const expiredAt   = new Date(Date.now() + 15 * 60 * 1000);

    QRCode.toCanvas(document.getElementById('qr-canvas'), QRIS_STRING, {
        width: 190,
        margin: 1,
        color: { dark: '#1a1a1a', light: '#ffffff' }
    });

    timerInterval = setInterval(() => {
        const remaining = Math.max(0, expiredAt - Date.now());
        const mins = String(Math.floor(remaining / 60000)).padStart(2, '0');
        const secs = String(Math.floor((remaining % 60000) / 1000)).padStart(2, '0');
        document.getElementById('timer-count').textContent = `${mins}:${secs}`;

        if (remaining === 0) {
            clearInterval(timerInterval);
            clearInterval(pollInterval);
            setStatus('expired', 'QR kedaluwarsa. Muat ulang halaman untuk QR baru.');
        }
    }, 1000);

    pollInterval = setInterval(async () => {
        try {
            const res  = await fetch(`qris_status.php?order_id=${ORDER_ID}`);
            const data = await res.json();
            if (data.status === 'paid') {
                clearInterval(pollInterval);
                clearInterval(timerInterval);
                showPaid();
            }
        } catch (e) {
            console.warn('Polling error:', e);
        }
    }, 3000);

    function setStatus(type, text) {
        const badge = document.getElementById('qr-status');
        const dot   = document.getElementById('status-dot');
        badge.className = `qr-status ${type}`;
        dot.className   = `status-dot ${type}`;
        document.getElementById('status-text').textContent = text;
    }

    function showPaid() {
        setStatus('paid', 'Pembayaran berhasil!');
        document.getElementById('order-status-label').textContent = 'Paid';
        document.getElementById('qris-instructions').style.display = 'none';
        document.getElementById('qr-timer').style.display          = 'none';
        document.getElementById('paid-overlay').style.display      = 'flex';
    }
</script>

</body>
</html>