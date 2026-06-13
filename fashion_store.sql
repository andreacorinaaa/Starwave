-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 12, 2026 at 04:00 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `fashion_store`
--

-- --------------------------------------------------------

--
-- Table structure for table `keranjang`
--

CREATE TABLE `keranjang` (
  `id` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_produk` int(11) NOT NULL,
  `nama_produk` varchar(255) NOT NULL,
  `harga` int(11) NOT NULL,
  `qty` int(11) NOT NULL DEFAULT 1,
  `ukuran` varchar(10) NOT NULL DEFAULT '-',
  `gambar` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `keranjang`
--

INSERT INTO `keranjang` (`id`, `id_user`, `id_produk`, `nama_produk`, `harga`, `qty`, `ukuran`, `gambar`, `created_at`) VALUES
(1, 1, 31, 'Trilogy Ring', 200000, 2, '-', 'asset/trilogyR.jpg', '2026-06-11 21:44:40'),
(3, 1, 19, 'Highwaist Straight Jeans', 320000, 1, 'M', 'asset/highwaistS.jpg', '2026-06-12 15:14:27');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `nama_produk` varchar(150) NOT NULL,
  `qty` int(11) NOT NULL DEFAULT 1,
  `nama_penerima` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `tanggal_order` date NOT NULL,
  `status` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `harga` int(11) NOT NULL,
  `total_harga` int(11) NOT NULL,
  `status_bayar` varchar(20) DEFAULT 'unpaid'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `id_user`, `nama_produk`, `qty`, `nama_penerima`, `email`, `tanggal_order`, `status`, `created_at`, `harga`, `total_harga`, `status_bayar`) VALUES
(29, 1, 'Bomber Jacket - Size S', 1, 'andrea indira', 'rara@gmail.com', '2026-06-07', 'selesai', '2026-06-07 14:29:57', 600000, 600000, 'unpaid'),
(33, 1, 'Wayfarer Puffer - Size M', 1, 'andrea indira', 'rara@gmail.com', '2026-06-10', 'selesai', '2026-06-10 15:54:09', 350000, 350000, 'unpaid'),
(34, 1, 'Denim Jacket - Size M', 1, 'andrea indira', 'rara@gmail.com', '2026-06-12', 'selesai', '2026-06-12 05:26:24', 500000, 500000, 'unpaid'),
(35, 1, 'Formal Pants - Size M', 1, 'andrea indira', 'rara@gmail.com', '2026-06-12', 'selesai', '2026-06-12 08:26:53', 300000, 300000, 'paid'),
(36, 1, 'Sport T-Shirt - Size M', 1, 'andrea indira', 'rara@gmail.com', '2026-06-12', 'pending_payment', '2026-06-12 08:39:10', 200000, 200000, 'unpaid'),
(37, 1, 'Bustier Top - Size M', 1, 'andrea indira', 'rara@gmail.com', '2026-06-12', 'pending_payment', '2026-06-12 08:43:31', 260000, 260000, 'unpaid'),
(38, 1, 'Tank Top - Size M', 1, 'andrea indira', 'rara@gmail.com', '2026-06-12', 'selesai', '2026-06-12 13:12:50', 130000, 130000, 'paid');

-- --------------------------------------------------------

--
-- Table structure for table `produk`
--

CREATE TABLE `produk` (
  `id` int(11) NOT NULL,
  `nama_produk` varchar(255) DEFAULT NULL,
  `harga` int(11) DEFAULT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `kategori` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `stok_s` tinyint(1) DEFAULT 1,
  `stok_m` tinyint(1) DEFAULT 1,
  `stok_l` tinyint(1) DEFAULT 1,
  `stok_xl` tinyint(1) DEFAULT 1,
  `stok_xxl` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `produk`
--

INSERT INTO `produk` (`id`, `nama_produk`, `harga`, `gambar`, `deskripsi`, `kategori`, `created_at`, `stok_s`, `stok_m`, `stok_l`, `stok_xl`, `stok_xxl`) VALUES
(1, 'Basic White T-Shirt', 200000, 'asset/mingyuman1.jpg', 'Kaos basic premium berbahan cotton lembut dan nyaman dipakai sehari-hari.', 'man', '2026-05-17 19:49:24', 1, 0, 1, 1, 1),
(2, 'Black Hoodie', 350000, 'asset/mingyuman2.jpg', 'Hoodie hitam dengan desain minimalis dan bahan tebal berkualitas.', 'man', '2026-05-17 19:49:24', 1, 1, 1, 1, 1),
(3, 'Denim Jacket', 500000, 'asset/mingyuman3.jpg', 'Jacket denim modern dengan style casual dan fashionable.', 'man', '2026-05-17 19:49:24', 1, 1, 1, 1, 1),
(4, 'Casual Shirt', 250000, 'asset/mingyuman4.jpg', 'Kemeja casual modern cocok untuk daily outfit.', 'man', '2026-05-17 19:49:24', 1, 1, 1, 1, 1),
(5, 'Monologo Tee', 800000, 'asset/trendcl1.jpg', 'Kaos premium dengan desain grafis logo minimalis yang ikonik, berbahan katun lembut yang nyaman dan cocok untuk gaya streetwear sehari-hari.', 'man', '2026-05-17 19:49:24', 1, 1, 1, 1, 1),
(6, 'Classic Trucker Jacket', 200000, 'asset/trendcl2.jpg', 'Jaket denim model trucker klasik dengan potongan timeless, dilengkapi kancing besi robust dan saku fungsional untuk tampilan maskulin yang tangguh.', 'man', '2026-05-17 19:49:24', 1, 1, 1, 1, 1),
(7, '90s Denim Trucker Jacket', 400000, 'asset/trendcl3.1.jpg', 'Jaket denim bergaya retro era 90-an dengan potongan relaxed fit yang longgar, memberikan kesan vintage yang autentik dan kasual.', 'man', '2026-05-17 19:49:24', 1, 1, 1, 1, 1),
(8, 'Oversize Tee', 180000, 'asset/oversizedT.jpg', 'Kaos berpotongan longgar dengan siluet dropped-shoulder modern, berbahan katun tebal namun adem yang sempurna untuk gaya casual streetwear.', 'man', '2026-05-17 19:49:24', 1, 1, 1, 1, 1),
(9, 'Cotton Crewneck T-Shirt', 270000, 'asset/trendcl4.jpg', 'Kaos leher bulat klasik berbahan 100% katun premium yang lembut, sejuk, dan menyerap keringat dengan pas sempurna untuk kenyamanan sepanjang hari.', 'man', '2026-05-17 19:49:24', 1, 1, 1, 1, 1),
(10, 'Chino Pants', 320000, 'asset/chinoP.jpg', 'Celana chino potongan slim-fit berbahan katun twill elastis yang ringan, memberikan tampilan semi-formal yang rapi namun tetap fleksibel.', 'man', '2026-05-17 19:49:24', 1, 1, 1, 1, 1),
(11, 'Bomber Jacket', 600000, 'asset/bomberJ.jpg', 'Jaket bomber modern dengan bahan luar windproof dan liner dalam yang lembut, dilengkapi saku lengan ikonik untuk gaya sporty urban yang tangguh.', 'man', '2026-05-17 19:49:24', 1, 1, 1, 1, 1),
(12, 'Basic White T-Shirt Women', 150000, 'asset/woman1.jpg', 'Kaos basic premium berbahan cotton lembut dan nyaman dipakai sehari-hari.', 'woman', '2026-05-17 19:49:24', 1, 1, 1, 1, 1),
(13, 'Bustier Top', 260000, 'asset/bustierT.png', 'Atasan bustier modis yang memberikan siluet tegas dan elegan untuk tampilan kasual maupun formal.', 'woman', '2026-05-17 19:49:24', 1, 1, 1, 1, 1),
(14, 'Crop Knit Hoodie Zip-up', 280000, 'asset/woman3.jpg', 'Hoodie rajut model crop dengan resleting depan yang hangat, trendi, dan nyaman digunakan.', 'woman', '2026-05-17 19:49:24', 1, 1, 1, 1, 1),
(15, 'Casual Shirt Women', 250000, 'asset/woman4.jpg', 'Kemeja kasual dengan potongan santai, sangat cocok untuk aktivitas harian atau hangout.', 'woman', '2026-05-17 19:49:24', 1, 1, 1, 1, 1),
(16, 'Formal Pants', 300000, 'asset/woman5.jpg', 'Celana formal berpotongan rapi dan bahan premium, ideal untuk kerja maupun acara semi-formal.', 'woman', '2026-05-17 19:49:24', 1, 1, 1, 1, 1),
(17, 'Mini Skirt', 210000, 'asset/miniS.jpg', 'Rok mini dengan desain modern yang mudah dipadukan untuk tampilan feminin yang aktif.', 'woman', '2026-05-17 19:49:24', 1, 1, 1, 1, 1),
(18, 'Sport T-Shirt', 200000, 'asset/sportT.jpg', 'Kaos olahraga berbahan cepat kering dan elastis, menjaga kenyamanan optimal saat bergerak.', 'woman', '2026-05-17 19:49:24', 1, 1, 1, 1, 1),
(19, 'Highwaist Straight Jeans', 320000, 'asset/highwaistS.jpg', 'Celana jeans highwaist potongan lurus yang memberikan kesan kaki lebih jenjang dan stylish.', 'woman', '2026-05-17 19:49:24', 1, 1, 1, 1, 1),
(20, 'Mini Dress', 380000, 'asset/miniD.png', 'Gaun mini elegan dengan potongan fit yang anggun, cocok untuk pesta maupun kencan malam.', 'woman', '2026-05-17 19:49:24', 1, 1, 1, 1, 1),
(21, 'Tank Top', 130000, 'asset/tankT.png', 'Tank top basic esensial berbahan adem, sangat pas untuk dalaman atau pakaian santai di rumah.', 'woman', '2026-05-17 19:49:24', 1, 1, 1, 1, 1),
(22, 'Oversize Shirt', 240000, 'asset/oversizeS.jpg', 'Kemeja berukuran oversize dengan gaya kekinian yang memberikan kesan santai namun tetap modis.', 'woman', '2026-05-17 19:49:24', 1, 1, 1, 1, 1),
(23, 'Cropped Ribbed Knit Cardigan', 230000, 'asset/croppedR.jpg', 'Kardigan rajut model crop bertekstur rib yang lembut, sempurna sebagai pelapis pakaian Anda.', 'woman', '2026-05-17 19:49:24', 1, 1, 1, 1, 1),
(24, 'Formal Pants Man', 300000, 'asset/mingyuman5.jpg', 'Celana formal berpotongan rapi dan bahan premium, ideal untuk kerja maupun acara semi-formal.', 'man', '2026-05-17 19:49:24', 1, 1, 1, 1, 1),
(25, 'Rhinestone Cylinder Clutch Bag', 150000, 'asset/aksesoris1.jpg', 'Tas genggam berbentuk silinder dengan hiasan rhinestones berkilau, sempurna untuk melengkapi gaun pesta malam Anda.', 'Accessories', '2026-05-17 19:49:24', 1, 1, 1, 1, 1),
(26, 'Wayfarer Puffer', 350000, 'asset/aksesoris2.jpg', 'Tas model puffer kasual dengan desain modern yang empuk dan ringan, sangat muat banyak untuk kebutuhan harian.', 'Accessories', '2026-05-17 19:49:24', 1, 1, 1, 1, 1),
(27, 'Hobo Bag ', 500000, 'asset/aksesoris3.jpg', 'Tas bahu model hobo berbahan kulit lembut dengan siluet melengkung yang elegan dan kompartemen yang luas.', 'Accessories', '2026-05-17 19:49:24', 1, 1, 1, 1, 1),
(28, 'Montblanc Iced Sea Automatic Date', 250000, 'asset/jamT.jpg', 'Jam tangan otomatis dengan dial bermotif tekstur es yang mewah, memberikan kesan sporty sekaligus profesional.', 'Accessories', '2026-05-17 19:49:24', 1, 1, 1, 1, 1),
(29, 'Collar Necklace ', 300000, 'asset/collarN.jpg', 'Kalung model kerah yang tegas dan berkilau, dirancang khusus sebagai statment piece untuk memperindah lingkar leher.', 'Accessories', '2026-05-17 19:49:24', 1, 1, 1, 1, 1),
(30, 'Santos de Cartier', 800000, 'asset/santosC.jpg', 'Jam tangan berdesain ikonik dengan bezel sekrup persegi yang legendaris, memancarkan kemewahan yang timeless.', 'Accessories', '2026-05-17 19:49:24', 1, 1, 1, 1, 1),
(31, 'Trilogy Ring', 200000, 'asset/trilogyR.jpg', 'Cincin tiga mata yang melambangkan masa lalu, masa kini, dan masa depan, dihiasi batu permata tiruan yang anggun.', 'Accessories', '2026-05-17 19:49:24', 1, 1, 1, 1, 1),
(32, ' Tiffany HardWear & Fred Force 10', 400000, 'asset/tifany.png', 'Perpaduan gelang rantai kokoh bergaya industrial urban dengan sentuhan maritime buckle yang mewah dan bold.', 'Accessories', '2026-05-17 19:49:24', 1, 1, 1, 1, 1),
(33, 'Triangle Bandana Lace', 180000, 'asset/bandana.jpg', 'Bandana rajut renda berbentuk segitiga bergaya vintage yang manis untuk menghias rambut atau pelengkap gaya kasual.', 'Accessories', '2026-05-17 19:49:24', 1, 1, 1, 1, 1),
(34, 'Chopard Alpine Eagle', 270000, 'asset/chopard.jpg', 'Jam tangan mewah dengan desain dial bertekstur iris mata elang yang kontemporer dan strap stainless steel yang kokoh.', 'Accessories', '2026-05-17 19:49:24', 1, 1, 1, 1, 1),
(35, 'Knot Jewelry Set', 320000, 'asset/setJ.jpg', 'Satu set perhiasan bermotif simpul ikatan yang serasi, terdiri dari kalung dan anting untuk tampilan formal yang padu.', 'Accessories', '2026-05-17 19:49:24', 1, 1, 1, 1, 1),
(36, 'Tiffany T1 Narrow Diamond Hinged Bangle', 600000, 'asset/bagle.jpg', 'Gelang bangle ramping dengan motif huruf T ikonik bertabur aksen permata berkilau yang elegan di satu sisinya.', 'Accessories', '2026-05-17 19:49:24', 1, 1, 1, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `ulasan`
--

CREATE TABLE `ulasan` (
  `id` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `nama_produk` varchar(255) NOT NULL,
  `id_order` int(11) NOT NULL,
  `id_produk` int(11) NOT NULL,
  `bintang` tinyint(1) NOT NULL DEFAULT 5,
  `komentar` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ulasan`
--

INSERT INTO `ulasan` (`id`, `id_user`, `nama_produk`, `id_order`, `id_produk`, `bintang`, `komentar`, `created_at`) VALUES
(2, 1, 'Bomber Jacket - Size S', 29, 11, 4, 'jacket nya nyaman dan gabikin gerah', '2026-06-11 21:20:45'),
(3, 1, 'Denim Jacket - Size M', 34, 3, 4, 'jacketnya pas ga kebesaran dan bahannya nyaman', '2026-06-12 15:37:32'),
(4, 1, 'Tank Top - Size M', 38, 21, 5, 'bahannya nyaman dipake seharian', '2026-06-12 21:33:30');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id_user` int(11) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(100) DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `nama_panggilan` varchar(100) DEFAULT NULL,
  `no_telepon` varchar(20) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `jenis_kelamin` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id_user`, `email`, `password`, `tanggal_lahir`, `nama_panggilan`, `no_telepon`, `alamat`, `jenis_kelamin`) VALUES
(1, 'rara@gmail.com', 'rara', '2006-10-20', 'andrea indira', '0812345678910', 'narmada muhajirin utara', ''),
(4, 'rarageulis405@gmail.com', 'songkang', '1998-10-13', 'nayesha', '', '', '');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `keranjang`
--
ALTER TABLE `keranjang`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_user` (`id_user`);

--
-- Indexes for table `produk`
--
ALTER TABLE `produk`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ulasan`
--
ALTER TABLE `ulasan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`id_user`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `keranjang`
--
ALTER TABLE `keranjang`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `produk`
--
ALTER TABLE `produk`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `ulasan`
--
ALTER TABLE `ulasan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE;

--
-- Constraints for table `ulasan`
--
ALTER TABLE `ulasan`
  ADD CONSTRAINT `fk_ulasan_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
