<?php
$servername = 'localhost';
$username = 'sa';
$password = '12345';
$dbname = 'auto_mail';

// Create connection using sqlsrv
$connectionInfo = array('UID' => $username, 'PWD' => $password, 'Database' => $dbname);
$conn = sqlsrv_connect($servername, $connectionInfo);

if ($conn === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Connection failed: ' . print_r(sqlsrv_errors(), true)]);
    exit;
}

// Query to get departments with their keywords
$sql = "SELECT 
    d.id,
    d.name,
    STUFF((SELECT ', ' + dk.keyword 
           FROM keywords dk 
           WHERE dk.department_id = d.id 
           FOR XML PATH('')) 
           , 1, 2, '') AS keywords
FROM departments d
ORDER BY d.name";

$stmt = sqlsrv_query($conn, $sql);

if ($stmt === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Database query error: ' . print_r(sqlsrv_errors(), true)]);
    exit;
}

$departments = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $departments[] = $row;
}

// Format the keywords as arrays
foreach ($departments as &$dept) {
    $dept['keywords'] = $dept['keywords'] ? explode(',', $dept['keywords']) : [];
}

echo json_encode($departments);
sqlsrv_close($conn);
?>
