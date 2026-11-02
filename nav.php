<?php
$pending_count = 0;
if (isset($_SESSION['user_id']) && $_SESSION['role'] !== 'admin') {
    $badge_stmt = $conn->prepare("SELECT COUNT(*) as c FROM claims c JOIN items i ON c.item_id = i.id WHERE i.user_id = ? AND c.status = 'Pending'");
    $badge_stmt->bind_param("i", $_SESSION['user_id']);
    $badge_stmt->execute();
    $pending_count = $badge_stmt->get_result()->fetch_assoc()['c'];
}
?>
<nav>
    <a href="/harayo_bhetiyo/index.php" class="logo">Harayo <span>Bhetiyo</span></a>
    <div class="nav-links">
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="/harayo_bhetiyo/report_item.php">+ Report Item</a>
            <a href="/harayo_bhetiyo/my_items.php" style="position:relative;">
                My Items
                <?php if ($pending_count > 0): ?>
                    <span style="position:absolute;top:-8px;right:-12px;background:#e94560;color:#fff;border-radius:50%;font-size:11px;font-weight:bold;width:18px;height:18px;display:flex;align-items:center;justify-content:center;"><?= $pending_count ?></span>
                <?php endif; ?>
            </a>
            <a href="/harayo_bhetiyo/my_claims.php">My Claims</a>
           <?php if ($_SESSION['role'] === 'admin'): ?>
    <a href="/harayo_bhetiyo/admin/dashboard.php">Admin</a>
<?php endif; ?>
            <a href="/harayo_bhetiyo/change_password.php">Change Password</a>
            <a href="/harayo_bhetiyo/edit_profile.php">Edit Profile</a>
            <a href="/harayo_bhetiyo/logout.php">Logout</a>
        <?php else: ?>
            <a href="/harayo_bhetiyo/login.php">Login</a>
            <a href="/harayo_bhetiyo/register.php">Register</a>
        <?php endif; ?>
    </div>
</nav>