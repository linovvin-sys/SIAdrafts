<?php
require_once __DIR__ . '/../../Backend/db.php';
$db   = new Database();
$conn = $db->connect();
$courses = [];
$res = $conn->query("SELECT course_id, course_code, course_name, total_units FROM course WHERE status = 'Approved' ORDER BY course_name ASC");
if ($res) $courses = $res->fetch_all(MYSQLI_ASSOC);
$db->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>EduSchool — Admissions</title>
<script src="https://code.iconify.design/iconify-icon/3.0.0/iconify-icon.min.js"></script>
<style>
  :root{
    --ink:#1B2A4A;
    --paper:#FAF7F0;
    --amber:#E8A33D;
    --sage:#7C9885;
    --white:#FFFFFF;
    --ink-soft: rgba(27,42,74,0.65);
    --ink-line: rgba(27,42,74,0.12);
    --nav-h: 76px;
  }

  *{ box-sizing:border-box; }
  html,body{ margin:0; scroll-behavior:smooth; }

  body{
    background:
      radial-gradient(1200px 500px at 50% 0%, rgba(232,163,61,0.10), transparent 60%),
      var(--paper);
    font-family: -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    color: var(--ink);
  }

  /* ---------- nav ---------- */
  .nav-wrap{
    position: fixed;
    top: 18px; left: 0; right: 0;
    z-index: 1000;
    display: flex;
    justify-content: center;
    padding: 0 20px;
  }

  .navbar{
    width: 100%;
    max-width: 980px;
    height: var(--nav-h);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 0 14px 0 10px;
    border-radius: 999px;
    background: rgba(255,255,255,0.72);
    border: 1px solid rgba(27,42,74,0.08);
    box-shadow: 0 1px 1px rgba(27,42,74,0.03), 0 12px 30px -14px rgba(27,42,74,0.22);
    backdrop-filter: blur(18px) saturate(140%);
    -webkit-backdrop-filter: blur(18px) saturate(140%);
  }

  .brand{
    display:flex; align-items:center; gap:11px;
    text-decoration:none; color: var(--ink);
    padding: 6px 10px 6px 6px;
  }
  .brand-mark{
    width: 38px; height: 38px; flex: none;
    border-radius: 11px;
    background: linear-gradient(155deg, var(--ink) 0%, #2c3e63 100%);
    display:flex; align-items:center; justify-content:center;
    position: relative;
    box-shadow: 0 4px 10px -4px rgba(27,42,74,0.5);
  }
  .brand-mark::after{
    content:""; position:absolute;
    width: 7px; height:7px; border-radius: 50%;
    background: var(--amber);
    top: 6px; right: 6px;
  }
  .brand-mark iconify-icon{ color:#FAF7F0; font-size:19px; }
  .brand-name{ font-weight:700; font-size:17px; letter-spacing:-0.01em; }
  .brand-name em{ color: var(--sage); font-style:normal; }

  .nav-links{
    display:flex; align-items:center; gap: 6px;
    list-style:none; margin:0; padding:0;
  }
  .nav-links a{
    text-decoration:none;
    color: var(--ink-soft);
    font-size: 14px;
    font-weight: 600;
    padding: 10px 16px;
    border-radius: 999px;
    transition: background .2s ease, color .2s ease;
  }
  .nav-links a:hover{ background: rgba(27,42,74,0.06); color: var(--ink); }

  .btn{
    display:inline-flex; align-items:center; gap:8px;
    text-decoration:none;
    font-weight: 700;
    font-size: 14px;
    padding: 11px 20px;
    border-radius: 999px;
    border: 1px solid transparent;
    cursor:pointer;
    transition: transform .15s ease, box-shadow .15s ease, background .2s ease;
  }
  .btn-login{
    color: var(--ink);
    border-color: var(--ink-line);
    background: transparent;
  }
  .btn-login:hover{ background: rgba(27,42,74,0.06); }
  .btn-apply{
    color: var(--ink);
    background: var(--amber);
    box-shadow: 0 8px 20px -8px rgba(232,163,61,0.7);
  }
  .btn-apply:hover{ transform: translateY(-1px); box-shadow: 0 12px 24px -8px rgba(232,163,61,0.8); }

  /* ---------- hero ---------- */
  .hero{
    min-height: 100vh;
    display:flex; flex-direction:column; align-items:center; justify-content:center;
    text-align:center;
    padding: calc(var(--nav-h) + 60px) 24px 80px;
  }

  .eyebrow{
    display:inline-flex; align-items:center; gap:8px;
    font-size: 13px; font-weight:700;
    letter-spacing: 0.04em; text-transform: uppercase;
    color: var(--sage);
    background: rgba(124,152,133,0.12);
    padding: 8px 16px;
    border-radius: 999px;
    margin-bottom: 26px;
  }

  h1{
    font-size: clamp(2.4rem, 5vw, 4.2rem);
    line-height: 1.05;
    letter-spacing: -0.02em;
    margin: 0 0 20px;
    max-width: 15ch;
  }
  h1 .accent{ color: var(--amber); }

  .lede{
    font-size: 18px;
    color: var(--ink-soft);
    max-width: 46ch;
    line-height: 1.6;
    margin: 0 0 40px;
  }

  .hero-actions{
    display:flex; gap: 14px; flex-wrap:wrap; justify-content:center;
    margin-bottom: 56px;
  }
  .btn-lg{ padding: 15px 28px; font-size: 15px; }

  .ref-note{
    font-size: 13.5px;
    color: var(--ink-soft);
    display:flex; align-items:center; gap:8px;
  }
  .ref-note code{
    background: var(--white);
    border: 1px solid var(--ink-line);
    padding: 2px 8px;
    border-radius: 6px;
    font-size: 12.5px;
  }

  /* ---------- steps ---------- */
  .steps{
    max-width: 980px;
    margin: 0 auto;
    padding: 40px 24px 100px;
  }
  .steps-head{
    text-align:center;
    margin-bottom: 48px;
  }
  .steps-head h2{
    font-size: clamp(1.6rem, 3vw, 2.2rem);
    margin: 0 0 12px;
    letter-spacing: -0.01em;
  }
  .steps-head p{ color: var(--ink-soft); margin:0; }

  .step-grid{
    display:grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 18px;
  }
  .step{
    background: var(--white);
    border: 1px solid var(--ink-line);
    border-radius: 18px;
    padding: 26px 22px;
    position: relative;
  }
  .step-num{
    font-size: 13px;
    font-weight: 800;
    color: var(--amber);
    margin-bottom: 14px;
  }
  .step h3{
    font-size: 16px;
    margin: 0 0 8px;
  }
  .step p{
    font-size: 13.5px;
    color: var(--ink-soft);
    line-height: 1.55;
    margin: 0;
  }

  /* ---------- programs ---------- */
  .programs{
    background: var(--ink);
    color: var(--paper);
    padding: 80px 24px;
  }
  .programs-inner{ max-width: 980px; margin:0 auto; }
  .programs-head{
    display:flex; align-items:flex-end; justify-content:space-between;
    gap: 20px; flex-wrap: wrap;
    margin-bottom: 36px;
  }
  .programs-head h2{
    font-size: clamp(1.6rem, 3vw, 2.2rem);
    margin: 0;
    letter-spacing: -0.01em;
  }
  .programs-head p{
    color: rgba(250,247,240,0.6);
    margin: 8px 0 0;
    max-width: 40ch;
  }
  .program-list{
    display:grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 14px;
  }
  .program-card{
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 16px;
    padding: 20px;
  }
  .program-card iconify-icon{ font-size:22px; color: var(--amber); margin-bottom:14px; display:block; }
  .program-card h4{ margin: 0 0 6px; font-size: 15px; }
  .program-card p{ margin:0; font-size: 13px; color: rgba(250,247,240,0.55); }

  /* ---------- footer ---------- */
  footer{
    padding: 40px 24px;
    text-align:center;
    font-size: 13px;
    color: var(--ink-soft);
  }

  @media (max-width: 760px){
    .nav-links{ display:none; }
    .step-grid{ grid-template-columns: 1fr 1fr; }
    .program-list{ grid-template-columns: 1fr; }
  }
</style>
</head>
<body>

  <div class="nav-wrap">
    <nav class="navbar">
      <a class="brand" href="#top">
        <span class="brand-mark"><iconify-icon icon="mdi:school"></iconify-icon></span>
        <span class="brand-name">Edu<em>School</em></span>
      </a>

      <ul class="nav-links">
        <li><a href="#steps">How it works</a></li>
        <li><a href="#programs">Programs</a></li>
      </ul>

      <div style="display:flex; align-items:center; gap:10px;">
        <a href="login.php" class="btn btn-login">Log in</a>
        <a href="/SIAdrafts/Frontend/View/Admission/online_admission.php" class="btn btn-apply">Apply Now</a>
      </div>
    </nav>
  </div>

  <main id="top" class="hero">
    <span class="eyebrow"><iconify-icon icon="mdi:calendar-check-outline"></iconify-icon> Admissions open for SY 2026–2027</span>
    <h1>Start your application <span class="accent">today.</span></h1>
    <p class="lede">Fill out the online form in about 10 minutes. We'll give you a reference number — bring it, along with your documents, when you visit us to finish enrolling.</p>

    <div class="hero-actions">
      <a href="/SIAdrafts/Frontend/View/Admission/online_admission.php" class="btn btn-apply btn-lg">
        <iconify-icon icon="mdi:file-document-edit-outline"></iconify-icon> Apply Now
      </a>
    </div>

    <div class="ref-note">
      Already applied? Your reference number looks like <code>REF-00000-001</code> — keep it for your campus visit.
    </div>
  </main>

  <section id="steps" class="steps">
    <div class="steps-head">
      <h2>How enrollment works</h2>
      <p>Four steps, two of them online.</p>
    </div>
    <div class="step-grid">
      <div class="step">
        <div class="step-num">01 · Online</div>
        <h3>Apply</h3>
        <p>Submit your personal, guardian, and academic history details. Get a reference number instantly.</p>
      </div>
      <div class="step">
        <div class="step-num">02 · On campus</div>
        <h3>Verify documents</h3>
        <p>Bring Form 137/SHS card, Certificate of Good Moral, PSA birth certificate, and 2x2 photos.</p>
      </div>
      <div class="step">
        <div class="step-num">03 · On campus</div>
        <h3>Enlist subjects</h3>
        <p>Staff will confirm your section and subject load for the term.</p>
      </div>
      <div class="step">
        <div class="step-num">04 · Treasury</div>
        <h3>Pay & confirm</h3>
        <p>Settle your down payment to officially lock in your enrollment.</p>
      </div>
    </div>
  </section>

  <section id="programs" class="programs">
    <div class="programs-inner">
      <div class="programs-head">
        <div>
          <h2>Programs open for enrollment</h2>
          <p>Pulled from the current course offering — swap in the real list from the <code style="opacity:.8">course</code> table.</p>
        </div>
      </div>
      <div class="program-list">
        <?php if (empty($courses)): ?>
          <p style="color:rgba(250,247,240,0.6);">No programs currently open for enrollment.</p>
        <?php else: ?>
          <?php foreach ($courses as $c): ?>
            <div class="program-card">
              <iconify-icon icon="mdi:school-outline"></iconify-icon>
              <h4><?= htmlspecialchars($c['course_name']) ?></h4>
              <p><?= (int)$c['total_units'] ?> total units</p>
              <button type="button" class="btn btn-apply" style="margin-top:12px;" onclick="openApplyModal(<?= (int)$c['course_id'] ?>, '<?= htmlspecialchars(addslashes($c['course_name'])) ?>')">
                Apply Now
              </button>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <footer>
    © 2026 EduSchool. This is a preview mockup — hook up real links before shipping.
  </footer>

  <div id="applyModal" style="display:none; position:fixed; inset:0; background:rgba(27,42,74,0.5); z-index:2000; align-items:center; justify-content:center;">
    <div style="background:var(--white); border-radius:18px; padding:28px; max-width:420px; width:90%;">
      <h3 id="applyModalTitle" style="margin:0 0 10px;">Apply for this program?</h3>
      <p style="color:var(--ink-soft); margin:0 0 20px;">You'll be taken to the application form with this program pre-selected.</p>
      <div style="display:flex; gap:10px; justify-content:flex-end;">
        <button type="button" class="btn btn-login" onclick="closeApplyModal()">Cancel</button>
        <a id="applyModalConfirm" href="#" class="btn btn-apply">Continue</a>
      </div>
    </div>
  </div>
  <script>
    function openApplyModal(courseId, courseName) {
      document.getElementById('applyModalTitle').textContent = 'Apply for ' + courseName + '?';
      document.getElementById('applyModalConfirm').href = '/SIAdrafts/Frontend/View/Admission/online_admission.php?course_id=' + courseId;
      document.getElementById('applyModal').style.display = 'flex';
    }
    function closeApplyModal() {
      document.getElementById('applyModal').style.display = 'none';
    }
  </script>

</body>
</html>