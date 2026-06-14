<?php
session_start();
include('config/koneksi.php');

header('Content-Type: application/json');

if (!isset($_GET['order_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'order_id tidak ada']);
    exit;
}

$order_id = (int)$_GET['order_id'];

$stmt = $pdo->prepare("SELECT status_bayar FROM orders WHERE id = ?");
$stmt->execute([$order_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo json_encode(['status' => 'error', 'message' => 'Order tidak ditemukan']);
    exit;
}

echo json_encode(['status' => $row['status_bayar'] ?? 'pending']);