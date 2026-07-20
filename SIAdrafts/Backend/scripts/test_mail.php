<?php
require_once __DIR__ . '/../mailer.php';

$ok = send_email('test@example.com', 'Foundation phase test email', '<p>If you can read this in Mailtrap, send_email() works.</p>');
echo $ok ? "SENT\n" : "FAILED — check error_log\n";
