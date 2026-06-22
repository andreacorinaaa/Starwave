<?php
// Cek apakah admin sudah login
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: ../masuk/login.php");
    exit;
}

// Koneksi database
include('../config/koneksi.php');
?>
