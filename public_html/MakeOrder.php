<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Asia/Beirut');
include 'connect.php';
ini_set('session.gc_maxlifetime', 3600); // 1 hour
session_start();
// At the very top, after session_start()
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 3600)) {
    session_unset();
    session_destroy();
    header('Location: login.php?timeout=1');
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();

// Initialize order session if not set
if (!isset($_SESSION['order'])) {
    $_SESSION['order'] = [
        'type' => 'retail', // retail, wholesale, onlineretail, onlinewholesale
        'items' => []
    ];
}

// Handle order type change
if (isset($_POST['order_type'])) {
    $_SESSION['order']['type'] = $_POST['order_type'];
    $_SESSION['order']['items'] = [];
    echo "success";
    exit;
}

// Add item to order (session)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_item') {
    $item = [
        'item_id' => intval($_POST['item_id']),
        'code' => $_POST['code'],
        'color' => $_POST['color'],
        'quantity' => intval($_POST['quantity']),
        'size' => $_POST['size'],
        'unit_price' => floatval($_POST['unit_price']),
        'discount' => floatval($_POST['discount']),
        'selled_price' => floatval($_POST['selled_price'])
    ];
    $_SESSION['order']['items'][] = $item;
    echo "success";
    exit;
}

// Remove item from order (session)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['item_index']) && $_POST['action'] === 'remove_item') {
    $index = intval($_POST['item_index']);
    if (isset($_SESSION['order']['items'][$index])) {
        array_splice($_SESSION['order']['items'], $index, 1);
    }
    echo "success";
    exit;
}

// Cancel order (clear session)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_order') {
    unset($_SESSION['order']);
    echo "success";
    exit;
}

// Submit order: insert into Order, OrderRetail/OrderWholeSale/OnlineOrderRetail/OnlineOrderWholeSale, update Items
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_order') {
    $order_type = $_SESSION['order']['type'];
    $items = $_SESSION['order']['items'];

    // Calculate totals
    $initial_total = 0;
    $total_with_discount = 0;
    $total_stock = 0;

    foreach ($items as $item) {
        $initial_total += $item['unit_price'] * $item['quantity'];
        $total_with_discount += $item['selled_price'] * $item['quantity'];
        // Get stock price from items table
        $res = $conn->query("SELECT stockprice FROM items WHERE id = {$item['item_id']}");
        $stock_price = $res->fetch_assoc()['stockprice'];
        $total_stock += $stock_price * $item['quantity'];
    }
    $total_discount = 100-(($total_with_discount/$initial_total)*100);
    $total_profit = $total_with_discount - $total_stock;

    // Insert into order
    
$order_date = date('Y-m-d H:i:s');
$stmt = $conn->prepare("INSERT INTO `order` (initialtotalprice, totalpricewithdiscount, discount, profit, order_date, status) VALUES (?, ?, ?, ?, ?, 'submitted')");
$stmt->bind_param("dddds", $initial_total, $total_with_discount, $total_discount, $total_profit, $order_date);
    $stmt->execute();
    $order_id = $conn->insert_id;
    $stmt->close();

    // Insert into correct order items table
    if ($order_type === 'retail') {
        $order_items_table = 'orderretail';
    } elseif ($order_type === 'wholesale') {
        $order_items_table = 'orderwholesale';
    } elseif ($order_type === 'onlineretail') {
        $order_items_table = 'onlineorderretail';
    } elseif ($order_type === 'onlinewholesale') {
        $order_items_table = 'onlineorderwholesale';
    } else {
        $order_items_table = 'orderretail'; // fallback
    }
    foreach ($items as $item) {
        $res = $conn->query("SELECT stockprice FROM items WHERE id = {$item['item_id']}");
        $stock_price = $res->fetch_assoc()['stockprice'];
        $stmt = $conn->prepare("INSERT INTO $order_items_table (orderid, itemid, code, color, quantity, size, unitprice, selledprice, discount, stockprice) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissiddddd", $order_id, $item['item_id'], $item['code'], $item['color'], $item['quantity'], $item['size'], $item['unit_price'], $item['selled_price'], $item['discount'], $stock_price);
        $stmt->execute();
        $stmt->close();
        // Update item quantity
        $conn->query("UPDATE items SET quantity = GREATEST(quantity - {$item['quantity']}, 0) WHERE id = {$item['item_id']}");
    }

    unset($_SESSION['order']);
    echo "success";
    exit;
}

// Fetch items for the add item form
$items = $conn->query("SELECT id, code, color, quantity, size, retailprice, wholesaleprice, imagepath FROM items");
$item_data = [];
// Fetch all sizes and build a map: id => name
$sizes = [];
$res = $conn->query("SELECT id, size FROM size");
while ($row = $res->fetch_assoc()) {
    $sizes[$row['id']] = $row['size'];
}
$js_sizes = json_encode($sizes);
while($row = $items->fetch_assoc()) $item_data[] = $row;
$unique_codes = array_values(array_unique(array_column($item_data, 'code')));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Make Order</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/background.css">
    <style>
        .order-id-badge { font-size: 1.1rem; }
        .table td, .table th { vertical-align: middle; }
        .item-card img { height: 180px; object-fit: cover; }
        .modal-xl { max-width: 90vw; }
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
                <li class="nav-item"><a class="nav-link" href="orders.php">Orders</a></li>
                <li class="nav-item"><a class="nav-link active" href="MakeOrder.php">Make Order</a></li>
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
            <span>Make New Order</span>
            <span class="order-id-badge">Order (not yet submitted)</span>
        </div>
        <div class="card-body">
            <div class="mb-3 row">
                <label class="col-sm-2 col-form-label">Order Type</label>
                <div class="col-sm-4">
                    <select id="orderType" class="form-select">
                        <option value="retail" <?= $_SESSION['order']['type'] === 'retail' ? 'selected' : '' ?>>Retail</option>
                        <option value="wholesale" <?= $_SESSION['order']['type'] === 'wholesale' ? 'selected' : '' ?>>Wholesale</option>
                        <option value="onlineretail" <?= $_SESSION['order']['type'] === 'onlineretail' ? 'selected' : '' ?>>OnlineRetail</option>
                        <option value="onlinewholesale" <?= $_SESSION['order']['type'] === 'onlinewholesale' ? 'selected' : '' ?>>OnlineWholeSale</option>
                    </select>
                </div>
                <div class="col-sm-6 text-end">
                    <button id="cancelOrderBtn" class="btn btn-outline-danger">Cancel</button>
                </div>
            </div>
            <div class="mb-3">
                <button id="newItemBtn" class="btn btn-primary">Add Item</button>
            </div>
            <div id="itemSelectionDiv" class="mb-3" style="display:none;"></div>
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Item ID</th>
                            <th>Code</th>
                            <th>Color</th>
                            <th>Quantity</th>
                            <th>Size</th>
                            <th>Unit Price</th>
                            <th>Discount (%)</th>
                            <th>Selled Price</th>
                            <th>Remove</th>
                        </tr>
                    </thead>
                    <tbody id="orderItemsTable">
                        <?php
                        if (!empty($_SESSION['order']['items'])):
                            foreach ($_SESSION['order']['items'] as $idx => $row):
                        ?>
                        <tr data-index="<?= $idx ?>">
                            <td><?= htmlspecialchars($row['item_id']) ?></td>
                            <td><?= htmlspecialchars($row['code']) ?></td>
                            <td><?= htmlspecialchars($row['color']) ?></td>
                            <td><?= $row['quantity'] ?></td>
                            <td><?= htmlspecialchars(isset($sizes[$row['size']]) ? $sizes[$row['size']] : $row['size']) ?></td>
                            <td><?= number_format($row['unit_price'], 2) ?></td>
                            <td><?= number_format($row['discount'], 2) ?></td>
                            <td><?= number_format($row['selled_price'], 2) ?></td>
                            <td>
                                <button class="btn btn-danger btn-sm remove-item-btn" data-index="<?= $idx ?>">Remove</button>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="9" class="text-center">No items in this order yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="row mt-3 align-items-center">
                <div class="col-md-6 text-end">
                    <span class="fw-bold">Total (before discount): </span>
                    <span id="totalBefore" class="me-3"></span>
                    <span class="fw-bold">Total (after discount): </span>
                    <span id="totalAfter"></span>
                </div>
                <div class="col-md-6 text-end">
                    <button id="submitOrderBtn" class="btn btn-success">Submit Order</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Item Picker Modal -->
<div class="modal fade" id="itemPickerModal" tabindex="-1" aria-labelledby="itemPickerModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="itemPickerModalLabel">Select an Item</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="text" id="itemSearchInput" class="form-control mb-3" placeholder="Search by code, color, or size...">
        <div class="row" id="itemGallery"></div>
      </div>
    </div>
  </div>
</div>

<script src="js/jquery-3.7.1.min.js"></script>
<script src="js/bootstrap.bundle.min.js"></script>
<script>
var itemData = <?php echo json_encode($item_data); ?>;
var sizeMap = <?php echo $js_sizes; ?>;

// Helper to get unique values
function unique(arr) {
    return [...new Set(arr)];
}

// Show modal and render gallery
$('#newItemBtn').click(function() {
    $('#itemPickerModal').modal('show');
    renderItemGallery('');
    $('#itemSearchInput').val('');
});

// Render the gallery
function renderItemGallery(filter) {
    var html = '';
    filter = filter.toLowerCase();
    
    // Group items by code and color to avoid duplicates
    var groupedItems = {};
    itemData.forEach(function(item) {
        var code = item.code || '';
        var color = item.color || '';
        var size = sizeMap[item.size] || item.size || '';
        var searchString = (code + ' ' + color + ' ' + size).toLowerCase();
        
        // Skip if doesn't match filter
        if (filter && searchString.indexOf(filter) === -1) return;
        
        var key = code + '|' + color;
        if (!groupedItems[key]) {
            groupedItems[key] = item; // Use first item found for this code+color combination
        }
    });
    
    // Render grouped items
    Object.values(groupedItems).forEach(function(item) {
        var code = item.code || '';
        var color = item.color || '';
        var availableSizes = itemData
            .filter(i => i.code === code && i.color === color)
            .map(i => sizeMap[i.size] || i.size)
            .join(', ');
            
        html += `
        <div class="col-md-3 mb-4">
            <div class="card item-card" style="cursor:pointer;" 
                data-id="${item.id}" 
                data-code="${code}" 
                data-color="${color}" 
                data-size="${item.size}" 
                data-retail="${item.retailprice}" 
                data-wholesale="${item.wholesaleprice}" 
                data-quantity="${item.quantity}">
                <img src="${item.imagepath ? item.imagepath : 'placeholder.png'}" class="card-img-top" alt="${code}">
                <div class="card-body text-center">
                    <div class="fw-bold">${code}</div>
                    <div class="text-muted">${color}</div>
                    <div class="small text-secondary">Sizes: ${availableSizes}</div>
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
    var item = $(this).data();
    $('#itemPickerModal').modal('hide');
    showAddItemForm(item);
});

// Function to show the add item form with color and size dropdowns
function showAddItemForm(selectedItem) {
    // Get all items with the same code
    var itemsWithCode = itemData.filter(i => i.code === selectedItem.code);

    // Get unique colors for this code
    var colors = [...new Set(itemsWithCode.map(i => i.color))];

    // Build the form
    var html = '<form id="addItemForm" class="row g-2 align-items-end">';
    html += `<input type="hidden" name="item_id" id="itemIdInput" value="${selectedItem.id}">`;
    html += `<div class="col-md-2"><label>Code</label><input type="text" class="form-control" name="code" id="codeInput" value="${selectedItem.code}" readonly></div>`;

    // Color dropdown
    html += `<div class="col-md-2"><label>Color</label><select class="form-select" name="color" id="colorSelect">`;
    colors.forEach(function(color) {
        html += `<option value="${color}"${color === selectedItem.color ? ' selected' : ''}>${color}</option>`;
    });
    html += `</select></div>`;

    // Size dropdown (will be filled by JS)
    html += `<div class="col-md-2"><label>Size</label><select class="form-select" name="size" id="sizeSelect"></select></div>`;

    html += `<div class="col-md-2"><label>Qty</label><input type="number" min="1" class="form-control" name="quantity" id="quantityInput" value="1" required></div>`;
    html += `<div class="col-md-2"><label>Unit Price</label><input type="number" min="0" step="0.01" class="form-control" name="unit_price" id="unitPriceInput" readonly required></div>`;
    html += '<div class="col-md-2"><label>Discount (%)</label><input type="number" min="0" max="100" step="0.01" class="form-control" name="discount" id="discountInput" value="0"></div>';
    html += '<div class="col-md-2"><label>Selled Price</label><input type="number" min="0" step="0.01" class="form-control" name="selled_price" id="selledPriceInput" required></div>';
    html += '<div class="col-md-2"><button class="btn btn-success" type="submit">Enter</button></div>';
    html += '</form>';
    $('#itemSelectionDiv').html(html).show();

    // Helper to update sizes and price
    function updateSizeDropdown() {
        var selectedColor = $('#colorSelect').val();
        var itemsWithColor = itemsWithCode.filter(i => i.color === selectedColor);
        var sizes = [...new Set(itemsWithColor.map(i => i.size))];
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
        var orderType = $('#orderType').val();
        var item = itemsWithCode.find(i => i.color === selectedColor && i.size == selectedSize);
        if (item) {
            $('#itemIdInput').val(item.id);
            // Use correct price for each order type
            if (orderType === 'retail' || orderType === 'onlineretail') {
                $('#unitPriceInput').val(item.retailprice);
            } else {
                $('#unitPriceInput').val(item.wholesaleprice);
            }
            $('#quantityInput').attr('max', item.quantity);
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
    $('#orderType').change(function() {
        updatePriceAndQty();
    });
    $('#discountInput').on('input', updateSelledPrice);
    $('#selledPriceInput').on('input', updateDiscount);
}

// Add item to order
$(document).on('submit', '#addItemForm', function(e) {
    e.preventDefault();
    var data = $(this).serializeArray();
    data.push({name: 'action', value: 'add_item'});
    $.post('MakeOrder.php', data, function(resp) {
        if (resp.trim() === 'success') {
            window.location.reload();
        }
    });
});

// Handle order type selection
$('#orderType').change(function() {
    var type = $(this).val();
    $.post('MakeOrder.php', {order_type: type}, function(resp) {
        if (resp.trim() === 'success') {
            window.location.reload();
        }
    });
});

// Cancel order
$('#cancelOrderBtn').click(function() {
    if (confirm('Are you sure you want to cancel this order?')) {
        $.post('MakeOrder.php', {action: 'cancel_order'}, function(resp) {
            if (resp.trim() === 'success') {
                window.location.reload();
            }
        });
    }
});

// Remove item from order
$(document).on('click', '.remove-item-btn', function() {
    var idx = $(this).data('index');
    $.post('MakeOrder.php', {action: 'remove_item', item_index: idx}, function(resp) {
        if (resp.trim() === 'success') {
            window.location.reload();
        }
    });
});

// Totals at the bottom
function updateTotals() {
    var items = <?php echo json_encode(isset($_SESSION['order']['items']) ? $_SESSION['order']['items'] : []); ?>;
    var total = 0;
    var totalAfter = 0;
    items.forEach(function(item) {
        total += parseFloat(item.unit_price) * parseInt(item.quantity);
        totalAfter += parseFloat(item.selled_price) * parseInt(item.quantity);
    });
    $('#totalBefore').text(total.toFixed(2));
    $('#totalAfter').text(totalAfter.toFixed(2));
}
updateTotals();

// Also update totals after adding/removing items
$(document).on('click', '.remove-item-btn', function() {
    setTimeout(updateTotals, 500);
});

// Submit order
$('#submitOrderBtn').click(function() {
    if ($('#orderItemsTable tr[data-index]').length === 0) {
        alert('Add at least one item to submit the order.');
        return;
    }
    $.post('MakeOrder.php', {action: 'submit_order'}, function(resp) {
        if (resp.trim() === 'success') {
            alert('Order submitted!');
            window.location.reload();
        }
    });
});
</script>
</body>
</html>