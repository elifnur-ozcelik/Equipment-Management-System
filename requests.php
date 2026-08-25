<?php
session_start();
require "db.php";
require "functions.php";

if (!isAuthenticated()) {
  header("Location: login.php?error");
  exit;
}

$user = $_SESSION["user"];
if (isset($_GET["cancel"])) {
  $cancel = (int)$_GET["cancel"];

  $sql = "DELETE FROM requests WHERE id = ?";
  $stmt = $db->prepare($sql);
  $stmt->execute([$cancel]);
}
$sql = "
  SELECT 
    r.*,
    i.id AS item_exists,
    i.image_path,
    i.category,
    i.availability
  FROM requests r
  LEFT JOIN items i ON i.id = r.item_id
  WHERE r.student_id = ?
  ORDER BY r.id DESC
";
$rs = $db->prepare($sql);
$rs->execute([$user["id"]]);
$requests = $rs->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Requests</title>
    <link rel="stylesheet" href="./css/requests.css">
    <link rel="stylesheet" href="./css/header.css">
</head>

<body class="page-requests">

    <header class="header" id="header">
        <nav class="navbar nav-container">
            <a href="student_main.php" class="brand">Welcome <?= htmlspecialchars($user["name"]) ?></a>

            <button class="burger" id="burger" type="button" aria-label="Open menu">
                <span class="burger-line"></span>
                <span class="burger-line"></span>
                <span class="burger-line"></span>
            </button>

            <div class="menu" id="menu">
                <ul class="menu-inner">
                    <li class="menu-item"><a href="student_main.php" class="menu-link nav-items">Items</a></li>
                    <li class="menu-item"><a href="requests.php" class="menu-link nav-requests">Requests</a></li>
                    <li class="menu-item"><a href="logout.php" class="menu-link">Logout</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <div class="container">
        <h2 class="page-heading">My Requests</h2>

        <?php if (empty($requests)): ?>
        <p style="color:white;">No requests found.</p>
        <?php else: ?>

        <?php foreach ($requests as $r): ?>
        <?php
        $isItemMissing = empty($r["item_exists"]);
        $isCheckedOut  = (!$isItemMissing && ($r["availability"] ?? "") === "checked_out");
        $notFound      = $isItemMissing || $isCheckedOut;
      ?>

        <div class="request-card <?= $notFound ? "unavailable" : "" ?>">

            <?php if ($notFound): ?>
            <div class="unavailable-overlay">
                This item is no longer available
            </div>
            <?php endif; ?>
            <?php if (!empty($r["image_path"])): ?>
            <div class="request-image">
                <img src="<?= htmlspecialchars($r["image_path"]) ?>" alt="Item">
            </div>
            <?php endif; ?>

            <div class="request-text">
                <p><strong>Item:</strong> <?= $r["category"] ?></p>
                <p><strong>Course:</strong> <?= $r["course_code"] ?></p>
                <p><strong>Purpose:</strong> <?= $r["purpose"] ?></p>
                <p><strong>Due date:</strong> <?= $r["due_date"]  ?></p>

                <p><strong>Status:</strong>
                    <span class="status <?= $r["status"] ?>">
                        <?= strtoupper($r["status"]) ?>
                    </span>
                </p>

                <div class="actions">
                    <?php if (!$notFound): ?>
                    <a class="btn"
                        href="requestForm.php?id=<?= (int)$r["item_id"] ?>&status=edit&request_id=<?= (int)$r["id"] ?>">Edit</a>
                    <a class="btn danger" href="?cancel=<?= (int)$r["id"] ?>">Cancel</a>
                    <?php else: ?>
                    <a class="btn danger" href="?cancel=<?= (int)$r["id"] ?>">Remove Request</a>
                    <?php endif; ?>
                </div>
            </div>

        </div>
        <?php endforeach; ?>

        <?php endif; ?>
    </div>

</body>

</html>