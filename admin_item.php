<?php
session_start();
require "db.php";
require "functions.php";

if (!isAuthenticated()) {
  header("Location: login.php?error");
  exit;
}
if (!in_array($_SESSION["user"]["userType"] ?? "", ["admin", "chair"], true)) {
  header("Location: login.php?error");
  exit;
}

if($_SERVER["REQUEST_METHOD"] === "GET")
{
  extract($_GET);
}

$user = $_SESSION["user"];
$msg = "";
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["import_csv"])) {

    if (!isset($_FILES["inventory_file"]) || $_FILES["inventory_file"]["error"] !== UPLOAD_ERR_OK) {
        $msg = "Please select a valid CSV file.";
    } else {

        $file = $_FILES["inventory_file"]["tmp_name"];

        if (($handle = fopen($file, "r")) !== false) {

            fgetcsv($handle); 

            $inserted = 0;
            $skipped  = 0;

            while (($data = fgetcsv($handle, 1000, ",")) !== false) {

                [
                    $asset_tag,
                    $description,
                    $brand,
                    $cost,
                    $model,
                    $category,
                    $department,
                    $location
                ] = array_pad($data, 8, null);

                if (empty($asset_tag)) continue;

                $check = $db->prepare("SELECT id FROM items WHERE asset_tag_id = ?");
                $check->execute([$asset_tag]);

                if ($check->rowCount() === 0) {

                    $stmt = $db->prepare("
                        INSERT INTO items
                        (asset_tag_id, description, brand, cost, model, category, department, location, availability)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'available')
                    ");

                    $stmt->execute([
                        $asset_tag,
                        $description,
                        $brand,
                        $cost,
                        $model,
                        $category,
                        $department,
                        $location
                    ]);

                    $inserted++;
                } else {
                    $skipped++;
                }
            }

            fclose($handle);
            $msg = "Import completed: $inserted items added, $skipped skipped.";
        } else {
            $msg = "Could not open file.";
        }
    }
}

if (isset($_GET["action"]) && $_GET["action"] === "delete" && isset($_GET["id"])) {
  $id = (int)$_GET["id"];
  $stmt = $db->prepare("DELETE FROM items WHERE id=?");
  $stmt->execute([$id]);
  $msg = "Item deleted.";
}

if (isset($_GET["action"]) && $_GET["action"] === "checkout"
    && isset($_GET["item_id"], $_GET["request_id"])) {

    $stmt = $db->prepare("UPDATE items SET availability = 'checked_out' WHERE id = ?");
    $stmt->execute([$item_id]);

    $stmt = $db->prepare("UPDATE requests SET status = 'checked_out' WHERE id = ?");
    $stmt->execute([$request_id]);

    $msg = "Item checked out successfully.";
    }

if (isset($_GET["action"]) && $_GET["action"] === "return"
    && isset($_GET["item_id"])) {

    $itemId = (int)$_GET["item_id"];

    $stmt = $db->prepare("UPDATE items SET availability = 'available' WHERE id = ?");
    $stmt->execute([$itemId]);
    $stmt = $db->prepare("
        UPDATE requests
        SET status = 'returned'
        WHERE item_id = ? AND status = 'checked_out'
    ");
    $stmt->execute([$itemId]);

    $msg = "Item returned and marked as available.";
}

$where = [];
$params = [];

if (!empty($_GET["id"])) {
  $where[] = "id = ?";
  $params[] = (int)$_GET["id"];
}

if (!empty($_GET["category"])) {
  $where[] = "category LIKE ?";
  $params[] = "%".$_GET["category"]."%";
}

if (!empty($_GET["availability"])) {
  $where[] = "availability = ?";
  $params[] = $_GET["availability"];
}

$sql = "SELECT i.*, 
        (SELECT COUNT(*) FROM requests r WHERE r.item_id = i.id AND r.status = 'approved') as total_borrowed,

        (SELECT r.id FROM requests r 
         WHERE r.item_id = i.id AND r.status = 'approved'
         LIMIT 1) AS approved_request_id,

        (SELECT GROUP_CONCAT(DISTINCT u.name SEPARATOR ', ') 
         FROM requests r 
         JOIN users u ON r.student_id = u.id 
         WHERE r.item_id = i.id AND r.status = 'approved') as borrower_names
        FROM items i";
if ($where) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY id DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Manage Items</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="./css/admin_item.css">
    <link rel="stylesheet" href="./css/header.css">
</head>

<body class="page-items">

    <header class="header" id="header">
        <nav class="navbar nav-container">
            <a href="admin_main.php" class="brand">Welcome <?= $user["name"] ?></a>

            <button class="burger" id="burger" type="button" aria-label="Open menu">
                <span class="burger-line"></span>
                <span class="burger-line"></span>
                <span class="burger-line"></span>
            </button>

            <div class="menu" id="menu">
                <ul class="menu-inner">
                    <li class="menu-item"><a href="admin_main.php" class="menu-link nav-users">Users</a></li>
                    <li class="menu-item"><a href="admin_item.php" class="menu-link nav-items">Inventory</a></li>
                    <?php if($user["userType"]==="chair") :?>
                    <li class="menu-item"><a href="chair_requests.php" class="menu-link nav-requests">Requests</a></li>
                    <?php endif ?> <li class="menu-item"><a href="logout.php" class="menu-link">Logout</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <div class="admin-shell">

        <?php if($msg): ?>
        <div class="alert"><strong><?= $msg ?></strong></div>
        <?php endif; ?>

        <div class="top-actions" style="margin-bottom:20px; display:flex; align-items:center; gap:10px;">

            <a class="btn" href="admin_item_form.php" style="text-decoration:none;color:white">
                + Add Item
            </a>

            <form method="post" enctype="multipart/form-data"
                style="margin-left:auto; display:flex; gap:8px; align-items:center;">

                <input type="file" name="inventory_file" accept=".csv" required style="font-size:12px;">

                <button type="submit" name="import_csv" class="btn" style="color:white">
                    Import CSV
                </button>

            </form>
        </div>

        <form method="get" class="filter-form">
            <div class="field">
                <label>ID</label>
                <input type="number" name="id" value="<?= $_GET["id"] ?? "" ?>">
            </div>

            <div class="field">
                <label>Category</label>
                <input type="text" name="category" value="<?= $_GET["category"] ?? "" ?>">
            </div>

            <div class="field">
                <label>Availability</label>
                <select name="availability">
                    <option value="" <?= (($_GET["availability"] ?? "") === "") ? "selected" : "" ?>>All</option>
                    <option value="available" <?= (($_GET["availability"] ?? "") === "available") ? "selected" : "" ?>>
                        available</option>
                    <option value="unavailable"
                        <?= (($_GET["availability"] ?? "") === "checked_out") ? "selected" : "" ?>>unavailable</option>
                </select>
            </div>

            <div class="field field-actions">
                <button class="btn" type="submit">Search</button>
            </div>
        </form>

        <div class="items-list">
            <?php foreach ($items as $it): ?>
            <div class="item-card">
                <div class="item-left">
                    <?php if (!empty($it['image_path'])): ?>
                    <img src="<?= htmlspecialchars($it['image_path']) ?>" alt="item"
                        style="width:100px; height:100px; object-fit:cover; border-radius:8px; margin-bottom:10px;">
                    <?php endif; ?>
                </div>


                <div class="item-center">
                    <h3 class="item-name"><?= $it["category"] ?></h3>
                    <p><strong>ID:</strong> <?= $it["id"] ?></p>
                    <p><strong>Asset Tag:</strong> <?= $it["asset_tag_id"] ?></p>
                    <p><strong>Brand:</strong> <?= $it["brand"] ?></p>
                    <p><strong>Category:</strong> <?= $it["category"] ?></p>
                    <p><strong>Availability:</strong> <?= $it["availability"] ?></p>

                    <p><strong>Total Borrowed:</strong> <?= $it["total_borrowed"] ?> times</p>
                    <p><strong>Users:</strong>
                        <?= $it["borrower_names"] ?: "None" ?></p>
                </div>

                <div class="item-right">
                    <div class="actions">

                        <?php if ($it["availability"] === "available" && !empty($it["approved_request_id"])): ?>
                        <a class="btn"
                            href="admin_item.php?action=checkout&item_id=<?= $it["id"] ?>&request_id=<?= $it["approved_request_id"] ?>">
                            Checkout
                        </a>
                        <?php endif; ?>

                        <?php if ($it["availability"] === "checked_out"): ?>
                        <a class="btn danger" href="admin_item.php?action=return&item_id=<?= $it["id"] ?>"
                            onclick="return confirm('Mark item as returned?');">
                            Return
                        </a>
                        <?php endif; ?>

                        <a class="btn" href="admin_item_update_form.php?id=<?= $it["id"] ?>&status=update">
                            Update
                        </a>
                        <a class="btn danger" href="admin_item.php?action=delete&id=<?= $it["id"] ?>"
                            onclick="return confirm('Delete this item?');">
                            Delete
                        </a>
                    </div>
                </div>

            </div>
            <?php endforeach; ?>
        </div>

    </div>
</body>

</html>