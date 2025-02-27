<?php
// chat.php

// Enable error reporting (for development only)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Read the JSON input
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['message'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No message provided']);
    exit;
}
$userMessage = $data['message'];

// Set your OpenAI API key
$apiKey = 'sk-your-api-key-here';
if (!$apiKey) {
    // Replace with your API key (or set it as an environment variable)
    $apiKey = 'sk-your-api-key-here';
}

// Prepare data for the OpenAI API call
$postData = [
    'model' => 'gpt-4',
    'messages' => [
        [
            'role' => 'user',
            'content' => $userMessage
        ]
    ]
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/chat/completions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $apiKey
]);

$response = curl_exec($ch);
if (curl_errno($ch)) {
    http_response_code(500);
    echo json_encode(['error' => 'Request Error: ' . curl_error($ch)]);
    exit;
}
curl_close($ch);

$responseData = json_decode($response, true);
if (isset($responseData['error'])) {
    http_response_code(500);
    echo json_encode(['error' => $responseData['error']]);
    exit;
}

$reply = $responseData['choices'][0]['message']['content'] ?? 'No reply received.';

// OPTIONAL: Save chat to MSSQL database if needed
// Uncomment and configure the following section if you want to log conversations to MSSQL

/*
 * include ('includes/dbcon.php');
 * // Connect using the sqlsrv extension
 * $conn = sqlsrv_connect($serverName, $connectionOptions);
 * if ($conn === false) {
 *     header('Content-Type: application/json');
 *     echo json_encode(['error' => 'Database connection failed', 'details' => sqlsrv_errors()]);
 *     exit;
 * }
 *
 * $tsql = 'INSERT INTO ChatHistory (UserMessage, BotReply, CreatedAt) VALUES (?, ?, GETDATE())';
 * $params = [$userMessage, $reply];
 * $stmt = sqlsrv_query($conn, $tsql, $params);
 * if ($stmt === false) {
 *     header('Content-Type: application/json');
 *     echo json_encode(['error' => 'Database query failed', 'details' => sqlsrv_errors()]);
 *     exit;
 * }
 * sqlsrv_free_stmt($stmt);
 * sqlsrv_close($conn);
 */

// Return the reply as JSON
header('Content-Type: application/json');
echo json_encode(['reply' => $reply]);
?>
