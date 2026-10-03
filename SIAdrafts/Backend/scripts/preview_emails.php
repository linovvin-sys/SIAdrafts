<?php
// CLI only. Renders the three branded emails with sample data so the design
// can be checked without filing an admission.
//   php Backend/scripts/preview_emails.php [outdir]            -> writes HTML files
//   php Backend/scripts/preview_emails.php --send you@mail.com -> also emails all three
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

require_once __DIR__ . '/../email_template.php';

$args  = array_slice($argv, 1);
$send  = null;
if (($i = array_search('--send', $args, true)) !== false) {
    $send = $args[$i + 1] ?? null;
    array_splice($args, $i, 2);
}
$outDir = $args[0] ?? (__DIR__ . '/email-previews');
@mkdir($outDir, 0777, true);

$school = 'Sample School';
$emails = [
    'admission-slip' => ['Admission Slip (preview)', email_admission_slip($school, [
        'first_name' => 'Ana', 'program' => 'BS Information Technology', 'year_level' => '1st Year',
        'school_year' => '2026-2027', 'semester' => '1',
    ], 'Ana Maria Cruz', 'ADM-2026-0001')],
    'portal-welcome' => ['Student Portal Welcome (preview)', email_portal_welcome($school, 'Ana', [
        'student_no' => '2026-00123', 'temp_password' => 'Xk7p-Q2mR',
    ])],
    'password-reset' => ['Password Reset (preview)', email_password_reset($school, 'Ana', '2026-00123', 'Xk7p-Q2mR')],
];

foreach ($emails as $name => [$subject, $html]) {
    file_put_contents("$outDir/$name.html", $html);
    echo "wrote $outDir/$name.html\n";
    if ($send) {
        require_once __DIR__ . '/../mailer.php';
        echo '  email to ' . $send . ': ' . (send_email($send, $subject, $html) ? "SENT\n" : "FAILED\n");
    }
}
