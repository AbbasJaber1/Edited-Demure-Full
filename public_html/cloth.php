<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
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
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['deleteClothId'])) {
    $deleteId = intval($_POST['deleteClothId']);
    $stmt = $conn->prepare("DELETE FROM cloth WHERE ID = ?");
    $stmt->bind_param("i", $deleteId);
    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "error";
    }
    $stmt->close();
    exit;
}

// Handle AJAX update request (for UsedQuantity logic)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['editClothId'])) {
    $id = intval($_POST['editClothId']);
    $source = trim($_POST['Source']);
    $clothName = trim($_POST['ClothName']);
    $yardQuantity = floatval($_POST['YardQuantity']);
    $price = floatval($_POST['Price']);
    $usedQuantity = floatval($_POST['UsedQuantity']);
    $remainingClothLocation = trim($_POST['RemainingClothLocation']);

    // Fetch current UsedQuantity and RemainingQuantity from DB
    $res = $conn->query("SELECT UsedQuantity, RemainingQuantity FROM cloth WHERE ID = $id");
    $row = $res->fetch_assoc();
    $currentUsed = floatval($row['UsedQuantity']);
    $currentRemaining = floatval($row['RemainingQuantity']);

    // Add new used to current used
    $newUsed = $currentUsed + $usedQuantity;
    // Subtract used from current remaining
    $newRemaining = $currentRemaining - $usedQuantity;
    if ($newRemaining < 0) $newRemaining = 0;

    $stmt = $conn->prepare("UPDATE cloth SET Source=?, ClothName=?, YardQuantity=?, Price=?, UsedQuantity=?, RemainingQuantity=?, RemainingClothLocation=? WHERE id=?");
    $stmt->bind_param("ssddddsi", $source, $clothName, $yardQuantity, $price, $newUsed, $newRemaining, $remainingClothLocation, $id);
    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "error";
    }
    $stmt->close();
    exit;
}

// Handle AJAX form submission for adding a cloth
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['ClothName']) && !isset($_POST['editClothId'])) {
    $source = trim($_POST['Source']);
    $clothName = trim($_POST['ClothName']);
    $yardQuantity = floatval($_POST['YardQuantity']);
    $price = floatval($_POST['Price']);
    $usedQuantity = 0;
    $remainingQuantity = $yardQuantity;
    $remainingClothLocation = trim($_POST['RemainingClothLocation']);

    if ($clothName !== "") {
        $stmt = $conn->prepare("INSERT INTO cloth (Source, ClothName, YardQuantity, Price, UsedQuantity, RemainingQuantity, RemainingClothLocation) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdddds", $source, $clothName, $yardQuantity, $price, $usedQuantity, $remainingQuantity, $remainingClothLocation);
        if ($stmt->execute()) {
            echo "<div class='alert alert-success'>Cloth added successfully!</div>";
        } else {
            echo "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
        }
        $stmt->close();
    } else {
        echo "<div class='alert alert-warning'>Cloth name cannot be empty.</div>";
    }
    exit; // Only output the message for AJAX
}

// Fetch all cloth records for display
$cloths = $conn->query("SELECT * FROM cloth ORDER BY ID DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Cloth - Demure Pour Tou</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS (local or CDN) -->
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <!---Background-->
    <link rel="stylesheet" href="css/background.css">
    <!-- jQuery (locally) -->
     
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
                    <li class="nav-item"><a class="nav-link " href="category.php">Category</a></li>
                    <li class="nav-item"><a class="nav-link" href="items.php">Items</a></li>
                    <li class="nav-item"><a class="nav-link active" href="cloth.php">Cloth</a></li>
                    <li class="nav-item"><a class="nav-link" href="view_items.php">View Items</a></li>
                    <li class="nav-item"><a class="nav-link" href="user_management.php">User Management</a></li>
                <?php endif; ?>
                <li class="nav-item"><a class="nav-link" href="orders.php">Orders</a></li>
                <li class="nav-item"><a class="nav-link" href="MakeOrder.php">Make Order</a></li>
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
            <h4 class="mb-0">Add Cloth</h4>
        </div>
        <div class="card-body">
            <div id="clothMsg"></div>
            <form id="addClothForm" autocomplete="off">
                <div class="mb-3">
                    <label for="Source" class="form-label">Source</label>
                    <input type="text" class="form-control" id="Source" name="Source" required>
                </div>
                <div class="mb-3">
                    <label for="ClothName" class="form-label">Cloth Name</label>
                    <input type="text" class="form-control" id="ClothName" name="ClothName" required>
                </div>
                <div class="mb-3">
                    <label for="YardQuantity" class="form-label">Yard Quantity</label>
                    <input type="number" step="0.01" class="form-control" id="YardQuantity" name="YardQuantity" required>
                </div>
                <div class="mb-3">
                    <label for="Price" class="form-label">Price</label>
                    <input type="number" step="0.01" class="form-control" id="Price" name="Price" required>
                </div>
                <div class="mb-3">
                    <label for="RemainingClothLocation" class="form-label">Cloth Location</label>
                    <input type="text" class="form-control" id="RemainingClothLocation" name="RemainingClothLocation">
                </div>
                <!-- Hidden fields for UsedQuantity and RemainingQuantity -->
                <input type="hidden" name="UsedQuantity" value="0">
                <input type="hidden" name="RemainingQuantity" id="RemainingQuantity" value="">
                <button type="submit" class="btn btn-success w-100">Add Cloth</button>
            </form>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0">All Cloth</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Source</th>
                        <th>Cloth Name</th>
                        <th>Yard Qty</th>
                        <th>Price</th>
                        <th>Used Qty</th>
                        <th>Remaining Qty</th>
                        <th>Location</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="clothTable">
                    <?php if ($cloths && $cloths->num_rows > 0): ?>
                        <?php while($row = $cloths->fetch_assoc()): ?>
                            <tr data-id="<?= $row['ID'] ?>">
                                <td><?= $row['ID'] ?></td>
                                <td><input type="text" class="form-control form-control-sm source" value="<?= htmlspecialchars($row['Source']) ?>"></td>
                                <td><input type="text" class="form-control form-control-sm clothname" value="<?= htmlspecialchars($row['ClothName']) ?>"></td>
                                <td><input type="number" step="0.01" class="form-control form-control-sm yardqty" value="<?= $row['YardQuantity'] ?>"></td>
                                <td><input type="number" step="0.01" class="form-control form-control-sm price" value="<?= $row['Price'] ?>"></td>
                                <td>
                                    <div>
                                        <div>Total: <span class="usedqty-total"><?= $row['UsedQuantity'] ?></span></div>
                                        <input type="number" step="0.01" class="form-control form-control-sm usedqty" value="0" min="0">
                                    </div>
                                </td>
                                <td><input type="number" step="0.01" class="form-control form-control-sm remainingqty" value="<?= $row['RemainingQuantity'] ?>" readonly></td>
                                <td>
                                    <input type="text" class="form-control form-control-sm location" value="<?= htmlspecialchars($row['RemainingClothLocation']) ?>">
                                </td>
                                <td>
                                    <div class="action-btns">
                                        <button type="button" class="btn btn-success btn-sm submit-edit">Submit</button>
                                        <button type="button" class="btn btn-danger btn-sm delete-cloth">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="9" class="text-center">No cloth records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
                    </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS (local or CDN) -->
    
<script>
$(document).ready(function() {
    // Set RemainingQuantity to YardQuantity on add
    $('#YardQuantity').on('input', function() {
        $('#RemainingQuantity').val($(this).val());
    });
    $('#addClothForm').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        $.post('cloth.php', formData, function(response) {
            $('#clothMsg').html(response);
            $('#addClothForm')[0].reset();
            $('#RemainingQuantity').val('');
            reloadClothTable();
        });
    });

    function reloadClothTable() {
        $('#clothTable').load('cloth.php #clothTable > *', function() {
            $('.usedqty').val('0');
        });
    }

    // Live preview RemainingQuantity in table when UsedQuantity changes
    $(document).on('input', '.usedqty', function() {
        var $row = $(this).closest('tr');
        var used = parseFloat($(this).val()) || 0;
        var actual = parseFloat($row.find('.remainingqty').attr('value')) || 0;
        var preview = actual - used;
        if (preview < 0) preview = 0;
        $row.find('.remainingqty').val(preview);
    });

    // On focusout, reset remaining to actual value (if not submitting)
    $(document).on('blur', '.usedqty', function() {
        var $row = $(this).closest('tr');
        var actual = $row.find('.remainingqty').attr('value');
        $row.find('.remainingqty').val(actual);
    });

    // Edit cloth (event delegation)
    $(document).on('click', '.submit-edit', function() {
        var $row = $(this).closest('tr');
        var id = $row.data('id');
        var source = $row.find('.source').val();
        var clothname = $row.find('.clothname').val();
        var yardqty = $row.find('.yardqty').val();
        var price = $row.find('.price').val();
        var usedqty = $row.find('.usedqty').val();
        var location = $row.find('.location').val();

        $.post('cloth.php', {
            editClothId: id,
            Source: source,
            ClothName: clothname,
            YardQuantity: yardqty,
            Price: price,
            UsedQuantity: usedqty,
            RemainingClothLocation: location
        }, function(response) {
            if (response.trim() === "success") {
                reloadClothTable();
            } else {
                alert('Failed to update cloth.');
            }
        });
    });

    // Delete cloth (event delegation)
    $(document).on('click', '.delete-cloth', function() {
        if (confirm('Are you sure you want to delete this cloth?')) {
            var row = $(this).closest('tr');
            var clothId = row.data('id');
            $.post('cloth.php', { deleteClothId: clothId }, function(response) {
                if (response.trim() === "success") {
                    reloadClothTable();
                } else {
                    alert('Failed to delete cloth.');
                }
            });
        }
    });
});
</script>
</body>
</html>