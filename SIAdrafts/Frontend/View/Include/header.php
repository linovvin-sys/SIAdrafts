<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$extraCss = $extraCss ?? [];
require_once __DIR__ . '/../../../Backend/csrf.php';
require_once __DIR__ . '/../../../Backend/cdn_assets.php';
require_once __DIR__ . '/../../../Backend/css_bundle_config.php';
require_once __DIR__ . '/../../../Backend/asset_url.php';
$_pageCsrfToken = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>EduSchool — <?= htmlspecialchars($pageTitle ?? '', ENT_QUOTES) ?></title>
  <script>
    // Applies the saved theme before first paint, so there's no flash of
    // light mode before dark mode kicks in. Must run synchronously, here
    // in <head>, before any CSS that depends on [data-theme] is used.
    (function () {
      try {
        var saved = localStorage.getItem('sia_theme');
        if (saved === 'dark' || saved === 'light') {
          document.documentElement.setAttribute('data-theme', saved);
        }
      } catch (e) {}
    })();
  </script>
  <script>
    // Per-tab session ownership check. The cookie session is shared by
    // every tab in the browser, but sessionStorage never travels with a
    // pasted/duplicated URL — only the tab that actually logged in has
    // the matching token in its sessionStorage. Any other tab riding on
    // the same cookie gets bounced back to login instead of silently
    // inheriting whoever's account is currently active.
    (function () {
      try {
        var serverToken = <?= json_encode($_SESSION['tab_token'] ?? null) ?>;
        var localToken = sessionStorage.getItem('sia_tab_token');
        if (serverToken && localToken !== serverToken) {
          window.location.href = '/SIAdrafts/Backend/api/Auth/logout.php?reason=tab';
        }
      } catch (e) {}
    })();
  </script>
  <link rel="stylesheet" href="/SIAdrafts/bootstrap-5.3.8-dist/css/bootstrap.min.css" />
  <?= cdn_style_tag(CDN_BOOTSTRAP_ICONS_CSS) ?>
  <?= cdn_style_tag(CDN_DATATABLES_BS5_CSS) ?>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,300;0,400;0,500;1,400;1,500&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
  <!-- admin.css + schedule.css + modal.css + registrar.css + required.css +
       dotty-staff.css + tokens.css, bundled into one response instead of 6
       separate round-trips per page load (see Backend/css_bundle.php). -->
  <link rel="stylesheet" href="<?= htmlspecialchars(css_bundle_url('admin'), ENT_QUOTES) ?>" />
  <?php foreach ($extraCss as $href): ?>
    <link rel="stylesheet" href="<?= htmlspecialchars(asset_url($href), ENT_QUOTES) ?>" />
  <?php endforeach; ?>
  <?= cdn_script_tag(CDN_SWEETALERT2, true) ?>
  <?= cdn_script_tag(CDN_CHARTJS, true) ?>
  <?= cdn_script_tag(CDN_ICONIFY, true) ?>
</head>
<body data-csrf="<?= htmlspecialchars($_pageCsrfToken, ENT_QUOTES) ?>" data-readonly="<?= (($_SESSION['role_name'] ?? '') === 'Admin') ? '1' : '0' ?>">

<?php if (!empty($_SESSION['user_id'])): ?>
<!-- Internal Dotty — role-scoped chat, see Backend/StaffChat/snapshot.php.
     Same mascot/markup/IDs as the public FAQ widget (Frontend/View/index.php)
     so Frontend/Js/Admin/dotty-staff.js can be a near-direct port of its script. -->
<div class="e-chat-scrim" id="chatScrim" aria-hidden="true"></div>

<button type="button" class="e-chat-toggle" id="chatToggle" aria-expanded="false" aria-controls="chatPanel" title="Ask Dotty">
  <span class="e-chat-toggle-face" aria-hidden="true">
    <span class="e-chat-socket"><span class="e-chat-eye"></span></span>
    <span class="e-chat-socket"><span class="e-chat-eye"></span></span>
  </span>
  <svg class="e-chat-close-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
</button>

<div class="e-chat-panel" id="chatPanel" role="dialog" aria-label="Internal Dotty chat">
  <div class="e-chat-head">
    <div class="e-chat-avatar" id="chatAvatar" aria-hidden="true">
      <span class="e-chat-socket"><span class="e-chat-eye"></span></span>
      <span class="e-chat-socket"><span class="e-chat-eye"></span></span>
    </div>
    <span class="e-chat-name">Dotty</span>
  </div>
  <div class="e-chat-log" id="chatLog"></div>
  <form class="e-chat-form" id="chatForm">
    <input type="text" class="e-chat-input" id="chatInput" placeholder="Ask about your figures…" maxlength="500" autocomplete="off">
    <button type="submit" class="e-chat-send" id="chatSend">Send</button>
  </form>
</div>
<?php endif; ?>
