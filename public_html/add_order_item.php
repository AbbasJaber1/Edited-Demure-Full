<?php
include 'connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = intval($_POST['order_id']);
    $table = $_POST['table'];
    $item_id = intval($_POST['item_id']);
    $code = $_POST['code'];
    $color = $_POST['color'];
    $size = $_POST['size'];
    $quantity = intval($_POST['quantity']);
    $unit_price = floatval($_POST['unit_price']);
    $selled_price = floatval($_POST['selled_price']);
    $discount = floatval($_POST['discount']);

    // Get stock price from items table
    $res = $conn->query("SELECT StockPrice FROM items WHERE ID = $item_id");
    $stock_price = $res && $res->num_rows ? $res->fetch_assoc()['StockPrice'] : 0;

    $stmt = $conn->prepare("INSERT INTO $table (OrderID, ItemID, Code, Color, Quantity, Size, UnitPrice, SelledPrice, Discount, StockPrice) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iissiddddd", $order_id, $item_id, $code, $color, $quantity, $size, $unit_price, $selled_price, $discount, $stock_price);

    if ($stmt->execute()) {
        // Subtract from items table
        $conn->query("UPDATE items SET Quantity = GREATEST(Quantity - $quantity, 0) WHERE ID = $item_id");

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
        $discount_val = $initial_total > 0 ? 100 - (($total_with_discount / $initial_total) * 100) : 0;
        $profit = $total_with_discount - $total_stock;

        $stmt2 = $conn->prepare("UPDATE `order` SET InitialTotalPrice=?, TotalPriceWithDiscount=?, Discount=?, Profit=? WHERE ID=?");
        $stmt2->bind_param("dddsi", $initial_total, $total_with_discount, $discount_val, $profit, $order_id);
        $stmt2->execute();
        $stmt2->close();

        echo 'success';
    } else {
        echo 'Error: ' . $conn->error;
    }
    $stmt->close();
}
?>