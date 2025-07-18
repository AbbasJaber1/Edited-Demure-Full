<?php
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

// Set timezone to match your local timezone
date_default_timezone_set('Asia/Beirut');

// Check if user is logged in and is admin
if (!isset($_SESSION['UserName']) || $_SESSION['Privilege'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$error = '';
$success = '';

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'edit_user':
                $userID = $_POST['user_id'];
                $username = $_POST['username'];
                $email = $_POST['email'];
                $password = $_POST['password'];
                $privilege = $_POST['privilege'];
                
                // Basic validation
                if (empty($username) || empty($email)) {
                    $error = "Username and email are required.";
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error = "Please enter a valid email address.";
                } else {
                    // Check if username exists for other users
                    $stmt = $conn->prepare("SELECT id FROM users WHERE UserName=? AND ID != ?");
                    $stmt->bind_param("si", $username, $userID);
                    $stmt->execute();
                    if ($stmt->get_result()->num_rows > 0) {
                        $error = "Username already exists.";
                    } else {
                        // Check if email exists for other users
                        $stmt = $conn->prepare("SELECT id FROM users WHERE email=? AND ID != ?");
                        $stmt->bind_param("si", $email, $userID);
                        $stmt->execute();
                        if ($stmt->get_result()->num_rows > 0) {
                            $error = "Email already exists.";
                        } else {
                            // Update user
                            if (!empty($password)) {
                                $stmt = $conn->prepare("UPDATE users SET UserName=?, email=?, Password=?, Privilege=? WHERE id=?");
                                $stmt->bind_param("ssssi", $username, $email, $password, $privilege, $userID);
                            } else {
                                $stmt = $conn->prepare("UPDATE users SET UserName=?, email=?, Privilege=? WHERE id=?");
                                $stmt->bind_param("sssi", $username, $email, $privilege, $userID);
                            }
                            
                            if ($stmt->execute()) {
                                $success = "User updated successfully.";
                            } else {
                                $error = "Failed to update user.";
                            }
                        }
                    }
                }
                break;
                
            case 'delete_user':
                $userID = $_POST['user_id'];
                
                // Prevent deleting yourself
                $stmt = $conn->prepare("SELECT UserName FROM users WHERE id=?");
                $stmt->bind_param("i", $userID);
                $stmt->execute();
                $user = $stmt->get_result()->fetch_assoc();
                
                if ($user['UserName'] === $_SESSION['UserName']) {
                    $error = "You cannot delete your own account.";
                } else {
                    $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
                    $stmt->bind_param("i", $userID);
                    if ($stmt->execute()) {
                        $success = "User deleted successfully.";
                    } else {
                        $error = "Failed to delete user.";
                    }
                }
                break;
                
            case 'add_user':
                $username = $_POST['username'];
                $email = $_POST['email'];
                $password = $_POST['password'];
                $privilege = $_POST['privilege'];
                
                // Basic validation
                if (empty($username) || empty($email) || empty($password)) {
                    $error = "All fields are required for new user.";
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error = "Please enter a valid email address.";
                } else {
                    // Check if username exists
                    $stmt = $conn->prepare("SELECT id FROM users WHERE UserName=?");
                    $stmt->bind_param("s", $username);
                    $stmt->execute();
                    if ($stmt->get_result()->num_rows > 0) {
                        $error = "Username already exists.";
                    } else {
                        // Check if email exists
                        $stmt = $conn->prepare("SELECT id FROM users WHERE email=?");
                        $stmt->bind_param("s", $email);
                        $stmt->execute();
                        if ($stmt->get_result()->num_rows > 0) {
                            $error = "Email already exists.";
                        } else {
                            // Add new user
                            $stmt = $conn->prepare("INSERT INTO users (UserName, email, Password, Privilege) VALUES (?, ?, ?, ?)");
                            $stmt->bind_param("ssss", $username, $email, $password, $privilege);
                            if ($stmt->execute()) {
                                $success = "User added successfully.";
                            } else {
                                $error = "Failed to add user.";
                            }
                        }
                    }
                }
                break;
        }
    }
}

// Handle AJAX requests for sales data
if (isset($_GET['action']) && $_GET['action'] === 'get_sales_data') {
    $type = $_GET['type'] ?? 'monthly';
    $year = intval($_GET['year'] ?? date('Y'));
    $month = intval($_GET['month'] ?? date('n'));
    $week = intval($_GET['week'] ?? date('W'));
    
    $salesData = [];
    $summary = ['total_sales' => 0, 'total_orders' => 0, 'total_profit' => 0, 'avg_order' => 0];
    
    try {
        switch ($type) {
            case 'daily':
                // Sales by hour for a specific month
                $stmt = $conn->prepare("
                    SELECT 
                        HOUR(order_date) as period,
                        SUM(TotalPriceWithDiscount) as total_sales,
                        COUNT(*) as order_count,
                        SUM(Profit) as total_profit
                    FROM `order` 
                    WHERE YEAR(order_date) = ? AND MONTH(order_date) = ? AND status = 'submitted'
                    GROUP BY HOUR(order_date)
                    ORDER BY period
                ");
                $stmt->bind_param("ii", $year, $month);
                break;
                
            case 'weekly':
                // Sales by day of week for a specific week
                $stmt = $conn->prepare("
                    SELECT 
                        DAYOFWEEK(order_date) as period,
                        SUM(TotalPriceWithDiscount) as total_sales,
                        COUNT(*) as order_count,
                        SUM(Profit) as total_profit
                    FROM `order` 
                    WHERE YEAR(order_date) = ? AND WEEK(order_date, 1) = ? AND status = 'submitted'
                    GROUP BY DAYOFWEEK(order_date)
                    ORDER BY period
                ");
                $stmt->bind_param("ii", $year, $week);
                break;
                
            case 'monthly':
            default:
                // Sales by month for a specific year
                $stmt = $conn->prepare("
                    SELECT 
                        MONTH(order_date) as period,
                        SUM(TotalPriceWithDiscount) as total_sales,
                        COUNT(*) as order_count,
                        SUM(Profit) as total_profit
                    FROM `order` 
                    WHERE YEAR(order_date) = ? AND status = 'submitted'
                    GROUP BY MONTH(order_date)
                    ORDER BY period
                ");
                $stmt->bind_param("i", $year);
                break;
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $salesData[] = $row;
            $summary['total_sales'] += $row['total_sales'];
            $summary['total_orders'] += $row['order_count'];
            $summary['total_profit'] += $row['total_profit'];
        }
        
        $summary['avg_order'] = $summary['total_orders'] > 0 ? $summary['total_sales'] / $summary['total_orders'] : 0;
        
        header('Content-Type: application/json');
        echo json_encode(['sales' => $salesData, 'summary' => $summary]);
        exit;
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
}

// Get all users
$stmt = $conn->prepare("SELECT * FROM users ORDER BY Privilege DESC, UserName ASC");
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>User Management - Demure Pour Tou</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/background.css">
    <style>
        .container-fluid { margin-top: 20px; }
        .card { box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .user-card { border-left: 4px solid #198754; }
        .admin-card { border-left: 4px solid #dc3545; }
        .btn-sm { margin: 2px; }
        .user-info { background-color: #f8f9fa; padding: 10px; border-radius: 5px; margin-bottom: 10px; }
        
        /* Dashboard styles */
        #salesChart { max-height: 400px; }
        .card-header h5 { margin: 0; }
        
        /* Navigation improvements */
        .navbar-nav .nav-link {
            white-space: nowrap;
            padding: 0.5rem 0.75rem;
        }
        .navbar-nav {
            gap: 0.25rem;
        }
        @media (max-width: 991.98px) {
            .navbar-nav .nav-link {
                white-space: normal;
            }
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-success">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">Demure Pour Tou</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <?php if (isset($_SESSION['Privilege']) && $_SESSION['Privilege'] === 'admin'): ?>
                    <li class="nav-item"><a class="nav-link" href="category.php">Category</a></li>
                    <li class="nav-item"><a class="nav-link" href="items.php">Items</a></li>
                    <li class="nav-item"><a class="nav-link" href="cloth.php">Cloth</a></li>
                    <li class="nav-item"><a class="nav-link" href="view_items.php">View Items</a></li>
                    <li class="nav-item"><a class="nav-link active" href="user_management.php">User Management</a></li>
                <?php endif; ?>
                <li class="nav-item"><a class="nav-link" href="orders.php">Orders</a></li>
                <li class="nav-item"><a class="nav-link" href="MakeOrder.php">Make Order</a></li>
                <li class="nav-item"><a class="nav-link" href="logout.php">Logout (<?= $_SESSION['UserName'] ?>)</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4">User Management</h2>
            
            <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

            <!-- Sales Dashboard -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">📊 Sales Dashboard</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label for="chartType" class="form-label">View Type:</label>
                            <select id="chartType" class="form-select">
                                <option value="daily">Daily (Hours)</option>
                                <option value="weekly">Weekly (Days)</option>
                                <option value="monthly" selected>Monthly (Months)</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="chartYear" class="form-label">Year:</label>
                            <select id="chartYear" class="form-select">
                                <?php
                                $currentYear = date('Y');
                                for ($year = $currentYear; $year >= $currentYear - 5; $year--) {
                                    echo "<option value='{$year}'" . ($year == $currentYear ? ' selected' : '') . ">{$year}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3" id="monthFilterDiv" style="display: none;">
                            <label for="chartMonth" class="form-label">Month:</label>
                            <select id="chartMonth" class="form-select">
                                <?php
                                $months = [
                                    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                                    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                                    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
                                ];
                                foreach ($months as $num => $name) {
                                    $selected = ($num == date('n')) ? ' selected' : '';
                                    echo "<option value='{$num}'{$selected}>{$name}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3" id="weekFilterDiv" style="display: none;">
                            <label for="chartWeek" class="form-label">Week:</label>
                            <select id="chartWeek" class="form-select">
                                <?php
                                for ($week = 1; $week <= 53; $week++) {
                                    $selected = ($week == date('W')) ? ' selected' : '';
                                    echo "<option value='{$week}'{$selected}>Week {$week}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-8">
                            <canvas id="salesChart" width="400" height="200"></canvas>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">Sales Summary</h6>
                                    <div id="salesSummary">
                                        <p><strong>Total Sales:</strong> <span id="totalSales">$0.00</span></p>
                                        <p><strong>Total Orders:</strong> <span id="totalOrders">0</span></p>
                                        <p><strong>Total Profit:</strong> <span id="totalProfit">$0.00</span></p>
                                        <p><strong>Average Order:</strong> <span id="avgOrder">$0.00</span></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add New User Button -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">➕ Add New User</h5>
                </div>
                <div class="card-body">
                    <form method="post" class="row g-3">
                        <input type="hidden" name="action" value="add_user">
                        <div class="col-md-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Privilege</label>
                            <select name="privilege" class="form-control" required>
                                <option value="worker">Worker</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">Add User</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Users List -->
            <div class="row">
                <?php foreach ($users as $user): ?>
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card <?= $user['Privilege'] === 'admin' ? 'admin-card' : 'user-card' ?>">
                            <div class="card-header <?= $user['Privilege'] === 'admin' ? 'bg-danger' : 'bg-success' ?> text-white">
                                <h6 class="mb-0">
                                    <?= $user['Privilege'] === 'admin' ? '👑' : '👤' ?> 
                                    <?= htmlspecialchars($user['UserName']) ?>
                                    <?= $user['UserName'] === $_SESSION['UserName'] ? ' (You)' : '' ?>
                                </h6>
                            </div>
                            <div class="card-body">
                                <form method="post" id="form_<?= $user['ID'] ?>">
                                    <input type="hidden" name="action" value="edit_user">
                                    <input type="hidden" name="user_id" value="<?= $user['ID'] ?>">
                                    
                                    <div class="mb-2">
                                        <label class="form-label small">Username</label>
                                        <input type="text" name="username" class="form-control form-control-sm" 
                                               value="<?= htmlspecialchars($user['UserName']) ?>" required>
                                    </div>
                                    
                                    <div class="mb-2">
                                        <label class="form-label small">Email</label>
                                        <input type="email" name="email" class="form-control form-control-sm" 
                                               value="<?= htmlspecialchars($user['email']) ?>" required>
                                    </div>
                                    
                                    <div class="mb-2">
                                        <label class="form-label small">New Password (leave empty to keep current)</label>
                                        <input type="password" name="password" class="form-control form-control-sm" 
                                               placeholder="Enter new password...">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label small">Privilege</label>
                                        <select name="privilege" class="form-control form-control-sm" required>
                                            <option value="worker" <?= $user['Privilege'] === 'worker' ? 'selected' : '' ?>>Worker</option>
                                            <option value="admin" <?= $user['Privilege'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                        </select>
                                    </div>
                                    
                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-warning btn-sm">💾 Update</button>
                                        <?php if ($user['UserName'] !== $_SESSION['UserName']): ?>
                                            <button type="button" class="btn btn-danger btn-sm" 
                                                    onclick="deleteUser(<?= $user['ID'] ?>, '<?= htmlspecialchars($user['UserName']) ?>')">
                                                Delete
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </form>
                            </div>
                            <div class="card-footer text-muted small">
                                <strong>Role:</strong> <?= ucfirst($user['Privilege']) ?><br>
                                <strong>ID:</strong> <?= $user['ID'] ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete user <strong id="deleteUsername"></strong>?</p>
                <p class="text-danger"><strong>This action cannot be undone!</strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="post" style="display: inline;">
                    <input type="hidden" name="action" value="delete_user">
                    <input type="hidden" name="user_id" id="deleteUserId">
                    <button type="submit" class="btn btn-danger">Delete User</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let salesChart;

function deleteUser(userId, username) {
    document.getElementById('deleteUserId').value = userId;
    document.getElementById('deleteUsername').textContent = username;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

// Dashboard functionality
function updateChart() {
    const chartType = document.getElementById('chartType').value;
    const year = document.getElementById('chartYear').value;
    const month = document.getElementById('chartMonth').value;
    const week = document.getElementById('chartWeek').value;
    
    // Show/hide filters based on chart type
    document.getElementById('monthFilterDiv').style.display = chartType === 'daily' ? 'block' : 'none';
    document.getElementById('weekFilterDiv').style.display = chartType === 'weekly' ? 'block' : 'none';
    
    // Fetch data
    const params = new URLSearchParams({
        action: 'get_sales_data',
        type: chartType,
        year: year,
        month: month,
        week: week
    });
    
    fetch('user_management.php?' + params)
        .then(response => response.json())
        .then(data => {
            updateSalesChart(data, chartType);
            updateSalesSummary(data);
        })
        .catch(error => {
            console.error('Error fetching sales data:', error);
        });
}

function updateSalesChart(data, chartType) {
    const ctx = document.getElementById('salesChart').getContext('2d');
    
    if (salesChart) {
        salesChart.destroy();
    }
    
    let labels, chartTitle;
    
    switch(chartType) {
        case 'daily':
            labels = Array.from({length: 24}, (_, i) => `${i}:00`);
            chartTitle = 'Sales by Hour';
            break;
        case 'weekly':
            labels = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            chartTitle = 'Sales by Day of Week';
            break;
        case 'monthly':
            labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            chartTitle = 'Sales by Month';
            break;
    }
    
    // Create datasets
    const salesData = new Array(labels.length).fill(0);
    const orderCountData = new Array(labels.length).fill(0);
    const profitData = new Array(labels.length).fill(0);
    
    // Fill data based on response
    if (data && data.sales) {
        data.sales.forEach(item => {
            let index;
            if (chartType === 'daily') {
                index = parseInt(item.period);
            } else if (chartType === 'weekly') {
                // Convert MySQL DAYOFWEEK (1=Sunday) to our array (0=Monday)
                index = (parseInt(item.period) + 5) % 7;
            } else {
                index = parseInt(item.period) - 1;
            }
            
            if (index >= 0 && index < salesData.length) {
                salesData[index] = parseFloat(item.total_sales);
                orderCountData[index] = parseInt(item.order_count);
                profitData[index] = parseFloat(item.total_profit);
            }
        });
    }
    
    salesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Sales ($)',
                data: salesData,
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.1,
                yAxisID: 'y'
            }, {
                label: 'Orders',
                data: orderCountData,
                borderColor: 'rgb(255, 99, 132)',
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                tension: 0.1,
                yAxisID: 'y1'
            }, {
                label: 'Profit ($)',
                data: profitData,
                borderColor: 'rgb(54, 162, 235)',
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                tension: 0.1,
                yAxisID: 'y'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: chartTitle
                }
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Sales & Profit ($)'
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Number of Orders'
                    },
                    grid: {
                        drawOnChartArea: false,
                    },
                }
            }
        }
    });
}

function updateSalesSummary(data) {
    if (data && data.summary) {
        document.getElementById('totalSales').textContent = '$' + parseFloat(data.summary.total_sales || 0).toFixed(2);
        document.getElementById('totalOrders').textContent = data.summary.total_orders || 0;
        document.getElementById('totalProfit').textContent = '$' + parseFloat(data.summary.total_profit || 0).toFixed(2);
        document.getElementById('avgOrder').textContent = '$' + parseFloat(data.summary.avg_order || 0).toFixed(2);
    }
}

// Event listeners
document.getElementById('chartType').addEventListener('change', updateChart);
document.getElementById('chartYear').addEventListener('change', updateChart);
document.getElementById('chartMonth').addEventListener('change', updateChart);
document.getElementById('chartWeek').addEventListener('change', updateChart);

// Initialize dashboard
document.addEventListener('DOMContentLoaded', function() {
    updateChart();
});
</script>
</body>
</html>
