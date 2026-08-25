<?php
$dsn = "mysql:host=localhost;port=3306;dbname=ctis256;charset=utf8mb4" ;
$user = "root" ;
$pass = "" ;

try {
  $db = new PDO($dsn, $user, $pass) ;
} catch( PDOException $ex) {
  echo "<p> Connection Error".$ex->getMessage()."<p>";
  exit ;
}