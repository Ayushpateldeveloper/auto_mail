<?php
// Database credentials
$servername = 'mysql.gb.stackcp.com:63665';
$username = 'suyog_test_db-3530313574b6';
$password = 'Ayupatel@$2310';
$dbname = 'suyog_test_db-3530313574b6';

// Create connection using mysqli
$conn = new mysqli($servername, $username, $password, $dbname);

$sql = 'SELECT * FROM locations where user_id=2007';
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // output data of each row
    while ($row = $result->fetch_assoc()) {
        echo 'id: ' . $row['id'] . ' - Name: ' . $row['latitude'] . ' ' . $row['longitude'] . '<br>';
    }
} else {
    echo 'No results found.';
}
