<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current  = $_POST['current_password'];
    $new      = $_POST['new_password'];
    $confirm  = $_POST['confirm_password'];

    $stmt = $conn->prepare("SELECT password FROM users WHERE id=?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (empty($current) || empty($new) || empty($confirm)) {
        $error = "All fields are required.";
    } elseif (!password_verify($current, $user['password'])) {
        $error = "Current password is incorrect.";
    } elseif (strlen($new) < 6) {
        $error = "New password must be at least 6 characters.";
    } elseif ($new !== $confirm) {
        $error = "New passwords do not match.";
    } elseif (password_verify($new, $user['password'])) {
        $error = "New password must be different from your current password.";
    } else {
        $hashed = password_hash($new, PASSWORD_DEFAULT);
        $ustmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
        $ustmt->bind_param("si", $hashed, $_SESSION['user_id']);
        $ustmt->execute();
        $success = "Password changed successfully.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Change Password — Harayo Bhetiyo</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php require 'nav.php'; ?>

<div class="container" style="padding-top:40px;">
    <div class="form-box">
        <h2>Change Password</h2>
        <p style="color:#666;margin-bottom:20px;font-size:14px;">Update the password for your account.</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <form method="POST" action="change_password.php">
            <div class="form-group">
                <label>Current Password</label>
                <input type="password" name="current_password" placeholder="Your current password" required>
            </div>
            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="new_password" placeholder="Minimum 6 characters" required>
            </div>
            <div class="form-group">
                <label>Confirm New Password</label>
                <input type="password" name="confirm_password" placeholder="Repeat new password" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;padding:12px;">Change Password</button>
        </form>
    </div>
</div>

<footer><p>© <?= date('Y') ?> <span style="color:#e94560;">Harayo Bhetiyo</span></p></footer>
</body>
</html>
