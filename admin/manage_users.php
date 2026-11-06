<?php
session_start();
require '../db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$success = '';

// Delete user
if (isset($_POST['delete_user'])) {
    $del_id = intval($_POST['user_id']);
    if ($del_id !== $_SESSION['user_id']) {
        $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
        $stmt->bind_param("i", $del_id);
        $stmt->execute();
        $success = "User deleted.";
    } else {
        $success = "You cannot delete your own admin account.";
    }
}

$users = $conn->query("SELECT u.*, (SELECT COUNT(*) FROM items WHERE user_id=u.id) as item_count, (SELECT COUNT(*) FROM claims WHERE user_id=u.id) as claim_count FROM users u ORDER BY u.created_at DESC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users — Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php require '../nav.php'; ?>

<div class="container" style="padding-top:30px;">
    <h2 style="margin-bottom:20px;">Manage Users</h2>
    <!-- Admin action buttons -->
<div style="display:flex;gap:12px;margin-bottom:25px;">
    <a href="manage_items.php" class="btn btn-secondary">Manage Items</a>
    <a href="manage_users.php" class="btn btn-primary">Manage Users</a>
</div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <div class="table-box">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Role</th>
                    <th>Verified</th>
                    <th>Items</th>
                    <th>Claims</th>
                    <th>Joined</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= $user['id'] ?></td>
                    <td><?= htmlspecialchars($user['name']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td><?= htmlspecialchars($user['phone']) ?></td>
                    <td><span class="badge" style="background:<?= $user['role']==='admin'?'#1a1a2e':'#e0f0ff' ?>;color:<?= $user['role']==='admin'?'#fff':'#2980b9' ?>;"><?= $user['role'] ?></span></td>
                    <td><?= $user['is_verified'] ? '✅' : '❌' ?></td>
                    <td><?= $user['item_count'] ?></td>
                    <td><?= $user['claim_count'] ?></td>
                    <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                    <td>
                        <?php if ($user['role'] !== 'admin'): ?>
                        <form method="POST" onsubmit="return confirm('Delete this user and all their data?')">
                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                            <button type="submit" name="delete_user" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                        <?php else: ?>
                            <span style="color:#888;font-size:12px;">Admin</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<footer><p>© <?= date('Y') ?> <span style="color:#e94560;">Harayo Bhetiyo</span> — Admin Panel</p></footer>
</body>
</html>
