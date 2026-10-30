<?php
session_start();
require 'db_connect.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Please enter your email and password.";
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if (!$user) {
            $error = "No account found with this email.";
        } elseif (!password_verify($password, $user['password'])) {
            $error = "Incorrect password.";
        } elseif ($user['is_verified'] == 0) {
            $_SESSION['pending_email'] = $email;
            header("Location: verify_otp.php");
            exit;
        } else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name']    = $user['name'];
            $_SESSION['email']   = $user['email'];
            $_SESSION['role']    = $user['role'];

            if ($user['role'] === 'admin') {
                header("Location: admin/dashboard.php");
            } else {
                header("Location: index.php");
            }
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Harayo Bhetiyo</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php require 'nav.php'; ?>

<div class="container" style="padding-top:40px;">
    <div class="form-box">
        <h2>Login</h2>
        <p style="color:#666;margin-bottom:20px;font-size:14px;">Welcome back to Harayo Bhetiyo.</p>

        <?php if (isset($_GET['verified'])): ?>
            <div class="alert alert-success">Email verified! You can now login.</div>
        <?php endif; ?>
        <?php if (isset($_GET['reset'])): ?>
            <div class="alert alert-success">Password reset successful! You can now login with your new password.</div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="your@email.com" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Your password" required>
            </div>
            <p style="text-align:right;margin:-8px 0 16px;font-size:13px;"><a href="forgot_password.php">Forgot password?</a></p>
            <button type="submit" class="btn btn-primary" style="width:100%;padding:12px;">Login</button>
        </form>
        <p style="text-align:center;margin-top:16px;font-size:14px;">No account? <a href="register.php">Register here</a></p>
    </div>
</div>

<footer><p>© <?= date('Y') ?> <span style="color:#e94560;">Harayo Bhetiyo</span></p></footer>
</body>
</html>
