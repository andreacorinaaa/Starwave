<?php
session_start();
include('config/koneksi.php');

if (!isset($_SESSION['user'])) {
    header("Location: masuk/login.php");
    exit;
}

$success = "";
$email   = $_SESSION['user'];

$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header("Location: masuk/login.php");
    exit;
}

/* ── Handle photo upload ── */
if (isset($_POST['update_photo']) && isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] === 0) {
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $mime    = mime_content_type($_FILES['foto_profil']['tmp_name']);

    if (in_array($mime, $allowed)) {
        $ext      = pathinfo($_FILES['foto_profil']['name'], PATHINFO_EXTENSION);
        $filename = 'foto_' . md5($email . time()) . '.' . $ext;
        $dir      = 'uploads/foto_profil/';

        if (!is_dir($dir)) mkdir($dir, 0755, true);

        if (move_uploaded_file($_FILES['foto_profil']['tmp_name'], $dir . $filename)) {
            if (!empty($user['foto_profil']) && file_exists($user['foto_profil'])) {
                unlink($user['foto_profil']);
            }
            $foto_path = $dir . $filename;
            $stmt = $pdo->prepare("UPDATE users SET foto_profil = ? WHERE email = ?");
            $stmt->execute([$foto_path, $email]);
            $success = "Foto profil berhasil diperbarui!";

            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    } else {
        $success = "Format file tidak didukung. Gunakan JPG, PNG, GIF, atau WebP.";
    }
}

/* ── Handle profile update ── */
if (isset($_POST['update_profile'])) {
    $nama_panggilan = trim($_POST['nama_panggilan']);
    $no_telepon     = trim($_POST['no_telepon']);
    $alamat         = trim($_POST['alamat']);
    $tanggal_lahir  = $_POST['tanggal_lahir'];
    $jenis_kelamin  = $_POST['jenis_kelamin'];

    $stmt = $pdo->prepare("UPDATE users SET nama_panggilan = ?, no_telepon = ?, alamat = ?, tanggal_lahir = ?, jenis_kelamin = ? WHERE email = ?");
    $stmt->execute([$nama_panggilan, $no_telepon, $alamat, $tanggal_lahir, $jenis_kelamin, $email]);

    $success = "Profil berhasil diperbarui!";

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account — STARWAVE</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <nav>
        <h1>STARWAVE</h1>
        <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="man.php">Man</a></li>
            <li><a href="woman.php">Woman</a></li>
            <li><a href="accessories.php">Accessories</a></li>
            <li><a href="order.php">Order</a></li>
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

<?php if (isset($_GET['peringatan']) && isset($_SESSION['peringatan_profil'])): ?>
    <div class="alert-profil">
        ⚠️ <?= htmlspecialchars($_SESSION['peringatan_profil']) ?>
        <?php if (!empty($_SESSION['redirect_after_profil'])): ?>
            <a href="<?= htmlspecialchars($_SESSION['redirect_after_profil']) ?>">← Kembali ke produk</a>
        <?php endif; ?>
    </div>
    <?php unset($_SESSION['peringatan_profil']); ?>
<?php endif; ?>

<div class="profile-page">
    <div class="profile-wrapper">

        <!-- ── LEFT: Avatar Panel ── -->
        <div class="avatar-panel">

            <div class="avatar-ring">
                <?php if (!empty($user['foto_profil']) && file_exists($user['foto_profil'])): ?>
                    <img src="<?= htmlspecialchars($user['foto_profil']) ?>" alt="Foto Profil" id="avatar-preview">
                <?php else: ?>
                    <img src="" alt="" id="avatar-preview" style="display:none;">
                    <svg id="avatar-default-svg" class="avatar-default-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <circle cx="12" cy="8" r="4"/>
                        <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
                    </svg>
                <?php endif; ?>
            </div>

            <div class="avatar-name">
                <?= htmlspecialchars(!empty($user['nama_panggilan']) ? $user['nama_panggilan'] : 'Pengguna') ?>
            </div>
            <div class="avatar-email"><?= htmlspecialchars($user['email']) ?></div>

            <form class="photo-upload-form" action="" method="POST" enctype="multipart/form-data">
                <label class="upload-btn" for="foto_profil">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="17 8 12 3 7 8"/>
                        <line x1="12" y1="3" x2="12" y2="15"/>
                    </svg>
                    Ganti Foto
                </label>
                <input type="file" id="foto_profil" name="foto_profil" accept="image/*">
                <span class="file-name-hint" id="file-name-hint">JPG, PNG, GIF, WebP</span>
                <button type="submit" name="update_photo" class="save-photo-btn" id="save-photo-btn">Simpan Foto</button>
            </form>

            <div class="divider-panel"></div>
            <a href="masuk/logout.php" class="logout-link">Logout</a>
        </div>

        <!-- ── RIGHT: Form Card ── -->
        <div class="profile-card">
            <h2>My Account</h2>

            <?php if ($success): ?>
                <div class="auth-alert success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                </div>

                <div class="form-group">
                    <label>Nama Panggilan</label>
                    <input type="text" name="nama_panggilan" placeholder="Nama panggilan kamu" value="<?= htmlspecialchars($user['nama_panggilan'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Nomor Telepon</label>
                    <input type="text" name="no_telepon" placeholder="08xxxxxxxxxx" value="<?= htmlspecialchars($user['no_telepon'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Alamat</label>
                    <textarea name="alamat" placeholder="Alamat lengkap kamu..."><?= htmlspecialchars($user['alamat'] ?? '') ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Tanggal Lahir</label>
                        <input type="date" name="tanggal_lahir" value="<?= htmlspecialchars($user['tanggal_lahir'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Jenis Kelamin</label>
                        <select name="jenis_kelamin">
                            <option value="">-- Pilih --</option>
                            <option value="Laki-laki" <?= ($user['jenis_kelamin'] ?? '') === 'Laki-laki' ? 'selected' : '' ?>>Laki-laki</option>
                            <option value="Perempuan" <?= ($user['jenis_kelamin'] ?? '') === 'Perempuan' ? 'selected' : '' ?>>Perempuan</option>
                        </select>
                    </div>
                </div>

                <button type="submit" name="update_profile">Simpan Perubahan</button>
            </form>
        </div>

    </div>
</div>

<footer>
    <div class="footer-box">
        <div><h3>Store</h3><p>Man</p><p>Woman</p><p>Accessories</p></div>
        <div><h3>Business</h3><p>starwave@gmail.com</p><p>081836737367367</p></div>
        <div><h3>Social</h3><p>Instagram : starwave.fashion</p></div>
    </div>
</footer>

<script>
    const input   = document.getElementById('foto_profil');
    const preview = document.getElementById('avatar-preview');
    const defSvg  = document.getElementById('avatar-default-svg');
    const hint    = document.getElementById('file-name-hint');
    const saveBtn = document.getElementById('save-photo-btn');

    input.addEventListener('change', function () {
        if (!this.files || !this.files[0]) return;
        const file = this.files[0];
        hint.textContent = file.name.length > 24 ? file.name.slice(0, 22) + '…' : file.name;
        const reader = new FileReader();
        reader.onload = function (e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
            if (defSvg) defSvg.style.display = 'none';
        };
        reader.readAsDataURL(file);
        saveBtn.style.display = 'inline-block';
    });
</script>

</body>
</html>