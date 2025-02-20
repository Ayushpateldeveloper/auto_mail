<!-- cdn -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"
    integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g=="
    crossorigin="anonymous" referrerpolicy="no-referrer"></script>

<?php
// Log the start of processing (optional)
$logMessage = sprintf("\n\n[%s] local.INFO: Starting webhook processing...", date('Y-m-d H:i:s'));

// Get JSON input from webhook POST
$data = file_get_contents('php://input') . "\n\n";
$arr = json_decode($data, true);

// Include your database connection (make sure dbcon.php is correctly configured)
include ('dbcon.php');
date_default_timezone_set('Asia/Kolkata');

// Define file types that need processing (if they contain media)
$fileType = array('image', 'voice', 'document', 'video', 'audio');

// Arrays to store processed messages per group
$foMcxArr = array();  // For messages from "F&O & MCX Strategies"
$testingArr = array();  // For messages from "Testing group for auto whatsapp"
$webhookArr = array();  // For messages from "Webhook"

// -------------------------------------------------------------------
// Function: insertMessageToDb()
// Inserts a message record into the specified table using a parameterized query.
function insertMessageToDb($data, $tableName, $con)
{
    // Parameterized SQL query (using square brackets for column names)
    // Note: We're not inserting the identity column [id] here.
    $sql = 'INSERT INTO ' . $tableName . ' 
        ([msg_id], [from_me], [type], [body], [fileName], [fromNo], [fromName], [msgTime], [source], [chat_name], [status], [forwarded], [replyID], [createdAt])
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

    $params = array(
        $data['id'],
        $data['from_me'],
        $data['type'],
        $data['body'],
        $data['fileName'],
        $data['from'],
        $data['from_name'],
        $data['timestamp'],
        $data['source'],
        $data['chat_name'],
        $data['status'],
        $data['forwarded'],
        $data['replyID'],
        $data['createdAt']
    );

    // Log parameters for debugging
    file_put_contents('debug_sql.log', sprintf("[%s] SQL Params: %s\n", date('Y-m-d H:i:s'), print_r($params, true)), FILE_APPEND);

    $stmt = sqlsrv_prepare($con, $sql, $params);
    if (!$stmt) {
        file_put_contents('messages.log', sprintf("[%s] SQL Prepare Error: %s\n", date('Y-m-d H:i:s'), json_encode(sqlsrv_errors())), FILE_APPEND);
        return false;
    }

    $result = sqlsrv_execute($stmt);
    if ($result === false) {
        file_put_contents('messages.log', sprintf("[%s] SQL Execute Error: %s\n", date('Y-m-d H:i:s'), json_encode(sqlsrv_errors())), FILE_APPEND);
        return false;
    }
    return true;
}

// -------------------------------------------------------------------
// Function: processMessage()
// Processes a single message, downloads any file if needed,
// and inserts it into the database. Sets msg_id and createdAt.
function processMessage($message, $fileType, $outputDir, $tableName, $con)
{
    $processedArr = array();

    // Basic message info
    $processedArr['id'] = $message['id'];
    $processedArr['msg_id'] = isset($message['msg_id']) ? $message['msg_id'] : '';
    $processedArr['from_me'] = $message['from_me'];
    $processedArr['type'] = $message['type'];
    $processedArr['chat_name'] = $message['chat_name'];
    $processedArr['timestamp'] = date('Y-m-d H:i:s', $message['timestamp']);
    $processedArr['source'] = $message['source'];
    $processedArr['status'] = $message['status'];
    $processedArr['from'] = $message['from'];
    $processedArr['from_name'] = $message['from_name'];
    // Set createdAt to current timestamp
    $processedArr['createdAt'] = date('Y-m-d H:i:s');

    // Process context/reply information
    if (isset($message['context']) && is_array($message['context'])) {
        $processedArr['replyID'] = isset($message['context']['quoted_id']) ? $message['context']['quoted_id'] : 'New-Msg';
        $processedArr['forwarded'] = isset($message['context']['forwarded']) ? $message['context']['forwarded'] : 'false';
    } else {
        $processedArr['replyID'] = 'New-Msg';
        $processedArr['forwarded'] = 'false';
    }

    // Process message content based on type
    if ($message['type'] == 'text') {
        $processedArr['body'] = $message['text']['body'];
        $processedArr['fileName'] = '';
    } else if (in_array($message['type'], $fileType)) {
        $link = $message[$message['type']]['link'];
        $processedArr['body'] = isset($message[$message['type']]['caption']) ? $message[$message['type']]['caption'] : 'No';
        $fileExt = pathinfo($link, PATHINFO_EXTENSION);
        $newFileName = $message['type'] . date('YmdHis') . '.' . $fileExt;
        $mediaContent = file_get_contents($link);
        if ($mediaContent === FALSE) {
            $processedArr['fileName'] = 'No File Found';
        } else {
            file_put_contents($outputDir . '/' . $newFileName, $mediaContent);
            $processedArr['fileName'] = $newFileName;
        }
    } else if ($message['type'] == 'action') {
        $processedArr['replyID'] = $message[$message['type']]['target'];
        $processedArr['type'] = $message[$message['type']]['type'];
        $processedArr['body'] = isset($message[$message['type']]['edited_content']['body'])
            ? $message[$message['type']]['edited_content']['body']
            : (isset($message[$message['type']]['edited_content']['caption'])
                ? $message[$message['type']]['edited_content']['caption']
                : '');
    } else if ($message['type'] == 'deleted') {
        $processedArr['type'] = 'deleted';
        $processedArr['body'] = isset($processedArr['body']) ? $processedArr['body'] : '';
        $processedArr['fileName'] = isset($processedArr['fileName']) ? $processedArr['fileName'] : '';
    } else {
        $processedArr['body'] = '';
        $processedArr['fileName'] = '';
    }

    // Insert the processed message into the database
    insertMessageToDb($processedArr, $tableName, $con);

    return $processedArr;
}

// -------------------------------------------------------------------
// Process messages and separate by group based on chat_name

if (isset($arr['messages']) && is_array($arr['messages'])) {
    foreach ($arr['messages'] as $message) {
        // Log each message's chat_name for debugging
        file_put_contents('debug_groups.log', sprintf("[%s] Chat Name: %s\n", date('Y-m-d H:i:s'), $message['chat_name']), FILE_APPEND);

        // Convert the chat name to lowercase for uniform comparison
        $chatNameLower = strtolower(trim($message['chat_name']));

        if ($chatNameLower == 'f&o & mcx strategies') {
            $foMcxArr[] = processMessage($message, $fileType, 'FO-MCX-Strategies', 'FO_MCX_Strategies', $con);
        } elseif ($chatNameLower == '696 ht management') {
            $testingArr[] = processMessage($message, $fileType, 'sixninesix-ht-management', '696_ht_management', $con);
        } elseif ($chatNameLower == 'nc discussion') {
            $webhookArr[] = processMessage($message, $fileType, 'NC-discussion', 'NC_discussion', $con);
        } elseif ($chatNameLower == 'webhook') {  // Modified: using lowercase comparison
            $webhookArr[] = processMessage($message, $fileType, 'Extruder', 'Extruder', $con);
        } elseif ($chatNameLower == 'po-list entries') {
            $webhookArr[] = processMessage($message, $fileType, 'PO-list-entries', 'PO_list_entries', $con);
        }
        // Add more groups here if needed...
    }
}
// Log the processed messages from all groups
$logOutputFile = 'processed_messages.log';
$logOutput = sprintf("[%s] FO & MCX Strategies Messages:\n%s\n", date('Y-m-d H:i:s'), print_r($foMcxArr, true));
$logOutput .= sprintf("[%s] Testing Group Messages:\n%s\n", date('Y-m-d H:i:s'), print_r($testingArr, true));
$logOutput .= sprintf("[%s] Webhook Messages:\n%s\n", date('Y-m-d H:i:s'), print_r($webhookArr, true));
file_put_contents($logOutputFile, $logOutput, FILE_APPEND);

// -------------------------------------------------------------------
// Function: sendWhatsAppMessage()
// Sends a WhatsApp message using the provided parameters.
function sendWhatsAppMessage($messageType, $bookingPageUrl, $phoneNumber, $token)
{
    if ($messageType === 'book') {
        $confirmationMessage = "*📋 Book Raw Materials*\n\n"
            . "Click the link below to provide the details for your raw material booking:\n"
            . "👉 ($bookingPageUrl)\n\n"
            . 'Thank you!';
    } elseif ($messageType === 'trade') {
        $confirmationMessage = "*📋 New commodity Trade*\n\n"
            . "Click the link below to provide the details for your raw material booking:\n"
            . "👉 ($bookingPageUrl)\n\n"
            . 'Thank you!';
    } elseif ($messageType === 'issue') {
        $confirmationMessage = "*📋 New Copper/Aluminium Issue*\n\n"
            . "Click the link below to provide the details for your Copper/Aluminium Issue:\n"
            . "👉 ($bookingPageUrl)\n\n"
            . 'Thank you!';
    } elseif ($messageType === 'testing') {
        $confirmationMessage = "*📋 Testing Message*\n\n"
            . "This is a test message.\n\n"
            . 'Thank you!';
    } else {
        return false;
    }
    $url = 'https://gate.whapi.cloud/messages/text';
    $data = [
        'to' => $phoneNumber,
        'body' => $confirmationMessage,
        'no_link_preview' => true,
    ];
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token,
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($httpCode == 200) {
        curl_close($ch);
        return true;
    } else {
        $error = curl_error($ch);
        curl_close($ch);
        return [
            'httpCode' => $httpCode,
            'response' => $response,
            'error' => $error,
            'payload' => json_encode($data, JSON_PRETTY_PRINT)
        ];
    }
}

// -------------------------------------------------------------------
// Define a log file for WhatsApp API results
$whatsappLogFile = 'whatsapp.log';

// Process Testing group messages for WhatsApp triggers
foreach ($testingArr as $msg) {
    $bodyLower = strtolower(trim($msg['body']));
    file_put_contents($whatsappLogFile, sprintf("[%s] [DEBUG] Testing Group Body: %s\n", date('Y-m-d H:i:s'), $bodyLower), FILE_APPEND);
    if ($bodyLower === 'book') {
        $result = sendWhatsAppMessage('book', 'https://tinyurl.com/4stndypm', '917202093444-1615358363@g.us', 'Exa88aT4Ztr24sFe5AHfl646ioNKIJOX');
        $logEntry = sprintf("[%s] [Testing Group] Sent 'book' message: %s\n", date('Y-m-d H:i:s'), print_r($result, true));
        file_put_contents($whatsappLogFile, $logEntry, FILE_APPEND);
    } elseif ($bodyLower === 'trade') {
        $result = sendWhatsAppMessage('trade', 'https://tinyurl.com/y5w8kz3n', '120363386814639131@g.us', 'Exa88aT4Ztr24sFe5AHfl646ioNKIJOX');
        $logEntry = sprintf("[%s] [Testing Group] Sent 'trade' message: %s\n", date('Y-m-d H:i:s'), print_r($result, true));
        file_put_contents($whatsappLogFile, $logEntry, FILE_APPEND);
    } elseif ($bodyLower === 'issue') {
        $result = sendWhatsAppMessage('issue', 'https://tinyurl.com/46wb6wpz', '120363390447846303@g.us', 'Exa88aT4Ztr24sFe5AHfl646ioNKIJOX');
        $logEntry = sprintf("[%s] [Testing Group] Sent 'issue' message: %s\n", date('Y-m-d H:i:s'), print_r($result, true));
        file_put_contents($whatsappLogFile, $logEntry, FILE_APPEND);
    } elseif ($bodyLower === 'testing') {
        $result = sendWhatsAppMessage('testing', 'https://tinyurl.com/your-link', '120363315341101588@g.us', 'Exa88aT4Ztr24sFe5AHfl646ioNKIJOX');
        $logEntry = sprintf("[%s] [Testing Group] Sent 'testing' message: %s\n", date('Y-m-d H:i:s'), print_r($result, true));
        file_put_contents($whatsappLogFile, $logEntry, FILE_APPEND);
    }
}

// Process FO & MCX Strategies messages for WhatsApp triggers
foreach ($foMcxArr as $msg) {
    $bodyLower = strtolower(trim($msg['body']));
    file_put_contents($whatsappLogFile, sprintf("[%s] [DEBUG] FO & MCX Strategies Body: %s\n", date('Y-m-d H:i:s'), $bodyLower), FILE_APPEND);
    if ($bodyLower === 'trade') {
        $result = sendWhatsAppMessage('trade', 'https://tinyurl.com/y5w8kz3n', '120363386814639131@g.us', 'Exa88aT4Ztr24sFe5AHfl646ioNKIJOX');
        $logEntry = sprintf("[%s] [FO & MCX Strategies] Sent 'trade' message: %s\n", date('Y-m-d H:i:s'), print_r($result, true));
        file_put_contents($whatsappLogFile, $logEntry, FILE_APPEND);
    }
    // Add further conditions for this group if required.
}

// Process Webhook messages for WhatsApp triggers
foreach ($webhookArr as $msg) {
    $bodyLower = strtolower(trim($msg['body']));
    file_put_contents($whatsappLogFile, sprintf("[%s] [DEBUG] Webhook Body: %s\n", date('Y-m-d H:i:s'), $bodyLower), FILE_APPEND);
    if ($bodyLower === 'trade') {
        $result = sendWhatsAppMessage('trade', 'https://tinyurl.com/y5w8kz3n', '120363386814639131@g.us', 'Exa88aT4Ztr24sFe5AHfl646ioNKIJOX');
        $logEntry = sprintf("[%s] [Webhook] Sent 'trade' message: %s\n", date('Y-m-d H:i:s'), print_r($result, true));
        file_put_contents($whatsappLogFile, $logEntry, FILE_APPEND);
    }
    // Add further conditions for the Webhook group if required.
}
?>
