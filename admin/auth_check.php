<?php
// Cek apakah admin sudah login
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: login_admin.php");
    exit;
}

// Koneksi database
include('../config/koneksi.php');
?>
