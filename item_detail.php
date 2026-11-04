<?php
session_start();
require 'db_connect.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT i.*, u.name as reporter_name, u.email as reporter_email, u.phone as reporter_phone FROM items i JOIN users u ON i.user_id = u.id WHERE i.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();

if (!$item) {
    header("Location: index.php");
    exit;
}

$already_claimed = false;   // true only if a Pending/Approved claim exists
$attempts_used = 0;         // total claims this user has made on this item
$is_owner = false;

if (isset($_SESSION['user_id'])) {
    $is_owner = ($item['user_id'] == $_SESSION['user_id']);

    $stmt2 = $conn->prepare("SELECT COUNT(*) AS total,
                                    COALESCE(SUM(status IN ('Pending','Approved')), 0) AS active
                             FROM claims WHERE item_id=? AND user_id=?");
    $stmt2->bind_param("ii", $id, $_SESSION['user_id']);
    $stmt2->execute();
    $cc = $stmt2->get_result()->fetch_assoc();
    $attempts_used = (int)$cc['total'];
    $already_claimed = ((int)$cc['active'] > 0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($item['item_name']) ?> Details</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php require 'nav.php'; ?>

<div class="container" style="padding-top:30px;">
    <div class="detail-box">

        <?php if ($item['image']): ?>
            <img src="uploads/<?= htmlspecialchars($item['image']) ?>" alt="Item Image">
        <?php endif; ?>

        <span class="badge badge-<?= strtolower($item['type']) ?>"><?= $item['type'] ?></span>
        <span class="badge badge-<?= strtolower($item['status']) ?>" style="margin-left:6px;"><?= $item['status'] ?></span>

        <h2 style="margin:12px 0;"><?= htmlspecialchars($item['item_name']) ?></h2>

        <div class="detail-row"><strong>Category:</strong> <?= $item['category'] ?></div>
<div class="detail-row"><strong>Location:</strong> <?= htmlspecialchars($item['area']) ?>, <?= htmlspecialchars($item['district']) ?></div>        <?php if ($item['additional_location']): ?>
            <div class="detail-row"><strong>More details:</strong> <?= htmlspecialchars($item['additional_location']) ?></div>
        <?php endif; ?>
        <div class="detail-row"><strong>Reported by:</strong> <?= htmlspecialchars($item['reporter_name']) ?></div>
        <div class="detail-row"><strong>Reported on:</strong> <?= date('F d, Y', strtotime($item['created_at'])) ?></div>

        <?php if ($item['description']): ?>
            <div style="margin:16px 0;">
                <strong>Description:</strong>
                <p class="wrap-text" style="margin-top:6px;color:#555;line-height:1.6;"><?= nl2br(htmlspecialchars($item['description'])) ?></p>
            </div>
        <?php endif; ?>

        <!-- Show reporter contact only if item is Claimed and current user is approved claimant -->
        <?php if ($item['status'] === 'Claimed' && isset($_SESSION['user_id'])): ?>
            <?php
            $stmt3 = $conn->prepare("SELECT * FROM claims WHERE item_id=? AND user_id=? AND status='Approved'");
            $stmt3->bind_param("ii", $id, $_SESSION['user_id']);
            $stmt3->execute();
            $approved_claim = $stmt3->get_result()->fetch_assoc();
            ?>
            <?php if ($approved_claim): ?>
                <div class="alert alert-success" style="margin-top:20px;">
                    <strong>Your claim was approved!</strong><br>
                    Contact the reporter to arrange collection:<br>
                    📧 <?= htmlspecialchars($item['reporter_email']) ?><br>
                    📞 <?= htmlspecialchars($item['reporter_phone']) ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

            <div style="margin-top:24px;display:flex;flex-wrap:wrap;align-items:center;gap:12px;">
            <?php if ($item['status'] === 'Open'): ?>
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <a href="login.php" class="btn btn-primary">Login to Submit Claim</a>
                <?php elseif ($is_owner): ?>
                    <span class="alert alert-info" style="display:inline-block;">This is your own report.</span>
                <?php elseif ($already_claimed): ?>
                    <span class="alert alert-info" style="display:inline-block;">You have already submitted a claim for this item.</span>
                                <?php elseif ($attempts_used >= 3): ?>
                    <div class="alert alert-error" style="width:100%;margin:0;">
                        <strong>You have already submitted a claim for this item.</strong><br>
                        You have used all 3 claim attempts, so no more claims can be made.
                    </div>
                <?php elseif ($attempts_used > 0): ?>
                    <div class="alert alert-info" style="width:100%;margin:0;">
                        <strong>You have already submitted a claim for this item.</strong><br>
                        Your previous claim was rejected. Do you want to resubmit with more proof?
                        (<?= $attempts_used ?> of 3 attempts used)
                    </div>
                    <a href="submit_claim.php?id=<?= $item['id'] ?>" class="btn btn-primary">Yes, Resubmit Claim</a>
                <?php else: ?>
                    <a href="submit_claim.php?id=<?= $item['id'] ?>" class="btn btn-primary">Submit Ownership Claim</a>
                <?php endif; ?>
            <?php else: ?>
                <span class="badge badge-<?= strtolower($item['status']) ?>" style="font-size:14px;padding:8px 16px;">Item is <?= $item['status'] ?></span>
            <?php endif; ?>
                 <a href="index.php" class="btn btn-secondary">Back to Listings</a>
        </div>
    </div>
</div>

<footer><p>© <?= date('Y') ?> <span style="color:#e94560;">Harayo Bhetiyo</span></p></footer>
</body>
</html>
