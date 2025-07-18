<?php
session_start();
// Redirect if already logged in
if (isset($_SESSION['Privilege'])) {
    if ($_SESSION['Privilege'] === 'admin') {
        header("Location: items.php");
        exit;
    } elseif ($_SESSION['Privilege'] === 'worker') {
        header("Location: MakeOrder.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome</title>
    <!-- Bootstrap CSS (local or CDN) -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/background.css">
    <script src="js/bootstrap.bundle.min.js"></script>
    <style>
        .container { max-width: 900px; margin-top: 40px; }
        .card { box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .table td, .table th { vertical-align: middle; }
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
                <?php if (!isset($_SESSION['UserName'])): ?>
                    <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>




</body>
</html>