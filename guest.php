<?php
require "db.php";   

$where = ["availability = 'available'"]; 
$params = [];
if (!empty($_GET["category"])) {
  $where[] = "category LIKE ?";
  $params[] = "%".$_GET["category"]."%";
}
if (!empty($_GET["brand"])) {
  $where[] = "brand LIKE ?";
  $params[] = "%".$_GET["brand"]."%";
}
if (!empty($_GET["id"])) {
  $where[] = "id = ?";
  $params[] = (int)$_GET["id"];
}

$sql = "SELECT * FROM items WHERE " . implode(" AND ", $where) . " ORDER BY id DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Available Equipment</title>
    <link rel="stylesheet" href="./css/products.css">
</head>

<body class="page-items">

    <div class="page-title">
        <h1>Available Equipment</h1>
        <p>Guests can browse and filter available items.</p>
    </div>

    <form method="get" class="filter-form" style="width:min(1100px,92vw); margin: 0 auto 18px auto;">
        <div class="field">
            <label>ID</label>
            <input type="number" name="id" value="<?= $_GET["id"] ?? "" ?>">
        </div>

        <div class="field">
            <label>Category</label>
            <input type="text" name="category" value="<?= $_GET["category"] ?? "" ?>">
        </div>

        <div class="field">
            <label>Brand</label>
            <input type="text" name="brand" value="<?= $_GET["brand"] ?? "" ?>">
        </div>

        <div class="field field-actions">
            <button class="btn" type="submit">Search</button>
        </div>
    </form>

    <div class="shell">
        <div class="container">
            <?php if (empty($items)): ?>
            <div class="empty-wrapper">
                <div class="empty-box">
                    <span class="empty-text">No available items found.</span>
                </div>
            </div>
            <?php else: ?>
            <div class="row">
                <?php foreach ($items as $item): ?>
                <div class="col">
                    <div class="product">
                        <div class="prod_img">
                            <img src="<?= $item["image_path"] ?>" alt="">
                        </div>
                        <div class="text">
                            <div class="brand"><span><?= $item["brand"] ?></span></div>
                            <div class="category">
                                <h3><?= $item["category"] ?></h3>
                            </div>
                            <div class="description-prod">
                                <p><?= $item["description"] ?></p>
                            </div>
                            <div class="card-footer">
                                <div class="priceDiv"><span class="price"><?= $item["cost"] ?> TL</span></div>
                                <div class="requestDiv">

                                    <a href="login.php" class="request-btn"
                                        style="display: inline-block; opacity:.6; text-decoration: none; text-align: center;">
                                        Login to Request
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

</body>

</html>