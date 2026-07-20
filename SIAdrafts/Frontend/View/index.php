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
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,300;0,400;0,500;1,400;1,500&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/SIAdrafts/Frontend/Css/editorial-theme.css">
</head>
<body class="editorial-body">

  <div class="e-nav-wrap">
    <nav class="e-navbar">
      <a class="e-brand" href="#top">Edu<em>School</em></a>

      <ul class="e-nav-links">
        <li><a href="#steps">How it works</a></li>
        <li><a href="#programs">Programs</a></li>
        <li><a href="#faq">FAQ</a></li>
      </ul>

      <div class="e-nav-actions">
        <a href="login.php" class="e-link">Log in</a>
        <a href="/SIAdrafts/Frontend/View/Admission/online_admission.php" class="e-btn">Apply Now</a>
      </div>
    </nav>
  </div>

  <main id="top" class="e-hero">
    <span class="e-eyebrow">Admissions Open — SY 2026–2027</span>
    <h1>Begin your <em>education</em>, one form at a time.</h1>
    <p class="e-lede">Fill out the online application in about ten minutes. We'll issue a reference number — bring it, along with your documents, when you visit us to complete enrollment.</p>

    <div class="e-hero-actions">
      <a href="/SIAdrafts/Frontend/View/Admission/online_admission.php" class="e-btn">Apply Now</a>
      <a href="#steps" class="e-link" style="text-decoration:none; color:var(--ink); border-bottom:1px solid var(--line); padding-bottom:3px; font-size:13.5px; font-weight:500;">See how it works</a>
    </div>

    <div class="e-ref-note">
      Already applied? Your reference number looks like <code>REF-00000-001</code> — keep it for your campus visit.
    </div>
  </main>

  <hr class="e-divider">

  <section id="steps" class="e-section">
    <div class="e-section-head">
      <span class="e-eyebrow">Process</span>
      <h2>How enrollment works</h2>
      <div class="e-heading-rule" data-rule></div>
      <p>Four steps, in order — two of them completed online, two on campus.</p>
    </div>
    <div class="e-steps">
      <div class="e-step">
        <div class="e-step-num">01</div>
        <div>
          <h3>Apply</h3>
          <p>Submit your personal, guardian, and academic history details online. Receive a reference number instantly.</p>
        </div>
      </div>
      <div class="e-step">
        <div class="e-step-num">02</div>
        <div>
          <h3>Verify documents</h3>
          <p>Bring your Form 137/SHS card, Certificate of Good Moral, PSA birth certificate, and 2x2 photos to campus.</p>
        </div>
      </div>
      <div class="e-step">
        <div class="e-step-num">03</div>
        <div>
          <h3>Enlist subjects</h3>
          <p>Our staff will confirm your section and subject load for the term.</p>
        </div>
      </div>
      <div class="e-step">
        <div class="e-step-num">04</div>
        <div>
          <h3>Pay &amp; confirm</h3>
          <p>Settle your down payment to officially lock in your enrollment.</p>
        </div>
      </div>
    </div>
  </section>

  <section id="programs" class="e-section">
    <div class="e-section-head">
      <span class="e-eyebrow">Programs</span>
      <h2>Open for enrollment</h2>
      <div class="e-heading-rule" data-rule></div>
      <p>Select a program below to begin the application with it pre-selected.</p>
    </div>
    <div class="e-program-list">
      <?php if (empty($courses)): ?>
        <p style="color:var(--ink-soft);">No programs currently open for enrollment.</p>
      <?php else: ?>
        <?php foreach ($courses as $c): ?>
          <div class="e-program-row">
            <div>
              <div class="e-program-name"><?= htmlspecialchars($c['course_name']) ?></div>
              <div class="e-program-meta"><?= (int)$c['total_units'] ?> total units</div>
            </div>
            <button type="button" class="e-program-apply" onclick="openApplyModal(<?= (int)$c['course_id'] ?>, '<?= htmlspecialchars(addslashes($c['course_name'])) ?>')">
              Apply →
            </button>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>

  <section class="e-section">
    <div class="e-section-head">
      <span class="e-eyebrow">From Our Applicants</span>
      <h2>What they say</h2>
      <div class="e-heading-rule" data-rule></div>
    </div>
    <!-- PLACEHOLDER: replace with real testimonials before this goes live -->
    <div class="e-quotes">
      <blockquote class="e-quote">
        <p>"The online form took less time than I expected, and I had my reference number right away."</p>
        <cite>— J. Mercado, BS Criminology, 1st Year</cite>
      </blockquote>
      <blockquote class="e-quote">
        <p>"Knowing the fee breakdown ahead of time meant no surprises when I got to Treasury."</p>
        <cite>— A. Reyes, Transferee</cite>
      </blockquote>
      <blockquote class="e-quote">
        <p>"Admissions staff walked me through document verification without any back-and-forth."</p>
        <cite>— K. Santos, Returning Student</cite>
      </blockquote>
    </div>
  </section>

  <section id="faq" class="e-section">
    <div class="e-section-head">
      <span class="e-eyebrow">Questions</span>
      <h2>Frequently asked</h2>
      <div class="e-heading-rule" data-rule></div>
    </div>
    <div class="e-faq">
      <div class="e-faq-item">
        <button type="button" class="e-faq-btn">
          <span>Do I need to bring documents to apply online?</span>
          <span class="e-faq-mark">+</span>
        </button>
        <div class="e-faq-body">No — the online form only needs your details. You can upload requirements now or mark them "submit at campus" and bring them in person.</div>
      </div>
      <div class="e-faq-item">
        <button type="button" class="e-faq-btn">
          <span>How long does the whole process take?</span>
          <span class="e-faq-mark">+</span>
        </button>
        <div class="e-faq-body">The online form takes about ten minutes. The on-campus steps (document verification, enlistment, and payment) depend on how busy the line is that day.</div>
      </div>
      <div class="e-faq-item">
        <button type="button" class="e-faq-btn">
          <span>I lost my reference number — what do I do?</span>
          <span class="e-faq-mark">+</span>
        </button>
        <div class="e-faq-body">Visit the Admissions counter with a valid ID and the staff can look up your application by name and birth date.</div>
      </div>
      <div class="e-faq-item">
        <button type="button" class="e-faq-btn">
          <span>Can I change my program after applying?</span>
          <span class="e-faq-mark">+</span>
        </button>
        <div class="e-faq-body">Yes — let Admissions staff know when you come in for document verification, and they can update it before you're enlisted.</div>
      </div>
    </div>
  </section>

  <section class="e-cta-band">
    <h2>Ready to start?</h2>
    <p>Your reference number is a few minutes away.</p>
    <a href="/SIAdrafts/Frontend/View/Admission/online_admission.php" class="e-btn">Apply Now</a>
  </section>

  <footer class="e-footer">
    © 2026 EduSchool. This is a preview mockup — replace placeholder content before deploying.
  </footer>

  <div id="applyModal" class="e-modal-overlay">
    <div class="e-modal-box">
      <h3 id="applyModalTitle">Apply for this program?</h3>
      <p>You'll be taken to the application form with this program pre-selected.</p>
      <div style="display:flex; gap:12px; justify-content:flex-end;">
        <button type="button" class="e-btn e-btn-outline" onclick="closeApplyModal()">Cancel</button>
        <a id="applyModalConfirm" href="#" class="e-btn">Continue</a>
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

    // FAQ accordion — smooth height transition.
    document.querySelectorAll('.e-faq-btn').forEach(function (btn) {
      var body = btn.nextElementSibling;
      btn.addEventListener('click', function () {
        var item = btn.closest('.e-faq-item');
        var isOpen = item.classList.toggle('open');
        body.style.maxHeight = isOpen ? body.scrollHeight + 'px' : '0px';
        btn.querySelector('.e-faq-mark').textContent = isOpen ? '−' : '+';
      });
    });

    // Signature device: self-drawing rule beneath each section heading,
    // fires once as the heading scrolls into view. Defaults to fully
    // drawn (via CSS) if IntersectionObserver isn't available, so a
    // script failure never hides content.
    (function () {
      var rules = document.querySelectorAll('[data-rule]');
      if (!rules.length) return;

      var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      if (reduced) {
        rules.forEach(function (r) { r.classList.add('drawn'); });
        return;
      }

      if (!('IntersectionObserver' in window)) {
        rules.forEach(function (r) { r.classList.add('drawn'); });
        return;
      }

      var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add('drawn');
            observer.unobserve(entry.target);
          }
        });
      }, { threshold: 0.6 });

      rules.forEach(function (r) { observer.observe(r); });
    })();
  </script>

</body>
</html>
