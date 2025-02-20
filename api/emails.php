<?php
header('Content-Type: application/json');
// Turn off error display in production
ini_set('display_errors', 0);
error_reporting(0);

require_once '../includes/dbcon.php';

try {
    // Get access token
    $query = 'SELECT TOP 1 access_token FROM tokens ORDER BY id DESC';
    $result = sqlsrv_query($conn, $query);
    if ($result === false) {
        throw new Exception('Error fetching access token: ' . print_r(sqlsrv_errors(), true));
    }
    $access_token = '';
    if ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
        $tokenData = json_decode($row['access_token'], true);
        $access_token = ($tokenData && isset($tokenData['access_token'])) ? $tokenData['access_token'] : $row['access_token'];
    }
    sqlsrv_free_stmt($result);
    if (empty($access_token)) {
        throw new Exception('Access token not found');
    }

    // Validate department_id parameter (optional)
    $dept_id = isset($_GET['department_id']) && is_numeric($_GET['department_id']) ? (int) $_GET['department_id'] : null;

    // Get keywords for the department if a valid department ID is provided.
    $keywords = [];
    if ($dept_id !== null && $dept_id > 0) {
        $sql = 'SELECT keyword FROM keywords WHERE department_id = ?';
        $stmt = sqlsrv_query($conn, $sql, [$dept_id]);
        if ($stmt === false) {
            throw new Exception('Error fetching keywords: ' . print_r(sqlsrv_errors(), true));
        }
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $keywords[] = $row['keyword'];
        }
        sqlsrv_free_stmt($stmt);
    }
    // Build Gmail API query string.
    // If keywords were found, build a query using them.
    $queryStrParts = [];
    if (!empty($keywords)) {
        foreach ($keywords as $keyword) {
            $queryStrParts[] = 'subject:' . $keyword;
            $queryStrParts[] = 'body:' . $keyword;
        }
    }
    // If no keywords are available (for All Departments or if no keywords exist), $queryStr will be empty,
    // meaning that the API will return emails without additional keyword filtering.
    $queryStr = implode(' OR ', $queryStrParts);

    // Add read/unread filter if specified
    $is_read = isset($_GET['is_read']) ? (bool) $_GET['is_read'] : null;
    if ($is_read !== null) {
        $queryStr .= $is_read ? ' label:read -label:unread' : ' label:unread -label:read';
    }

    // Prepare Gmail API URL
    $url = 'https://gmail.googleapis.com/gmail/v1/users/me/messages?q=' . urlencode($queryStr);
    if (isset($_GET['pageToken'])) {
        $url .= '&pageToken=' . urlencode($_GET['pageToken']);
    }
    $url .= '&maxResults=10';

    // Call Gmail API using cURL
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $access_token]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  // Only for development!
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if (curl_errno($ch)) {
        throw new Exception('Curl error: ' . curl_error($ch));
    }
    curl_close($ch);
    if ($httpCode !== 200) {
        throw new Exception('Failed to fetch emails from Gmail API. HTTP Code: ' . $httpCode . ', Response: ' . $response);
    }
    $messages = json_decode($response, true);
    if (!$messages) {
        throw new Exception('Invalid response from Gmail API: ' . $response);
    }
    if (!isset($messages['messages'])) {
        echo json_encode([
            'emails' => [],
            'nextPageToken' => null,
            'hasMore' => false
        ]);
        exit;
    }

    // Fetch full details for each message
    $emails = [];
    foreach ($messages['messages'] as $message) {
        $messageId = $message['id'];
        $urlMsg = 'https://gmail.googleapis.com/gmail/v1/users/me/messages/' . $messageId . '?format=full';
        $chMsg = curl_init($urlMsg);
        curl_setopt($chMsg, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $access_token]);
        curl_setopt($chMsg, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($chMsg, CURLOPT_SSL_VERIFYPEER, false);
        $responseMsg = curl_exec($chMsg);
        $httpCodeMsg = curl_getinfo($chMsg, CURLINFO_HTTP_CODE);
        if (curl_errno($chMsg)) {
            continue;
        }
        curl_close($chMsg);
        if ($httpCodeMsg === 200) {
            $emailData = json_decode($responseMsg, true);
            if ($emailData) {
                $headers = $emailData['payload']['headers'];
                $email = [
                    'id' => $messageId,
                    'threadId' => $emailData['threadId'],
                    'from' => '',
                    'to' => '',
                    'subject' => '',
                    'date' => '',
                    'snippet' => $emailData['snippet'],
                    'is_read' => !in_array('UNREAD', $emailData['labelIds']),
                    'has_attachment' => false
                ];
                if (isset($emailData['payload']['parts'])) {
                    foreach ($emailData['payload']['parts'] as $part) {
                        if (isset($part['filename']) && !empty($part['filename'])) {
                            $email['has_attachment'] = true;
                            break;
                        }
                    }
                }
                foreach ($headers as $header) {
                    switch ($header['name']) {
                        case 'From':
                            $email['from'] = $header['value'];
                            break;
                        case 'To':
                            $email['to'] = $header['value'];
                            break;
                        case 'Subject':
                            $email['subject'] = $header['value'];
                            break;
                        case 'Date':
                            $email['date'] = $header['value'];
                            break;
                    }
                }
                $emails[] = $email;
            }
        }
    }

    echo json_encode([
        'emails' => $emails,
        'nextPageToken' => isset($messages['nextPageToken']) ? $messages['nextPageToken'] : null,
        'hasMore' => isset($messages['nextPageToken'])
    ]);
} catch (Exception $e) {
    error_log('Email API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} finally {
    if (isset($conn)) {
        sqlsrv_close($conn);
    }
}
?>
