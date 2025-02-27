<?php
header('Content-Type: application/json');
require_once 'includes/dbcon.php';

// Get department ID
$department_id = isset($_GET['department_id']) ? (int)$_GET['department_id'] : 0;

if ($department_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid department ID']);
    exit;
}

// Fetch keywords for the department
$sql = "SELECT keyword FROM keywords WHERE department_id = ?";
$stmt = sqlsrv_query($conn, $sql, array($department_id));

if ($stmt === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . print_r(sqlsrv_errors(), true)]);
    exit;
}

$keywords = array();
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $keywords[] = $row['keyword'];
}

echo json_encode($keywords);
sqlsrv_close($conn);
