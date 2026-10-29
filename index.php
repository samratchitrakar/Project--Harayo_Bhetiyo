<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require 'db_connect.php';

// Filters
$type     = isset($_GET['type']) ? $_GET['type'] : '';
$district = isset($_GET['district']) ? $_GET['district'] : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';
$search   = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query safely
$where = ["i.status = 'Open'"];
$params = [];
$types  = '';

if ($type)     { $where[] = "i.type = ?";     $params[] = $type;     $types .= 's'; }
if ($district) { $where[] = "i.district = ?"; $params[] = $district; $types .= 's'; }
if ($category) { $where[] = "i.category = ?"; $params[] = $category; $types .= 's'; }
if ($search)   { $where[] = "i.item_name LIKE ?"; $params[] = "%$search%"; $types .= 's'; }

$sql = "SELECT i.*, u.name as reporter_name FROM items i
        JOIN users u ON i.user_id = u.id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY i.created_at DESC";

$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Harayo Bhetiyo - Lost & Found Nepal</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php require 'nav.php'; ?>

<div class="hero">
    <h1>हरायो <span>भेटियो</span></h1>
    <p>Nepal's Lost & Found Platform</p>
    <?php if (!isset($_SESSION['user_id'])): ?>
        <a href="register.php" class="btn btn-primary">Get Started</a>
        <a href="login.php" class="btn btn-secondary" style="margin-left:10px;">Login</a>
    <?php else: ?>
        <a href="report_item.php" class="btn btn-primary">+ Report an Item</a>
    <?php endif; ?>
</div>

<div class="container">

    <!-- Filters -->
    <form method="GET" action="index.php">
        <div class="filters">
            <input type="text" name="search" placeholder="Search items..." value="<?= htmlspecialchars($search) ?>">
            <select name="type">
                <option value="">All Types</option>
                <option value="Lost" <?= $type === 'Lost' ? 'selected' : '' ?>>Lost</option>
                <option value="Found" <?= $type === 'Found' ? 'selected' : '' ?>>Found</option>
            </select>
            <select name="district">
                <option value="">All Districts</option>
                <?php foreach (['Kathmandu','Lalitpur','Bhaktapur'] as $d): ?>
                    <option value="<?= $d ?>" <?= $district === $d ? 'selected' : '' ?>><?= $d ?></option>
                <?php endforeach; ?>
            </select>
            <select name="category">
                <option value="">All Categories</option>
                <?php foreach (['Electronics','Documents','Clothing & Accessories','Bags & Wallets','Keys','Jewellery','Books & Stationery','Other'] as $c): ?>
                    <option value="<?= $c ?>" <?= $category === $c ? 'selected' : '' ?>><?= $c ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary">Search</button>
            <a href="index.php" class="btn btn-secondary">Clear</a>
        </div>
    </form>

    <!-- Items Grid -->
    <?php if (empty($items)): ?>
        <div class="alert alert-info">No items found. <a href="report_item.php">Be the first to report one.</a></div>
    <?php else: ?>
        <div class="grid">
            <?php foreach ($items as $item): ?>
            <div class="card">
                <?php if ($item['image']): ?>
                    <img src="uploads/<?= htmlspecialchars($item['image']) ?>" alt="Item Image">
                <?php else: ?>
                    <img src="https://via.placeholder.com/400x180?text=No+Image" alt="No Image">
                <?php endif; ?>
                <div class="card-body">
                    <span class="badge badge-<?= strtolower($item['type']) ?>"><?= $item['type'] ?></span>
                    <h3><?= htmlspecialchars($item['item_name']) ?></h3>
                    <p><?= $item['category'] ?></p>
                    <p><?= $item['area'] ?>, <?= $item['district'] ?></p>
                    <p>Reported by <?= htmlspecialchars($item['reporter_name']) ?></p>
                    <p><?= date('M d, Y', strtotime($item['created_at'])) ?></p>
                    <br>
                    <a href="item_detail.php?id=<?= $item['id'] ?>" class="btn btn-primary btn-sm">View Details</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<footer>
    <p>© <?= date('Y') ?> <span style="color:#e94560;">Harayo Bhetiyo</span></p>
</footer>

<script src="js/app.js"></script>
</body>
</html>
