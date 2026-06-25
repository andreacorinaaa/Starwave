# Starwave
Sistem Informasi Penjualan Fashion Modern Berbasis Web, Fashion Modern untuk Setiap Gayamu.

# Deskripsi
Starwave adalah platform fashion modern yang menyediakan berbagai pilihan outfit kekinian yang terinspirasi dari gaya para idol yang selalu menjadi pusat perhatian dan trendsetter di kalangan anak muda. Website Starwave dikembangkan oleh Andrea dan Indira sebagai platform olshop fashion modern dengan konsep simple, stylish, dan kekinian. 

Selain itu StarWave menyediakan berbagai fitur yang memudahkan pengguna dalam berbelanja fashion kekinian secara online. Pengguna dapat membuat akun, login, dan mengatur ulang password jika lupa untuk mengakses aplikasi, mencari produk melalui fitur pencarian dan kategori (Man, Woman, Accessories), serta melihat detail produk seperti foto, harga, ukuran, stok, dan deskripsi pakaian. Selain itu, tersedia fitur keranjang belanja yang memungkinkan pengguna mengatur barang dan memilih beberapa produk untuk di beli nanti. StarWave juga dilengkapi dengan proses checkout yang praktis, pembayaran via QRIS dengan upload bukti transfer, pengiriman gratis ongkir (delivery), riwayat pesanan, serta fitur ulasan dan rating untuk produk yang sudah selesai dipesan. Pengguna juga dapat mengelola profil pribadi termasuk foto profil, nomor telepon, wilayah, dan alamat pengiriman.

# Technologi Stack
  - Frontend: HTML, css, JavaScript
  - Backend: PHP
  - Database: MySQL
  - Local server: XAMPP
  - Version control: Git/Github
  
# Alamat Website
http://localhost/starwave

# Menu Utama
USER
  - GUEST (belum login)
    - Sign Up / Registrasi
    - Login
    - Lupa Password
    - Melihat Daftar Produk
    - Melihat Kategori Produk (Man, Woman, Accessories)
    - Mencari Produk (Search)
    - Melihat Detail Produk

  - MEMBER (sudah login)
    - Melihat Daftar Produk
    - Melihat Kategori Produk (Man, Woman, Accessories)
    - Mencari Produk (Search)
    - Melihat Detail Produk
    - Memilih Ukuran & Jumlah Produk
    - Menambahkan Produk ke Keranjang
    - Mengelola Keranjang (ubah jumlah, hapus item, pilih item checkout)
    - Membuat Pesanan (Checkout)
    - Melakukan Pembayaran (QRIS + Upload Bukti Transfer)
    - Melihat Riwayat Pesanan
    - Membatalkan Pesanan
    - Beli Ulang Pesanan (jika QR Kadaluarsa)
    - Memberi Ulasan & Rating Produk
    - Mengedit My Account (foto profil, nama, no. telepon, wilayah, alamat, tanggal lahir)
    - Logout

ADMIN
  - Login Admin
  - Melihat Dashboard (ringkasan produk, pesanan, user)
  - Menambah Produk
  - Melihat Semua Produk
  - Mengedit Produk
  - Menghapus Produk
  - Melihat Semua Pesanan
  - Melihat Bukti Pembayaran
  - Mengubah Status Pesanan
  - Melihat Daftar Pengguna
  - Melihat Semua Ulasan & Rating Produk
  - Logout

# Sitemap

```
STARWAVE
│
├── Home (index.php)
│   ├── Banner
│   ├── New Arrivals
│   └── Trend Collection (produk terlaris)
│
├── Search
│   └── Hasil Pencarian Produk
│
├── Man / Woman / Accessories (man.php, woman.php, accessories.php)
│   ├── Banner Kategori
│   └── Daftar Produk
│       ├── Gambar Produk
│       ├── Nama Produk
│       └── Harga Produk
│
├── Detail Produk (detail.php)
│   ├── Deskripsi Barang
│   ├── Pilih Ukuran (S/M/L/XL/XXL) — khusus Man & Woman
│   ├── Info Stok per Ukuran / Stok Accessories
│   ├── Jumlah Barang (Qty)
│   ├── Total Harga
│   ├── Aksi
│   │   ├── Masukkan ke Keranjang
│   │   └── Beli Langsung
│   └── Ulasan Produk
│       ├── Rata-rata Rating (dari semua ulasan)
│       ├── Distribusi Bintang (1–5)
│       └── Daftar Komentar User
│
├── Keranjang (keranjang.php)
│   ├── Daftar Item
│   │   ├── Gambar & Nama Produk
│   │   ├── Ukuran
│   │   ├── Qty (bisa diubah)
│   │   ├── Subtotal
│   │   └── Hapus Item
│   ├── Pilih Item (checkbox) untuk Checkout
│   └── Total Belanja
│       └── Checkout (Beli Semua)
│
├── Order
│   ├── Pembayaran (payment.php)
│   │   ├── Detail Pesanan
│   │   ├── QR Code QRIS
│   │   ├── Total Pembayaran
│   │   ├── Status (Belum Bayar / Menunggu Konfirmasi / Lunas / QR Kadaluarsa)
│   │   └── Upload Bukti Transfer
│   │
│   └── Riwayat Pesanan (order.php)
│       ├── Nama Produk
│       ├── Jumlah
│       ├── Penerima
│       ├── Tanggal Beli
│       ├── Status
│       └── Aksi
│           ├── Lanjut Bayar
│           ├── Membatalkan Pesanan
│           ├── Beli Ulang (jika QR Kadaluarsa)
│           ├── Beri Ulasan (jika Selesai)
│           └── Hapus Riwayat
│
├── Akun
│   ├── Login (masuk/login.php)
│   ├── Register (masuk/register.php)
│   ├── Lupa Password (masuk/lupa_password.php → masuk/reset.php)
│   └── Logout (masuk/logout.php)
│
├── User / Profile (profile.php)
│   ├── Foto Profil (upload/ubah)
│   ├── Email
│   ├── Nama Panggilan
│   ├── Nomor Telepon
│   ├── Wilayah
│   ├── Alamat
│   ├── Tanggal Lahir
│   └── Log Out
│
└── Admin Panel (admin/)
    ├── Login Admin (login_admin.php)
    ├── Dashboard (dashboard.php)
    │   └── Ringkasan: Total Produk, Pesanan Pending, Total User
    ├── Kelola Produk (produk.php, tambah_produk.php)
    │   ├── Tambah Produk
    │   ├── Edit Produk
    │   └── Hapus Produk
    ├── Kelola Pesanan (pesanan.php, pesanan_view.php)
    │   ├── Lihat Detail Pesanan & Bukti Bayar
    │   └── Ubah Status Pesanan
    ├── Kelola Pengguna (pengguna.php)
    │   └── Lihat Daftar User
    ├── Kelola Ulasan (ulasan.php)
    │   ├── Rata-rata Rating Keseluruhan
    │   └── Distribusi Bintang
    └── Logout

``` 


# Team members and Responsibilities

| No | Member Name | Role | Responsibilities |
|----|--------------|------|-------------------|
| 1 | Indira Ramdhani Sabrina | Backend / Database Side | Mengembangkan fitur menggunakan PHP, mengelola struktur database, menangani proses pengolahan data, dan mengintegrasikan fungsi backend ke dalam sistem. |
| 2 | Andrea Corina Rahmadi | Frontend Side | Mengembangkan antarmuka website menggunakan HTML, CSS, dan JavaScript, membuat tampilan yang responsif, serta meningkatkan interaktivitas dan desain website. |

# Screenshot Tampilan Web
<img width="1920" height="1080" alt="image" src="https://github.com/user-attachments/assets/73bb44e1-504d-4305-8d92-aa7a8d55abaa" />
<img width="1920" height="1080" alt="image" src="https://github.com/user-attachments/assets/7c9e96aa-7bbf-488a-a87d-1b213875a0ad" />
<img width="1920" height="1080" alt="image" src="https://github.com/user-attachments/assets/b74fbfbe-725f-4573-bbe1-55d70b413555" />

# NIM Members Groups
  - Andrea Corina Rahmadi: F1D02410104
  - Indira Ramadhani Sabrina: F1D02410057
