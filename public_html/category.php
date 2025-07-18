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

// Handle AJAX form submission for adding a category
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['categoryName'])) {
    $categoryName = trim($_POST['categoryName']);
    if ($categoryName !== "") {
        $stmt = $conn->prepare("INSERT INTO category (Name) VALUES (?)");
        $stmt->bind_param("s", $categoryName);
        if ($stmt->execute()) {
            echo "<div class='alert alert-success'>Category added successfully!</div>";
        } else {
            echo "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
        }
        $stmt->close();
    } else {
        echo "<div class='alert alert-warning'>Category name cannot be empty.</div>";
    }
    exit; // Only output the message for AJAX
}

// Fetch all categories for display
$categories = $conn->query("SELECT ID, Name FROM category ORDER BY ID DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Categories - Demure Pour Tou</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS (local or CDN) -->
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <!---Background-->
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
                    <li class="nav-item"><a class="nav-link active" href="category.php">Category</a></li>
                    <li class="nav-item"><a class="nav-link" href="items.php">Items</a></li>
                    <li class="nav-item"><a class="nav-link" href="cloth.php">Cloth</a></li>
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
            <h4 class="mb-0">Add Category</h4>
        </div>
        <div class="card-body">
            <div id="catMsg"></div>
            <form id="addCategoryForm" autocomplete="off">
                <div class="mb-3">
                    <label for="categoryName" class="form-label">Category Name</label>
                    <input type="text" class="form-control" id="categoryName" name="categoryName" required>
                </div>
                <button type="submit" class="btn btn-success w-100">Add Category</button>
            </form>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0">All Categories</h5>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th style="width:80px;">ID</th>
                        <th>Name</th>
                    </tr>
                </thead>
                <tbody id="categoryTable">
                    <?php if ($categories && $categories->num_rows > 0): ?>
                        <?php while($row = $categories->fetch_assoc()): ?>
                            <tr>
                                <td><?= $row['ID'] ?></td>
                                <td><?= htmlspecialchars($row['Name']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="2" class="text-center">No categories found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Bootstrap JS (local or CDN) -->
<script src="js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    $('#addCategoryForm').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        $.post('category.php', formData, function(response) {
            $('#catMsg').html(response);
            $('#addCategoryForm')[0].reset();
            // Reload the category table
            $('#categoryTable').load('category.php #categoryTable > *');
        });
    });
});
</script>
</body>
</html>