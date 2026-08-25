<?php
  session_start() ;
  require "db.php" ;
  require "functions.php" ;
  
  if( !isAuthenticated()) {
      header("Location: login.php?error") ;
      exit ; 
  }
 
  $user = $_SESSION["user"] ;

  $message = null;

if (isset($_GET["request_item"])) {
    $itemId = $_GET["request_item"];
    $sql = "SELECT 1 FROM requests WHERE student_id = ? AND item_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$user["id"], $itemId]);

    if ($stmt->rowCount() > 0) {
        $message = "You already requested this item.";
    } else {
        header("Location: requestForm.php?id=" . $itemId);
        exit;
    }
}

 $where  = [];
  $params = [];

  if (!empty($_GET["id"])) {
    $where[]  = "id = ?";
    $params[] = (int)$_GET["id"];
  }

  if (!empty($_GET["category"])) {
    $where[]  = "category LIKE ?";
    $params[] = "%".$_GET["category"]."%";
  }

  $sql = "SELECT * FROM items";
  if ($where) $sql .= " WHERE " . implode(" AND ", $where);
  $sql .= " ORDER BY id DESC";

  $rs = $db->prepare($sql);
  $rs->execute($params);
  $items = $rs->fetchAll(PDO::FETCH_ASSOC);

 
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="./css/products.css">
    <link rel="stylesheet" href="./css/header.css">
</head>

<body class="page-items">
    <header class="header" id="header">
        <nav class="navbar nav-container">
            <a href="student_main.php" class="brand">Welcome <?=$user["name"]?></a>

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

    <div class="page-title">
        <h1>Available Items for Request</h1>
        <p>Please select an item below to submit a request.</p>
    </div>
    <?php if ($message): ?>
    <div class="alert-warning">
        <?=$message?>
    </div>
    <?php endif; ?>


    <form method="get" class="filter-form" style="width:min(1100px,92vw); margin: 0 auto 18px auto;">
        <div class="field">
            <label>ID</label>
            <input type="number" name="id" value="<?= htmlspecialchars($_GET["id"] ?? "") ?>">
        </div>

        <div class="field">
            <label>Category</label>
            <input type="text" name="category" value="<?= htmlspecialchars($_GET["category"] ?? "") ?>">
        </div>

        <div class="field field-actions">
            <button class="btn" type="submit">Search</button>
        </div>
    </form>

    <div class="shell">
        <div class="container">

            <?php if (empty($items)) : ?>
            <div class="empty-wrapper">
                <div class="empty-box">
                    <span class="empty-text">No items found for this filter</span>
                </div>
            </div>
            <?php else: ?>
            <div class="row">


                <?php foreach($items as $item) :?>
                <div class="col">
                    <div class="product">
                        <div class="prod_img">
                            <img src="<?=$item["image_path"]?>" alt="">
                        </div>
                        <div class="text">
                            <div class="brand">
                                <span><?=$item["brand"]?></span>
                            </div>

                            <div class="category">
                                <h3><?=$item["category"]?></h3>
                            </div>
                            <div class="description-prod">
                                <p><?=$item["description"]?></p>
                            </div>
                            <div class="card-footer">
                                <div class="priceDiv"><span class="price"><?=$item["cost"]?>TL</span></div>
                                <div class="requestDiv"><a href="?request_item=<?=$item["id"]?>"
                                        class="request-btn">Request</a></div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach ?>

            </div>
            <?php endif ?>

        </div>
    </div>

</body>

</html>