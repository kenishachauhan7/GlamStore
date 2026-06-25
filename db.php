<?php
$host = "sql300.infinityfree.com";
$dbname = "if0_42269560_glamstore";
$username = "if0_42269560";
$password = "WirRJ5IKrLD";

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>