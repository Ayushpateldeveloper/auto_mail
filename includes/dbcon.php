<?php
// $servername = 'mysql.gb.stackcp.com:63665';
// $username = 'suyog_test_db-3530313574b6';
// $password = 'Ayupatel@$2310';
// $dbname = 'suyog_test_db-3530313574b6';

$servername = 'localhost';  // or your server name
$username = 'sa';
$password = '12345';
$dbname = 'auto_mail';

// Create connection using sqlsrv
$connectionInfo = array('UID' => $username, 'PWD' => $password, 'Database' => $dbname);
$conn = sqlsrv_connect($servername, $connectionInfo);

if ($conn === false) {
    die('Connection failed: ' . print_r(sqlsrv_errors(), true));
}
?>
