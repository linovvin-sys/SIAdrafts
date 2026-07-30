<?php

require_once __DIR__ . '/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function send_email(string $to, string $subject, string $bodyHtml): bool
{
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = config('SMTP_HOST');
        $mail->SMTPAuth   = true;
        $mail->Username   = config('SMTP_USERNAME');
        $mail->Password   = config('SMTP_PASSWORD');
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int)config('SMTP_PORT');

        $mail->setFrom(config('SMTP_FROM_EMAIL'), config('SMTP_FROM_NAME'));
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $bodyHtml;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('send_email failed: ' . $mail->ErrorInfo);
        return false;
    }
}
