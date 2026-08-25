<?php
session_start();
require "db.php";
require "functions.php";

if (!isAuthenticated()) {
    header("Location: login.php?error");
    exit;
}

$user = $_SESSION["user"];
if ($user["userType"] !== "chair") {
    header("Location: login.php?error");
    exit;
}

function updateStatus($inst_decision, $chair_decision) {
    $inst_decision  = $inst_decision  ?? "pending";
    $chair_decision = $chair_decision ?? "pending";
    if ($inst_decision === "rejected" || $chair_decision === "rejected") return "rejected";
    if ($inst_decision === "approved" && $chair_decision === "approved") return "approved";
    return "pending";
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"])) {
    $requestId = (int)($_POST["request_id"] ?? 0);
    $action    = $_POST["action"] ?? "";
    $comment   = trim($_POST["comment"] ?? "");

    if ($requestId > 0 && in_array($action, ["approved", "rejected"], true)) {
        $stmt = $db->prepare("UPDATE requests SET chair_decision = ?, chair_comment = ? WHERE id = ?");
        $stmt->execute([$action, $comment, $requestId]);

        $stmt = $db->prepare("SELECT instructor_decision, chair_decision FROM requests WHERE id = ?");
        $stmt->execute([$requestId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $newStatus = updateStatus($row["instructor_decision"], $row["chair_decision"]);

            $stmt = $db->prepare("UPDATE requests SET status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $requestId]);

            if ($newStatus === "approved" || $newStatus === "rejected") {
                $uStmt = $db->prepare("SELECT u.email, u.name FROM users u JOIN requests r ON u.id = r.student_id WHERE r.id = ?");
                $uStmt->execute([$requestId]);
                $student = $uStmt->fetch();

                if ($newStatus === "approved") {
                    $subject = "Request Approved";
                    $body = "Hello {$student['name']}, your request has been approved.";
                } else {
                    $subject = "Request Rejected";
                    $body = "Hello {$student['name']}, your request was rejected. <br> <b>Reason:</b> " . htmlspecialchars($comment);
                }
                sendStatusMail($student["email"], $student["name"], $subject, $body);
            }
        }
    }
}

$filter = $_GET['filter'] ?? 'all';
$params = [];
$filterSql = "";

if ($filter !== 'all') {
    $filterSql = " AND r.status = ? ";
    $params[] = $filter;
}

$sql = "
SELECT
    r.id,
    u.name AS student_name,
    i.brand,
    i.model,
    r.course_code,
    r.due_date,
    r.purpose,
    r.status,
    r.instructor_decision,
    r.chair_decision,
    r.chair_comment
FROM requests r
JOIN users u ON r.student_id = u.id
JOIN items i ON r.item_id = i.id
WHERE 1=1
$filterSql
ORDER BY r.id DESC
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

$quick_comments = $db->query("
    SELECT * FROM quick_comments
    ORDER BY comment_type ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css/requests.css">
    <link rel="stylesheet" href="./css/header.css">
    <title>Chair Requests</title>
</head>

<body class="page-requests">

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
                    <li class="menu-item"><a href="admin_item.php" class="menu-link nav-inventory">Inventory</a></li>
                    <li class="menu-item"><a href="chair_requests.php" class="menu-link nav-requests">Requests</a></li>
                    <li class="menu-item"><a href="logout.php" class="menu-link">Logout</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <div class="filter-container">
        <form method="get" id="filterForm">
            <select name="filter" class="filter-box" onchange="document.getElementById('filterForm').submit()">
                <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>All</option>
                <option value="pending" <?= $filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="approved" <?= $filter === 'approved' ? 'selected' : '' ?>>Approved</option>
                <option value="rejected" <?= $filter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
            </select>
        </form>
    </div>

    <div class="container" style="max-width:800px;margin:0 auto;padding:20px;">
        <h2 style="color:white;">All Rental Requests</h2>

        <?php if (empty($requests)): ?>
        <p style="color:white;">No requests found.</p>
        <?php else: ?>
        <?php foreach ($requests as $r): ?>

        <?php
        $chairCanDecide =
            ($r["instructor_decision"] === "approved" &&
            ($r["chair_decision"] ?? "pending") === "pending");

        $color = "#ffcc00";
        if ($r["status"] === "approved") $color = "#4CAF50";
        if ($r["status"] === "rejected") $color = "#f44336";
        ?>

        <div class="request-card"
            style="margin-bottom:20px;background:rgba(255,255,255,0.1);padding:20px;border-radius:8px;">
            <div class="request-text" style="color:white;">

                <p><strong>Student:</strong> <?= htmlspecialchars($r["student_name"]) ?></p>
                <p><strong>Item:</strong> <?= htmlspecialchars($r["brand"] . " " . $r["model"]) ?></p>
                <p><strong>Course:</strong> <?= htmlspecialchars($r["course_code"]) ?></p>
                <p><strong>Due date:</strong> <?= htmlspecialchars($r["due_date"]) ?></p>
                <p><strong>Message:</strong> <?= htmlspecialchars($r["purpose"]) ?></p>

                <p><strong>Instructor decision:</strong> <?= htmlspecialchars($r["instructor_decision"]) ?></p>
                <p><strong>Status:</strong>
                    <span style="color:<?= $color ?>;font-weight:bold;">
                        <?= strtoupper($r["status"]) ?>
                    </span>
                </p>

                <?php if ($chairCanDecide): ?>
                <form method="post" style="margin-top:15px;">
                    <input type="hidden" name="request_id" value="<?= $r["id"] ?>">

                    <select class="filter-box" style="width:100%;margin-bottom:10px;"
                        onchange="document.getElementById('chair-comment-<?= $r['id'] ?>').value=this.value">
                        <option value="">-- Quick Comments --</option>
                        <?php foreach ($quick_comments as $qc): ?>
                        <option value="<?= htmlspecialchars($qc['comment_text']) ?>">
                            [<?= strtoupper($qc['comment_type']) ?>]
                            <?= htmlspecialchars(substr($qc['comment_text'],0,50)) ?>...
                        </option>
                        <?php endforeach; ?>
                    </select>

                    <textarea id="chair-comment-<?= $r['id'] ?>" name="comment"
                        placeholder="Comment (required for rejection)"
                        style="width:100%;padding:10px;margin-bottom:10px;border-radius:4px;"></textarea>

                    <button type="submit" name="action" value="approved"
                        onclick="document.getElementById('chair-comment-<?= $r['id'] ?>').required=false;"
                        style="background:#4CAF50;color:white;padding:10px 20px;border:none;border-radius:4px;">
                        Approve
                    </button>

                    <button type="submit" name="action" value="rejected"
                        onclick="document.getElementById('chair-comment-<?= $r['id'] ?>').required=true;"
                        style="background:#f44336;color:white;padding:10px 20px;border:none;border-radius:4px;">
                        Reject
                    </button>
                </form>

                <?php elseif (!empty($r["chair_comment"])): ?>
                <p style="margin-top:10px;font-style:italic;color:#ccc;">
                    <strong>Chair Comment:</strong> <?= htmlspecialchars($r["chair_comment"]) ?>
                </p>
                <?php endif; ?>

            </div>
        </div>

        <?php endforeach; ?>
        <?php endif; ?>
    </div>

</body>

</html>