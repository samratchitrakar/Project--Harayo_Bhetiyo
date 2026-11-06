<?php
session_start();
require '../db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$success = '';

// Delete item (admin can still remove spam / inappropriate posts)
if (isset($_POST['delete_item'])) {
    $item_id = intval($_POST['item_id']);

    $stmt = $conn->prepare("SELECT image FROM items WHERE id=?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row && $row['image'] && file_exists("../uploads/" . $row['image'])) {
        unlink("../uploads/" . $row['image']);
    }

    $stmt = $conn->prepare("DELETE FROM items WHERE id=?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $success = "Item deleted successfully.";
}

// ---------- Filters ----------
$q        = trim($_GET['q'] ?? '');
$type     = $_GET['type'] ?? '';
$district = $_GET['district'] ?? '';
$category = $_GET['category'] ?? '';
$year     = (isset($_GET['year'])  && ctype_digit($_GET['year']))  ? (int)$_GET['year']  : 0;
$month    = (isset($_GET['month']) && ctype_digit($_GET['month'])) ? (int)$_GET['month'] : 0;

$where  = [];
$params = [];
$types  = '';

if ($q !== '') {
    $where[]  = "(i.item_name LIKE ? OR i.category LIKE ? OR i.area LIKE ? OR i.district LIKE ?)";
    $like     = '%' . $q . '%';
    array_push($params, $like, $like, $like, $like);
    $types   .= 'ssss';
}
if (in_array($type, ['Lost', 'Found'], true)) {
    $where[]  = "i.type = ?";
    $params[] = $type;
    $types   .= 's';
}
if ($district !== '') {
    $where[]  = "i.district = ?";
    $params[] = $district;
    $types   .= 's';
}
if ($category !== '') {
    $where[]  = "i.category = ?";
    $params[] = $category;
    $types   .= 's';
}

// Year and month are both optional: use either one or both
if ($year >= 1970 && $year <= 2100) {
    $where[]  = "YEAR(i.created_at) = ?";
    $params[] = $year;
    $types   .= 'i';
} else {
    $year = 0;
}
if ($month >= 1 && $month <= 12) {
    $where[]  = "MONTH(i.created_at) = ?";
    $params[] = $month;
    $types   .= 'i';
} else {
    $month = 0;
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Dropdown options come straight from the data
$districts  = $conn->query("SELECT DISTINCT district FROM items WHERE district <> '' ORDER BY district")->fetch_all(MYSQLI_ASSOC);
$years      = $conn->query("SELECT DISTINCT YEAR(created_at) AS y FROM items ORDER BY y DESC")->fetch_all(MYSQLI_ASSOC);
$categories = $conn->query("SELECT DISTINCT category FROM items WHERE category <> '' ORDER BY category")->fetch_all(MYSQLI_ASSOC);

// ---------- Sorting (whitelist prevents SQL injection) ----------
$allowed_sorts = [
    'date'     => 'i.created_at',
    'details'  => 'i.item_name',
    'category' => 'i.category',
    'address'  => 'i.district, i.area',
];

// The "Sort by" dropdown sends values like "date-asc"; column-header links send sort + dir
$sort_in = $_GET['sort'] ?? 'date';
$dir_in  = $_GET['dir']  ?? 'desc';
if (isset($_GET['sort_by']) && preg_match('/^(date|details|category|address)-(asc|desc)$/', $_GET['sort_by'], $m)) {
    $sort_in = $m[1];
    $dir_in  = $m[2];
}

$sort = array_key_exists($sort_in, $allowed_sorts) ? $sort_in : 'date';
$order_column = $allowed_sorts[$sort];
$direction = ($dir_in === 'asc') ? 'ASC' : 'DESC';
$current_sort = $sort . '-' . strtolower($direction);

// Builds a sort link that keeps the current filters
function sort_link($key, $label, $sort, $direction) {
    $query = $_GET;
    unset($query['sort_by']);
    $query['sort'] = $key;
    $query['dir']  = ($sort === $key && $direction === 'ASC') ? 'desc' : 'asc';
    $arrow = $sort === $key ? ($direction === 'ASC' ? ' ▲' : ' ▼') : '';
    return '<a href="?' . htmlspecialchars(http_build_query($query)) . '" style="color:inherit;text-decoration:none;">' . $label . $arrow . '</a>';
}

// ---------- Query ----------
$sql = "SELECT i.*, u.name as reporter
        FROM items i JOIN users u ON i.user_id = u.id
        $where_sql
        ORDER BY $order_column $direction";
$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Items — Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php require '../nav.php'; ?>

<div class="container" style="padding-top:30px;">
    <h2 style="margin-bottom:20px;">Manage Items</h2>

    <!-- Admin action buttons -->
    <div style="display:flex;gap:12px;margin-bottom:25px;">
        <a href="manage_items.php" class="btn btn-primary">Manage Items</a>
        <a href="manage_users.php" class="btn btn-secondary">Manage Users</a>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <!-- Search / filter bar -->
    <form method="GET" class="filters">
        <input type="text" name="q" placeholder="Search items..." value="<?= htmlspecialchars($q) ?>">

        <select name="type">
            <option value="">All Types</option>
            <option value="Lost"  <?= $type === 'Lost'  ? 'selected' : '' ?>>Lost</option>
            <option value="Found" <?= $type === 'Found' ? 'selected' : '' ?>>Found</option>
        </select>

        <select name="district">
            <option value="">All Districts</option>
            <?php foreach ($districts as $d): ?>
                <option value="<?= htmlspecialchars($d['district']) ?>" <?= $district === $d['district'] ? 'selected' : '' ?>><?= htmlspecialchars($d['district']) ?></option>
            <?php endforeach; ?>
        </select>

        <select name="category">
            <option value="">All Categories</option>
            <?php foreach ($categories as $c): ?>
                <option value="<?= htmlspecialchars($c['category']) ?>" <?= $category === $c['category'] ? 'selected' : '' ?>><?= htmlspecialchars($c['category']) ?></option>
            <?php endforeach; ?>
        </select>

        <select name="year">
            <option value="">Year</option>
            <?php foreach ($years as $y): ?>
                <option value="<?= (int)$y['y'] ?>" <?= $year === (int)$y['y'] ? 'selected' : '' ?>><?= (int)$y['y'] ?></option>
            <?php endforeach; ?>
        </select>

        <select name="month">
            <option value="">Month</option>
            <?php foreach (['January','February','March','April','May','June','July','August','September','October','November','December'] as $n => $mname): ?>
                <option value="<?= $n + 1 ?>" <?= $month === $n + 1 ? 'selected' : '' ?>><?= $mname ?></option>
            <?php endforeach; ?>
        </select>

        <select name="sort_by">
            <?php
            $sort_options = [
                'date-desc' => 'Newest First',
                'date-asc'  => 'Oldest First',
            ];
            foreach ($sort_options as $val => $label): ?>
                <option value="<?= $val ?>" <?= $current_sort === $val ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="btn btn-primary">Search</button>
        <a href="manage_items.php" class="btn btn-secondary">Clear</a>
    </form>

    <div class="table-box">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th><?= sort_link('details', 'Item', $sort, $direction) ?></th>
                    <th>Type</th>
                    <th><?= sort_link('category', 'Category', $sort, $direction) ?></th>
                    <th><?= sort_link('address', 'Location', $sort, $direction) ?></th>
                    <th>Reporter</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$items): ?>
                <tr><td colspan="9" style="text-align:center;padding:20px;">No items found.</td></tr>
                <?php endif; ?>

                <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= $item['id'] ?></td>
                    <td><a href="../item_detail.php?id=<?= $item['id'] ?>"><?= htmlspecialchars($item['item_name']) ?></a></td>
                    <td><span class="badge badge-<?= strtolower($item['type']) ?>"><?= htmlspecialchars($item['type']) ?></span></td>
                    <td><?= htmlspecialchars($item['category']) ?></td>
                    <td><?= htmlspecialchars($item['area']) ?>, <?= htmlspecialchars($item['district']) ?></td>
                    <td><?= htmlspecialchars($item['reporter']) ?></td>
                    <td><span class="badge badge-<?= strtolower($item['status']) ?>"><?= htmlspecialchars($item['status']) ?></span></td>
                    <td><?= date('M d', strtotime($item['created_at'])) ?></td>
                    <td>
                        <form method="POST" onsubmit="return confirm('Delete this item permanently?')">
                            <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                            <button type="submit" name="delete_item" class="btn btn-danger btn-sm">Delete</button>
                        </form>
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