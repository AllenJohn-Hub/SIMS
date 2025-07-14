<?php
require_once 'config.php';
header('Content-Type: application/json');

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$qty = isset($_POST['qty']) ? intval($_POST['qty']) : 0;

if ($id <= 0 || $qty <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid id or qty']);
    exit;
}

// Update stock in the database
$stmt = $conn->prepare('UPDATE inventory SET stock = GREATEST(stock - ?, 0) WHERE id = ?');
if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'DB prepare failed']);
    exit;
}
$stmt->bind_param('ii', $qty, $id);
if ($stmt->execute()) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'DB update failed']);
}
$stmt->close();
$conn->close(); 