<?php
 session_start() ;
 require "functions.php" ;

 if(!isAuthenticated())
 {
    header("Location: index.php?error") ;
    exit ;
 }

 setTokenByEmail($_SESSION["user"]["email"],null);
 setcookie("token","",1);

 session_destroy();
 setcookie("PHPSESSID","",1);

 header("Location:login.php");

?>