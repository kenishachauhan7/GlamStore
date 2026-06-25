<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$host = "localhost";

// db.php - Database connection file
// Include this file on every page: include('db.php');

$host = "localhost";
$user = "root";
$password = "";
$database = "glamstore_db";

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>