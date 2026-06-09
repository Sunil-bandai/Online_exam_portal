<?php

session_start();

$host   = "localhost";
$user   = "root";
$pass   = "";
$dbname = "online_exam";

$conn = mysqli_connect($host, $user, $pass, $dbname);

if (!$conn) {
    die("❌ Connection failed: " . mysqli_connect_error());
}
?>