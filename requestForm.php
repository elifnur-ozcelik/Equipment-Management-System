<?php
  session_start();
  require "db.php";
  require "functions.php";
  
  if (!isAuthenticated()) {
      header("Location: login.php?error");
      exit;
  }
 
  $user = $_SESSION["user"];

  if (isset($_GET)) {
    extract($_GET);
  }

  $status = $status ?? "";

  $sql = "select * from items where id = ?";
  $rs = $db->prepare($sql);
  $rs->execute([$id]);
  $item = $rs->fetch(PDO::FETCH_ASSOC);

  $sql = "select distinct course_code, course_name
          from givenCourses
          order by course_code";
  $stmt = $db->prepare($sql);
  $stmt->execute();
  $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $errors = [];
  $messageError = "";


  $course_code = "";
  $due_date    = "";
  $phone       = "";
  $message     = "";

  if( $_SERVER["REQUEST_METHOD"] === "POST")
  {
    $course_code = $_POST["course_code"] ?? "";
    $due_date    = $_POST["due_date"] ?? "";
    $phone       = $_POST["phone"] ?? "";
    $message     = $_POST["message"] ?? "";


    if ($course_code === "") {
        $errors["course_code"] = "Course code is required.";
    }
    if ($due_date === "") {
        $errors["due_date"] = "Due date is required.";
    }
    if (trim($message) === "") {
        $errors["message"] = "Message is required.";
    }

    if (empty($errors)) {
        if ($status === "edit") {
              $sql = "update requests set item_id = ?, course_code = ?, purpose = ?, due_date = ? where id = ?";
              $stmt = $db->prepare($sql);
              $stmt->execute([ $item_id, $course_code, $message, $due_date, $request_id]);
             header("Location: requests.php?status=updated");
            exit;
        } else {
          $sql = "INSERT INTO requests (student_id, item_id, course_code, purpose, due_date)
              VALUES (?, ?, ?, ?, ?)";
          $stmt = $db->prepare($sql);
          $stmt->execute([$user["id"], $id, $course_code, $message, $due_date]);

          header("Location: requests.php?status=created");
          exit;
        }
    } else {
        $messageError = "Please fill in all required fields.";
    }
  }
    
?>

<!doctype html>
<html lang="en">

<head>
    <title>Request Form</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <link href="https://fonts.googleapis.com/css?family=Roboto:400,100,300,700" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="css/requestForm.css">
</head>

<body>
    <main class="page">
        <section class="card">
            <a href="student_main.php" class="back-link">← Back</a>

            <h1 class="card-title">Request <?= $item["description"] ?></h1>

            <?php if ($messageError !== ""): ?>
            <div style="color:red; margin-bottom:15px;">
                <p><?= $messageError ?></p>
            </div>
            <?php endif; ?>
            <form class="form" method="POST"
                action="requestForm.php?id=<?= $id ?><?= ($status === "edit" ? "&status=edit&request_id={$request_id}" : "") ?>">

                <div class="field">
                    <label class="label" for="course_code">
                        Course code <span class="req">*</span>
                    </label>

                    <div class="row-2">
                        <select class="input" name="course_code" id="course_code">
                            <option value="">-- Select a course --</option>

                            <?php foreach ($courses as $course): ?>
                            <option value="<?= $course['course_code'] ?>"
                                <?= ($course_code === $course['course_code']) ? "selected" : "" ?>>
                                <?= $course['course_code'] ?> – <?= $course['course_name'] ?>
                            </option>
                            <?php endforeach; ?>
                        </select>

                        <?php if (isset($errors["course_code"])): ?>
                        <div style="color:#ffb3b3; margin-top:6px; font-size: 13px;"><?= $errors["course_code"] ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="field">
                    <label class="label" for="due_date">
                        Due date <span class="req">*</span>
                    </label>
                    <input class="input" type="date" name="due_date" id="due_date" value="<?= $due_date ?>">

                    <?php if (isset($errors["due_date"])): ?>
                    <div style="color:#ffb3b3; margin-top:6px; font-size: 13px;"><?= $errors["due_date"] ?></div>
                    <?php endif; ?>
                </div>

                <div class="field">
                    <label class="label" for="phone">Phone number</label>
                    <input class="input" type="text" name="phone" id="phone" value="<?= $phone ?>"
                        placeholder="Eg. +90 5xx xxx xx xx">
                </div>

                <div class="field">
                    <label class="label" for="message">
                        Message <span class="req">*</span>
                    </label>
                    <textarea class="textarea" name="message" id="message" rows="6"
                        placeholder="Please enter your purpose for requesting this item"><?= $message ?></textarea>


                    <?php if (isset($errors["message"])): ?>
                    <div style="color:#ffb3b3; margin-top:6px; font-size: 13px;"><?= $errors["message"] ?></div>
                    <?php endif; ?>
                </div>

                <button class="btn" type="submit">Submit</button>
            </form>
        </section>
    </main>
</body>

</html>