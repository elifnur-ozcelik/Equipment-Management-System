<?php
session_start();
require "db.php";
require "functions.php";

if (!isAuthenticated()) {
    header("Location: login.php?error");
    exit;
}

if (!isset($_SESSION["user"]["userType"]) || $_SESSION["user"]["userType"] !== "admin"&& $_SESSION["user"]["userType"] !== "chair") {
    header("Location: login.php?error");
    exit;
}

$user = $_SESSION["user"];

$message = "";
$asset_tag   = "";
$description = "";
$brand       = "";
$model       = "";
$cost        = "";
$category    = "";
$location    = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $asset_tag   = trim($_POST["asset_tag_id"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $brand       = trim($_POST["brand"] ?? "");
    $model       = trim($_POST["model"] ?? "");
    $cost        = trim($_POST["cost"] ?? "");
    $category    = trim($_POST["category"] ?? "");
    $location    = trim($_POST["location"] ?? "");

    $image_path = null; 

    if (isset($_FILES["uploaded_img"])) {
        $file = $_FILES["uploaded_img"];

        $ext = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION)); 
        $allowed = ["jpg", "png", "gif"]; 

        if ($file["error"] == UPLOAD_ERR_INI_SIZE) { 
            $message = "File size exceeds upload_max_size in php.ini.";
        } elseif ($file["error"] == UPLOAD_ERR_NO_FILE) { 

        } elseif (!in_array($ext, $allowed)) { 
            $message = "Only jpg, png, and gif files are allowed.";
        } elseif ($file["error"] == UPLOAD_ERR_OK) { 

            $filename = bin2hex(random_bytes(8)) . "." . $ext; 
            $target_dir = "./uploaded_img/";

            if (move_uploaded_file($file["tmp_name"], $target_dir . $filename)) { 
                $image_path = $target_dir . $filename;
            } else {
                $message = "Upload failed (check folder permissions).";
            }
        }
    }

    if (
        $asset_tag === "" ||
        $description === "" ||
        $brand === "" ||
        $model === "" ||
        $cost === "" ||
        $category === "" ||
        $location === ""
    ) {
        $message = "Please fill in all fields.";
    } else {

        $stmt = $db->prepare("
            INSERT INTO items
            (asset_tag_id, description, brand, model, cost, category, location)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $asset_tag,
            $description,
            $brand,
            $model,
            $cost,
            $category,
            $location
        ]);

        $message = "Item added successfully.";
    }
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Add Item</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="./css/admin_item_form.css">
</head>

<body>
    <main class="page">
        <section class="card">

            <p class="top-links">
                <a href="admin_main.php" class="back-link">← Back to Admin Dashboard</a>
            </p>

            <h1 class="card-title">Add New Item</h1>

            <?php if ($message !== ""): ?>
            <p class="card-subtitle"><strong><?= $message ?></strong></p>
            <?php endif; ?>

            <form class="form" method="post" action="admin_item_form.php" enctype="multipart/form-data">

                <div class="field">
                    <label class="label">Asset Tag</label>
                    <input class="input" type="text" name="asset_tag_id" value="<?= $asset_tag ?>">
                </div>

                <div class="field">
                    <label class="label">Description</label>
                    <input class="input" type="text" name="description" value="<?= $description ?>">
                </div>

                <div class="field">
                    <label class="label">Brand</label>
                    <input class="input" type="text" name="brand" value="<?= $brand ?>">
                </div>

                <div class="field">
                    <label class="label">Model</label>
                    <input class="input" type="text" name="model" value="<?= $model ?>">
                </div>

                <div class="field">
                    <label class="label">Cost</label>
                    <input class="input" type="number" step="1" name="cost" value="<?= $cost ?>">
                </div>

                <div class="field">
                    <label class="label">Category</label>
                    <input class="input" type="text" name="category" value="<?= $category ?>">
                </div>

                <div class="field">
                    <label class="label">Location</label>
                    <input class="input" type="text" name="location" value="<?= $location ?>">
                </div>

                <div class="field">
                    <label class="label">Item Image</label>
                    <input class="input" type="file" name="uploaded_img">
                </div>

                <button class="btn" type="submit">Add Item</button>

            </form>

        </section>
    </main>
</body>

</html>