<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include 'connect.php';

if (isset($_GET['ajax']) && $_GET['ajax'] == 1 && isset($_GET['order_id'])) {
    $orderId = intval($_GET['order_id']);
    // Determine type
    $table = null;
    if ($conn->query("SELECT 1 FROM orderretail WHERE OrderID = $orderId")->num_rows > 0) {
        $table = 'orderretail';
    } elseif ($conn->query("SELECT 1 FROM orderwholesale WHERE OrderID = $orderId")->num_rows > 0) {
        $table = 'orderwholesale';
    } elseif ($conn->query("SELECT 1 FROM onlineorderretail WHERE OrderID = $orderId")->num_rows > 0) {
        $table = 'onlineorderretail';
    } elseif ($conn->query("SELECT 1 FROM onlineorderwholesale WHERE OrderID = $orderId")->num_rows > 0) {
        $table = 'onlineorderwholesale';
    }
    if ($table) {
        echo '<script>var correcttable = "'.$table.'"; var currentOrderId = '.$orderId.';</script>';

        $items = $conn->query("SELECT * FROM $table WHERE OrderID = $orderId");
        // Fetch all sizes for lookup
        $sizes = [];
        $res = $conn->query("SELECT ID, Size FROM size");
        while ($row = $res->fetch_assoc()) {
            $sizes[$row['ID']] = $row['Size'];
        }
        if ($items->num_rows) {
            echo '<button class="btn btn-primary mb-3" id="addOrderItemBtn">Add Item</button>';
            echo '<div id="addOrderItemDiv" style="display:none;"></div>';
            echo '<div class="table-responsive"><table class="table table-bordered">';
            echo '<thead><tr>
                <th>Item ID</th>
                <th>Code</th>
                <th>Color</th>
                <th>Quantity</th>
                <th>Size</th>
                <th>Unit Price</th>
                <th>Selled Price</th>
                <th>Discount</th>
                <th>Stock Price</th>
                <th>Action</th>
            </tr></thead><tbody>';
            while ($row = $items->fetch_assoc()) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($row['ItemID']) . '</td>';
                echo '<td>' . htmlspecialchars($row['Code']) . '</td>';

               $availableColors = [];
                    $resColors = $conn->query("SELECT DISTINCT Color FROM items WHERE Code = '" . $row['Code'] . "'");
                    while ($colorRow = $resColors->fetch_assoc()) {
                        $availableColors[] = $colorRow['Color'];
                    }
                    $colorOptions = '';
                    foreach ($availableColors as $color) {
                        $selected = ($color == $row['Color']) ? ' selected' : '';
                        $colorOptions .= '<option value="' . htmlspecialchars($color) . '"' . $selected . '>' . htmlspecialchars($color) . '</option>';
                    }
                    echo '<td><select class="form-select order-color-select" data-code="' . htmlspecialchars($row['Code']) . '" data-rowid="' . $row['ID'] . '" name="color_' . $row['ID'] . '">' . $colorOptions . '</select></td>';

                    // Editable quantity input
                    echo '<td><input type="number" min="1" class="form-control" name="quantity_' . $row['ID'] . '" value="' . htmlspecialchars($row['Quantity']) . '"></td>';

                    // --- Size dropdown ---
                    $availableSizes = [];
                    $res2 = $conn->query("SELECT DISTINCT Size FROM items WHERE Code = '" . $row['Code'] . "' AND Color = '" . $row['Color'] . "'");
                    while ($sizeRow = $res2->fetch_assoc()) {
                        $availableSizes[] = $sizeRow['Size'];
                    }
                    $sizeOptions = '';
                    foreach ($availableSizes as $sizeId) {
                        $selected = ($sizeId == $row['Size']) ? ' selected' : '';
                        $sizeName = isset($sizes[$sizeId]) ? $sizes[$sizeId] : $sizeId;
                        $sizeOptions .= '<option value="' . $sizeId . '"' . $selected . '>' . htmlspecialchars($sizeName) . '</option>';
                    }
                    echo '<td><select class="form-select order-size-select" data-code="' . htmlspecialchars($row['Code']) . '" data-rowid="' . $row['ID'] . '" name="size_' . $row['ID'] . '">' . $sizeOptions . '</select></td>';
               
                // Defensive: handle nulls for prices
                $unitPrice = isset($row['UnitPrice']) ? number_format($row['UnitPrice'], 2) : '0.00';
                $selledPrice = isset($row['SelledPrice']) ? number_format($row['SelledPrice'], 2) : '0.00';
                $discount = isset($row['Discount']) ? number_format($row['Discount'], 2) : '0.00';
                $stockPrice = isset($row['StockPrice']) ? number_format($row['StockPrice'], 2) : '0.00';

                echo '<td>' . $unitPrice . '</td>';
                echo '<td>' . $selledPrice . '</td>';
                echo '<td>' . $discount . '</td>';
                echo '<td>' . $stockPrice . '</td>';
                // Edit and Delete buttons
                echo '<td>
                    <button class="btn btn-success edit-item-btn" data-id="' . $row['ID'] . '">Edit</button>
                    <button class="btn btn-danger delete-item-btn" data-id="' . $row['ID'] . '">Delete</button>
                </td>';
                echo '</tr>';
            }
            echo '</tbody></table></div>';
        } else {
            echo '<button class="btn btn-primary mb-3" id="addOrderItemBtn">Add Item</button>';
            echo '<div id="addOrderItemDiv" style="display:none;"></div>';
            echo '<div class="text-center">No items found for this order.</div>';
        }
    } else {
        echo '<div class="text-center">No items found for this order.</div>';
    }
    exit;
}

// Handle filters
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$orderType = isset($_GET['order_type']) ? $_GET['order_type'] : 'all';

// Build WHERE clause
$where = [];
$params = [];
if ($orderType == 'retail') {
    $where[] = "`order`.id IN (SELECT OrderID FROM orderretail)";
} elseif ($orderType == 'wholesale') {
    $where[] = "`order`.id IN (SELECT OrderID FROM orderwholesale)";
} elseif ($orderType == 'onlineretail') {
    $where[] = "`order`.id IN (SELECT OrderID FROM onlineorderretail)";
} elseif ($orderType == 'onlinewholesale') {
    $where[] = "`order`.id IN (SELECT OrderID FROM onlineorderwholesale)";
}
if ($date) {
    $where[] = "DATE(`order`.order_date) = ?";
    $params[] = $date;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Fetch orders
$sql = "SELECT * FROM `order` $whereSql ORDER BY ID DESC";
$stmt = $conn->prepare($sql);
if ($date && count($params)) {
    $stmt->bind_param("s", $params[0]);
}
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate monthly totals
$month = date('Y-m', strtotime($date));
$monthStart = $month . '-01';
$monthEnd = date('Y-m-t', strtotime($monthStart));
$monthWhere = [];
if ($orderType == 'retail') {
    $monthWhere[] = "`order`.ID IN (SELECT OrderID FROM orderretail)";
} elseif ($orderType == 'wholesale') {
    $monthWhere[] = "`order`.ID IN (SELECT OrderID FROM orderwholesale)";
} elseif ($orderType == 'onlineretail') {
    $monthWhere[] = "`order`.ID IN (SELECT OrderID FROM onlineorderretail)";
} elseif ($orderType == 'onlinewholesale') {
    $monthWhere[] = "`order`.ID IN (SELECT OrderID FROM onlineorderwholesale)";
}
$monthWhere[] = "DATE(`order`.order_date) BETWEEN '$monthStart' AND '$monthEnd'";
$monthWhereSql = 'WHERE ' . implode(' AND ', $monthWhere);
$totals = $conn->query("SELECT SUM(TotalPriceWithDiscount) as total_discount, SUM(Profit) as total_profit FROM `order` $monthWhereSql")->fetch_assoc();

// Calculate daily totals
$dailyWhere = [];
if ($orderType == 'retail') {
    $dailyWhere[] = "`order`.ID IN (SELECT OrderID FROM orderretail)";
} elseif ($orderType == 'wholesale') {
    $dailyWhere[] = "`order`.ID IN (SELECT OrderID FROM orderwholesale)";
} elseif ($orderType == 'onlineretail') {
    $dailyWhere[] = "`order`.ID IN (SELECT OrderID FROM onlineorderretail)";
} elseif ($orderType == 'onlinewholesale') {
    $dailyWhere[] = "`order`.ID IN (SELECT OrderID FROM onlineorderwholesale)";
}
$dailyWhere[] = "DATE(`order`.order_date) = '$date'";
$dailyWhereSql = 'WHERE ' . implode(' AND ', $dailyWhere);
$dailyTotals = $conn->query("SELECT SUM(TotalPriceWithDiscount) as total_discount, SUM(Profit) as total_profit FROM `order` $dailyWhereSql")->fetch_assoc();

// Fetch all items and sizes for the add item modal
$allItems = [];
$res = $conn->query("SELECT ID, Code, Color, Quantity, Size, RetailPrice, WholeSalePrice, ImagePath FROM items");
while($row = $res->fetch_assoc()) $allItems[] = $row;
$sizes = [];
$res2 = $conn->query("SELECT ID, Size FROM size");
while ($row = $res2->fetch_assoc()) {
    $sizes[$row['ID']] = $row['Size'];
}
$js_sizes = json_encode($sizes);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Orders</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/background.css">
    <style>
        .modal-xl { max-width: 90vw; }
        .item-card img { height: 120px; object-fit: cover; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-success">
    <div class="container">
        <a class="navbar-brand" href="index.php">Demure Pour Tou</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <?php if (isset($_SESSION['Privilege']) && $_SESSION['Privilege'] === 'admin'): ?>
                    <li class="nav-item"><a class="nav-link" href="category.php">Category</a></li>
                    <li class="nav-item"><a class="nav-link" href="items.php">Items</a></li>
                    <li class="nav-item"><a class="nav-link" href="cloth.php">Cloth</a></li>
                    <li class="nav-item"><a class="nav-link" href="view_items.php">View Items</a></li>
                    <li class="nav-item"><a class="nav-link" href="user_management.php">User Management</a></li>
                <?php endif; ?>
                <li class="nav-item"><a class="nav-link active" href="orders.php">Orders</a></li>
                <li class="nav-item"><a class="nav-link" href="MakeOrder.php">Make Order</a></li>
                <?php if (isset($_SESSION['UserName'])): ?>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Logout (<?php echo htmlspecialchars($_SESSION['UserName']); ?>)</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<div class="container mt-4">
    <div class="card">
        <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
            <span>Orders</span>
        </div>
        <div class="card-body">
            <form class="row g-3 mb-3" method="get">
                <div class="col-md-3">
                    <label for="date" class="form-label">Date</label>
                    <input type="date" id="date" name="date" class="form-control" value="<?= htmlspecialchars($date) ?>">
                </div>
                <div class="col-md-3">
                    <label for="order_type" class="form-label">Order Type</label>
                    <SELECT ID="order_type" Name="order_type" class="form-select">
                        <option value="all" <?= $orderType == 'all' ? 'selected' : '' ?>>All</option>
                        <option value="retail" <?= $orderType == 'retail' ? 'selected' : '' ?>>Retail</option>
                        <option value="wholesale" <?= $orderType == 'wholesale' ? 'selected' : '' ?>>Wholesale</option>
                        <option value="onlineretail" <?= $orderType == 'onlineretail' ? 'selected' : '' ?>>OnlineRetail</option>
                        <option value="onlinewholesale" <?= $orderType == 'onlinewholesale' ? 'selected' : '' ?>>OnlineWholeSale</option>
                    </select>
                </div>
                <div class="col-md-3 align-self-end">
                    <button class="btn btn-primary" type="submit">Filter</button>
                </div>
            </form>
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Total (with discount)</th>
                            <?php if (isset($_SESSION['Privilege']) && $_SESSION['Privilege'] === 'admin'): ?>
                                <th>Profit</th>
                            <?php endif; ?>
                            <th>Status</th>
                            <th>View</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($orders): foreach ($orders as $order): ?>
                        <tr>
                            <td><?= $order['ID'] ?></td>
                            <td><?= $order['order_date'] ?></td>
                            <td>
                                <?php
                                // Determine order type for display
                                if ($conn->query("SELECT 1 FROM orderretail WHERE OrderID = {$order['ID']}")->num_rows > 0) {
                                    echo 'Retail';
                                } elseif ($conn->query("SELECT 1 FROM orderwholesale WHERE OrderID = {$order['ID']}")->num_rows > 0) {
                                    echo 'Wholesale';
                                } elseif ($conn->query("SELECT 1 FROM onlineorderretail WHERE OrderID = {$order['ID']}")->num_rows > 0) {
                                    echo 'OnlineRetail';
                                } elseif ($conn->query("SELECT 1 FROM onlineorderwholesale WHERE OrderID = {$order['ID']}")->num_rows > 0) {
                                    echo 'OnlineWholeSale';
                                } else {
                                    echo 'Unknown';
                                }
                                ?>
                            </td>
                            <td><?= number_format($order['TotalPriceWithDiscount'], 2) ?></td>
                            <?php if (isset($_SESSION['Privilege']) && $_SESSION['Privilege'] === 'admin'): ?>
                                <td><?= number_format($order['Profit'], 2) ?></td>
                            <?php endif; ?>
                            <td><?= htmlspecialchars($order['status']) ?></td>
                            <td>
                                <button class="btn btn-info btn-sm view-order-btn" data-orderid="<?= $order['ID'] ?>">View Order</button>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="<?php echo (isset($_SESSION['Privilege']) && $_SESSION['Privilege'] === 'admin') ? '7' : '6'; ?>" class="text-center">No orders found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="row mt-3">
                <div class="col-md-6">
                    <span class="fw-bold">Total (with discount) for <span class="text-primary"><?= htmlspecialchars($date) ?></span>: </span>
                    <span><?= number_format((float)($dailyTotals['total_discount'] ?? 0), 2) ?></span>

                    <br>
                    <?php if (isset($_SESSION['Privilege']) && $_SESSION['Privilege'] === 'admin'): ?>
                        <span class="fw-bold">Total Profit for <span class="text-primary"><?= htmlspecialchars($date) ?></span>: </span>
                        <span><?= number_format((float)($dailyTotals['total_profit'] ?? 0), 2) ?></span>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <span class="fw-bold">Total (with discount) for month: </span>
                    <span><?= number_format((float)($totals['total_discount'] ?? 0), 2) ?></span>
                    <br>
                    <?php if (isset($_SESSION['Privilege']) && $_SESSION['Privilege'] === 'admin'): ?>
                        <span class="fw-bold">Total Profit for month: </span>
                        <span><?= number_format($totals['total_profit'], 2) ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Order Items Modal -->
<div class="modal fade" id="orderItemsModal" tabindex="-1" aria-labelledby="orderItemsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="orderItemsModalLabel">Order Items</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="orderItemsModalBody">
        <!-- Items will be loaded here -->
      </div>
    </div>
  </div>
</div>

<!-- Add Item Picker Modal -->
<div class="modal fade" id="itemPickerModal" tabindex="-1" aria-labelledby="itemPickerModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="itemPickerModalLabel">SELECT an Item</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="text" ID="itemSearchInput" class="form-control mb-3" placeholder="Search by code, Color, or size...">
        <div class="row" id="itemGallery"></div>
      </div>
    </div>
  </div>
</div>

<script src="js/jquery-3.7.1.min.js"></script>
<script src="js/bootstrap.bundle.min.js"></script>
<script>
var allItemData = <?php echo json_encode($allItems); ?>;
var sizeMap = <?php echo $js_sizes; ?>;

$(function() {
    $('.view-order-btn').click(function() {
        var orderId = $(this).data('orderid');
        $('#orderItemsModalLabel').text('Order #' + orderId + ' Items');
        $('#orderItemsModalBody').html('<div class="text-center">Loading...</div>');
        $('#orderItemsModal').modal('show');
        $.get('orders.php', {ajax: 1, order_id: orderId}, function(resp) {
            // Hide profit column if not admin
            <?php if (!isset($_SESSION['Privilege']) || $_SESSION['Privilege'] !== 'admin'): ?>
            var $body = $('<div>' + resp + '</div>');
            $body.find('th:contains("Stock Price"),th:contains("Profit"),td:nth-child(9)').hide();
            $body.find('th:contains("Profit")').hide();
            $body.find('td:nth-child(9)').hide();
            $('#orderItemsModalBody').html($body.html());
            <?php else: ?>
            $('#orderItemsModalBody').html(resp);
            <?php endif; ?>
        });
    });


    // Dynamic size dropdown for order items table
    $(document).on('change', '.order-color-select', function() {
        var $colorSelect = $(this);
        var code = $colorSelect.data('code');
        var rowid = $colorSelect.data('rowid');
        var selectedColor = $colorSelect.val();

        // Find all sizes for this code and color
        var sizes = [];
        allItemData.forEach(function(item) {
            if (item.Code === code && item.Color === selectedColor) {
                sizes.push(item.Size);
            }
        });

        // Remove duplicates
        sizes = [...new Set(sizes)];

        // Build options
        var sizeOptions = '';
        sizes.forEach(function(sizeId) {
            var sizeName = sizeMap[sizeId] || sizeId;
            sizeOptions += '<option value="' + sizeId + '">' + sizeName + '</option>';
        });

        // Update the size dropdown in the same row
        var $sizeSelect = $('select.order-size-select[data-rowid="' + rowid + '"]');
        $sizeSelect.html(sizeOptions);
    });


    // Edit item (Color, Size, Quantity)
    $(document).on('click', '.edit-item-btn', function() {
        var NeededId = $(this).data('id');
        var newColor = $('select[name="color_' + NeededId + '"]').val();
        var newSize = $('select[name="size_' + NeededId + '"]').val();
        var newQty = $('input[name="quantity_' + NeededId + '"]').val();
        $.ajax({
            type: 'POST',
            url: 'update_order_item.php',
            data: {
                NeededID: NeededId,
                new_color: newColor,
                new_size: newSize,
                new_quantity: newQty,
                table: correcttable,
            },
            success: function(response) {
                if ($.trim(response) === 'success') {
                    location.reload();
                } else {
                    alert('Failed to update order item. \n\nServer said: ' + response);
                }
            },
            error: function(jqXHR) {
                alert('A critical error occurred.\n\n' + jqXHR.responseText);
            }
        });
    });

    // Delete item
    $(document).on('click', '.delete-item-btn', function() {
        if (!confirm('Are you sure you want to remove this item from the order?')) return;
        var NeededId = $(this).data('id');
        $.ajax({
            type: 'POST',
            url: 'delete_order_item.php',
            data: {
                NeededID: NeededId,
                table: correcttable,
            },
            success: function(response) {
                var resp = $.trim(response);
                if (resp === 'success' || resp === 'deleted_order') {
                    location.reload();
                } else {
                    alert('Failed to delete order item. \n\nServer said: ' + response);
                }
            },
            error: function(jqXHR) {
                alert('A critical error occurred.\n\n' + jqXHR.responseText);
            }
        });
    });

    // Add new item (show picker)
    $(document).on('click', '#addOrderItemBtn', function() {
        $('#itemPickerModal').modal('show');
        renderItemGallery('');
        $('#itemSearchInput').val('');
    });

    // Render the gallery
    function renderItemGallery(filter) {
        var html = '';
        filter = filter.toLowerCase();
        allItemData.forEach(function(item) {
    var code = item.Code || '';
    var color = item.Color || '';
    var size = sizeMap[item.Size] || item.Size || '';
    var searchString = (code + ' ' + color + ' ' + size).toLowerCase();
    if (filter && searchString.indexOf(filter) === -1) return;
    html += `
    <div class="col-md-3 mb-4">
        <div class="card item-card" style="cursor:pointer;" 
            data-id="${item.ID}" 
            data-Code="${code}" 
            data-Color="${color}" 
            data-Size="${item.Size}" 
            data-retail="${item.RetailPrice}" 
            data-wholesale="${item.WholeSalePrice}" 
            data-Quantity="${item.Quantity}">
            <img src="${item.ImagePath ? item.ImagePath : 'placeholder.png'}" class="card-img-top" alt="${code}">
            <div class="card-body text-center">
                <div class="fw-bold">${code}</div>
                <div>${color} ${size}</div>
            </div>
        </div>
    </div>
    `;
});
        $('#itemGallery').html(html);
    }

    // Search bar in modal
    $('#itemSearchInput').on('input', function() {
        renderItemGallery($(this).val());
    });

    // When an item is clicked in the modal
    $(document).on('click', '.item-card', function() {
    var $card = $(this);
    var item = {
        ID: $card.data('id'),
        Code: $card.data('code'),
        Color: $card.data('color'),
        Size: $card.data('size'),
        RetailPrice: $card.data('retail'),
        WholeSalePrice: $card.data('wholesale'),
        Quantity: $card.data('quantity'),
        ImagePath: $card.data('imagepath')
    };
    $('#itemPickerModal').modal('hide');
    showAddOrderItemForm(item);
});

    // Show add item form
    function showAddOrderItemForm(selectedItem) {
    // Get all items with the same code
    var itemsWithCode = allItemData.filter(i => i.Code === selectedItem.Code);

    // Get unique colors for this code
    var colors = [...new Set(itemsWithCode.map(i => i.Color))];

    // Build the form
   // Build the form
var html = '<form id="addOrderItemForm" class="row g-2 align-items-end">';
html += `<input type="hidden" name="item_id" id="itemIdInput" value="${selectedItem.ID}">`;
html += `<input type="hidden" name="order_id" value="${typeof currentOrderId !== 'undefined' ? currentOrderId : ''}">`;
html += `<input type="hidden" name="table" value="${typeof correcttable !== 'undefined' ? correcttable : ''}">`;
html += `<div class="col-md-2"><label>Code</label><input type="text" class="form-control" name="code" id="codeInput" value="${selectedItem.Code}" readonly></div>`;

html += `<div class="col-md-2"><label>Color</label><select class="form-select" name="color" id="colorSelect">`;
colors.forEach(function(Color) {
    html += `<option value="${Color}"${Color === selectedItem.Color ? ' selected' : ''}>${Color}</option>`;
});
html += `</select></div>`;

html += `<div class="col-md-2"><label>Size</label><select class="form-select" name="size" id="sizeSelect"></select></div>`;

html += `<div class="col-md-2"><label>Qty</label><input type="number" min="1" class="form-control" name="quantity" id="quantityInput" value="1" required></div>`;
html += `<div class="col-md-2"><label>Unit Price</label><input type="number" min="0" step="0.01" class="form-control" name="unit_price" id="unitPriceInput" readonly required></div>`;
html += '<div class="col-md-2"><label>Discount (%)</label><input type="number" min="0" max="100" step="0.01" class="form-control" name="discount" id="discountInput" value="0"></div>';
html += '<div class="col-md-2"><label>Selled Price</label><input type="number" min="0" step="0.01" class="form-control" name="selled_price" id="selledPriceInput" required></div>';
html += '<div class="col-md-2"><button class="btn btn-success" type="submit">Add</button></div>';
html += '</form>';
$('#addOrderItemDiv').html(html).show();
    // Helper to update sizes and price
    function updateSizeDropdown() {
        var selectedColor = $('#colorSelect').val();
        var itemsWithColor = itemsWithCode.filter(i => i.Color === selectedColor);
        var sizes = [...new Set(itemsWithColor.map(i => i.Size))]; // <-- FIXED HERE
        var sizeOptions = '';
        sizes.forEach(function(sizeId) {
            var sizeName = sizeMap[sizeId] || sizeId;
            sizeOptions += `<option value="${sizeId}">${sizeName}</option>`;
        });
        $('#sizeSelect').html(sizeOptions);

        // After updating sizes, update price and quantity
        updatePriceAndQty();
    }

    function updatePriceAndQty() {
        var selectedColor = $('#colorSelect').val();
        var selectedSize = $('#sizeSelect').val();
        var item = itemsWithCode.find(i => i.Color === selectedColor && i.Size == selectedSize); // <-- FIXED HERE
        if (item) {
            $('#itemIdInput').val(item.ID);
            $('#unitPriceInput').val(item.RetailPrice); // You can adjust for wholesale if needed
            $('#quantityInput').attr('max', item.Quantity);
            $('#quantityInput').val(1);
            updateSelledPrice();
        }
    }

    // Live price/discount sync
    function updateSelledPrice() {
        var unitPrice = parseFloat($('#unitPriceInput').val()) || 0;
        var discount = parseFloat($('#discountInput').val()) || 0;
        var selledPrice = unitPrice * (1 - discount / 100);
        $('#selledPriceInput').val(selledPrice.toFixed(2));
    }
    function updateDiscount() {
        var unitPrice = parseFloat($('#unitPriceInput').val()) || 0;
        var selledPrice = parseFloat($('#selledPriceInput').val()) || 0;
        var discount = unitPrice > 0 ? (1 - (selledPrice / unitPrice)) * 100 : 0;
        $('#discountInput').val(discount.toFixed(2));
    }

    // Initial setup
    updateSizeDropdown();

    $('#colorSelect').change(function() {
        updateSizeDropdown();
    });
    $('#sizeSelect').change(function() {
        updatePriceAndQty();
    });
    $('#discountInput').on('input', updateSelledPrice);
    $('#selledPriceInput').on('input', updateDiscount);
}

    // Add item to order
    $(document).on('submit', '#addOrderItemForm', function(e) {
    e.preventDefault();
    var data = $(this).serialize(); // <-- Use serialize() instead of serializeArray()
    $.post('add_order_item.php', data, function(resp) {
        if (resp.trim() === 'success') {
            location.reload();
        } else {
            alert('Failed to add item. \n\nServer said: ' + resp);
        }
    });
});
});
</script>
</body>
</html>