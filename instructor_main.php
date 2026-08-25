<?php
session_start();
require "db.php";
require "functions.php";

if (!isAuthenticated()) {
    header("Location: login.php?error");
    exit;
}
$user = $_SESSION["user"];

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

    if (in_array($action, ["approved", "rejected"], true)) {
        $updateSql = "UPDATE requests SET instructor_decision = ?, instructor_comment = ? WHERE id = ?";
        $db->prepare($updateSql)->execute([$action, $comment, $requestId]);

        $stmt = $db->prepare("SELECT chair_decision FROM requests WHERE id = ?");
        $stmt->execute([$requestId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $newStatus = updateStatus($action, $row["chair_decision"] ?? "pending");

        $db->prepare("UPDATE requests SET status = ? WHERE id = ?")->execute([$newStatus, $requestId]);

        if ($newStatus === "rejected") {
             $uStmt = $db->prepare("SELECT email, name FROM users WHERE id = (SELECT student_id FROM requests WHERE id = ?)");
             $uStmt->execute([$requestId]);
             $student = $uStmt->fetch();

             $subject = "Request Rejected by Instructor";
             $body = "Hello {$student['name']}, your request was rejected by the instructor. <br> <b>Reason:</b> " . htmlspecialchars($comment);
             
             sendStatusMail($student["email"], $student["name"], $subject, $body);
        }
    }
    header("Location: instructor_main.php?success=1");
    exit;
}

$filter = $_GET['filter'] ?? 'all';
$params = [$user["name"]];
$filterSql = "";

if ($filter !== 'all') {
    $filterSql = " AND r.instructor_decision = ? ";
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
    r.instructor_decision,
    r.instructor_comment
FROM requests r
JOIN users u ON r.student_id = u.id
JOIN items i ON r.item_id = i.id
JOIN givencourses g ON (g.course_code COLLATE utf8mb4_turkish_ci = r.course_code COLLATE utf8mb4_turkish_ci)
WHERE g.instructor_name COLLATE utf8mb4_turkish_ci = ?
$filterSql
ORDER BY r.id DESC
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

$quick_comments = $db->query("SELECT * FROM quick_comments ORDER BY comment_type ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css/requests.css">
    <link rel="stylesheet" href="./css/header.css">
    <title>Instructor Page</title>
</head>

<body class="page-requests">

    <header class="header" id="header">
        <nav class="navbar nav-container">
            <a href="instructor_main.php" class="brand">
                Welcome <?= htmlspecialchars($user["name"]) ?>
            </a>
            <div class="menu">
                <ul class="menu-inner">
                    <li class="menu-item"><a href="instructor_main.php" class="menu-link nav-requests">Requests</a></li>
                    <li class="menu-item"><a href="logout.php" class="menu-link">Logout</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <div class="filter-container">
        <form method="get" id="filterForm">
            <select name="filter" class="filter-box" onchange="document.getElementById('filterForm').submit()">
                <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>All Status</option>
                <option value="pending" <?= $filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="approved" <?= $filter === 'approved' ? 'selected' : '' ?>>Approved</option>
                <option value="rejected" <?= $filter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
            </select>
        </form>
    </div>

    <div class="container" style="max-width: 800px; margin: 0 auto; padding: 20px;">
        <h2 style="color:white; margin-top:10px;">Rental Requests</h2>

        <?php if (empty($requests)): ?>
        <p style="color:white;">No requests found for this filter.</p>
        <?php else: ?>
        <?php foreach ($requests as $r): ?>
        <div class="request-card"
            style="margin-bottom:20px; background: rgba(255,255,255,0.1); padding: 20px; border-radius: 8px;">
            <div class="request-text" style="color:white;">
                <p><strong>Student name:</strong> <?= htmlspecialchars($r["student_name"]) ?></p>
                <p><strong>Item name:</strong> <?= htmlspecialchars($r["brand"] . " " . $r["model"]) ?></p>
                <p><strong>Course:</strong> <?= htmlspecialchars($r["course_code"]) ?></p>
                <p><strong>Due date:</strong> <?= htmlspecialchars($r["due_date"]) ?></p>
                <p><strong>Message:</strong> <?= htmlspecialchars($r["purpose"]) ?></p>

                <p><strong>Status:</strong>
                    <?php 
                                $decision = $r["instructor_decision"];
                                $color = "#ffcc00";
                                if ($decision === "approved") $color = "#4CAF50";
                                if ($decision === "rejected") $color = "#f44336";
                            ?>
                    <span style="font-weight:bold; color: <?= $color ?>;">
                        <?= strtoupper(htmlspecialchars($decision)) ?>
                    </span>
                </p>

                <?php if ($decision === "pending"): ?>
                <form method="post" action="" style="margin-top:15px;">
                    <input type="hidden" name="request_id" value="<?= $r["id"] ?>">

                    <select class="filter-box" style="width:100%; margin-bottom:10px; font-size: 0.85rem;"
                        onchange="this.nextElementSibling.value = this.value">
                        <option value="">-- Quick Comments --</option>
                        <?php foreach ($quick_comments as $qc): ?>
                        <option value="<?= htmlspecialchars($qc['comment_text']) ?>">
                            [<?= strtoupper($qc['comment_type']) ?>]
                            <?= htmlspecialchars(substr($qc['comment_text'], 0, 50)) ?>...
                        </option>
                        <?php endforeach; ?>
                    </select>

                    <textarea id="comment-<?= $r['id'] ?>" name="comment" placeholder="Comment (required for rejection)"
                        style="width:100%; padding:10px; border-radius:4px; margin-bottom:10px;"></textarea>

                    <button type="submit" name="action" value="approved"
                        onclick="document.getElementById('comment-<?= $r['id'] ?>').required = false;"
                        style="background:#4CAF50; color:white; border:none; padding:10px 20px; cursor:pointer; border-radius:4px; margin-right:10px;">
                        Approve
                    </button>

                    <button type="submit" name="action" value="rejected"
                        onclick="document.getElementById('comment-<?= $r['id'] ?>').required = true;"
                        style="background:#f44336; color:white; border:none; padding:10px 20px; cursor:pointer; border-radius:4px;">
                        Reject
                    </button>
                </form>
                <?php elseif (!empty($r["instructor_comment"])): ?>
                <p
                    style="margin-top:10px; font-style:italic; color:#ccc; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 10px;">
                    <strong>Your Comment:</strong> <?= htmlspecialchars($r["instructor_comment"]) ?>
                </p>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>

</html>