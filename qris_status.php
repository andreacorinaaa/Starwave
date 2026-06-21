<?php
session_start();
include('config/koneksi.php');

// Kasih tau browser kalau response ini JSON, bukan HTML biasa
header('Content-Type: application/json');

if (!isset($_GET['order_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'order_id tidak ada']);
    exit;
}

// (int) di sini penting buat keamanan — biar order_id nggak bisa
$order_id = (int)$_GET['order_id'];

$stmt = $pdo->prepare("SELECT status_bayar FROM orders WHERE id = ?");
$stmt->execute([$order_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

// Kalau order_id yang dikirim ternyata tidak ada di database
if (!$row) {
    echo json_encode(['status' => 'error', 'message' => 'Order tidak ditemukan']);
    exit;
}

echo json_encode(['status' => $row['status_bayar'] ?? 'pending']);