<?php
header('Content-Type: application/json');
require_once 'config.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['items'], $data['total'], $data['cash_tendered'])) {
        throw new Exception('Missing required data.');
    }
    $items = $data['items'];
    $total = floatval($data['total']);
    $cash = floatval($data['cash_tendered']);
    $subtotal = floatval($data['subtotal'] ?? 0);
    $total_item_discount = floatval($data['total_item_discount'] ?? 0);
    $cart_discount = floatval($data['cart_discount'] ?? 0);
    $cart_discount_amount = floatval($data['cart_discount_amount'] ?? 0);
    
    if (!is_array($items) || count($items) === 0) {
        throw new Exception('No items provided.');
    }
    if ($total <= 0 || $cash < $total) {
        throw new Exception('Invalid total or cash tendered.');
    }

    $conn->begin_transaction();
    // Insert into receipt with discount information
    $stmt = $conn->prepare("INSERT INTO receipt (total_amount, cash_tendered, subtotal, total_item_discount, cart_discount, cart_discount_amount) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('dddddd', $total, $cash, $subtotal, $total_item_discount, $cart_discount, $cart_discount_amount);
    if (!$stmt->execute()) throw new Exception('Failed to save receipt.');
    $receipt_id = $stmt->insert_id;
    $stmt->close();

    // Insert each item and update stock
    $itemStmt = $conn->prepare("INSERT INTO receipt_items (receipt_id, inventory_id, quantity, price_each, discount_percent) VALUES (?, ?, ?, ?, ?)");
    $stockStmt = $conn->prepare("UPDATE inventory SET stock = stock - ? WHERE id = ? AND stock >= ?");
    foreach ($items as $item) {
        $iid = intval($item['id']);
        $qty = intval($item['qty']);
        $price = floatval($item['price']);
        $discount = floatval($item['discount'] ?? 0);
        if ($qty < 1) throw new Exception('Invalid quantity for item.');
        // Insert receipt item with discount
        $itemStmt->bind_param('iiidd', $receipt_id, $iid, $qty, $price, $discount);
        if (!$itemStmt->execute()) throw new Exception('Failed to save receipt item.');
        // Update stock
        $stockStmt->bind_param('iii', $qty, $iid, $qty);
        if (!$stockStmt->execute() || $conn->affected_rows === 0) throw new Exception('Insufficient stock for item.');
    }
    $itemStmt->close();
    $stockStmt->close();
    $conn->commit();
    echo json_encode(['status' => 'success', 'receipt_id' => $receipt_id]);
} catch (Exception $e) {
    if (isset($conn) && $conn->errno === 0) $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} 