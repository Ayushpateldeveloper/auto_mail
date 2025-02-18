<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve the posted data
    $latitude = $_POST['latitude'] ?? '';
    $longitude = $_POST['longitude'] ?? '';
    $timestamp = $_POST['timestamp'] ?? '';

    // For demonstration, we'll simply return a success message.
    // You can insert the data into a database or write to a file as needed.
    echo "Location (Lat: $latitude, Lng: $longitude) received at $timestamp.";
} else {
    echo 'No data received.';
}
?>
