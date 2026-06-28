<?php include 'db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>EduSchool</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
<link rel="stylesheet" href="css/login.css">
<link rel="stylesheet" href="css/admission.css">
<script src="https://code.iconify.design/iconify-icon/3.0.0/iconify-icon.min.js"></script>
</head>
<body>
    
<div class="container">
  <div class="row justify-content-center">
    <div class="col-12 col-sm-9 col-md-7 col-lg-5">

      <div class="card login-card p-4 p-sm-5">
        <div class="card-body p-0">

          <div class="login-mark d-flex align-items-center justify-content-center mb-3">
            <iconify-icon icon="mdi:school"></iconify-icon> 
          </div>

          <h1 class="login-title h3 fw-semibold mb-2">Welcome back</h1>
          <p class="text-ink-soft mb-4">Log in to access your dashboard, admission status, and enrollment records.</p>

          <form action="dashboard.php" method="POST" autocomplete="off">

            <div class="mb-3">
              <label for="login-email" class="form-label fw-bold small">Email address</label>
              <div class="input-group">
                <span class="input-group-text">
                  <iconify-icon icon="mdi:email-outline"></iconify-icon>
                </span>
                <input type="email" class="form-control" id="login-email" name="email"
                       placeholder="Professor/staff ID" required>
              </div>
            </div>

            <div class="mb-3">
              <label for="login-password" class="form-label fw-bold small">Password</label>
              <div class="input-group">
                <span class="input-group-text">
                  <iconify-icon icon="mdi:lock-outline"></iconify-icon>
                </span>
                <input type="password" class="form-control" id="login-password" name="password"
                       placeholder="Enter your password" required>
                <button type="button" class="btn btn-icon border" data-target="login-password" aria-label="Show password">
                  <iconify-icon icon="mdi:eye-outline"></iconify-icon>
                </button>
              </div>
            </div>

            <div class="d-flex align-items-center justify-content-between mb-4">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="login-remember" name="remember">
                <label class="form-check-label text-ink-soft small" for="login-remember">
                  Remember me
                </label>
              </div>
              <a href="#" class="link-sage small">Forgot password?</a>
            </div>

            <button type="submit" class="btn btn-login w-100 py-2 d-flex align-items-center justify-content-center gap-2">
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