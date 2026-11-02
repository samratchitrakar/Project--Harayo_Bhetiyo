<?php
session_start();
require 'db_connect.php';
require 'send_email.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $phone    = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];

    // Validate
    if (empty($name) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } elseif (!empty($phone) && !preg_match('/^9[78][0-9]{8}$/', $phone)) {
        $error = "Phone number must be 10 digits starting with 97 or 98.";
    } else {
        // Check email exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = "This email is already registered.";
        } else {
            // Hash password
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            // Generate OTP
            $otp    = rand(100000, 999999);
            $expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));

            // Insert user
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, phone, otp, otp_expiry, is_verified) VALUES (?,?,?,?,?,?,0)");
            $stmt->bind_param("ssssss", $name, $email, $hashed, $phone, $otp, $expiry);

            if ($stmt->execute()) {
                // Send OTP email
                $sent = sendOTPEmail($email, $name, $otp);

                $_SESSION['pending_email'] = $email;

                if ($sent) {
                    $success = "Registration successful! Check your email for the OTP.";
                } else {
                    $success = "Registered! Email sending failed. OTP: <strong>$otp</strong> (demo mode)";
                }

                header("Location: verify_otp.php");
                exit;
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — Harayo Bhetiyo</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php require 'nav.php'; ?>

<div class="container" style="padding-top:40px;">
    <div class="form-box">
        <h2>Create Account</h2>
        <p style="color:#666;margin-bottom:20px;font-size:14px;">Join Harayo Bhetiyo to report and find lost items.</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <form method="POST" action="register.php">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" placeholder="Your full name" required>
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="your@email.com" required>
            </div>
            <div class="form-group">
                <label>Phone Number</label>
                <input type="text" name="phone" placeholder="98XXXXXXXX" maxlength="10" pattern="9[78][0-9]{8}" inputmode="numeric" oninput="this.value=this.value.replace(/[^0-9]/g,'')">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Minimum 6 characters" required>
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" placeholder="Repeat your password" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;padding:12px;">Register</button>
        </form>
        <p style="text-align:center;margin-top:16px;font-size:14px;">Already have an account? <a href="login.php">Login</a></p>
    </div>
</div>

<footer><p>© <?= date('Y') ?> <span style="color:#e94560;">Harayo Bhetiyo</span></p></footer>
</body>
</html>
