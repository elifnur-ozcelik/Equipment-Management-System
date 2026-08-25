<?php
session_start();
require "db.php";
require "functions.php";

if (!isAuthenticated()) {
  header("Location: login.php?error");
  exit;
}

$user = $_SESSION["user"];
if (!in_array($_SESSION["user"]["userType"] ?? "", ["admin", "chair"], true)) {
  header("Location: login.php?error");
  exit;
}

$id = (int)($_GET["id"] ?? 0);
if ($id <= 0) {
  header("Location: admin_item.php?error=invalid_id");
  exit;
}

$sql = "SELECT * FROM items WHERE id = ?";
$rs  = $db->prepare($sql);
$rs->execute([$id]);
$item = $rs->fetch(PDO::FETCH_ASSOC);

if (!$item) {
  header("Location: admin_item.php?error=item_not_found");
  exit;
}

$message = "";
$asset_tag_id = $item["asset_tag_id"] ?? "";
$description  = $item["description"] ?? "";
$brand        = $item["brand"] ?? "";
$model        = $item["model"] ?? "";
$location     = $item["location"] ?? "";
$category     = $item["category"] ?? "";
$availability = $item["availability"] ?? "available";
$image_path   = $item["image_path"] ?? null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {

  $asset_tag_id = trim($_POST["asset_tag_id"] ?? "");
  $description  = trim($_POST["description"] ?? "");
  $brand        = trim($_POST["brand"] ?? "");
  $model        = trim($_POST["model"] ?? "");
  $location     = trim($_POST["location"] ?? "");
  $category     = trim($_POST["category"] ?? "");
  $availability = $_POST["availability"] ?? "available";

  if (isset($_FILES["uploaded_img"]) && $_FILES["uploaded_img"]["error"] != UPLOAD_ERR_NO_FILE) {
      $file = $_FILES["uploaded_img"];
      $ext = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION)); 
      $allowed = ["jpg", "png", "gif"]; 

      if ($file["error"] == UPLOAD_ERR_INI_SIZE) { 
          $message = "File size exceeds upload_max_size in php.ini.";
      } elseif (!in_array($ext, $allowed)) { 
          $message = "Only jpg, png, and gif files are allowed.";
      } elseif ($file["error"] == UPLOAD_ERR_OK) { 
          $filename = bin2hex(random_bytes(8)) . "." . $ext; 
          $target_dir = "./uploaded_img/";

          if (move_uploaded_file($file["tmp_name"], $target_dir . $filename)) { 
              $image_path = $target_dir . $filename;
          } else {
              $message = "Upload failed.";
          }
      }
  }

  if (
    $asset_tag_id === "" || $description === "" || $brand === "" ||
    $model === "" || $location === "" || $category === ""
  ) {
    $message = "Please fill in all fields.";
  } elseif ($message === "")

    $sql = "UPDATE items
            SET asset_tag_id = ?,
                description  = ?,
                brand        = ?,
                model        = ?,
                location     = ?,
                category     = ?,
                availability = ?,
                image_path   = ?
            WHERE id = ?";

    $stmt = $db->prepare($sql);
    $stmt->execute([
      $asset_tag_id,
      $description,
      $brand,
      $model,
      $location,
      $category,
      $availability,
      $image_path,
      $id
    ]);

    header("Location: admin_item.php?updated=1");
    exit;
  }
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Update Item</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="./css/admin_item_form.css">
</head>

<body>
    <main class="page">
        <section class="card">

            <p class="top-links">
                <a href="admin_item.php" class="back-link">← Back</a>
            </p>

            <h1 class="card-title">Update <?= htmlspecialchars($item["description"]) ?></h1>

            <?php if ($message !== ""): ?>
            <p class="card-subtitle"><strong><?= $message ?></strong></p>
            <?php endif; ?>

            <form class="form" method="POST" action="admin_item_update_form.php?id=<?= $id ?>"
                enctype="multipart/form-data">

                <div class="field">
                    <label class="label">Asset Tag ID *</label>
                    <input class="input" type="text" name="asset_tag_id" value="<?= htmlspecialchars($asset_tag_id) ?>">
                </div>

                <div class="field">
                    <label class="label">Description *</label>
                    <input class="input" type="text" name="description" value="<?= htmlspecialchars($description) ?>">
                </div>

                <div class="field">
                    <label class="label">Brand *</label>
                    <input class="input" type="text" name="brand" value="<?= htmlspecialchars($brand) ?>">
                </div>

                <div class="field">
                    <label class="label">Model *</label>
                    <input class="input" type="text" name="model" value="<?= htmlspecialchars($model) ?>">
                </div>

                <div class="field">
                    <label class="label">Location *</label>
                    <input class="input" type="text" name="location" value="<?= htmlspecialchars($location) ?>">
                </div>

                <div class="field">
                    <label class="label">Category *</label>
                    <input class="input" type="text" name="category" value="<?= htmlspecialchars($category) ?>">
                </div>

                <div class="field">
                    <label class="label">Availability</label>
                    <select class="input" name="availability">
                        <option value="available" <?= ($availability === "available") ? "selected" : "" ?>>available
                        </option>
                        <option value="unavailable" <?= ($availability === "unavailable") ? "selected" : "" ?>>
                            unavailable</option>
                    </select>
                </div>

                <div class="field">
                    <label class="label">Change Item Image (Current image will remain if empty)</label>
                    <?php if ($image_path): ?>
                    <img src="<?= $image_path ?>" alt="current"
                        style="width: 80px; display: block; margin-bottom: 10px; border-radius: 4px;">
                    <?php endif; ?>
                    <input class="input" type="file" name="uploaded_img">
                </div>

                <button class="btn" type="submit">Save</button>

                <p class="card-subtitle" style="margin-top:14px;">
                    <a href="admin_item.php" class="back-link" style="margin:0;">Cancel</a>
                </p>

            </form>

        </section>
    </main>
</body>

</html>