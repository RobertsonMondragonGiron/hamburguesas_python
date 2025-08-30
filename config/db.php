<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "hamburguesas_db";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
?>
