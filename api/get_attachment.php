<?php
require_once '../includes/dbcon.php';
// include 'includes/header.php';

// Fetch the latest access token from the tokens table
$query = 'SELECT TOP 1 access_token FROM tokens ORDER BY id DESC';
$result = sqlsrv_query($conn, $query);
if ($result === false) {
    http_response_code(500);
    echo 'Error executing query: ' . print_r(sqlsrv_errors(), true);
    exit;
}
$access_token = '';
if ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
    $access_token = $row['access_token'];
}
sqlsrv_free_stmt($result);

if (empty($access_token)) {
    http_response_code(401);
    echo 'Access token not found.';
    exit;
}

// Validate required parameters
if (!isset($_GET['messageId']) || !isset($_GET['attachmentId'])) {
    http_response_code(400);
    echo 'Missing required parameters.';
    exit;
}

$messageId = $_GET['messageId'];
$attachmentId = $_GET['attachmentId'];
$userId = 'me';  // 'me' for the authenticated user's Gmail

// Optional parameters from the URL to set the Content-Type and filename.
$mimeTypeParam = isset($_GET['mimeType']) ? $_GET['mimeType'] : 'application/octet-stream';
$filename = isset($_GET['filename']) ? $_GET['filename'] : 'attachment';

// Build the Gmail API URL for retrieving the attachment
$url = "https://gmail.googleapis.com/gmail/v1/users/{$userId}/messages/{$messageId}/attachments/{$attachmentId}";

// Initialize cURL to call the Gmail API endpoint
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $access_token"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($httpCode !== 200) {
    http_response_code($httpCode);
    echo 'Error fetching attachment.';
    curl_close($ch);
    exit;
}

curl_close($ch);

// Decode the JSON response; Gmail returns an object with a 'data' property.
$attachment = json_decode($response, true);
if (!isset($attachment['data'])) {
    http_response_code(500);
    echo 'Attachment data not found.';
    exit;
}

// Convert base64url to standard base64 and decode.
$base64UrlData = $attachment['data'];
$base64Data = strtr($base64UrlData, '-_', '+/');
$attachmentData = base64_decode($base64Data);

// Output the file using the provided MIME type and filename.
header('Content-Type: ' . $mimeTypeParam);
header('Content-Length: ' . strlen($attachmentData));

// For inline images, use "inline"; for downloads, you may use "attachment".
header('Content-Disposition: inline; filename="' . $filename . '"');
echo $attachmentData;
