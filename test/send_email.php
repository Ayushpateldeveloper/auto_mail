<?php
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->Port       = 465;
    $mail->SMTPAuth   = true;
    $mail->Username   = 'alert@seplcables.com';
    $mail->Password   = 'Sabudana@123';
    $mail->SMTPSecure = 'ssl';

    // Recipients
    $mail->setFrom('alert@seplcables.com', 'PO');
    $mail->addAddress('ayushsuyog@gmail.com');  // Primary recipient
    // $mail->addCC('it1@seplcables.com');       // CC recipient

    // Content settings
    $mail->isHTML(true);
    $mail->Subject = 'Manufacturing Clearance Pending';
    $mail->Body    = 'Note: PO Acceptance was given 7 days, however manufacturing clearance is still pending.';

    // Optional: add an attachment if needed
    // $mail->addAttachment($file_name);

    $mail->send();
    echo 'Message has been sent';
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}
