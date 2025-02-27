<?php
require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/dbcon.php';

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

// Function to send email using basic SMTP authentication with PHPMailer
function sendEmail($recipient, $subject, $body)
{
    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'alert@seplcables.com';
        $mail->Password = 'Sabudana@123';
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;

        // Recipients
        $mail->setFrom('alert@seplcables.com', 'PO');
        $mail->addAddress($recipient);

        // Email content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;

        $mail->send();
        return 'Message sent to ' . htmlspecialchars($recipient);
    } catch (Exception $e) {
        return 'Mailer Error: ' . $mail->ErrorInfo;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Expected parameters: 'recipient' (manually entered email) and 'emails' (array of selected email IDs)
    $recipient = isset($_POST['recipient']) ? trim($_POST['recipient']) : '';
    $emails = isset($_POST['emails']) ? $_POST['emails'] : [];

    if (empty($recipient) || empty($emails)) {
        echo 'Recipient email and selected emails are required.';
        exit;
    }

    $responseMessages = [];
    // For each selected email ID, fetch email details from your database or service.
    // For now, using placeholders for subject and body.
    foreach ($emails as $emailId) {
        // Replace these with actual database lookups if needed.
        $subject = 'Forwarded Email - Fetched Subject for Email ID: ' . $emailId;
        $body = 'Forwarded Email Body for Email ID: ' . $emailId;
        $responseMessages[] = sendEmail($recipient, $subject, $body);
    }
    echo implode("\n", $responseMessages);
}
?>
