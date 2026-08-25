<?php
  session_start();
  require "db.php" ;
  require "functions.php";
  if(!empty($_GET))
	extract($_GET);
	if (!empty($_POST)) {
		extract($_POST);

		if (checkUser($email, $pass,$user)) {

			if (isset($remember)) {

				$token = sha1(uniqid() . "Private Key is Here" . time() ) ;
				setcookie("token", $token, time() + 60*60*24*365*10);
				setTokenByEmail($email, $token);
			}
			$_SESSION["user"] = $user;
			
			if ($user["userType"] === "instructor") {
				header("Location: instructor_main.php");
			} else if ($user["userType"] === "student") {
				header("Location: student_main.php");
			} else if ($user["userType"] === "admin") {
				header("Location: admin_main.php");
			}
			else if ($user["userType"] === "chair") {
				header("Location: admin_main.php");
			}
			exit;

		} else {
			$fail = true;
		}
	}


  if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_COOKIE["token"])) {
    $user = getUserByToken($_COOKIE["token"]) ;
    if ( $user ) 
	{
        $_SESSION["user"] = $user ; 
       if($user["userType"] === "instructor")
            header("Location:instructor_main.php") ;
		else if($user["userType"] === "student")
		header("Location:student_main.php") ;    
		else if ($user["userType"] === "admin") {
				header("Location: admin_main.php");}
		else if ($user["userType"] === "chair") {
				header("Location: admin_main.php");
			}    
		exit ; 
    
 }
}
 
  if ( $_SERVER["REQUEST_METHOD"] == "GET" && isAuthenticated()) {
         if($_SESSION["user"]["userType"] === "instructor")
            header("Location:instructor_main.php") ;
          else if($_SESSION["user"]["userType"] === "student")
            header("Location:student_main.php") ;
		else if ($_SESSION["user"]["userType"] === "admin") 
		header("Location: admin_main.php");    
		else if ($_SESSION["user"]["userType"] === "chair") {
				header("Location: admin_main.php");
			}
         exit ;
  		 
	}
  ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Login V1</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="icon" type="image/png" href="images/icons/favicon.ico" />

    <link rel="stylesheet" type="text/css" href="fonts/font-awesome-4.7.0/css/font-awesome.min.css">

    <link rel="stylesheet" type="text/css" href="./css/login.css">

</head>

<body>

    <div class="limiter">
        <div class="container-login100">
            <div class="wrap-login100">
                <div class="login100-pic js-tilt" data-tilt>
                    <img src="images/img-01.png" alt="IMG">
                </div>

                <form action="?" method="post" class="login100-form validate-form">
                    <span class="login100-form-title">
                        Member Login
                    </span>

                    <div class="wrap-input100 validate-input" data-validate="Valid email is required: ex@abc.xyz">
                        <input class="input100" type="text" name="email" placeholder="Email">
                        <span class="focus-input100"></span>
                        <span class="symbol-input100">
                            <i class="fa fa-envelope" aria-hidden="true"></i>
                        </span>
                    </div>

                    <div class="wrap-input100 validate-input" data-validate="Password is required">
                        <input class="input100" type="password" name="pass" placeholder="Password">
                        <span class="focus-input100"></span>
                        <span class="symbol-input100">
                            <i class="fa fa-lock" aria-hidden="true"></i>
                        </span>
                    </div>

                    <div class="container-login100-form-btn">
                        <button class="login100-form-btn">
                            Login
                        </button>
                    </div>

                    <div class="text-center" style="margin-top:15px;">
                        <a href="guest.php" style="color:#7a5cff; font-weight:600; text-decoration:none;">
                            Continue as Guest
                        </a>
                    </div>

                    <div>
                        <div class="checkbox">
                            <input class="input-checkbox100" id="ckb1" type="checkbox" name="remember">
                            Remember me
                        </div>
                        <?php 
                     if ( isset($fail)) {
                        echo "<p class='error'>Wrong email or password</p>" ; 
                     }

					 if ( isset($error)) {
                        echo "<p class='error'>You tried to access main.php directly</p>" ; 
                     }
   
                  ?>
                    </div>
            </div>
            </form>
        </div>
    </div>
    </div>


    <script src="vendor/jquery/jquery-3.2.1.min.js"></script>
    <script src="vendor/bootstrap/js/popper.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.min.js"></script>
    <script src="vendor/select2/select2.min.js"></script>
    <script src="vendor/tilt/tilt.jquery.min.js"></script>
    <script>
    $('.js-tilt').tilt({
        scale: 1.1
    })
    </script>
    <script src="js/main.js"></script>
</body>

</html>