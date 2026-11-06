<?php
session_start();
require '../db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Stats
$total_items   = $conn->query("SELECT COUNT(*) as c FROM items")->fetch_assoc()['c'];
$total_users   = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='user'")->fetch_assoc()['c'];
$open_items    = $conn->query("SELECT COUNT(*) as c FROM items WHERE status='Open'")->fetch_assoc()['c'];
$total_claims  = $conn->query("SELECT COUNT(*) as c FROM claims")->fetch_assoc()['c'];
$pending_claims= $conn->query("SELECT COUNT(*) as c FROM claims WHERE status='Pending'")->fetch_assoc()['c'];
$resolved      = $conn->query("SELECT COUNT(*) as c FROM items WHERE status='Resolved'")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — Harayo Bhetiyo</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php require '../nav.php'; ?>

<div class="container" style="padding-top:30px;">
    <h2 style="margin-bottom:24px;">Admin Dashboard</h2>

    <div class="admin-stats">
        <div class="stat-card">
            <h2><?= $total_items ?></h2>
            <p>Total Items</p>
        </div>
        <div class="stat-card">
            <h2><?= $open_items ?></h2>
            <p>Open Items</p>
        </div>
        <div class="stat-card">
            <h2><?= $total_claims ?></h2>
            <p>Total Claims</p>
        </div>
        <div class="stat-card">
            <h2><?= $pending_claims ?></h2>
            <p>Pending Claims</p>
        </div>
        <div class="stat-card">
            <h2><?= $resolved ?></h2>
            <p>Resolved Items</p>
        </div>
        <div class="stat-card">
            <h2><?= $total_users ?></h2>
            <p>Registered Users</p>
        </div>
    </div>

    <!-- Admin action buttons -->
    <div style="display:flex;gap:12px;margin:25px 0;">
        <a href="manage_items.php" class="btn btn-primary">Manage Items</a>
        <a href="manage_users.php" class="btn btn-secondary">Manage Users</a>
    </div>

    <!-- Recent items -->
    <div class="table-box">
        <h3 style="margin-bottom:16px;">Recent Reports</h3>
        <?php
        $recent = $conn->query("SELECT i.*, u.name as reporter FROM items i JOIN users u ON i.user_id = u.id ORDER BY i.created_at DESC LIMIT 10")->fetch_all(MYSQLI_ASSOC);
        ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Item</th>
                    <th>Type</th>
                    <th>Category</th>
                    <th>Location</th>
                    <th>Reporter</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent as $r): ?>
                <tr>
                    <td><?= $r['id'] ?></td>
                    <td><a href="../item_detail.php?id=<?= $r['id'] ?>"><?= htmlspecialchars($r['item_name']) ?></a></td>
                    <td><span class="badge badge-<?= strtolower($r['type']) ?>"><?= htmlspecialchars($r['type']) ?></span></td>
                    <td><?= htmlspecialchars($r['category']) ?></td>
                    <td><?= htmlspecialchars($r['area']) ?>, <?= htmlspecialchars($r['district']) ?></td>
                    <td><?= htmlspecialchars($r['reporter']) ?></td>
                    <td><span class="badge badge-<?= strtolower($r['status']) ?>"><?= htmlspecialchars($r['status']) ?></span></td>
                    <td><?= date('M d', strtotime($r['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<footer><p>© <?= date('Y') ?> <span style="color:#e94560;">Harayo Bhetiyo</span> — Admin Panel</p></footer>
</body>
</html>