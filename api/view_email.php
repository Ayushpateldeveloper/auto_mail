<?php
header('Content-Type: application/json');
ob_start();  // Start output buffering
ini_set('display_errors', 0);  // Hide errors in output
ini_set('log_errors', 1);  // Enable error logging
ini_set('error_log', __DIR__ . '/error_log.txt');  // Log errors to a file
error_reporting(E_ALL);

if (!isset($_GET['messageId'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Message ID is required']);
    exit;
}

$messageId = $_GET['messageId'];

// Get access token from request headers
$headers = getallheaders();
$accessToken = null;
foreach ($headers as $header => $value) {
    if (strtolower($header) === 'authorization') {
        $accessToken = str_replace('Bearer ', '', $value);
        break;
    }
}

if (!$accessToken) {
    http_response_code(401);
    echo json_encode(['error' => 'Access token is required']);
    exit;
}

try {
    // Fetch full email content from Gmail API
    $url = 'https://gmail.googleapis.com/gmail/v1/users/me/messages/' . $messageId . '?format=full';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception("Failed to fetch email from Gmail API. HTTP Code: $httpCode");
    }

    $emailData = json_decode($response, true);
    if (!$emailData) {
        throw new Exception('Invalid JSON response from Gmail API.');
    }

    // Mark the email as read by removing the UNREAD label
    $modifyUrl = 'https://gmail.googleapis.com/gmail/v1/users/me/messages/' . $messageId . '/modify';
    $modifyPayload = json_encode([
        'removeLabelIds' => ['UNREAD']
    ]);

    $ch = curl_init($modifyUrl);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $modifyPayload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $modifyResponse = curl_exec($ch);
    $modifyHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    // Determine if the status was updated successfully
    $statusChanged = ($modifyHttpCode === 200);
    if (!$statusChanged) {
        error_log("Failed to mark email as read. HTTP Code: $modifyHttpCode. cURL Error: $curlError. Response: $modifyResponse");
    }

    // Extract email details
    $headersArray = $emailData['payload']['headers'];
    $email = [
        'id' => $messageId,
        'subject' => '',
        'from' => '',
        'to' => '',
        'date' => '',
        'body' => '',
        'attachments' => []
    ];

    foreach ($headersArray as $header) {
        switch ($header['name']) {
            case 'Subject':
                $email['subject'] = $header['value'];
                break;
            case 'From':
                $email['from'] = $header['value'];
                break;
            case 'To':
                $email['to'] = $header['value'];
                break;
            case 'Date':
                $email['date'] = $header['value'];
                break;
        }
    }

    // Function to extract email body and attachments recursively
    function processMessagePart($part)
    {
        $result = ['body' => '', 'attachments' => []];

        try {
            if (isset($part['parts'])) {
                foreach ($part['parts'] as $subpart) {
                    $subResult = processMessagePart($subpart);
                    $result['body'] .= $subResult['body'];
                    $result['attachments'] = array_merge($result['attachments'], $subResult['attachments']);
                }
            } else {
                if ($part['mimeType'] === 'text/plain' || $part['mimeType'] === 'text/html') {
                    $data = $part['body']['data'] ?? '';
                    $result['body'] .= base64_decode(str_replace(['-', '_'], ['+', '/'], $data));
                } elseif (isset($part['body']['attachmentId'])) {
                    $result['attachments'][] = [
                        'id' => $part['body']['attachmentId'],
                        'filename' => $part['filename'],
                        'mimeType' => $part['mimeType']
                    ];
                }
            }
        } catch (Exception $e) {
            return ['error' => 'Failed to process message part: ' . $e->getMessage()];
        }
        return $result;
    }

    $content = processMessagePart($emailData['payload']);
    if (isset($content['error'])) {
        echo json_encode(['error' => $content['error']]);
        exit;
    }

    $email['body'] = $content['body'];
    $email['attachments'] = $content['attachments'];

    // Add the statusChanged property to inform the client if the email was marked as read
    $email['statusChanged'] = $statusChanged;

    ob_clean();  // Clear buffer before output
    echo json_encode($email);
} catch (Exception $e) {
    http_response_code(500);
    ob_clean();
    echo json_encode(['error' => $e->getMessage()]);
}
?>
