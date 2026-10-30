<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Approve or reject claim
if (isset($_POST['action']) && isset($_POST['claim_id']) && is_numeric($_POST['claim_id'])) {
    $claim_id = intval($_POST['claim_id']);
    $action   = $_POST['action'];

    if ($action === 'approve') {
        $stmt = $conn->prepare("SELECT item_id FROM claims WHERE id=?");
        $stmt->bind_param("i", $claim_id);
        $stmt->execute();
        $claim_row = $stmt->get_result()->fetch_assoc();

        if ($claim_row) {
            $item_id = $claim_row['item_id'];

            // Verify item belongs to current user
            $stmt2 = $conn->prepare("SELECT id FROM items WHERE id=? AND user_id=?");
            $stmt2->bind_param("ii", $item_id, $user_id);
            $stmt2->execute();

            if ($stmt2->get_result()->num_rows > 0) {
                // Approve this claim
                $stmt3 = $conn->prepare("UPDATE claims SET status='Approved' WHERE id=?");
                $stmt3->bind_param("i", $claim_id);
                $stmt3->execute();

                // Reject all other claims for same item
                $stmt4 = $conn->prepare("UPDATE claims SET status='Rejected' WHERE item_id=? AND id!=?");
                $stmt4->bind_param("ii", $item_id, $claim_id);
                $stmt4->execute();

                // Update item status to Claimed
                $stmt5 = $conn->prepare("UPDATE items SET status='Claimed' WHERE id=?");
                $stmt5->bind_param("i", $item_id);
                $stmt5->execute();

                $success = "Claim approved. Item is now marked as Claimed.";
            }
        }

    } elseif ($action === 'reject') {
        $stmt = $conn->prepare("UPDATE claims SET status='Rejected' WHERE id=?");
        $stmt->bind_param("i", $claim_id);
        $stmt->execute();
        $success = "Claim rejected.";

    } elseif ($action === 'resolve') {
        if (isset($_POST['item_id']) && is_numeric($_POST['item_id'])) {
            $item_id = intval($_POST['item_id']);
            $stmt = $conn->prepare("UPDATE items SET status='Resolved' WHERE id=? AND user_id=?");
            $stmt->bind_param("ii", $item_id, $user_id);
            $stmt->execute();
            $success = "Item marked as Resolved. Well done!";
        }
    }
}

// Get user's items with claims
$stmt = $conn->prepare("SELECT * FROM items WHERE user_id=? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Items</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php require 'nav.php'; ?>

<div class="container" style="padding-top:30px;">
    <h2 style="margin-bottom:20px;">My Reported Items</h2>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= $error ?></div>
    <?php endif; ?>

    <?php if (empty($items)): ?>
        <div class="alert alert-info">You have not reported any items yet. <a href="report_item.php">Report one now.</a></div>
    <?php else: ?>
        <?php foreach ($items as $item): ?>
        <div class="table-box" style="margin-bottom:24px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;flex-wrap:wrap;gap:8px;">
                <div>
                    <span class="badge badge-<?= strtolower($item['type']) ?>"><?= $item['type'] ?></span>
                    <span class="badge badge-<?= strtolower($item['status']) ?>" style="margin-left:6px;"><?= $item['status'] ?></span>
                    <strong style="margin-left:10px;"><?= htmlspecialchars($item['item_name']) ?></strong>
                    <span style="color:#888;font-size:13px;margin-left:8px;"><?= $item['area'] ?>, <?= $item['district'] ?></span>
                </div>
                <div style="display:flex;gap:8px;">
                    <?php if ($item['status'] === 'Claimed'): ?>
                        <form method="POST">
                            <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                            <input type="hidden" name="action" value="resolve">
                            <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Confirm physical handover and mark as Resolved?')">Mark Resolved</button>
                        </form>
                    <?php endif; ?>
                    <a href="item_detail.php?id=<?= $item['id'] ?>" class="btn btn-secondary btn-sm">View</a>
                </div>
            </div>

            <?php if ($item['secret_detail']): ?>
                <p style="font-size:13px;color:#666;margin-bottom:12px;">🔒 Secret detail: <em><?= htmlspecialchars($item['secret_detail']) ?></em></p>
            <?php endif; ?>

            <!-- Claims for this item -->
            <?php
            $cstmt = $conn->prepare("SELECT c.*, u.name, u.email, u.phone FROM claims c JOIN users u ON c.user_id = u.id WHERE c.item_id=? ORDER BY c.claimed_at DESC");
            $cstmt->bind_param("i", $item['id']);
            $cstmt->execute();
            $claims = $cstmt->get_result()->fetch_all(MYSQLI_ASSOC);
            ?>

            <?php if (empty($claims)): ?>
                <p style="color:#888;font-size:13px;">No claims yet.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Claimant</th>
                            <th>Proof Description</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($claims as $claim): ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars($claim['name']) ?><br>
                                <small style="color:#888;"><?= htmlspecialchars($claim['email']) ?> | <?= htmlspecialchars($claim['phone']) ?></small>
                            </td>
                            <td><?= nl2br(htmlspecialchars($claim['proof_description'])) ?></td>
                            <td><span class="badge badge-<?= strtolower($claim['status']) ?>"><?= $claim['status'] ?></span></td>
                            <td>
                                <?php if ($claim['status'] === 'Pending' && $item['status'] === 'Open'): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="claim_id" value="<?= $claim['id'] ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Approve this claim?')">Approve</button>
                                    </form>
                                    <form method="POST" style="display:inline;margin-left:4px;">
                                        <input type="hidden" name="claim_id" value="<?= $claim['id'] ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Reject this claim?')">Reject</button>
                                    </form>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<footer><p>© <?= date('Y') ?> <span style="color:#e94560;">Harayo Bhetiyo</span></p></footer>
</body>
</html>
