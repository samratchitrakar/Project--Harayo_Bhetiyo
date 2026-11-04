<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name']);
    $phone = trim($_POST['phone']);

    if (empty($name)) {
        $error = "Name cannot be empty.";
    } elseif (!empty($phone) && !preg_match('/^9[78][0-9]{8}$/', $phone)) {
        $error = "Phone number must be 10 digits starting with 97 or 98.";
    } else {
        $stmt = $conn->prepare("UPDATE users SET name=?, phone=? WHERE id=?");
        $stmt->bind_param("ssi", $name, $phone, $user_id);
        $stmt->execute();
        $_SESSION['name'] = $name;
        $success = "Profile updated successfully.";
    }
}

$stmt = $conn->prepare("SELECT name, email, phone FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile — Harayo Bhetiyo</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php require 'nav.php'; ?>

<div class="container" style="padding-top:40px;">
    <div class="form-box">
        <h2>Edit Profile</h2>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <form method="POST" action="edit_profile.php">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
            </div>
            <div class="form-group">
                <label>Email (cannot be changed)</label>
                <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled>
            </div>
            <div class="form-group">
                <label>Phone Number</label>
                <input type="text" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" placeholder="98XXXXXXXX" maxlength="10" pattern="9[78][0-9]{8}" inputmode="numeric" oninput="this.value=this.value.replace(/[^0-9]/g,'')">
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;padding:12px;">Save Changes</button>
        </form>
    </div>
</div>

<footer><p>© <?= date('Y') ?> <span style="color:#e94560;">Harayo Bhetiyo</span></p></footer>
</body>
</html>