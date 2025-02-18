
<?php
// Database credentials
$servername = 'mysql.gb.stackcp.com:63665';
$username = 'suyog_test_db-3530313574b6';
$password = 'Ayupatel@$2310';
$dbname = 'suyog_test_db-3530313574b6';

// Create connection using mysqli
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Process only POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve posted data
    $latitude = isset($_POST['latitude']) ? $conn->real_escape_string($_POST['latitude']) : '';
    $longitude = isset($_POST['longitude']) ? $conn->real_escape_string($_POST['longitude']) : '';
    $timestamp = date('Y-m-d H:i:s');

    // Insert location data into the table
    $sql = "INSERT INTO locations (latitude, longitude, created_at) VALUES ('$latitude', '$longitude', '$timestamp')";
    if ($conn->query($sql) === TRUE) {
        echo 'Location saved successfully!';
    } else {
        echo 'Error: ' . $sql . '<br>' . $conn->error;
    }
} else {
    echo 'Invalid request.';
}

$conn->close();
?>

