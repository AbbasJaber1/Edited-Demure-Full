<?php
require_once 'connect.php';
session_start();

// Set timezone to match your local timezone
date_default_timezone_set('Asia/Beirut');

require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->Load();

$mailUsername = $_ENV['MAIL_USERNAME'];
$mailPassword = $_ENV['MAIL_PASSWORD'];
$mailHost = $_ENV['MAIL_HOST'];
$mailPort = $_ENV['MAIL_PORT'];
$mailEncryption = $_ENV['MAIL_ENCRYPTION'];
$mailFromAddress = $_ENV['MAIL_FROM_ADDRESS'];
$mailFromName = $_ENV['MAIL_FROM_NAME'];



$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
    $username = $_POST['username'];
    
    if (empty($username)) {
        $error = "Please enter your username.";
    } else {
        $stmt = $conn->prepare("SELECT UserName, Privilege, email FROM users WHERE UserName=?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

            if ($user && !empty($user['email'])) {
        $token = strtoupper(substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 8));
        $currentTime = time();
        $expiryTime = $currentTime + 3600; // Add 1 hour (3600 seconds)
        $expiry = date("Y-m-d H:i:s", $expiryTime);
        
        // Save token and expiry
        $stmt = $conn->prepare("UPDATE users SET reset_token=?, token_expire=? WHERE UserName=?");
        $stmt->bind_param("sss", $token, $expiry, $username);
        $stmt->execute();

        // Send email
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = $mailHost;
            $mail->SMTPAuth = true;
            $mail->Username = $mailUsername;
            $mail->Password = $mailPassword;
            $mail->SMTPSecure = $mailEncryption;
            $mail->Port = $mailPort;

            $mail->setFrom(
                $mailFromAddress, 
                $mailFromName
            );
            $mail->addAddress($user['email'], $user['UserName']);

            $mail->Subject = 'Password Reset Token - Demure Pour Tou';
            $mail->isHTML(true);
            $mail->Body = "
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background-color: #198754; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                        .content { padding: 20px; background-color: #f8f9fa; border-radius: 0 0 5px 5px; }
                        .token-box { 
                            font-size: 32px; 
                            font-weight: bold; 
                            background-color: #e9ecef; 
                            padding: 20px; 
                            text-align: center; 
                            margin: 20px 0; 
                            border-radius: 8px; 
                            letter-spacing: 3px;
                            border: 2px dashed #198754;
                        }
                        .warning { color: #dc3545; font-weight: bold; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h2>Password Reset Request</h2>
                        </div>
                        <div class='content'>
                            <p>Dear <strong>{$user['UserName']}</strong>,</p>
                            <p>We received a request to reset your password for your Demure Pour Tou account.</p>
                            <p><strong>Your 8-digit reset token is:</strong></p>
                            <div class='token-box'>{$token}</div>
                            <p>Please copy this token and paste it in the password reset form<br></p>
                            <p class='warning'>⚠️ This token will expire in 1 hour for security reasons.</p>
                            <p>If you didn't request this password reset, please ignore this message and your password will remain unchanged.</p>
                        </div>
                    </div>
                </body>
                </html>
            ";

            $mail->send();
            $success = "A password reset token has been sent to your email. Please check your email and use the token in the password reset form.";
        } catch (Exception $e) {
            $error = "Mailer Error: {$mail->ErrorInfo}";
        }
    } else {
        $error = "Username not found or missing email.";
    }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Forgot Password</title>
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
            <h4 class="mb-0">Forgot Password</h4>
        </div>
        <div class="card-body">
            <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
            <?php if (!$success): ?>
                <form method="post" autocomplete="off">
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" required autofocus>
                    </div>
                    <button class="btn btn-success w-100" type="submit">Send Reset Token</button>
                </form>
            <?php else: ?>
                <p>Once you receive the token in your email, click below to enter it:</p>
                <a href="update_password.php" class="btn btn-primary w-100">Enter Reset Token</a>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>
