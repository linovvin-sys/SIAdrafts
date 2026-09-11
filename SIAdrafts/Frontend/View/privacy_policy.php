<?php
$themeCssPath = __DIR__ . '/../Css/editorial-theme.css';
$themeCssVer  = file_exists($themeCssPath) ? filemtime($themeCssPath) : time();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Privacy Policy &amp; Terms — EduSchool</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,300;0,400;0,500;1,400;1,500&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/SIAdrafts/Frontend/Css/editorial-theme.css?v=<?= (int)$themeCssVer ?>">
<style>
  .policy-wrap { max-width: 680px; margin: 0 auto; padding: 56px 24px 90px; }
  .policy-wrap h1 {
    font-family: 'Fraunces', serif;
    font-weight: 500;
    font-size: clamp(28px, 4vw, 38px);
    margin: 0 0 6px;
  }
  .policy-meta { color: var(--ink-soft); font-size: 14px; margin: 0 0 40px; }
  .policy-wrap h2 {
    font-family: 'Fraunces', serif;
    font-weight: 500;
    font-size: 21px;
    margin: 40px 0 12px;
    color: var(--ink);
  }
  .policy-wrap p, .policy-wrap li { line-height: 1.65; color: var(--ink); font-size: 15.5px; }
  .policy-wrap ul { padding-left: 20px; }
  .policy-wrap li { margin-bottom: 8px; }
  .notice-box {
    background: rgba(4, 106, 56, 0.06);
    border: 1px solid rgba(4, 106, 56, 0.22);
    border-radius: 10px;
    padding: 16px 18px;
    font-size: 14px;
    margin: 0 0 36px;
    color: var(--ink);
  }
  .policy-wrap table { width: 100%; border-collapse: collapse; margin: 14px 0 20px; font-size: 14.5px; }
  .policy-wrap th, .policy-wrap td { text-align: left; padding: 8px 10px; border-bottom: 1px solid rgba(31,46,40,0.12); vertical-align: top; }
  .policy-wrap th { font-weight: 600; }
  a.back { color: var(--ink-soft); font-size: 13px; text-decoration: none; }
  a.back:hover { color: var(--accent); }
</style>
</head>
<body>
<div class="policy-wrap">
  <a class="back" href="/SIAdrafts/Frontend/View/index.php">&larr; Back to EduSchool</a>
  <h1>Privacy Policy &amp; Terms of Use</h1>
  <p class="policy-meta">Last updated: <?= date('F j, Y') ?></p>

  <div class="notice-box">
    <strong>Draft placeholder — not yet legally reviewed.</strong> This page states, in plain terms, what data this
    system collects and why, as a starting point. It has not been reviewed by counsel and the retention periods and
    contact details below are placeholders. Before this is relied on for actual Data Privacy Act (RA 10173)
    compliance, the institution's own Data Protection Officer should review and finalize it.
  </div>

  <h2>What we collect</h2>
  <p>To process admissions, enrollment, and billing, this system collects and stores:</p>
  <table>
    <tr><th>Category</th><th>Examples</th></tr>
    <tr><td>Identity &amp; contact information</td><td>Full name, date of birth, address, phone number, email</td></tr>
    <tr><td>Admission documents</td><td>Report cards, birth certificate, ID photos, and other requirements you upload</td></tr>
    <tr><td>Academic records</td><td>Enrolled subjects, sections, schedules, and enrollment status per term</td></tr>
    <tr><td>Payment records</td><td>Tuition and fee amounts, payment status, and transaction references. Card and
        e-wallet details themselves are handled directly by our payment processor (PayMongo) and are never stored
        on this system.</td></tr>
    <tr><td>Account &amp; security data</td><td>Username, encrypted password, login timestamps, and IP address at
        login — kept for account security, not for tracking your activity elsewhere</td></tr>
  </table>

  <h2>Why we collect it</h2>
  <ul>
    <li>To process your application for admission and enrollment.</li>
    <li>To generate and track billing, and to reconcile online payments.</li>
    <li>To maintain accurate academic and enrollment records required by the institution.</li>
    <li>To secure your account — for example, detecting repeated failed login attempts.</li>
    <li>To communicate with you about your application, enrollment, or account (e.g. announcements, password
        resets).</li>
  </ul>

  <h2>Who we share it with</h2>
  <p>
    We do not sell or rent personal data. Information is shared only where necessary to operate the system:
  </p>
  <ul>
    <li><strong>PayMongo</strong> (our payment processor) receives what's needed to process an online payment.</li>
    <li>Relevant school personnel (Registrar, Treasury, Admission, Admin, and your professors) can access the
        portions of your record relevant to their role.</li>
    <li>We do not share personal data with any other third party except where required by law.</li>
  </ul>

  <h2>How long we keep it</h2>
  <p>
    <em>Placeholder — to be confirmed by the institution:</em> academic and financial records are typically kept for
    the duration of enrollment plus a retention period required for institutional recordkeeping. Login/security
    logs are kept for a shorter period focused on incident investigation, not indefinite storage.
  </p>

  <h2>Your rights under the Data Privacy Act (RA 10173)</h2>
  <p>As a data subject, you have the right to:</p>
  <ul>
    <li>Be informed that your personal data is being processed (this page).</li>
    <li>Access your own data held by the institution.</li>
    <li>Correct inaccurate or outdated information.</li>
    <li>Object to certain processing, and request erasure or blocking of data no longer needed for the purposes
        above, subject to the institution's legitimate recordkeeping obligations.</li>
    <li>Be indemnified for damages from unauthorized use of your data.</li>
    <li>File a complaint with the National Privacy Commission.</li>
  </ul>
  <p>
    Students can exercise the access and erasure rights directly, self-service, from the
    <a href="/SIAdrafts/Frontend/View/Student/privacy.php">My Data &amp; Privacy</a> page in the student portal —
    no need to email or call in for those two specifically. For anything else, contact the institution using the
    details below.
  </p>

  <h2>Security</h2>
  <p>
    Passwords are stored using one-way cryptographic hashing, never in plain text. Accounts are protected by
    session security controls, login rate limiting, and optional two-factor authentication for staff. Our
    security practices are reviewed on an ongoing basis; no system can guarantee absolute security, but we take
    reasonable, documented steps to protect your data.
  </p>

  <h2>Terms of use</h2>
  <ul>
    <li>You are responsible for keeping your account credentials confidential and for all activity under your
        account.</li>
    <li>You agree to provide accurate information during admission and enrollment.</li>
    <li>Misuse of this system — including attempting to access another user's records, or submitting fraudulent
        documents — may result in account suspension and referral to the institution for disciplinary action.</li>
    <li>This system is provided "as is" for the institution's administrative use; features and availability may
        change.</li>
  </ul>

  <h2>Contact</h2>
  <p>
    <em>Placeholder — to be filled in by the institution:</em> questions about this policy, or requests to
    exercise your data privacy rights, can be directed to the school's designated Data Protection Officer at
    <strong>[DPO contact email/phone to be added]</strong>.
  </p>
</div>
</body>
</html>
