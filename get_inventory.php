<?php
require_once 'config.php';
header('Content-Type: application/json');

$result = $conn->query("SELECT i.id, i.name, i.stock, i.price, c.category FROM inventory i LEFT JOIN category c ON i.category_id = c.category_id ORDER BY i.name ASC");
$items = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
}
echo json_encode(['status' => 'success', 'items' => $items]);
$conn->close(); 