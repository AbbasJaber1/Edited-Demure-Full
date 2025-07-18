<?php
include 'connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['NeededID']);
    $table = $_POST['table'];
    $new_color = $_POST['new_color'];
    $new_size = $_POST['new_size'];
    $new_quantity = intval($_POST['new_quantity']);

    // Get current order item info
    $res = $conn->query("SELECT * FROM $table WHERE ID = $id");
    if (!$res || $res->num_rows == 0) {
        echo 'Error: Order item not found';
        exit;
    }
    $item = $res->fetch_assoc();
    $old_quantity = intval($item['Quantity']);
    $old_item_id = intval($item['ItemID']);
    $order_id = intval($item['OrderID']);
    $Code = $item['Code'];
    $old_color = $item['Color'];
    $old_size = $item['Size'];

    // Determine order type (retail/wholesale)
    $orderType = (strpos($table, 'retail') !== false) ? 'retail' : 'wholesale';
    $priceColumn = ($orderType === 'retail') ? 'RetailPrice' : 'WholeSalePrice';

    // If color or size changed, get the new item ID and prices for the new color/size
    if ($new_color != $old_color || $new_size != $old_size) {
        $res2 = $conn->query("SELECT ID, $priceColumn, StockPrice FROM items WHERE Code ='$Code' AND Color ='$new_color' AND Size ='$new_size' LIMIT 1");
        if (!$res2 || $res2->num_rows == 0) {
            echo 'Error: New item (color/size) not found';
            exit;
        }
        $new_item = $res2->fetch_assoc();
        $new_item_id = intval($new_item['ID']);
        $unit_price = floatval($new_item[$priceColumn]);
        $selled_price = $unit_price; // Add discount logic if needed
        $discount = 0; // Adjust if you have discount logic
        $stock_price = floatval($new_item['StockPrice']);

        // Update the order item with new item ID, Color, Size, and prices
        $stmt = $conn->prepare("UPDATE $table SET ItemID =?, Color =?, Size =?, Quantity =?, UnitPrice =?, SelledPrice =?, Discount =?, StockPrice =? WHERE ID =?");
        $stmt->bind_param("issiddddi", $new_item_id, $new_color, $new_size, $new_quantity, $unit_price, $selled_price, $discount, $stock_price, $id);
        $stmt->execute();
        $stmt->close();

        // Return old quantity to old item, subtract new quantity from new item
        $conn->query("UPDATE items SET Quantity = quantity + $old_quantity WHERE ID = $old_item_id");
        $conn->query("UPDATE items SET Quantity = GREATEST(Quantity - $new_quantity, 0) WHERE ID = $new_item_id");
    } else {
        // Only quantity changed, update and adjust stock
        $stmt = $conn->prepare("UPDATE $table SET Quantity =? WHERE ID =?");
        $stmt->bind_param("ii", $new_quantity, $id);
        $stmt->execute();
        $stmt->close();

        $diff = $old_quantity - $new_quantity;
        if ($diff != 0) {
            $conn->query("UPDATE items SET Quantity = quantity + $diff WHERE ID = $old_item_id");
        }
    }

    // Recalculate order totals
    $order_items = $conn->query("SELECT * FROM $table WHERE OrderID = $order_id");
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

    $stmt2 = $conn->prepare("UPDATE `order` SET InitialTotalPrice =?, TotalPriceWithDiscount =?, Discount =?, Profit =? WHERE ID =?");
    $stmt2->bind_param("dddsi", $initial_total, $total_with_discount, $discount, $profit, $order_id);
    $stmt2->execute();
    $stmt2->close();

    echo 'success';
}
?>