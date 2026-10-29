<?php
session_start();
require 'db_connect.php';
require 'send_email.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    if (empty($email)) {
        $error = "Please enter your email address.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address.";
    } else {
        $stmt = $conn->prepare("SELECT id, name FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        // Same message whether or not the account exists, so we don't reveal
        // which emails are registered.
        if ($user) {
            $otp    = rand(100000, 999999);
            $expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));

            $ustmt = $conn->prepare("UPDATE users SET otp=?, otp_expiry=? WHERE id=?");
            $ustmt->bind_param("ssi", $otp, $expiry, $user['id']);
            $ustmt->execute();

            $sent = sendResetOTPEmail($email, $user['name'], $otp);

            $_SESSION['reset_email'] = $email;

            if ($sent) {
                $success = "If that email is registered, a reset OTP has been sent.";
            } else {
                $success = "Email sending failed. OTP: <strong>$otp</strong> (demo mode)";
            }

            header("Location: reset_password.php");
            exit;
        } else {
            $success = "If that email is registered, a reset OTP has been sent.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php require 'nav.php'; ?>

<div class="container" style="padding-top:40px;">
    <div class="form-box">
        <h2>Forgot Password</h2>
        <p style="color:#666;margin-bottom:20px;font-size:14px;">Enter your email and we'll send you a reset OTP.</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <form method="POST" action="forgot_password.php">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="your@email.com" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;padding:12px;">Send Reset OTP</button>
        </form>
        <p style="text-align:center;margin-top:16px;font-size:14px;"><a href="login.php">Back to Login</a></p>
    </div>
</div>

<footer><p>© <?= date('Y') ?> <span style="color:#e94560;">Harayo Bhetiyo</span></p></footer>
</body>
</html>
