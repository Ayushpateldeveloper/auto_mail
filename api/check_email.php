<?php
ini_set('display_errors', 0);
error_reporting(0);

require_once '../includes/dbcon.php';

header('Content-Type: application/json');

$email = isset($_GET['email']) ? trim($_GET['email']) : '';

if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Email parameter missing']);
    exit;
}

$sql = 'SELECT id FROM users WHERE email = ? AND isActive = 1';
$params = [$email];
$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    echo json_encode(['success' => false, 'message' => 'Error checking email']);
    exit;
}

if (sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    echo json_encode(['success' => true, 'exists' => true]);
} else {
    echo json_encode(['success' => true, 'exists' => false]);
}

sqlsrv_close($conn);
?>
