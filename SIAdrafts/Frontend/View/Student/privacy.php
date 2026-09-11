<?php
require_once __DIR__ . '/../../../Backend/require_student.php';
require_student();
require_once __DIR__ . '/../../../Backend/csrf.php';
$_pageCsrfToken = csrf_token();
$pageTitle = 'My Data & Privacy';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Data &amp; Privacy — EduSchool</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,300;0,400;0,500;1,400;1,500&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/SIAdrafts/Frontend/Css/Student/student.css">
</head>
<body class="student-body" data-csrf="<?= htmlspecialchars($_pageCsrfToken, ENT_QUOTES) ?>">
<div style="max-width:640px;margin:0 auto;padding:40px 20px 80px;">
  <a href="/SIAdrafts/Frontend/View/Student/dashboard.php" style="font-size:13px;color:inherit;opacity:.7;text-decoration:none;">&larr; Back to dashboard</a>
  <h1 style="font-family:'Fraunces',serif;font-weight:500;font-size:28px;margin:16px 0 6px;">My Data &amp; Privacy</h1>
  <p style="opacity:.75;font-size:14.5px;line-height:1.6;margin:0 0 28px;">
    Under the Data Privacy Act (RA 10173), you can request a copy of your data or ask that it be deleted.
    See our <a href="/SIAdrafts/Frontend/View/privacy_policy.php">Privacy Policy</a> for the full details.
  </p>

  <div style="border:1px solid rgba(0,0,0,.1);border-radius:12px;padding:20px;margin-bottom:16px;">
    <h2 style="font-size:16px;margin:0 0 6px;">Download my data</h2>
    <p style="font-size:13.5px;opacity:.75;margin:0 0 12px;">Get a copy of your profile, enrollment records, payment history, and login history as a file.</p>
    <button type="button" id="exportBtn" style="padding:9px 16px;border-radius:8px;border:none;background:#2f5d3f;color:#fff;font-weight:600;cursor:pointer;">Download my data</button>
    <span id="exportMsg" style="font-size:13px;margin-left:10px;"></span>
  </div>

  <div style="border:1px solid rgba(0,0,0,.1);border-radius:12px;padding:20px;">
    <h2 style="font-size:16px;margin:0 0 6px;">Request account deletion</h2>
    <p style="font-size:13.5px;opacity:.75;margin:0 0 12px;">
      This submits a request for the Registrar's Office to review — it does not delete anything automatically,
      since academic and financial records have their own retention requirements. They'll contact you.
    </p>
    <button type="button" id="eraseBtn" style="padding:9px 16px;border-radius:8px;border:1px solid #9a3324;background:transparent;color:#9a3324;font-weight:600;cursor:pointer;">Request account deletion</button>
    <span id="eraseMsg" style="font-size:13px;margin-left:10px;"></span>
  </div>
</div>

<script>
const CSRF_TOKEN = <?= json_encode($_pageCsrfToken) ?>;

document.getElementById('exportBtn').addEventListener('click', async () => {
  const msg = document.getElementById('exportMsg');
  msg.textContent = 'Preparing…';
  try {
    const res = await fetch('/SIAdrafts/Backend/api/Accounts/export_my_data.php', { method: 'POST' });
    const data = await res.json();
    if (!data.success) { msg.textContent = data.error || 'Something went wrong.'; return; }
    const blob = new Blob([JSON.stringify(data.data, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'my-data.json';
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(url);
    msg.textContent = 'Downloaded.';
  } catch (e) {
    msg.textContent = 'Something went wrong.';
  }
});

document.getElementById('eraseBtn').addEventListener('click', async () => {
  const msg = document.getElementById('eraseMsg');
  if (!confirm('Submit a request to delete your account and data? The Registrar\'s Office will review it.')) return;
  msg.textContent = 'Submitting…';
  const body = new URLSearchParams({ csrf_token: CSRF_TOKEN });
  const res = await fetch('/SIAdrafts/Backend/api/Accounts/request_data_erasure.php', { method: 'POST', body });
  const data = await res.json();
  msg.textContent = data.success ? data.message : (data.error || 'Something went wrong.');
});
</script>
</body>
</html>
