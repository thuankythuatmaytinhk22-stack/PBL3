<?php
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'water_sampling_system';
$port = 3307;

$conn = new mysqli($host, $username, $password, $database, $port);

if ($conn->connect_error) {
    die("Kết nối database thất bại: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>