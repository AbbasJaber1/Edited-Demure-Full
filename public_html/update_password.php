<?php
require_once 'connect.php';
session_start();

// Set timezone to match your local timezone
date_default_timezone_set('Asia/Beirut');

$token = $_GET['token'] ?? '';
$error = '';
$success = '';
$showPasswordForm = false;

// Handle token verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_token'])) {
    $token = trim(strtoupper($_POST['token'])); // Normalize token input
    
    $stmt = $conn->prepare("SELECT UserName, token_expire FROM users WHERE reset_token=?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if ($user && strtotime($user['token_expire']) > time()) {
        $showPasswordForm = true;
    } else {
        $error = "Invalid or expired token. Please make sure you entered the token correctly.";
    }
}

// Handle password update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $token = trim(strtoupper($_POST['token'])); // Normalize token
    $newPassword = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    
    if (empty($newPassword) || empty($confirmPassword)) {
        $error = "Both password fields are required.";
        $showPasswordForm = true;
    } elseif (strlen($newPassword) < 6) {
        $error = "Password must be at least 6 characters long.";
        $showPasswordForm = true;
    } elseif ($newPassword !== $confirmPassword) {
        $error = "Passwords do not match.";
        $showPasswordForm = true;
    } else {
        $stmt = $conn->prepare("SELECT UserName, token_expire FROM users WHERE reset_token=?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if ($user && strtotime($user['token_expire']) > time()) {
            $stmt = $conn->prepare("UPDATE users SET Password=?, reset_token=NULL, token_expire=NULL WHERE reset_token=?");
            $stmt->bind_param("ss", $newPassword, $token);
            $stmt->execute();
            $success = "Your password has been updated successfully. You can now log in.";
        } else {
            $error = "Invalid or expired token.";
        }
    }
}

// If token is provided in URL, verify it automatically
if ($token && !$showPasswordForm && !$success) {
    $token = trim(strtoupper($token)); // Normalize token
    $stmt = $conn->prepare("SELECT UserName, token_expire FROM users WHERE reset_token=?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if ($user && strtotime($user['token_expire']) > time()) {
        $showPasswordForm = true;
    } else {
        $error = "Invalid or expired token.";
        $token = '';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Reset Password</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/background.css">
    <style>
        .container { max-width: 500px; margin-top: 60px; }
        .card { box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-success">
    <div class="container">
        <a class="navbar-brand" href="index.php">Demure Pour Tou</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <?php if (!isset($_SESSION['UserName'])): ?>
                    <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                    <li class="nav-item"><a class="nav-link" href="register.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<div class="container">
    <div class="card">
        <div class="card-header bg-success text-white">
            <h4 class="mb-0">Reset Password</h4>
        </div>
        <div class="card-body">
            <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
                <a href="login.php" class="btn btn-primary w-100">Go to Login</a>
            <?php elseif ($showPasswordForm): ?>
                <form method="post" autocomplete="off">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="password" class="form-control" required minlength="6">
                        <div class="form-text">Password must be at least 6 characters long.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required minlength="6">
                    </div>
                    <button class="btn btn-success w-100" type="submit" name="update_password">Update Password</button>
                </form>
            <?php else: ?>
                <form method="post" autocomplete="off">
                    <div class="mb-3">
                        <label class="form-label">Enter Reset Token</label>
                        <input type="text" name="token" class="form-control text-center" 
                               style="font-size: 18px; letter-spacing: 2px; text-transform: uppercase;" 
                               value="<?= htmlspecialchars($token) ?>" 
                               placeholder="XXXXXXXX" 
                               maxlength="8" 
                               required autofocus>
                        <div class="form-text">Enter the 8-character token you received in your email.</div>
                    </div>
                    <button class="btn btn-success w-100" type="submit" name="verify_token">Verify Token</button>
                </form>
                <div class="mt-3 text-center">
                    <a href="forgot_password.php" class="text-muted">Didn't receive a token? Request a new one</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>
