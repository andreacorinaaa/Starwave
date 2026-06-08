<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - STARWAVE</title>

    <!-- FONT AWESOME -->
    <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <link rel="stylesheet" href="admin.css">
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">

    <div>

        <div class="logo">
            <h2>STARWAVE</h2>
            <p>FASHION ADMIN</p>
        </div>

        <ul class="menu">

            <li class="active">
                <i class="fa-solid fa-house"></i>
                Dashboard
            </li>

            <li>
                <i class="fa-solid fa-shirt"></i>
                Produk
            </li>

            <li>
                <i class="fa-solid fa-layer-group"></i>
                Kategori
            </li>

            <li>
                <i class="fa-solid fa-cart-shopping"></i>
                Pesanan
            </li>

            <li>
                <i class="fa-solid fa-users"></i>
                Pelanggan
            </li>

            <li>
                <i class="fa-solid fa-chart-line"></i>
                Laporan
            </li>

            <li>
                <i class="fa-solid fa-gear"></i>
                Pengaturan
            </li>

        </ul>

    </div>

    <a href="#" class="logout">
        <i class="fa-solid fa-right-from-bracket"></i>
        Logout
    </a>

</div>

<!-- MAIN -->
<div class="main">

    <!-- TOPBAR -->
    <div class="topbar">

        <div>
            <h1>Dashboard</h1>
            <p>Selamat datang kembali Admin ✨</p>
        </div>

        <div class="top-right">

            <input type="text" placeholder="Cari produk...">

            <div class="admin-profile">

                <img src="https://i.pravatar.cc/50" alt="">

                <div>
                    <h4>Admin</h4>
                    <span>admin@starwave.com</span>
                </div>

            </div>

        </div>

    </div>

    <!-- CARD -->
    <div class="cards">

        <div class="card">

            <div class="icon purple">
                <i class="fa-solid fa-box"></i>
            </div>

            <div>
                <h2>256</h2>
                <p>Total Produk</p>
            </div>

        </div>

        <div class="card">

            <div class="icon green">
                <i class="fa-solid fa-cart-shopping"></i>
            </div>

            <div>
                <h2>1.247</h2>
                <p>Total Pesanan</p>
            </div>

        </div>

        <div class="card">

            <div class="icon orange">
                <i class="fa-solid fa-users"></i>
            </div>

            <div>
                <h2>892</h2>
                <p>Total Pelanggan</p>
            </div>

        </div>

        <div class="card">

            <div class="icon blue">
                <i class="fa-solid fa-wallet"></i>
            </div>

            <div>
                <h2>Rp125JT</h2>
                <p>Total Pendapatan</p>
            </div>

        </div>

    </div>

    <!-- TABLE -->
    <div class="table-container">

        <div class="table-header">

            <h2>Daftar Produk</h2>

            <button>
                + Tambah Produk
            </button>

        </div>

        <table>

            <thead>
                <tr>
                    <th>No</th>
                    <th>Produk</th>
                    <th>Kategori</th>
                    <th>Harga</th>
                    <th>Stok</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>

            <tbody>

                <tr>
                    <td>1</td>
                    <td>Oversize Hoodie Black</td>
                    <td>Hoodie</td>
                    <td>Rp250.000</td>
                    <td>35</td>

                    <td>
                        <span class="status active">
                            Aktif
                        </span>
                    </td>

                    <td>

                        <button class="edit">
                            Edit
                        </button>

                        <button class="delete">
                            Hapus
                        </button>

                    </td>
                </tr>

                <tr>
                    <td>2</td>
                    <td>Minimal White Sneakers</td>
                    <td>Sepatu</td>
                    <td>Rp350.000</td>
                    <td>12</td>

                    <td>
                        <span class="status warning">
                            Stok Tipis
                        </span>
                    </td>

                    <td>

                        <button class="edit">
                            Edit
                        </button>

                        <button class="delete">
                            Hapus
                        </button>

                    </td>
                </tr>

                <tr>
                    <td>3</td>
                    <td>Kemeja Linen Beige</td>
                    <td>Kemeja</td>
                    <td>Rp220.000</td>
                    <td>28</td>

                    <td>
                        <span class="status active">
                            Aktif
                        </span>
                    </td>

                    <td>

                        <button class="edit">
                            Edit
                        </button>

                        <button class="delete">
                            Hapus
                        </button>

                    </td>
                </tr>

            </tbody>

        </table>

    </div>

</div>

</body>
</html>