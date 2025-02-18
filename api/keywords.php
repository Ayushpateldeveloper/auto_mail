<?php
header('Content-Type: application/json');
ini_set('display_errors', 0);
require_once '../includes/dbcon.php';

$departmentId = isset($_GET['departmentId']) ? (int) $_GET['departmentId'] : 0;

if ($departmentId > 0) {
    $sql = "SELECT keyword FROM keywords WHERE department_id = $departmentId ORDER BY keyword";
    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt === false) {
        // Log errors instead of displaying them
        error_log(print_r(sqlsrv_errors(), true));
        echo json_encode([]);
        exit;
    }
    $keywords = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $keywords[] = $row['keyword'];
    }
    echo json_encode($keywords);
} else {
    echo json_encode([]);
}
sqlsrv_close($conn);
?>
