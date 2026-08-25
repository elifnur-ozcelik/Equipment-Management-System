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
$pageTitle = "User Management";
$user = $_SESSION["user"];

$action = $_GET["action"] ?? "list";
$id     = $_GET["id"] ?? null;
$message = "";

if ($action === "delete" && $id !== null) {
    if ((string)$id === (string)$_SESSION["user"]["id"]) {
        $message = "You cannot delete your own account.";
    } else {
        $stmt = $db->prepare("DELETE FROM users WHERE id=?");
        $stmt->execute([$id]);
        $message = "User deleted.";
    }
    $action = "list";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $formAction = $_POST["form_action"] ?? "";
    $name       = trim($_POST["name"] ?? "");
    $email      = trim($_POST["email"] ?? "");
    $password   = $_POST["password"] ?? "";
    $userType   = $_POST["userType"] ?? "";

    if ($name === "" || $email === "") {
        $message = "Please fill all required fields.";
    } else {

        if ($formAction === "add") {

            if ($password === "") {
                $message = "Password is required.";
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare(
                    "INSERT INTO users (name,email,password,userType,token)
                     VALUES (?,?,?,?,NULL)"
                );
                $stmt->execute([$name, $email, $hashed, $userType]);
                $message = "User added.";
                $action = "list";
            }

        } elseif ($formAction === "edit") {

            $editId = $_POST["id"] ?? null;

            if ($editId === null) {
                $message = "Missing user id.";
            } else {
                if ($password !== "") {
                    $hashed = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $db->prepare(
                        "UPDATE users SET name=?, email=?, password=?, userType=? WHERE id=?"
                    );
                    $stmt->execute([$name, $email, $hashed, $userType, $editId]);
                } else {
                    $stmt = $db->prepare(
                        "UPDATE users SET name=?, email=?, userType=? WHERE id=?"
                    );
                    $stmt->execute([$name, $email, $userType, $editId]);
                }

                if ((string)$editId === (string)$_SESSION["user"]["id"]) {
                    $stmt = $db->prepare("SELECT * FROM users WHERE id=?");
                    $stmt->execute([$editId]);
                    $_SESSION["user"] = $stmt->fetch(PDO::FETCH_ASSOC);
                    $user = $_SESSION["user"];
                }

                $message = "User updated.";
                $action = "list";
            }
        }
    }
}

$editUser = null;
if ($action === "edit" && $id !== null) {
    $stmt = $db->prepare("SELECT * FROM users WHERE id=?");
    $stmt->execute([$id]);
    $editUser = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$editUser) {
        $message = "User not found.";
        $action = "list";
    }
}

$users = [];
if ($action === "list") {
    $filterType = $_GET["type"] ?? "";
    if ($filterType !== "") {
        $stmt = $db->prepare("SELECT * FROM users WHERE userType=? ORDER BY id DESC");
        $stmt->execute([$filterType]);
    } else {
        $stmt = $db->query("SELECT * FROM users ORDER BY id DESC");
    }
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!doctype html>
<html lang="tr">

<head>
    <meta charset="utf-8">
    <title><?= $pageTitle ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="./css/header.css">
    <link rel="stylesheet" href="./css/admin_main.css">
</head>

<body class="page-admin">

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
                    <?php if($user["userType"]==="chair") :?>
                    <li class="menu-item"><a href="chair_requests.php" class="menu-link nav-requests">Requests</a></li>
                    <?php endif ?>
                    <li class="menu-item"><a href="logout.php" class="menu-link">Logout</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <div class="admin-shell">

        <div class="admin-title">
            <h1>User Management</h1>
            <p>Manage students, instructors and admins.</p>
        </div>

        <?php if ($message !== ""): ?>
        <div class="alert"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($action === "add"): ?>

        <div class="card-box">
            <h2>Add User</h2>

            <form method="post">
                <input type="hidden" name="form_action" value="add">

                <label>Name</label>
                <input type="text" name="name" required>

                <label>Email</label>
                <input type="email" name="email" required>

                <label>Password</label>
                <input type="password" name="password" required>

                <label>User Type</label>
                <select name="userType" required>
                    <option value="student">student</option>
                    <option value="instructor">instructor</option>
                    <option value="admin">admin</option>
                </select>

                <div class="form-actions">
                    <button class="btn" type="submit">Add</button>
                    <a class="btn" href="admin_main.php">Cancel</a>
                </div>
            </form>
        </div>

        <?php elseif ($action === "edit" && $editUser): ?>

        <div class="card-box">
            <h2>Edit User</h2>

            <form method="post">
                <input type="hidden" name="form_action" value="edit">
                <input type="hidden" name="id" value="<?= htmlspecialchars($editUser["id"]) ?>">

                <label>Name</label>
                <input type="text" name="name" value="<?= htmlspecialchars($editUser["name"]) ?>" required>

                <label>Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($editUser["email"]) ?>" required>

                <label>New Password (optional)</label>
                <input type="password" name="password">

                <label>User Type</label>
                <select name="userType" required>
                    <option value="student" <?= $editUser["userType"]==="student" ? "selected" : "" ?>>student</option>
                    <option value="instructor" <?= $editUser["userType"]==="instructor" ? "selected" : "" ?>>instructor
                    </option>
                    <option value="admin" <?= $editUser["userType"]==="admin" ? "selected" : "" ?>>admin</option>
                </select>

                <div class="form-actions">
                    <button class="btn" type="submit">Save</button>
                    <a class="btn" href="admin_main.php">Cancel</a>
                </div>
            </form>
        </div>

        <?php else: ?>

        <div class="top-actions">
            <a class="btn" href="admin_main.php?action=add" style="color:white">+ Add User</a>

            <?php $selectedType = $_GET["type"] ?? ""; ?>
            <form method="get" class="filter-form">
                <label>Fılter</label>
                <select name="type">
                    <option value="" <?= $selectedType==="" ? "selected" : "" ?>>All</option>
                    <option value="student" <?= $selectedType==="student" ? "selected" : "" ?>>student</option>
                    <option value="instructor" <?= $selectedType==="instructor" ? "selected" : "" ?>>instructor</option>
                    <option value="admin" <?= $selectedType==="admin" ? "selected" : "" ?>>admin</option>
                </select>
                <button class="btn" type="submit">Apply</button>
                <a class="btn" href="admin_main.php">Clear</a>
            </form>
        </div>

        <div class="users-list">
            <?php foreach ($users as $u): ?>
            <div class="user-card">

                <div class="user-center">
                    <h3 class="item-name"><?= htmlspecialchars($u["name"]) ?></h3>
                    <p><strong>Email:</strong> <?= htmlspecialchars($u["email"]) ?></p>
                    <p><strong>User Type:</strong> <?= htmlspecialchars($u["userType"]) ?></p>
                    <p><strong>User ID:</strong> <?= htmlspecialchars($u["id"]) ?></p>
                </div>

                <div class="user-right">
                    <div class="actions">
                        <a class="btn" href="admin_main.php?action=edit&id=<?= $u["id"] ?>">Edit</a>

                        <?php if ((string)$u["id"] !== (string)$_SESSION["user"]["id"]): ?>
                        <a class="btn danger" href="admin_main.php?action=delete&id=<?= $u["id"] ?>"
                            onclick="return confirm('Delete this user?');">
                            Delete
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
            <?php endforeach; ?>
        </div>

        <?php endif; ?>

    </div>
</body>

</html>