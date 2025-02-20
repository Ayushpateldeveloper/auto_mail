<?php
ini_set('display_errors', 0);
error_reporting(0);

require_once '../includes/dbcon.php';

header('Content-Type: application/json');

// Handle incoming requests
$requestMethod = $_SERVER['REQUEST_METHOD'];

switch ($requestMethod) {
    case 'GET':
        getUsers();
        break;
    case 'POST':
        addUser();
        break;
    case 'PUT':
        updateUser();
        break;
    case 'DELETE':
        deleteUser();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

function getUsers()
{
    global $conn;
    // Only return active users (isActive = 1)
    $sql = 'SELECT id, username, email FROM users WHERE isActive = 1';
    $stmt = sqlsrv_query($conn, $sql);
    $users = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $users[] = $row;
    }
    echo json_encode(['success' => true, 'users' => $users]);
}

function addUser()
{
    global $conn;
    $data = json_decode(file_get_contents('php://input'), true);
    $username = $data['username'];
    $email = $data['email'];

    // Check if an active user with the same email already exists
    $sql = 'SELECT id FROM users WHERE email = ? AND isActive = 1';
    $params = [$email];
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt && sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        echo json_encode(['success' => false, 'message' => 'Email already exists']);
        return;
    }

    // Set isActive to 1 (active) by default
    $sql = 'INSERT INTO users (username, email, isActive) VALUES (?, ?, 1)';
    $params = [$username, $email];
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt) {
        echo json_encode(['success' => true, 'message' => 'User added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error adding user']);
    }
}

function updateUser()
{
    global $conn;
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'];
    $username = $data['username'];
    $email = $data['email'];

    // Check for uniqueness: Ensure no other active user has the same email
    $sql = 'SELECT id FROM users WHERE email = ? AND isActive = 1 AND id <> ?';
    $params = [$email, $id];
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt && sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        echo json_encode(['success' => false, 'message' => 'Email already exists']);
        return;
    }

    $sql = 'UPDATE users SET username = ?, email = ? WHERE id = ?';
    $params = [$username, $email, $id];
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt) {
        echo json_encode(['success' => true, 'message' => 'User updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating user']);
    }
}

function deleteUser()
{
    global $conn;
    // Try to decode JSON body first
    $data = json_decode(file_get_contents('php://input'), true);
    if (empty($data) || !isset($data['id'])) {
        // Fallback: check query string parameter
        $id = isset($_GET['id']) ? $_GET['id'] : null;
    } else {
        $id = $data['id'];
    }

    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'User ID is missing']);
        return;
    }

    // Soft delete: update isActive column to 0 instead of deleting
    $sql = 'UPDATE users SET isActive = 0 WHERE id = ?';
    $params = [$id];
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt) {
        echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error deleting user']);
    }
}

sqlsrv_close($conn);
?>
