<?php
// admin/check_new_logs.php
include '../database.php';

$last_id = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;

$sql = "SELECT COUNT(*) as count FROM pump_logs WHERE id > ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $last_id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

header('Content-Type: application/json');
echo json_encode($data);
?>