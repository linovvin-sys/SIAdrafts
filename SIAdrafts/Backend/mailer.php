<?php

require_once __DIR__ . '/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Sends via Brevo's HTTPS REST API when BREVO_API_KEY is configured,
 * falling back to PHPMailer/SMTP otherwise.
 *
 * The SMTP path is what this always used -- but confirmed live on
 * Railway (direct fsockopen test to smtp-relay.brevo.com on both 587 and
 * 465) that outbound SMTP ports are blocked entirely at the network
 * level: the connection just times out, never even reaching a TCP
 * handshake. That's a standard anti-abuse policy on most PaaS platforms,
 * not something fixable from inside the app. PHPMailer's own default
 * per-step timeout is 300 seconds, so every email send during a real
 * request (admission confirmation, enrollment credentials, password
 * resets) was hanging the whole request for minutes before finally
 * giving up -- indistinguishable from the app itself being stuck.
 *
 * Brevo's REST API (https://api.brevo.com/v3/smtp/email) sends the exact
 * same transactional email over plain HTTPS instead, which is already
 * confirmed working (port 443 connects instantly). Needs a separate
 * credential from the SMTP username/password -- a v3 API key from
 * Brevo's dashboard under SMTP & API > API Keys -- set as
 * BREVO_API_KEY in .env. Until that's set, this transparently falls
 * back to the original SMTP path, so local dev (where SMTP ports
 * typically aren't blocked) keeps working unchanged.
 */
function send_email(string $to, string $subject, string $bodyHtml): bool
{
    $apiKey = config('BREVO_API_KEY');
    if ($apiKey) {
        return send_email_via_brevo_api($apiKey, $to, $subject, $bodyHtml);
    }
    return send_email_via_smtp($to, $subject, $bodyHtml);
}

function send_email_via_brevo_api(string $apiKey, string $to, string $subject, string $bodyHtml): bool
{
    $payload = [
        'sender'      => [
            'name'  => config('SMTP_FROM_NAME') ?? 'EduSchool',
            'email' => config('SMTP_FROM_EMAIL'),
        ],
        'to'          => [['email' => $to]],
        'subject'     => $subject,
        'htmlContent' => $bodyHtml,
    ];

    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            'accept: application/json',
            'api-key: ' . $apiKey,
            'content-type: application/json',
        ],
        // Generous but finite -- this is on the request path for admission
        // submission, enrollment, and password resets; a real network
        // failure should surface in seconds, not hang the request the way
        // the old SMTP path's 300s-per-step default did.
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_CONNECTTIMEOUT => 10,
    ]);
    $raw    = curl_exec($ch);
    $errNo  = curl_errno($ch);
    $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    if ($errNo || $raw === false) {
        error_log('send_email (Brevo API): network error, curl errno ' . $errNo);
        return false;
    }
    if ($status < 200 || $status >= 300) {
        error_log('send_email (Brevo API): HTTP ' . $status . ' — ' . $raw);
        return false;
    }
    return true;
}

function send_email_via_smtp(string $to, string $subject, string $bodyHtml): bool
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
        // PHPMailer's own default is 300s per SMTP step -- on a host that
        // blocks the port outright (confirmed on Railway), the connection
        // attempt itself eats this whole budget before failing. Capped
        // short so a blocked/unreachable relay fails fast instead of
        // hanging the request it was called from.
        $mail->Timeout    = 15;

        $mail->setFrom(config('SMTP_FROM_EMAIL'), config('SMTP_FROM_NAME'));
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $bodyHtml;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('send_email (SMTP) failed: ' . $mail->ErrorInfo);
        return false;
    }
}
