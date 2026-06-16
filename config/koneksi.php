<?php

date_default_timezone_set('Asia/Makassar');

try {
    $pdo = new PDO("mysql:host=localhost;dbname=fashion_store;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec("SET time_zone = '+08:00'");
} catch (PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}

?>