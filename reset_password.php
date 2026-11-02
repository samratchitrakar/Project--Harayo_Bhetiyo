<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['reset_email'])) {
    header("Location: forgot_password.php");
    exit;
}

$email = $_SESSION['reset_email'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entered_otp = trim($_POST['otp']);
    $password    = $_POST['password'];
    $confirm     = $_POST['confirm_password'];

    $stmt = $conn->prepare("SELECT otp, otp_expiry FROM users WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!$row) {
        $error = "Account not found.";
    } elseif (empty($entered_otp) || empty($password) || empty($confirm)) {
        $error = "All fields are required.";
    } elseif ($entered_otp != $row['otp']) {
        $error = "Incorrect OTP. Please try again.";
    } elseif (strtotime($row['otp_expiry']) < time()) {
        $error = "OTP has expired. <a href='forgot_password.php'>Request a new one</a>";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $ustmt = $conn->prepare("UPDATE users SET password=?, otp=NULL, otp_expiry=NULL WHERE email=?");
        $ustmt->bind_param("ss", $hashed, $email);
        $ustmt->execute();

        unset($_SESSION['reset_email']);
        header("Location: login.php?reset=1");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php require 'nav.php'; ?>

<div class="container" style="padding-top:40px;">
    <div class="form-box">
        <h2>Reset Password</h2>
        <p style="color:#666;margin-bottom:20px;font-size:14px;">Enter the OTP sent to<br><strong><?= htmlspecialchars($email) ?></strong> and choose a new password.</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" action="reset_password.php">
            <div class="form-group">
                <label>OTP</label>
                <input type="text" name="otp" maxlength="6" placeholder="000000" required autofocus>
            </div>
            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="password" placeholder="Minimum 6 characters" required>
            </div>
            <div class="form-group">
                <label>Confirm New Password</label>
                <input type="password" name="confirm_password" placeholder="Repeat new password" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;padding:12px;">Reset Password</button>
        </form>

        <p style="margin-top:16px;font-size:13px;color:#888;">
            Did not receive it? <a href="forgot_password.php">Request a new OTP</a>
        </p>
    </div>
</div>

<footer><p>© <?= date('Y') ?> <span style="color:#e94560;">Harayo Bhetiyo</span></p></footer>
</body>
</html>
