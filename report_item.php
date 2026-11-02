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
    $type        = $_POST['type'];
    $item_name   = trim($_POST['item_name']);
    $category    = $_POST['category'];
    $description = trim($_POST['description']);
    $district    = $_POST['district'];
    $area        = $_POST['area'];
    $add_loc     = trim($_POST['additional_location']);
    $secret      = trim($_POST['secret_detail']);
    $user_id     = $_SESSION['user_id'];
    $image_name  = '';

    // Validate
 if (empty($item_name) || empty($category) || empty($district) || empty($area)) {
        $error = "Please fill all required fields.";
    } else {
        // Image upload
        if (!empty($_FILES['image']['name'])) {
    $allowed_types = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
    ];
    $max_size = 3 * 1024 * 1024; // 3MB
    $file_type = mime_content_type($_FILES['image']['tmp_name']);
    $file_size = $_FILES['image']['size'];

    if (!array_key_exists($file_type, $allowed_types)) {
        $error = "Only JPG, PNG, and GIF images are allowed.";
    } elseif ($file_size > $max_size) {
        $error = "Image must be under 3MB.";
    } else {
        
        $ext = $allowed_types[$file_type];
        $image_name = uniqid('img_') . '.' . $ext;
        if (!move_uploaded_file($_FILES['image']['tmp_name'], "uploads/" . $image_name)) {
            $error = "Image upload failed. Check uploads/ folder permissions.";
            $image_name = '';
                }
            }
        }
        if (!$error) {
            $stmt = $conn->prepare("INSERT INTO items (user_id, type, item_name, category, description, district, area, additional_location, image, secret_detail) VALUES (?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("isssssssss", $user_id, $type, $item_name, $category, $description, $district, $area, $add_loc, $image_name, $secret);

            if ($stmt->execute()) {
                $new_id = $conn->insert_id;

                // Smart matching — find possible matches
                $match_type = ($type === 'Lost') ? 'Found' : 'Lost';
                $match_stmt = $conn->prepare("SELECT i.*, u.name as reporter_name FROM items i JOIN users u ON i.user_id = u.id WHERE i.type=? AND i.category=? AND i.district=? AND i.status='Open' AND i.user_id != ? ORDER BY i.created_at DESC LIMIT 5");
                $match_stmt->bind_param("sssi", $match_type, $category, $district, $user_id);
                $match_stmt->execute();
                $matches = $match_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

                $success = "Item reported successfully!";
            } else {
                $error = "Failed to save item. Please try again.";
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
    <title>Report Item</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php require 'nav.php'; ?>

<div class="container" style="padding-top:30px;">

    <?php if ($success && !empty($matches)): ?>
        <!-- Smart Matching Results -->
        <div class="match-box">
            <h3>🔍 Smart Match — Possible matches found!</h3>
            <p style="font-size:13px;color:#856404;margin-bottom:12px;">These <?= $match_type ?> reports might be related to yours:</p>
            <div class="grid">
                <?php foreach ($matches as $m): ?>
                <div class="card">
                    <?php if ($m['image']): ?>
                        <img src="uploads/<?= htmlspecialchars($m['image']) ?>" alt="Item">
                    <?php else: ?>
                        <img src="https://via.placeholder.com/400x180?text=No+Image" alt="No Image">
                    <?php endif; ?>
                    <div class="card-body">
                        <span class="badge badge-<?= strtolower($m['type']) ?>"><?= $m['type'] ?></span>
                        <h3><?= htmlspecialchars($m['item_name']) ?></h3>
                        <p>📍 <?= $m['area'] ?>, <?= $m['district'] ?></p>
                        <p>👤 <?= htmlspecialchars($m['reporter_name']) ?></p>
                        <br>
                        <a href="item_detail.php?id=<?= $m['id'] ?>" class="btn btn-primary btn-sm">View & Claim</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php elseif ($success): ?>
        <div class="alert alert-success"><?= $success ?> No matching items found yet. Your report is now live.</div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= $error ?></div>
    <?php endif; ?>

    <div class="form-box" style="max-width:640px;">
        <h2>Report a Lost or Found Item</h2>

        <form method="POST" action="report_item.php" enctype="multipart/form-data">
            <div class="form-group">
                <label>Type *</label>
                <select name="type" required>
                    <option value="Lost">Lost</option>
                    <option value="Found">Found</option>
                </select>
            </div>
            <div class="form-group">
                <label>Item Name *</label>
                <input type="text" name="item_name" placeholder="e.g. Blue wallet, Student ID card" required>
            </div>
            <div class="form-group">
                <label>Category *</label>
                <select name="category" required>
                    <option value="">-- Select Category --</option>
                    <?php foreach (['Electronics','Documents','Clothing & Accessories','Bags & Wallets','Keys','Jewellery','Books & Stationery','Other'] as $c): ?>
                        <option value="<?= $c ?>"><?= $c ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" placeholder="Describe the item in detail..."></textarea>
            </div>
            <div class="form-group">
                <label>District *</label>
                <select name="district" id="district" required>
                    <option value="">-- Select District --</option>
                    <option value="Kathmandu">Kathmandu</option>
                    <option value="Lalitpur">Lalitpur</option>
                    <option value="Bhaktapur">Bhaktapur</option>
                </select>
            </div>
            <div class="form-group">
                <label>Area *</label>
                <select name="area" id="area" required>
                    <option value="">-- Select District First --</option>
                </select>
            </div>
            <div class="form-group">
                <label>Additional Location Info</label>
                <input type="text" name="additional_location" placeholder="e.g. Near main gate, 3rd floor left side">
            </div>
            <div class="form-group">
                <label>Item Photo</label>
                <input type="file" name="image" accept="image/*">
                <small style="color:#888;">JPG, PNG, GIF only. Max 3MB.</small>
            </div>
            <div class="form-group">
                <label>Secret Detail <small style="color:#888;">(private — only you and the real owner will know)</small></label>
                <input type="text" name="secret_detail" placeholder="e.g. There is a scratch on the back, sticker inside the bag">
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;padding:12px;">Submit Report</button>
        </form>
    </div>
</div>

<footer><p>© <?= date('Y') ?> <span style="color:#e94560;">Harayo Bhetiyo</span></p></footer>
<script src="js/app.js"></script>
</body>
</html>
