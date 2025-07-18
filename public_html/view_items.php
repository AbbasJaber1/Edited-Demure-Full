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

// Handle delete item AJAX
if (isset($_POST['delete_id'])) {
    $id = intval($_POST['delete_id']);
    $stmt = $conn->prepare("DELETE FROM items WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    echo "success";
    exit;
}

// Fetch all sizes for dropdown and mapping
$sizes = [];
$res = $conn->query("SELECT id, size FROM size");
while($row = $res->fetch_assoc()) $sizes[$row['id']] = $row['size'];

// Handle edit item AJAX
if (isset($_POST['edit_id'])) {
    $id = intval($_POST['edit_id']);
    $code = $_POST['code'];
    $color = $_POST['color'];
    $quantity = intval($_POST['quantity']);
    $size = $_POST['size'];
    $mediaType = $_POST['mediaType'];
    $stockPrice = floatval($_POST['stockPrice']);
    $wholeSalePrice = floatval($_POST['wholeSalePrice']);
    $retailPrice = floatval($_POST['retailPrice']);
    $category = $_POST['category'];
    $stmt = $conn->prepare("UPDATE items SET Code=?, Color=?, Quantity=?, Size=?, MediaType=?, StockPrice=?, WholeSalePrice=?, RetailPrice=?, Category=? WHERE id=?");
    $stmt->bind_param("ssissdddsi", $code, $color, $quantity, $size, $mediaType, $stockPrice, $wholeSalePrice, $retailPrice, $category, $id);
    $stmt->execute();
    $stmt->close();
    echo "success";
    exit;
}

$items_per_page = 20;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $items_per_page;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_sql = '';
$params = [];
$param_types = '';

if ($search !== '') {
    $search_sql = "WHERE items.Code LIKE ?";
    $params[] = "%$search%";
    $param_types .= 's';
}

// AJAX handler for real-time search
if (isset($_GET['ajax'])) {
     header('Content-Type: application/json');
    // Count total items for pagination
    $count_query = "SELECT COUNT(*) as total FROM items " . ($search_sql ? $search_sql : '');
    $stmt = $conn->prepare($count_query);
    if ($search_sql) $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $total_items_result = $stmt->get_result();
    $total_items = $total_items_result->fetch_assoc()['total'];
    $total_pages = ceil($total_items / $items_per_page);
    $stmt->close();

    // Fetch items for current page (join Size for size name)
    $query = "
        SELECT 
            items.ID, items.Code, items.Color, items.Quantity, items.Size, 
            size.Size AS SizeName,
            items.MediaType, items.ImagePath, items.StockPrice, 
            items.WholeSalePrice, items.RetailPrice, 
            category.Name AS CategoryName, items.Category
        FROM items
        LEFT JOIN category ON items.Category = category.ID
        LEFT JOIN size ON items.Size = size.id
        " . ($search_sql ? $search_sql : '') . "
        LIMIT $items_per_page OFFSET $offset
    ";
    $stmt = $conn->prepare($query);
    if ($search_sql) $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    // Build table rows
    $rows = '';
    while($row = $result->fetch_assoc()) {
        $rows .= '<tr>';
        $rows .= '<td>' . htmlspecialchars($row['ID']) . '</td>';
        $rows .= '<td>' . htmlspecialchars($row['Code']) . '</td>';
        $rows .= '<td>' . htmlspecialchars($row['Color']) . '</td>';
        $rows .= '<td>' . htmlspecialchars($row['Quantity']) . '</td>';
        $rows .= '<td>' . htmlspecialchars($row['SizeName']) . '</td>';
        $rows .= '<td>' . htmlspecialchars($row['MediaType']) . '</td>';
        $rows .= '<td>';
        if (!empty($row['ImagePath'])) {
            $rows .= '<img src="' . htmlspecialchars($row['ImagePath']) . '" alt="Item Image" style="max-width:60px;max-height:60px;">';
        }
        $rows .= '</td>';
        $rows .= '<td>' . htmlspecialchars($row['StockPrice']) . '</td>';
        $rows .= '<td>' . htmlspecialchars($row['WholeSalePrice']) . '</td>';
        $rows .= '<td>' . htmlspecialchars($row['RetailPrice']) . '</td>';
        $rows .= '<td>' . htmlspecialchars($row['CategoryName']) . '</td>';
        $rows .= '<td>
            <button class="btn btn-sm btn-warning edit-btn" 
                data-id="' . $row['ID'] . '"
                data-code="' . htmlspecialchars($row['Code']) . '"
                data-color="' . htmlspecialchars($row['Color']) . '"
                data-quantity="' . htmlspecialchars($row['Quantity']) . '"
                data-size="' . htmlspecialchars($row['Size']) . '"
                data-size-name="' . htmlspecialchars($row['SizeName']) . '"
                data-mediatype="' . htmlspecialchars($row['MediaType']) . '"
                data-stockprice="' . htmlspecialchars($row['StockPrice']) . '"
                data-wholesaleprice="' . htmlspecialchars($row['WholeSalePrice']) . '"
                data-retailprice="' . htmlspecialchars($row['RetailPrice']) . '"
                data-category="' . htmlspecialchars($row['Category']) . '"
            >Edit</button>
            <button class="btn btn-sm btn-danger delete-btn" data-id="' . $row['ID'] . '">Delete</button>
        </td>';
        $rows .= '</tr>';
    }

    // Build pagination
    $pagination = '<ul class="pagination justify-content-center">';
    $pagination .= '<li class="page-item ' . ($page <= 1 ? 'disabled' : '') . '">';
    $pagination .= '<a class="page-link page-link-ajax" href="#" data-page="' . ($page-1) . '">Previous</a></li>';
    for($i = 1; $i <= $total_pages; $i++) {
        $pagination .= '<li class="page-item ' . ($i == $page ? 'active' : '') . '">';
        $pagination .= '<a class="page-link page-link-ajax" href="#" data-page="' . $i . '">' . $i . '</a></li>';
    }
    $pagination .= '<li class="page-item ' . ($page >= $total_pages ? 'disabled' : '') . '">';
    $pagination .= '<a class="page-link page-link-ajax" href="#" data-page="' . ($page+1) . '">Next</a></li>';
    $pagination .= '</ul>';

        if (!$stmt) {
        echo json_encode(['error' => 'SQL prepare failed: ' . $conn->error]);
        exit;
    }

    echo json_encode(['rows' => $rows, 'pagination' => $pagination]);
    exit;
}

// Fetch categories for edit form
$categories = [];
$res = $conn->query("SELECT id, Name FROM category");
while($row = $res->fetch_assoc()) $categories[] = $row;

// Count total items for pagination (for initial page load)
$count_query = "SELECT COUNT(*) as total FROM items " . ($search_sql ? $search_sql : '');
$stmt = $conn->prepare($count_query);
if ($search_sql) $stmt->bind_param($param_types, ...$params);
$stmt->execute();
$total_items_result = $stmt->get_result();
$total_items = $total_items_result->fetch_assoc()['total'];
$total_pages = ceil($total_items / $items_per_page);
$stmt->close();

// Fetch items for current page (join Size for size name)
$query = "
    SELECT 
        items.ID, items.Code, items.Color, items.Quantity, items.Size, 
        size.size AS SizeName,
       items.MediaType, items.ImagePath, items.StockPrice, 
        items.WholeSalePrice, items.RetailPrice, 
        category.Name AS CategoryName, items.Category
    FROM items
    LEFT JOIN category ON items.Category = category.ID
    LEFT JOIN size ON items.Size = size.id
    " . ($search_sql ? $search_sql : '') . "
    LIMIT $items_per_page OFFSET $offset
";
$stmt = $conn->prepare($query);
if ($search_sql) $stmt->bind_param($param_types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Items Log</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/background.css">
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
                    <li class="nav-item"><a class="nav-link" href="category.php">Category</a></li>
                    <li class="nav-item"><a class="nav-link" href="items.php">Items</a></li>
                    <li class="nav-item"><a class="nav-link" href="cloth.php">Cloth</a></li>
                    <li class="nav-item"><a class="nav-link active" href="view_items.php">View Items</a></li>
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
<div class="container mt-4">
    <h2 class="mb-4">Items Log</h2>
    <div class="mb-3">
        <input type="text" id="searchInput" class="form-control" placeholder="Search by Code...">
    </div>
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead class="table-success">
                <tr>
                    <th>ID</th>
                    <th>Code</th>
                    <th>Color</th>
                    <th>Quantity</th>
                    <th>Size</th>
                    <th>Media Type</th>
                    <th>Image</th>
                    <th>Stock Price</th>
                    <th>Wholesale Price</th>
                    <th>Retail Price</th>
                    <th>Category</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="itemsTableBody">
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['ID']) ?></td>
                    <td><?= htmlspecialchars($row['Code']) ?></td>
                    <td><?= htmlspecialchars($row['Color']) ?></td>
                    <td><?= htmlspecialchars($row['Quantity']) ?></td>
                    <td><?= htmlspecialchars($row['SizeName']) ?></td>
                    <td><?= htmlspecialchars($row['MediaType']) ?></td>
                    <td>
                        <?php if (!empty($row['ImagePath'])): ?>
                            <img src="<?= htmlspecialchars($row['ImagePath']) ?>" alt="Item Image" style="max-width:60px;max-height:60px;">
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($row['StockPrice']) ?></td>
                    <td><?= htmlspecialchars($row['WholeSalePrice']) ?></td>
                    <td><?= htmlspecialchars($row['RetailPrice']) ?></td>
                    <td><?= htmlspecialchars($row['CategoryName']) ?></td>
                    <td>
                        <button class="btn btn-sm btn-warning edit-btn"
                            data-id="<?= $row['ID'] ?>"
                            data-code="<?= htmlspecialchars($row['Code']) ?>"
                            data-color="<?= htmlspecialchars($row['Color']) ?>"
                            data-quantity="<?= htmlspecialchars($row['Quantity']) ?>"
                            data-size="<?= htmlspecialchars($row['Size']) ?>"
                            data-size-name="<?= htmlspecialchars($row['SizeName']) ?>"
                            data-mediatype="<?= htmlspecialchars($row['MediaType']) ?>"
                            data-stockprice="<?= htmlspecialchars($row['StockPrice']) ?>"
                            data-wholesaleprice="<?= htmlspecialchars($row['WholeSalePrice']) ?>"
                            data-retailprice="<?= htmlspecialchars($row['RetailPrice']) ?>"
                            data-category="<?= htmlspecialchars($row['Category']) ?>"
                        >Edit</button>
                        <button class="btn btn-sm btn-danger delete-btn" data-id="<?= $row['ID'] ?>">Delete</button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <nav id="paginationNav">
        <ul class="pagination justify-content-center">
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page-1 ?>">Previous</a>
            </li>
            <?php for($i = 1; $i <= $total_pages; $i++): ?>
            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
            </li>
            <?php endfor; ?>
            <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page+1 ?>">Next</a>
            </li>
        </ul>
    </nav>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editItemModal" tabindex="-1" aria-labelledby="editItemModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form id="editItemForm">
        <div class="modal-header">
          <h5 class="modal-title" id="editItemModalLabel">Edit Item</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body row g-3">
            <input type="hidden" name="edit_id" id="edit_id">
            <div class="col-md-4">
                <label class="form-label">Code</label>
                <input type="text" class="form-control" name="code" id="edit_code" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Color</label>
                <input type="text" class="form-control" name="color" id="edit_color" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Quantity</label>
                <input type="number" class="form-control" name="quantity" id="edit_quantity" min="0" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Size</label>
                <select class="form-select" name="size" id="edit_size" required>
                    <?php foreach($sizes as $size_id => $size_name): ?>
                        <option value="<?= $size_id ?>"><?= htmlspecialchars($size_name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Media Type</label>
                <input type="text" class="form-control" name="mediaType" id="edit_mediaType">
            </div>
            <div class="col-md-4">
                <label class="form-label">Stock Price</label>
                <input type="number" class="form-control" name="stockPrice" id="edit_stockPrice" min="0" step="0.01" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Wholesale Price</label>
                <input type="number" class="form-control" name="wholeSalePrice" id="edit_wholeSalePrice" min="0" step="0.01" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Retail Price</label>
                <input type="number" class="form-control" name="retailPrice" id="edit_retailPrice" min="0" step="0.01" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Category</label>
                <select class="form-select" name="category" id="edit_category" required>
                    <?php foreach($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['Name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Save Changes</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
    
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const tableBody = document.getElementById('itemsTableBody');
    const paginationNav = document.getElementById('paginationNav');
    let typingTimer;
    const itemsPerPage = <?= $items_per_page ?>;
    function fetchItems(query = '', page = 1) {
        const xhr = new XMLHttpRequest();
        xhr.open('GET', `<?= basename($_SERVER['PHP_SELF']) ?>?ajax=1&search=${encodeURIComponent(query)}&page=${page}`, true);
        xhr.onload = function() {
            if (xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                tableBody.innerHTML = response.rows;
                paginationNav.innerHTML = response.pagination;
            }
        };
        xhr.send();
    }

    searchInput.addEventListener('input', function() {
        clearTimeout(typingTimer);
        typingTimer = setTimeout(function() {
            fetchItems(searchInput.value);
            
        }, 300); // 300ms debounce
    });

    // Initial load
    fetchItems();

    // Handle pagination clicks (event delegation)
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('page-link-ajax')) {
            e.preventDefault();
            const page = e.target.getAttribute('data-page');
            fetchItems(searchInput.value, page);
        }
    });

    // Edit button click
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('edit-btn')) {
            const btn = e.target;
            document.getElementById('edit_id').value = btn.getAttribute('data-id');
            document.getElementById('edit_code').value = btn.getAttribute('data-code');
            document.getElementById('edit_color').value = btn.getAttribute('data-color');
            document.getElementById('edit_quantity').value = btn.getAttribute('data-quantity');
            document.getElementById('edit_size').value = btn.getAttribute('data-size');
            document.getElementById('edit_mediaType').value = btn.getAttribute('data-mediatype');
            document.getElementById('edit_stockPrice').value = btn.getAttribute('data-stockprice');
            document.getElementById('edit_wholeSalePrice').value = btn.getAttribute('data-wholesaleprice');
            document.getElementById('edit_retailPrice').value = btn.getAttribute('data-retailprice');
            document.getElementById('edit_category').value = btn.getAttribute('data-category');
            var editModal = new bootstrap.Modal(document.getElementById('editItemModal'));
            editModal.show();
        }
    });

    // Edit form submit
    document.getElementById('editItemForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '<?= basename($_SERVER['PHP_SELF']) ?>', true);
        xhr.onload = function() {
            if (xhr.status === 200 && xhr.responseText.trim() === 'success') {
                fetchItems(searchInput.value);
                bootstrap.Modal.getInstance(document.getElementById('editItemModal')).hide();
            }
        };
        xhr.send(formData);
    });

    // Delete button click
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('delete-btn')) {
            if (confirm('Are you sure you want to delete this item?')) {
                const id = e.target.getAttribute('data-id');
                const formData = new FormData();
                formData.append('delete_id', id);
                const xhr = new XMLHttpRequest();
                xhr.open('POST', '<?= basename($_SERVER['PHP_SELF']) ?>', true);
                xhr.onload = function() {
                    if (xhr.status === 200 && xhr.responseText.trim() === 'success') {
                        fetchItems(searchInput.value);
                    }
                };
                xhr.send(formData);
            }
        }
    });
});

</script>
</body>
</html>