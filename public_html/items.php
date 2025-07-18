<?php
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
include 'connect.php';

// Handle AJAX delete request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['deleteItemId'])) {
    $deleteId = intval($_POST['deleteItemId']);
    // Delete image file from server
    $imgRes = $conn->query("SELECT imagepath FROM items WHERE id = $deleteId");
    if ($imgRes && $imgRow = $imgRes->fetch_assoc()) {
        if (!empty($imgRow['imagepath']) && file_exists($imgRow['imagepath'])) {
            unlink($imgRow['imagepath']);
        }
    }
    $stmt = $conn->prepare("DELETE FROM items WHERE id = ?");
    $stmt->bind_param("i", $deleteId);
    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "error";
    }
    $stmt->close();
    exit;
}

// Handle AJAX form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['deleteItemId'])) {
    $code = $_POST['code'];
    $color = $_POST['color'];
    $quantity = $_POST['quantity'];
    $size = $_POST['size'];
    $category = $_POST['category'];
    $stockPrice = $_POST['stockPrice'];
    $wholeSalePrice = $_POST['wholeSalePrice'];
    $retailPrice = $_POST['retailPrice'];

    // Handle image upload
    $mediaType = $_FILES['image']['type'];
    $target_dir = "uploads/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    $imageFileType = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
    $imagePath = $target_dir . uniqid() . "." . $imageFileType;
    move_uploaded_file($_FILES["image"]["tmp_name"], $imagePath);

    // Insert into database (mediaType is now string)
    $stmt = $conn->prepare("INSERT INTO items (code, color, quantity, size, mediatype, imagepath, stockprice, wholesaleprice, retailprice, category) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssiissdddi", $code, $color, $quantity, $size, $mediaType, $imagePath, $stockPrice, $wholeSalePrice, $retailPrice, $category);

    if ($stmt->execute()) {
        $msg = "<div class='alert alert-success'>Item added successfully!</div>";
    } else {
        $msg = "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
    }
    $stmt->close();

    // If AJAX, just echo the message and exit
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo $msg;
        exit;
    }
}

// Fetch categories and sizes for dropdowns
$categories = $conn->query("SELECT id, name FROM category");
$sizes = $conn->query("SELECT id, size FROM size");

// Fetch all items for display
$items = $conn->query("SELECT items.id, code, color, quantity, size.size AS sizename, mediatype, imagepath, stockprice, wholesaleprice, retailprice, category.name AS categoryname
                       FROM items
                       JOIN size ON items.size = size.id
                       JOIN category ON items.category = category.id
                       ORDER BY items.id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Item - Demure Pour Tou</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS (local or CDN) -->
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <!--Background iAMGES LINK-->
    <link rel="stylesheet" href="css/background.css">
    <!-- jQuery (CDN) -->
    <script src="js/jquery-3.7.1.min.js"></script>
    <script src="js/bootstrap.bundle.min.js"></script>
    <style>
        .container { max-width: 1200px; margin-top: 40px; }
        .card { box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .table td, .table th { vertical-align: middle; }
        .table input { max-width: 100px; }
        .action-btns { display: flex; gap: 4px; }
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
                <li class="nav-item">
                  <a class="nav-link" href="category.php">Category</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link active" href="items.php">Items</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" href="cloth.php">Cloth</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" href="view_items.php">View Items</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" href="user_management.php">User Management</a>
                </li>
            <?php endif; ?>
            <li class="nav-item">
              <a class="nav-link" href="orders.php">Orders</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="MakeOrder.php">Make Order</a>
            </li>
            <?php if (isset($_SESSION['UserName'])): ?>
                <li class="nav-item"><a class="nav-link" href="logout.php">Logout (<?php echo htmlspecialchars($_SESSION['UserName']); ?>)</a></li>
            <?php endif; ?>
          </ul>
        </div>
      </div>
    </nav>
<div class="container">
    <div class="card">
        <div class="card-header bg-success text-white">
            <h4 class="mb-0">Add New Item</h4>
        </div>
        <div class="card-body">
            <div id="formMsg"></div>
            <form id="addItemForm" enctype="multipart/form-data" autocomplete="off">
                <div class="mb-3">
                    <label for="code" class="form-label">Code</label>
                    <input type="text" class="form-control" id="code" name="code" required>
                </div>
                <div class="mb-3">
                    <label for="color" class="form-label">Color</label>
                    <input type="text" class="form-control" id="color" name="color" required>
                </div>
                <div class="mb-3">
                    <label for="quantity" class="form-label">Quantity</label>
                    <input type="number" class="form-control" id="quantity" name="quantity" min="1" required>
                </div>
                <div class="mb-3">
                    <label for="size" class="form-label">Size</label>
                    <select class="form-select" id="size" name="size" required>
                        <option value="">Select Size</option>
                        <?php if ($sizes) { while($row = $sizes->fetch_assoc()): ?>
                            <option value="<?= $row['id'] ?>"><?= $row['size'] ?></option>
                        <?php endwhile; } ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="category" class="form-label">Category</label>
                    <select class="form-select" id="category" name="category" required>
                        <option value="">Select Category</option>
                        <?php if ($categories) { while($row = $categories->fetch_assoc()): ?>
                            <option value="<?= $row['id'] ?>"><?= $row['name'] ?></option>
                        <?php endwhile; } ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="stockPrice" class="form-label">Stock Price</label>
                    <input type="number" step="0.01" class="form-control" id="stockPrice" name="stockPrice" required>
                </div>
                <div class="mb-3">
                    <label for="wholeSalePrice" class="form-label">Wholesale Price</label>
                    <input type="number" step="0.01" class="form-control" id="wholeSalePrice" name="wholeSalePrice" required>
                </div>
                <div class="mb-3">
                    <label for="retailPrice" class="form-label">Retail Price</label>
                    <input type="number" step="0.01" class="form-control" id="retailPrice" name="retailPrice" required>
                </div>
                <div class="mb-3">
                    <label for="image" class="form-label">Item Image</label>
                    <input class="form-control" type="file" id="image" name="image" accept=".jpg,.jpeg,.png" required onchange="previewImage(event)">
                    <div class="mt-2">
                        <img id="imagePreview" src="#" alt="Image Preview" style="display:none; max-width: 100%; max-height: 200px; border: 1px solid #ddd; padding: 4px;">
                    </div>
                </div>
                <button type="submit" class="btn btn-success w-100">Add Item</button>
            </form>
        </div>
    </div>

    <!-- All Items Table -->
    
</div>
<!-- Bootstrap JS (local or CDN) -->
<script src="js/bootstrap.bundle.min.js"></script>
<script>
function previewImage(event) {
    const input = event.target;
    const preview = document.getElementById('imagePreview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        }
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.src = '#';
        preview.style.display = 'none';
    }
}

$(document).ready(function() {
    $('#addItemForm').on('submit', function(e) {
        e.preventDefault();

        var formData = new FormData(this);

        $.ajax({
            url: 'items.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                $('#formMsg').html(response);
                $('#addItemForm')[0].reset();
                $('#imagePreview').attr('src', '#').hide();
                // Reload the items table
                $('#itemsTable').load('items.php #itemsTable > *');
            }
        });
    });

    // Delete item
    $(document).on('click', '.delete-item', function() {
        if (confirm('Are you sure you want to delete this item?')) {
            var itemId = $(this).data('id');
            $.post('items.php', { deleteItemId: itemId }, function(response) {
                if (response.trim() === "success") {
                    $('#itemsTable').load('items.php #itemsTable > *');
                } else {
                    alert('Failed to delete item.');
                }
            });
        }
    });
});
</script>
</body>
</html>