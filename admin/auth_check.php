<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: ../masuk/login.php");
    exit;
}

include('../config/koneksi.php');
?>
