<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>EduSchool</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="login.css">
<script src="https://code.iconify.design/iconify-icon/3.0.0/iconify-icon.min.js"></script>
</head>
<body>

  <div class="nav-wrap" id="navWrap">
    <nav class="navbar">
      <a class="brand" href="#">
        <span class="brand-mark">
          <iconify-icon icon="mdi:school" style="color:#FAF7F0; font-size:19px;"></iconify-icon>
        </span>
        <span class="brand-name">Edu<em>School</em></span>
      </a>

      <ul class="nav-links">
        <li><a href="#" class="active">Dashboard</a></li>
        <li><a href="#">Admission</a></li>
        <li><a href="#">Enrollment</a></li>
      </ul>

      <div class="nav-cta">
        <a href="#" class="btn-enroll btn-enroll-desktop">
          Enroll now
          <iconify-icon icon="mdi:arrow-right" style="font-size:15px;"></iconify-icon>
        </a>
        <button class="nav-toggle" id="navToggle" aria-label="Toggle menu" aria-expanded="false">
          <span></span>
        </button>
      </div>
    </nav>
  </div>

  <div class="mobile-panel" id="mobilePanel">
    <a href="#" class="active">Home</a>
    <a href="#">About</a>
    <a href="#">Contact</a>
    <a href="#" class="btn-enroll">
      Enroll now
      <iconify-icon icon="mdi:arrow-right" style="font-size:15px;"></iconify-icon>
    </a>
  </div>
