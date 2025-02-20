
<!-- cdn -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

<?php
$logMessage = sprintf("\n\n[%s] local.INFO: ", date('Y-m-d H:i:s'));

// Assuming you're using POST method for your webhook
$data = file_get_contents('php://input') . "\n\n";
// file_put_contents('messages.log', $data, FILE_APPEND);

$arr = json_decode($data, true);
include ('dbcon.php');
date_default_timezone_set('Asia/Kolkata');

$fileType = array('image', 'voice', 'document', 'video', 'audio');
$finalArr = array();
$bookArr = array();
$barodaArr = array();
$testArr = array();
$CoppArr = array();

function insertMessageToDb($data, $tableName, $con)
{
    $sql = 'INSERT INTO ' . $tableName . "(msg_id, from_me, type, body, fileName, fromNo, fromName, msgTime, source, chat_name, status, forwarded, replyID) 
            VALUES('" . $data['id'] . "','"
        . $data['from_me'] . "','"
        . $data['type'] . "','"
        . $data['body'] . "','"
        . $data['fileName'] . "','"
        . $data['from'] . "','"
        . $data['from_name'] . "','"
        . $data['timestamp'] . "','"
        . $data['source'] . "','"
        . $data['chat_name'] . "','"
        . $data['status'] . "','"
        . $data['forwarded'] . "','"
        . $data['replyID'] . "')";

    $run = sqlsrv_query($con, $sql);
    if ($run == false) {
        file_put_contents('messages.log', json_encode(sqlsrv_errors()), FILE_APPEND);
        return false;
    }
    return true;
}

function processMessage($message, $fileType, $outputDir, $tableName, $con)
{
    $processedArr = array();

    // Basic message info
    $processedArr['id'] = $message['id'];
    $processedArr['from_me'] = $message['from_me'];
    $processedArr['type'] = $message['type'];
    $processedArr['chat_name'] = $message['chat_name'];
    $processedArr['timestamp'] = date('Y-m-d H:i:s', $message['timestamp']);
    $processedArr['source'] = $message['source'];
    $processedArr['status'] = $message['status'];
    $processedArr['from'] = $message['from'];
    $processedArr['from_name'] = $message['from_name'];

    // Process context/reply info
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
        // Move file to specified directory
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
        $processedArr['fileName'] = '';
        $processedArr['body'] = '';
    }

    // Insert data into database
    insertMessageToDb($processedArr, $tableName, $con);

    return $processedArr;
}

if (isset($arr['messages']) && is_array($arr['messages'])) {
    foreach ($arr['messages'] as $message) {
        if ($message['chat_name'] == 'Payment Collection Issues') {
            $finalArr = processMessage($message, $fileType, 'Payment-Msg', 'Payment_Group', $con);
        } else if ($message['chat_name'] == 'RM Booking') {
            $bookArr = processMessage($message, $fileType, 'booking-Msg', 'booking_group', $con);
        } else if ($message['chat_name'] == 'Baroda Office') {
            $barodaArr = processMessage($message, $fileType, 'Baroda-office', 'baroda_office', $con);
        } else if ($message['chat_name'] == 'Testing group for auto whatsapp') {
            $testArr = processMessage($message, $fileType, 'Test-Msg', 'test_group', $con);
        } else if ($message['chat_name'] == 'Copper/Aluminium issues') {
            $CoppArr = processMessage($message, $fileType, 'copper-aluminum-issues', 'cu_alu_issue', $con);
        }
    }
}

// Payment Colletion Issues group //

if (sizeof($finalArr) > 0) {
    // Removed database insertion code here
}

// baroda office group //

if (sizeof($barodaArr) > 0) {
    // Removed database insertion code here
}

// RM booking group //

if (sizeof($bookArr) > 0) {
    // Removed database insertion code here
    if (strtolower(trim($bookArr['body'])) == 'book') {
        $token = 'Exa88aT4Ztr24sFe5AHfl646ioNKIJOX';
        $phoneNumber = '917202093444-1615358363@g.us';
        $bookingPageUrl = 'https://tinyurl.com/4stndypm';

        // Construct the WhatsApp message
        $confirmationMessage = "*📋 Book Raw Materials*\n\n"
            . "Click the link below to provide the details for your raw material booking:\n"
            . "👉 ($bookingPageUrl)\n\n"
            . 'Thank you!';

        $url = 'https://gate.whapi.cloud/messages/text';

        // API request
        $data = [
            'to' => $phoneNumber,
            'body' => $confirmationMessage,
            'no_link_preview' => true,  // set the link no preview mode//
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

        // Execute cURL and handle the response
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode == 200) {
            echo 'Message sent successfully!';
        } else {
            echo "Failed to send message. HTTP Code: $httpCode<br>";
            echo 'Response: ' . htmlspecialchars($response) . '<br>';
            echo 'Payload: ' . json_encode($data, JSON_PRETTY_PRINT) . '<br>';
            echo 'cURL Error: ' . curl_error($ch);
        }

        curl_close($ch);
    } else {
        echo "The message does not exactly contain the word 'book'.";
    }
}
// trade booking finance dept group //
if (sizeof($testArr) > 0) {
    // Removed database insertion code here
    if (strtolower(trim($testArr['body'])) == 'trade') {
        $token = 'Exa88aT4Ztr24sFe5AHfl646ioNKIJOX';
        $phoneNumber = '120363386814639131@g.us';
        $bookingPageUrl = 'https://tinyurl.com/y5w8kz3n';

        // Construct the WhatsApp message
        $confirmationMessage = "*📋 New commodity Trade*\n\n"
            . "Click the link below to provide the details for your raw material booking:\n"
            . "👉 ($bookingPageUrl)\n\n"
            . 'Thank you!';

        $url = 'https://gate.whapi.cloud/messages/text';

        // API request
        $data = [
            'to' => $phoneNumber,
            'body' => $confirmationMessage,
            'no_link_preview' => true,  // set the link no preview mode//
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

        // Execute cURL and handle the response
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode == 200) {
            echo 'Message sent successfully!';
        } else {
            echo "Failed to send message. HTTP Code: $httpCode<br>";
            echo 'Response: ' . htmlspecialchars($response) . '<br>';
            echo 'Payload: ' . json_encode($data, JSON_PRETTY_PRINT) . '<br>';
            echo 'cURL Error: ' . curl_error($ch);
        }

        curl_close($ch);
    } else {
        echo "The message does not exactly contain the word 'book'.";
    }
}
// copper aluminum issues group //
if (sizeof($CoppArr) > 0) {
    // Removed database insertion code here
    if (strtolower(trim($CoppArr['body'])) == 'issue') {
        $token = 'Exa88aT4Ztr24sFe5AHfl646ioNKIJOX';
        $phoneNumber = '120363390447846303@g.us';
        $bookingPageUrl = 'https://tinyurl.com/46wb6wpz';

        // Construct the WhatsApp message
        $confirmationMessage = "*📋 New Copper/Aluminium Issue*\n\n"
            . "Click the link below to provide the details for your Copper/Aluminium Issue:\n"
            . "👉 ($bookingPageUrl)\n\n"
            . 'Thank you!';

        $url = 'https://gate.whapi.cloud/messages/text';

        // API request
        $data = [
            'to' => $phoneNumber,
            'body' => $confirmationMessage,
            'no_link_preview' => true,  // set the link no preview mode//
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

        // Execute cURL and handle the response
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode == 200) {
            echo 'Message sent successfully!';
        } else {
            echo "Failed to send message. HTTP Code: $httpCode<br>";
            echo 'Response: ' . htmlspecialchars($response) . '<br>';
            echo 'Payload: ' . json_encode($data, JSON_PRETTY_PRINT) . '<br>';
            echo 'cURL Error: ' . curl_error($ch);
        }

        curl_close($ch);
    } else {
        echo "The message does not exactly contain the word 'book'.";
    }
}

// echo '<pre>';
// print_r($finalArr);
// echo '</pre>';
// file_put_contents('array.log', json_encode($barodaArr), FILE_APPEND);
// error_log($logMessage.json_encode($finalArr), 3, "array.log");

?>