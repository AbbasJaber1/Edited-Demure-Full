<?php
include 'connect.php';

if (!isset($_POST['NeededID'], $_POST['table'])) {
    exit('Missing parameters');
}

$NeededID = intval($_POST['NeededID']);
$table = $_POST['table'];

// Only allow specific tables for security
$allowedTables = ['orderretail', 'orderwholesale', 'onlineorderretail', 'onlineorderwholesale'];
if (!in_array($table, $allowedTables)) {
    exit('Invalid table');
}

// Fetch the order item row (also get OrderID)
$stmt = $conn->prepare("SELECT ItemID, Quantity, OrderID FROM $table WHERE ID = ?");
$stmt->bind_param("i", $NeededID);
$stmt->execute();
$res = $stmt->get_result();
if (!$res->num_rows) {
    exit('Order item not found');
}
$row = $res->fetch_assoc();
$itemId = $row['ItemID'];
$qtyToReturn = $row['Quantity'];
$orderId = $row['OrderID'];

// Update the items table: add back the quantity
$stmt2 = $conn->prepare("UPDATE items SET Quantity = Quantity + ? WHERE ID = ?");
$stmt2->bind_param("ii", $qtyToReturn, $itemId);
if (!$stmt2->execute()) {
    exit('Failed to update stock');
}

// Delete the order item
$stmt3 = $conn->prepare("DELETE FROM $table WHERE ID = ?");
$stmt3->bind_param("i", $NeededID);
if (!$stmt3->execute() && $stmt3->errno !== 0) {
    exit('Failed to delete order item: ' . $stmt3->error);
}

// ...existing code...

// Recalculate order totals after deleting the item
$order_items = $conn->query("SELECT * FROM $table WHERE OrderID = $orderId");
$initial_total = 0;
$total_with_discount = 0;
$total_stock = 0;
while ($row = $order_items->fetch_assoc()) {
    $initial_total += $row['UnitPrice'] * $row['Quantity'];
    $total_with_discount += $row['SelledPrice'] * $row['Quantity'];
    $total_stock += $row['StockPrice'] * $row['Quantity'];
}
$discount = $initial_total > 0 ? 100 - (($total_with_discount / $initial_total) * 100) : 0;
$profit = $total_with_discount - $total_stock;

$stmtUpdateOrder = $conn->prepare("UPDATE `order` SET InitialTotalPrice = ?, TotalPriceWithDiscount = ?, Profit = ?, Discount = ? WHERE ID = ?");
$stmtUpdateOrder->bind_param("dddsi", $initial_total, $total_with_discount, $profit, $discount, $orderId);
$stmtUpdateOrder->execute();
// ...existing code...

// Check if this was the last item in the order
$stmt4 = $conn->prepare("SELECT COUNT(*) as cnt FROM $table WHERE OrderID = ?");
$stmt4->bind_param("i", $orderId);
$stmt4->execute();
$res4 = $stmt4->get_result();
$row4 = $res4->fetch_assoc();
if ($row4['cnt'] == 0) {
    // Delete the order itself
    $stmt5 = $conn->prepare("DELETE FROM `order` WHERE ID = ?");
    $stmt5->bind_param("i", $orderId);
    $stmt5->execute();
    echo 'deleted_order';
} else {
    echo 'success';
}


?>
