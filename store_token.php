<?php
// Include database connection (ensure this file uses sqlsrv_connect)
include ('includes/dbcon.php');

// Check connection
if ($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}

// For testing purposes, we'll use a sample JSON string.
// In production, you might retrieve this from $_POST or from php://input.
$json = $_POST['access_token'];

// Decode JSON to an associative array
$data = json_decode($json, true);
if (!$data) {
    die('Invalid JSON input');
}

// Extract only the access_token
$access_token = $data['access_token'];

// Prepare the SQL statement to insert the access_token
$sql = 'INSERT INTO tokens (access_token) VALUES (?)';
$params = array($access_token);

// Prepare and execute using SQLSRV functions
$stmt = sqlsrv_prepare($conn, $sql, $params);
if (!$stmt) {
    die('Prepare failed: ' . print_r(sqlsrv_errors(), true));
}

if (sqlsrv_execute($stmt)) {
    // echo 'Access token stored successfully';
} else {
    die('Error: ' . print_r(sqlsrv_errors(), true));
}

// Free statement and close connection
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
?>
