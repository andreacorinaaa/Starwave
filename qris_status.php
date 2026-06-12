<?php
session_start();
include('config/koneksi.php');

header('Content-Type: application/json');

if (!isset($_GET['order_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'order_id tidak ada']);
    exit;
}

$order_id = (int)$_GET['order_id'];

$stmt = mysqli_prepare($conn, "SELECT status_bayar FROM orders WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $order_id);
mysqli_stmt_execute($stmt);
$row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$row) {
    echo json_encode(['status' => 'error', 'message' => 'Order tidak ditemukan']);
    exit;
}

echo json_encode(['status' => $row['status_bayar'] ?? 'pending']);