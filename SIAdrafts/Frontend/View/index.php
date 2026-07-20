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
<link rel="stylesheet" href="https://unpkg.com/aos@2.3.4/dist/aos.css">
<link rel="stylesheet" href="/SIAdrafts/Frontend/Css/glass-theme.css">
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
    transition: transform .35s cubic-bezier(.34,1.56,.64,1), box-shadow .3s ease;
  }
  .brand:hover .brand-mark{
    transform: rotate(-8deg) scale(1.08);
    box-shadow: 0 6px 16px -4px rgba(27,42,74,0.6);
  }
  .brand-mark::after{
    content:""; position:absolute;
    width: 7px; height:7px; border-radius: 50%;
    background: var(--amber);
    top: 6px; right: 6px;
    transition: transform .3s ease;
  }
  .brand:hover .brand-mark::after{ transform: scale(1.3); }
  .brand-mark iconify-icon{ color:#FAF7F0; font-size:19px; }
  .brand-name{ font-weight:700; font-size:17px; letter-spacing:-0.01em; }
  .brand-name em{ color: var(--sage); font-style:normal; }

  .nav-links{
    display:flex; align-items:center; gap: 6px;
    list-style:none; margin:0; padding:0;
  }
  .nav-links a{
    position: relative;
    text-decoration:none;
    color: var(--ink-soft);
    font-size: 14px;
    font-weight: 600;
    padding: 10px 16px;
    border-radius: 999px;
    transition: background .2s ease, color .2s ease;
  }
  .nav-links a::after{
    content:"";
    position:absolute;
    left: 16px; right: 16px; bottom: 6px;
    height: 2px;
    border-radius: 2px;
    background: var(--amber);
    transform: scaleX(0);
    transform-origin: left;
    transition: transform .3s cubic-bezier(.16,1,.3,1);
  }
  .nav-links a:hover{ color: var(--ink); }
  .nav-links a:hover::after{ transform: scaleX(1); }

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
  .btn iconify-icon{ transition: transform .3s cubic-bezier(.16,1,.3,1); }
  .btn:hover iconify-icon{ transform: translateX(3px); }
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
  .btn-apply:hover{ transform: translateY(-2px); box-shadow: 0 14px 28px -8px rgba(232,163,61,0.85); }
  .btn-apply:active{ transform: translateY(0); }

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
  .program-card iconify-icon{ font-size:22px; color: var(--amber); margin-bottom:14px; display:block; transition: transform .35s cubic-bezier(.34,1.56,.64,1); }
  .program-card:hover iconify-icon{ transform: scale(1.18) rotate(-6deg); }
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

  /* ---------- stats strip ---------- */
  .stats{
    max-width: 980px;
    margin: 0 auto;
    padding: 0 24px 20px;
  }
  .stats-grid{
    display:grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
  }
  .stat-tile{
    padding: 26px 20px;
    text-align:center;
  }
  .stat-tile .stat-num{
    font-size: clamp(1.8rem, 3vw, 2.4rem);
    font-weight: 800;
    letter-spacing: -0.02em;
    color: var(--ink);
    line-height: 1;
    margin-bottom: 8px;
  }
  .stat-tile .stat-label{
    font-size: 12.5px;
    color: var(--ink-soft);
    font-weight: 600;
    letter-spacing: 0.01em;
  }

  /* ---------- features ---------- */
  .features{
    max-width: 980px;
    margin: 0 auto;
    padding: 60px 24px;
  }
  .feature-grid{
    display:grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 18px;
  }
  .feature-card{
    padding: 26px 22px;
  }
  .feature-card iconify-icon{
    font-size: 24px;
    color: var(--amber);
    display:block;
    margin-bottom: 16px;
    transition: transform .35s cubic-bezier(.34,1.56,.64,1);
  }
  .feature-card:hover iconify-icon{
    transform: scale(1.18) rotate(-6deg);
  }
  .feature-card h4{
    font-size: 15.5px;
    margin: 0 0 8px;
  }
  .feature-card p{
    font-size: 13.5px;
    color: var(--ink-soft);
    line-height: 1.55;
    margin: 0;
  }

  /* ---------- testimonials ---------- */
  .testimonials{
    max-width: 980px;
    margin: 0 auto;
    padding: 20px 24px 80px;
  }
  .testimonial-grid{
    display:grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 18px;
  }
  .testimonial-card{
    padding: 26px 22px;
    display:flex;
    flex-direction:column;
    gap: 14px;
  }
  .testimonial-quote{
    font-size: 14px;
    line-height: 1.65;
    color: var(--ink);
    margin: 0;
  }
  .testimonial-quote::before{ content: "“"; color: var(--amber); font-weight:800; }
  .testimonial-quote::after{ content: "”"; color: var(--amber); font-weight:800; }
  .testimonial-person{
    display:flex; align-items:center; gap:10px;
    margin-top: auto;
  }
  .testimonial-avatar{
    width: 36px; height:36px; border-radius:50%;
    background: linear-gradient(155deg, var(--ink), #2c3e63);
    color: var(--paper);
    display:flex; align-items:center; justify-content:center;
    font-size: 13px; font-weight:700; flex:none;
    transition: transform .3s cubic-bezier(.34,1.56,.64,1);
  }
  .testimonial-card:hover .testimonial-avatar{ transform: scale(1.12); }
  .testimonial-name{ font-size: 13px; font-weight:700; color: var(--ink); }
  .testimonial-role{ font-size: 12px; color: var(--ink-soft); }

  /* ---------- FAQ ---------- */
  .faq{
    max-width: 760px;
    margin: 0 auto;
    padding: 20px 24px 90px;
  }

  /* ---------- CTA banner ---------- */
  .cta-banner{
    max-width: 980px;
    margin: 0 auto 90px;
    padding: 56px 40px;
    text-align:center;
  }
  .cta-banner h2{
    font-size: clamp(1.6rem, 3vw, 2.1rem);
    margin: 0 0 12px;
    letter-spacing: -0.01em;
  }
  .cta-banner p{
    color: var(--ink-soft);
    margin: 0 0 26px;
    max-width: 46ch;
    margin-left:auto; margin-right:auto;
  }

  @media (max-width: 760px){
    .stats-grid{ grid-template-columns: 1fr 1fr; }
    .feature-grid{ grid-template-columns: 1fr 1fr; }
    .testimonial-grid{ grid-template-columns: 1fr; }
  }
</style>
</head>
<body>

  <div class="glass-bg-orbs" aria-hidden="true">
    <div class="glass-orb glass-orb--amber"></div>
    <div class="glass-orb glass-orb--indigo"></div>
    <div class="glass-orb glass-orb--rose"></div>
  </div>

  <div class="nav-wrap">
    <nav class="navbar glass-nav">
      <a class="brand" href="#top">
        <span class="brand-mark"><iconify-icon icon="mdi:school"></iconify-icon></span>
        <span class="brand-name">Edu<em>School</em></span>
      </a>

      <ul class="nav-links">
        <li><a href="#steps">How it works</a></li>
        <li><a href="#programs">Programs</a></li>
        <li><a href="#faq">FAQ</a></li>
      </ul>

      <div style="display:flex; align-items:center; gap:10px;">
        <a href="login.php" class="btn btn-login glass-btn">Log in</a>
        <a href="/SIAdrafts/Frontend/View/Admission/online_admission.php" class="btn btn-apply glass-btn-primary">Apply Now</a>
      </div>
    </nav>
  </div>

  <main id="top" class="hero">
    <span class="eyebrow" data-aos="fade-up"><iconify-icon icon="mdi:calendar-check-outline"></iconify-icon> Admissions open for SY 2026–2027</span>
    <h1 data-aos="fade-up" data-aos-delay="100">Start your application <span class="accent">today.</span></h1>
    <p class="lede" data-aos="fade-up" data-aos-delay="200">Fill out the online form in about 10 minutes. We'll give you a reference number — bring it, along with your documents, when you visit us to finish enrolling.</p>

    <div class="hero-actions" data-aos="fade-up" data-aos-delay="300">
      <a href="/SIAdrafts/Frontend/View/Admission/online_admission.php" class="btn btn-apply btn-lg glass-btn-primary">
        <iconify-icon icon="mdi:file-document-edit-outline"></iconify-icon> Apply Now
      </a>
    </div>

    <div class="ref-note" data-aos="fade-up" data-aos-delay="400">
      Already applied? Your reference number looks like <code>REF-00000-001</code> — keep it for your campus visit.
    </div>
  </main>

  <section class="stats">
    <div class="stats-grid">
      <?php $courseCount = count($courses) ?: 4; ?>
      <div class="stat-tile glass-card" data-aos="zoom-in">
        <div class="stat-num" data-count-to="<?= (int)$courseCount ?>" data-count-suffix="+">0+</div>
        <div class="stat-label">Programs Open</div>
      </div>
      <div class="stat-tile glass-card" data-aos="zoom-in" data-aos-delay="100">
        <div class="stat-num" data-count-to="4">0</div>
        <div class="stat-label">Simple Steps</div>
      </div>
      <div class="stat-tile glass-card" data-aos="zoom-in" data-aos-delay="200">
        <div class="stat-num" data-count-to="10" data-count-prefix="~">~0</div>
        <div class="stat-label">Minutes to Apply</div>
      </div>
      <div class="stat-tile glass-card" data-aos="zoom-in" data-aos-delay="300">
        <div class="stat-num" data-count-to="100" data-count-suffix="%">0%</div>
        <div class="stat-label">Online First Step</div>
      </div>
    </div>
  </section>

  <section class="features">
    <div class="steps-head">
      <h2 data-aos="fade-up">Why apply through EduSchool</h2>
      <p data-aos="fade-up" data-aos-delay="100">Built to make the paperwork part painless.</p>
    </div>
    <div class="feature-grid">
      <div class="feature-card glass-card" data-aos="fade-up">
        <iconify-icon icon="mdi:lightning-bolt-outline"></iconify-icon>
        <h4>Fast Online Application</h4>
        <p>Fill out one form, get a reference number instantly — no queueing just to start.</p>
      </div>
      <div class="feature-card glass-card" data-aos="fade-up" data-aos-delay="100">
        <iconify-icon icon="mdi:cash-check"></iconify-icon>
        <h4>Transparent Fees</h4>
        <p>See your fee breakdown before you commit — no surprise charges at Treasury.</p>
      </div>
      <div class="feature-card glass-card" data-aos="fade-up" data-aos-delay="200">
        <iconify-icon icon="mdi:account-heart-outline"></iconify-icon>
        <h4>Dedicated Support</h4>
        <p>Admissions staff are ready to help you finish verification and enlistment on campus.</p>
      </div>
      <div class="feature-card glass-card" data-aos="fade-up" data-aos-delay="300">
        <iconify-icon icon="mdi:progress-check"></iconify-icon>
        <h4>Track Your Status</h4>
        <p>Keep your reference number handy to follow your application from home to campus.</p>
      </div>
    </div>
  </section>

  <section id="steps" class="steps">
    <div class="steps-head">
      <h2 data-aos="fade-up">How enrollment works</h2>
      <p data-aos="fade-up" data-aos-delay="100">Four steps, two of them online.</p>
    </div>
    <div class="step-grid">
      <div class="step glass-card" data-aos="fade-up">
        <div class="step-num">01 · Online</div>
        <h3>Apply</h3>
        <p>Submit your personal, guardian, and academic history details. Get a reference number instantly.</p>
      </div>
      <div class="step glass-card" data-aos="fade-up" data-aos-delay="100">
        <div class="step-num">02 · On campus</div>
        <h3>Verify documents</h3>
        <p>Bring Form 137/SHS card, Certificate of Good Moral, PSA birth certificate, and 2x2 photos.</p>
      </div>
      <div class="step glass-card" data-aos="fade-up" data-aos-delay="200">
        <div class="step-num">03 · On campus</div>
        <h3>Enlist subjects</h3>
        <p>Staff will confirm your section and subject load for the term.</p>
      </div>
      <div class="step glass-card" data-aos="fade-up" data-aos-delay="300">
        <div class="step-num">04 · Treasury</div>
        <h3>Pay &amp; confirm</h3>
        <p>Settle your down payment to officially lock in your enrollment.</p>
      </div>
    </div>
  </section>

  <section id="programs" class="programs">
    <div class="programs-inner">
      <div class="programs-head">
        <div>
          <h2 data-aos="fade-right">Programs open for enrollment</h2>
          <p data-aos="fade-right" data-aos-delay="100">Pick a program below to jump straight into the application with it pre-selected.</p>
        </div>
      </div>
      <div class="program-list">
        <?php if (empty($courses)): ?>
          <p style="color:rgba(250,247,240,0.6);">No programs currently open for enrollment.</p>
        <?php else: ?>
          <?php foreach ($courses as $i => $c): ?>
            <div class="program-card glass-card glass-card--dark" data-aos="fade-up" data-aos-delay="<?= $i * 100 ?>">
              <iconify-icon icon="mdi:school-outline"></iconify-icon>
              <h4><?= htmlspecialchars($c['course_name']) ?></h4>
              <p><?= (int)$c['total_units'] ?> total units</p>
              <button type="button" class="btn btn-apply glass-btn-primary" style="margin-top:12px;" onclick="openApplyModal(<?= (int)$c['course_id'] ?>, '<?= htmlspecialchars(addslashes($c['course_name'])) ?>')">
                Apply Now
              </button>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <section class="testimonials">
    <div class="steps-head">
      <h2 data-aos="fade-up">What applicants say</h2>
      <p data-aos="fade-up" data-aos-delay="100">A few notes from students who've been through the process.</p>
    </div>
    <!-- PLACEHOLDER: replace with real testimonials before this goes live -->
    <div class="testimonial-grid">
      <div class="testimonial-card glass-card" data-aos="fade-right">
        <p class="testimonial-quote">The online form took less time than I expected, and I had my reference number right away.</p>
        <div class="testimonial-person">
          <div class="testimonial-avatar">JM</div>
          <div>
            <div class="testimonial-name">J. Mercado</div>
            <div class="testimonial-role">BS Criminology, 1st Year</div>
          </div>
        </div>
      </div>
      <div class="testimonial-card glass-card" data-aos="fade-right" data-aos-delay="100">
        <p class="testimonial-quote">Knowing the fee breakdown ahead of time meant no surprises when I got to Treasury.</p>
        <div class="testimonial-person">
          <div class="testimonial-avatar">AR</div>
          <div>
            <div class="testimonial-name">A. Reyes</div>
            <div class="testimonial-role">Transferee</div>
          </div>
        </div>
      </div>
      <div class="testimonial-card glass-card" data-aos="fade-right" data-aos-delay="200">
        <p class="testimonial-quote">Admissions staff walked me through document verification without any back-and-forth.</p>
        <div class="testimonial-person">
          <div class="testimonial-avatar">KS</div>
          <div>
            <div class="testimonial-name">K. Santos</div>
            <div class="testimonial-role">Returning Student</div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section id="faq" class="faq">
    <div class="steps-head">
      <h2 data-aos="fade-up">Frequently asked questions</h2>
    </div>
    <div class="glass-accordion" id="faqAccordion">
      <div class="glass-accordion-item" data-aos="fade-up">
        <button type="button" class="glass-accordion-btn">
          <span>Do I need to bring documents to apply online?</span>
          <iconify-icon class="glass-accordion-icon" icon="mdi:chevron-down"></iconify-icon>
        </button>
        <div class="glass-accordion-body">No — the online form only needs your details. You can upload requirements now or mark them "submit at campus" and bring them in person.</div>
      </div>
      <div class="glass-accordion-item" data-aos="fade-up" data-aos-delay="100">
        <button type="button" class="glass-accordion-btn">
          <span>How long does the whole process take?</span>
          <iconify-icon class="glass-accordion-icon" icon="mdi:chevron-down"></iconify-icon>
        </button>
        <div class="glass-accordion-body">The online form takes about 10 minutes. The on-campus steps (document verification, enlistment, and payment) depend on how busy the line is that day.</div>
      </div>
      <div class="glass-accordion-item" data-aos="fade-up" data-aos-delay="200">
        <button type="button" class="glass-accordion-btn">
          <span>I lost my reference number — what do I do?</span>
          <iconify-icon class="glass-accordion-icon" icon="mdi:chevron-down"></iconify-icon>
        </button>
        <div class="glass-accordion-body">Visit the Admissions counter with a valid ID and the staff can look up your application by name and birth date.</div>
      </div>
      <div class="glass-accordion-item" data-aos="fade-up" data-aos-delay="300">
        <button type="button" class="glass-accordion-btn">
          <span>Can I change my program after applying?</span>
          <iconify-icon class="glass-accordion-icon" icon="mdi:chevron-down"></iconify-icon>
        </button>
        <div class="glass-accordion-body">Yes — let Admissions staff know when you come in for document verification, and they can update it before you're enlisted.</div>
      </div>
    </div>
  </section>

  <section class="cta-banner glass-card" data-aos="zoom-in">
    <h2>Ready to start?</h2>
    <p>Your reference number is a few minutes away.</p>
    <a href="/SIAdrafts/Frontend/View/Admission/online_admission.php" class="btn btn-apply btn-lg glass-btn-primary">
      <iconify-icon icon="mdi:file-document-edit-outline"></iconify-icon> Apply Now
    </a>
  </section>

  <footer>
    © 2026 EduSchool. This is a preview mockup — hook up real links before shipping.
  </footer>

  <div id="applyModal" style="display:none; position:fixed; inset:0; background:rgba(27,42,74,0.5); z-index:2000; align-items:center; justify-content:center;">
    <div class="glass-modal" style="padding:28px; max-width:420px; width:90%;">
      <h3 id="applyModalTitle" style="margin:0 0 10px;">Apply for this program?</h3>
      <p style="color:var(--ink-soft); margin:0 0 20px;">You'll be taken to the application form with this program pre-selected.</p>
      <div style="display:flex; gap:10px; justify-content:flex-end;">
        <button type="button" class="btn btn-login glass-btn" onclick="closeApplyModal()">Cancel</button>
        <a id="applyModalConfirm" href="#" class="btn btn-apply glass-btn-primary">Continue</a>
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

    document.querySelectorAll('.glass-accordion-btn').forEach(function (btn) {
      var body = btn.nextElementSibling;
      btn.addEventListener('click', function () {
        var item = btn.closest('.glass-accordion-item');
        var isOpen = item.classList.toggle('open');
        body.style.maxHeight = isOpen ? body.scrollHeight + 'px' : '0px';
      });
    });

    // Count-up animation for the stats strip, fires once each tile
    // scrolls into view.
    (function () {
      var tiles = document.querySelectorAll('.stat-num[data-count-to]');
      if (!tiles.length || !('IntersectionObserver' in window)) return;

      function animateCount(el) {
        var target = parseFloat(el.dataset.countTo);
        var prefix = el.dataset.countPrefix || '';
        var suffix = el.dataset.countSuffix || '';
        var duration = 1100;
        var start = null;

        function easeOutCubic(t) { return 1 - Math.pow(1 - t, 3); }

        function step(ts) {
          if (start === null) start = ts;
          var progress = Math.min((ts - start) / duration, 1);
          var value = Math.round(target * easeOutCubic(progress));
          el.textContent = prefix + value + suffix;
          if (progress < 1) requestAnimationFrame(step);
        }
        requestAnimationFrame(step);
      }

      var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            animateCount(entry.target);
            observer.unobserve(entry.target);
          }
        });
      }, { threshold: 0.6 });

      tiles.forEach(function (el) { observer.observe(el); });
    })();
  </script>

  <script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
  <script>
    AOS.init({ duration: 800, easing: 'ease-out-cubic', once: true });
  </script>

</body>
</html>