<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Withdraw pending claim
if (isset($_POST['withdraw']) && isset($_POST['claim_id']) && is_numeric($_POST['claim_id'])) {
    $claim_id = intval($_POST['claim_id']);
    $stmt = $conn->prepare("DELETE FROM claims WHERE id=? AND user_id=? AND status='Pending'");
    $stmt->bind_param("ii", $claim_id, $user_id);
    $stmt->execute();
}

// Get all claims by this user
$stmt = $conn->prepare("
    SELECT c.*, 
           i.item_name, i.type, i.district, i.area, i.status as item_status, i.image,
           u.name as reporter_name, u.email as reporter_email, u.phone as reporter_phone 
    FROM claims c 
    JOIN items i ON c.item_id = i.id 
    JOIN users u ON i.user_id = u.id 
    WHERE c.user_id=? 
    ORDER BY c.claimed_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$claims = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Claims</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php require 'nav.php'; ?>

<div class="container" style="padding-top:30px;">
    <h2 style="margin-bottom:20px;">My Claims</h2>

    <?php if (empty($claims)): ?>
        <div class="alert alert-info">You have not submitted any claims yet. <a href="index.php">Browse listings.</a></div>
    <?php else: ?>
        <?php foreach ($claims as $claim): ?>
        <div class="table-box" style="margin-bottom:20px;padding:20px;">

            <!-- Item Info -->
            <div style="display:flex;gap:16px;align-items:flex-start;margin-bottom:16px;flex-wrap:wrap;">
                <?php if ($claim['image']): ?>
                    <img src="uploads/<?= htmlspecialchars($claim['image']) ?>" 
                         style="width:80px;height:80px;object-fit:cover;border-radius:6px;">
                <?php endif; ?>
                <div>
                    <span class="badge badge-<?= strtolower($claim['type']) ?>"><?= $claim['type'] ?></span>
                    <span class="badge badge-<?= strtolower($claim['status']) ?>" style="margin-left:6px;"><?= $claim['status'] ?></span>
                    <h3 style="margin:6px 0;"><?= htmlspecialchars($claim['item_name']) ?></h3>
                    <p style="color:#666;font-size:13px;">📍 <?= $claim['area'] ?>, <?= $claim['district'] ?></p>
                    <p style="color:#666;font-size:13px;">🕐 Claimed on <?= date('M d, Y', strtotime($claim['claimed_at'])) ?></p>
                </div>
            </div>

            <!-- Proof submitted -->
            <div style="background:#f8f9fa;padding:12px;border-radius:6px;margin-bottom:12px;">
                <p style="font-size:13px;color:#444;"><strong>Your proof submitted:</strong></p>
                <p style="font-size:13px;color:#666;margin-top:4px;"><?= nl2br(htmlspecialchars($claim['proof_description'])) ?></p>
            </div>

         
            <!-- Status result -->
            <?php if (trim($claim['status']) === 'Approved'): ?>
    <div class="alert alert-success">
        <strong>✅ Your claim was approved!</strong><br><br>
        Contact the reporter to arrange collection:<br><br>
        <strong>👤 Name:</strong> <?= htmlspecialchars($claim['reporter_name']) ?><br>
        <strong>📧 Email:</strong> <?= htmlspecialchars($claim['reporter_email']) ?><br>
        <strong>📞 Phone:</strong> <?= htmlspecialchars($claim['reporter_phone']) ?>
    </div>

            <?php elseif ($claim['status'] === 'Rejected'): ?>
                <div class="alert alert-error">
                    <strong>❌ Your claim was not approved.</strong><br>
                    The reporter did not verify your proof. You may browse other listings or try again with more specific proof.
                </div>

            <?php elseif ($claim['status'] === 'Pending'): ?>
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
                    <div class="alert alert-info" style="margin:0;flex:1;">
                        ⏳ <strong>Pending</strong> — Waiting for the reporter to review your claim.
                    </div>
                    <form method="POST" onsubmit="return confirm('Withdraw this claim?')">
                        <input type="hidden" name="claim_id" value="<?= $claim['id'] ?>">
                        <input type="hidden" name="withdraw" value="1">
                        <button type="submit" class="btn btn-danger btn-sm">Withdraw</button>
                    </form>
                </div>
            <?php endif; ?>

        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<footer><p>© <?= date('Y') ?> <span style="color:#e94560;">Harayo Bhetiyo</span></p></footer>
</body>
</html>