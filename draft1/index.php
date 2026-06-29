<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
$page_scripts = ['js/password-toggle.js', 'js/login-submit.js'];
?>
<?php include 'header.php' ?>
<div class="container" style="padding-top:calc(var(--nav-h) + 56px); padding-bottom:60px;">
  <div class="row justify-content-center">
    <div class="col-12 col-sm-9 col-md-7 col-lg-5">

      <div class="card login-card p-4 p-sm-5">
        <div class="card-body p-0">

          <div class="login-mark d-flex align-items-center justify-content-center mb-3">
            <iconify-icon icon="mdi:school"></iconify-icon>
          </div>

          <h1 class="login-title h3 fw-semibold mb-2">Welcome back</h1>
          <p class="text-ink-soft mb-4">Log in to access the enrollment system.</p>

          <div id="login-error" class="alert-box alert-error mb-3" hidden></div>

          <form id="login-form" action="api/login.php" method="POST" autocomplete="off" novalidate>

            <div class="mb-3">
              <label for="login-username" class="form-label fw-bold small">Username / Email</label>
              <div class="input-group">
                <span class="input-group-text">
                  <iconify-icon icon="mdi:account-outline"></iconify-icon>
                </span>
                <input type="text" class="form-control" id="login-username" name="username"
                       placeholder="Enter your username or email" required autocomplete="username">
              </div>
            </div>

            <div class="mb-3">
              <label for="login-password" class="form-label fw-bold small">Password</label>
              <div class="input-group">
                <span class="input-group-text">
                  <iconify-icon icon="mdi:lock-outline"></iconify-icon>
                </span>
                <input type="password" class="form-control" id="login-password" name="password"
                       placeholder="Enter your password" required autocomplete="current-password">
                <button type="button" class="btn btn-icon border" data-target="login-password" aria-label="Show password">
                  <iconify-icon icon="mdi:eye-outline"></iconify-icon>
                </button>
              </div>
            </div>

            <div class="mb-4">
              <a href="#" class="link-sage small">Forgot password?</a>
            </div>

            <button type="submit" id="login-btn" class="btn btn-login w-100 py-2 d-flex align-items-center justify-content-center gap-2">
              Log in
              <iconify-icon icon="mdi:arrow-right"></iconify-icon>
            </button>

          </form>

        </div>
      </div>

    </div>
  </div>
</div>
<?php include 'footer.php' ?>