<?php
require_once __DIR__ . '/../../Backend/db.php';
$db   = new Database();
$conn = $db->connect();
$courses = [];
$res = $conn->query("SELECT course_id, course_code, course_name, total_units FROM course WHERE status = 'Approved' ORDER BY course_name ASC");
if ($res) $courses = $res->fetch_all(MYSQLI_ASSOC);
$db->close();

function course_monogram(string $name): string {
    $stop = ['of', 'in', 'and', 'the', 'for'];
    $words = preg_split('/\s+/', trim($name));
    $letters = '';
    foreach ($words as $w) {
        $w = preg_replace('/[^A-Za-z]/', '', $w);
        if ($w === '' || in_array(strtolower($w), $stop, true)) continue;
        $letters .= strtoupper($w[0]);
        if (strlen($letters) >= 3) break;
    }
    return $letters ?: strtoupper(substr($name, 0, 2));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>EduSchool — Admissions</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Open+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/SIAdrafts/Frontend/Css/glass-theme.css">
</head>
<body class="glass-body">

  <div class="g-orb-layer" aria-hidden="true">
    <div class="g-orb g-orb--1" data-speed="0.08"></div>
    <div class="g-orb g-orb--2" data-speed="0.14"></div>
    <div class="g-orb g-orb--3" data-speed="0.05"></div>
    <div class="g-orb g-orb--4" data-speed="0.18"></div>
  </div>

  <div class="g-nav-wrap" id="gNavWrap">
    <nav class="g-navbar">
      <a class="g-brand" href="#top">Edu<em>School</em></a>

      <ul class="g-nav-links">
        <li><a href="#steps">How it works</a></li>
        <li><a href="#why">Why us</a></li>
        <li><a href="#programs">Programs</a></li>
        <li><a href="#faq">FAQ</a></li>
      </ul>

      <div class="g-nav-actions">
        <a href="login.php" class="g-link">Log in</a>
        <a href="/SIAdrafts/Frontend/View/Admission/online_admission.php" class="g-btn">Apply Now</a>
      </div>
    </nav>
  </div>

  <main id="top" class="g-hero">
    <div class="g-hero-bg" style="background-image:url('/SIAdrafts/Frontend/Images/landing/hero.jpg')"></div>
    <div class="g-hero-inner">
      <div class="g-hero-card g-glass g-glass-dark g-tilt-in">
        <span class="g-eyebrow g-eyebrow--on-dark">Admissions Open — SY 2026–2027</span>
        <h1>Begin your <em>education</em>, one form at a time.</h1>
        <p class="g-hero-lede">Fill out the online application in about ten minutes. We'll issue a reference number — bring it, along with your documents, when you visit us to complete enrollment.</p>

        <div class="g-hero-actions">
          <a href="/SIAdrafts/Frontend/View/Admission/online_admission.php" class="g-btn">Apply Now <span class="g-arrow">→</span></a>
          <a href="#steps" class="g-btn g-btn-glass">See how it works</a>
        </div>

        <div class="g-ref-note">
          Already applied? Your reference number looks like <code>REF-00000-001</code> — keep it for your campus visit.
        </div>
      </div>
    </div>
  </main>

  <section id="steps" class="g-section">
    <div class="g-section-head">
      <span class="g-eyebrow">Process</span>
      <h2>How enrollment works</h2>
      <p>Four steps, in order — two of them completed online, two on campus.</p>
    </div>
    <div class="g-steps">
      <div class="g-step g-glass g-tilt-in">
        <div class="g-step-num">1</div>
        <h3>Apply</h3>
        <p>Submit your personal, guardian, and academic history details online. Receive a reference number instantly.</p>
      </div>
      <div class="g-step g-glass g-tilt-in">
        <div class="g-step-num">2</div>
        <h3>Verify documents</h3>
        <p>Bring your Form 137/SHS card, Certificate of Good Moral, PSA birth certificate, and 2x2 photos to campus.</p>
      </div>
      <div class="g-step g-glass g-tilt-in">
        <div class="g-step-num">3</div>
        <h3>Enlist subjects</h3>
        <p>Our staff will confirm your section and subject load for the term.</p>
      </div>
      <div class="g-step g-glass g-tilt-in">
        <div class="g-step-num">4</div>
        <h3>Pay &amp; confirm</h3>
        <p>Settle your down payment to officially lock in your enrollment.</p>
      </div>
    </div>
  </section>

  <section id="why" class="g-feature-band g-tilt-in">
    <div class="g-feature-bg" style="background-image:url('/SIAdrafts/Frontend/Images/landing/feature.jpg')"></div>
    <div class="g-feature-inner">
      <div class="g-feature-card g-glass g-glass-dark">
        <span class="g-eyebrow g-eyebrow--on-dark">Why EduSchool</span>
        <h2>Built around a smoother enrollment day.</h2>
        <p>A clear online application, transparent fees, and staff who walk you through document verification without back-and-forth.</p>
        <div class="g-stat-row">
          <div class="g-stat g-glass g-glass-dark">
            <div class="g-stat-num"><?= count($courses) ?: '4' ?>+</div>
            <div class="g-stat-label">Programs Open</div>
          </div>
          <div class="g-stat g-glass g-glass-dark">
            <div class="g-stat-num">4</div>
            <div class="g-stat-label">Step Process</div>
          </div>
          <div class="g-stat g-glass g-glass-dark">
            <div class="g-stat-num">~10</div>
            <div class="g-stat-label">Minute Application</div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section id="programs" class="g-section">
    <div class="g-section-head">
      <span class="g-eyebrow">Programs</span>
      <h2>Open for enrollment</h2>
      <p>Select a program below to begin the application with it pre-selected.</p>
    </div>
    <div class="g-program-list">
      <?php if (empty($courses)): ?>
        <p style="color:var(--g-fg-soft);">No programs currently open for enrollment.</p>
      <?php else: ?>
        <?php foreach ($courses as $i => $c): ?>
          <div class="g-program-card g-glass g-tilt-in" onclick="openApplyModal(<?= (int)$c['course_id'] ?>, '<?= htmlspecialchars(addslashes($c['course_name'])) ?>')">
            <div class="g-program-monogram"><?= htmlspecialchars(course_monogram($c['course_name'])) ?></div>
            <div class="g-program-name"><?= htmlspecialchars($c['course_name']) ?></div>
            <div class="g-program-meta"><?= (int)$c['total_units'] ?> Total Units</div>
            <button type="button" class="g-program-apply" onclick="event.stopPropagation(); openApplyModal(<?= (int)$c['course_id'] ?>, '<?= htmlspecialchars(addslashes($c['course_name'])) ?>')">
              Apply <span class="g-arrow">→</span>
            </button>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>

  <section class="g-section">
    <div class="g-section-head">
      <span class="g-eyebrow">From Our Applicants</span>
      <h2>What they say</h2>
    </div>
    <!-- PLACEHOLDER: replace with real testimonials before this goes live -->
    <div class="g-quotes">
      <blockquote class="g-quote g-glass g-tilt-in">
        <div class="g-quote-mark">“</div>
        <p>"The online form took less time than I expected, and I had my reference number right away."</p>
        <cite>— J. Mercado, BS Criminology, 1st Year</cite>
      </blockquote>
      <blockquote class="g-quote g-glass g-tilt-in">
        <div class="g-quote-mark">“</div>
        <p>"Knowing the fee breakdown ahead of time meant no surprises when I got to Treasury."</p>
        <cite>— A. Reyes, Transferee</cite>
      </blockquote>
      <blockquote class="g-quote g-glass g-tilt-in">
        <div class="g-quote-mark">“</div>
        <p>"Admissions staff walked me through document verification without any back-and-forth."</p>
        <cite>— K. Santos, Returning Student</cite>
      </blockquote>
    </div>
  </section>

  <section id="faq" class="g-section">
    <div class="g-section-head">
      <span class="g-eyebrow">Questions</span>
      <h2>Frequently asked</h2>
    </div>
    <div class="g-faq">
      <div class="g-faq-item g-glass g-tilt-in">
        <button type="button" class="g-faq-btn">
          <span>Do I need to bring documents to apply online?</span>
          <span class="g-faq-mark">+</span>
        </button>
        <div class="g-faq-body">No — the online form only needs your details. You can upload requirements now or mark them "submit at campus" and bring them in person.</div>
      </div>
      <div class="g-faq-item g-glass g-tilt-in">
        <button type="button" class="g-faq-btn">
          <span>How long does the whole process take?</span>
          <span class="g-faq-mark">+</span>
        </button>
        <div class="g-faq-body">The online form takes about ten minutes. The on-campus steps (document verification, enlistment, and payment) depend on how busy the line is that day.</div>
      </div>
      <div class="g-faq-item g-glass g-tilt-in">
        <button type="button" class="g-faq-btn">
          <span>I lost my reference number — what do I do?</span>
          <span class="g-faq-mark">+</span>
        </button>
        <div class="g-faq-body">Visit the Admissions counter with a valid ID and the staff can look up your application by name and birth date.</div>
      </div>
      <div class="g-faq-item g-glass g-tilt-in">
        <button type="button" class="g-faq-btn">
          <span>Can I change my program after applying?</span>
          <span class="g-faq-mark">+</span>
        </button>
        <div class="g-faq-body">Yes — let Admissions staff know when you come in for document verification, and they can update it before you're enlisted.</div>
      </div>
    </div>
  </section>

  <section class="g-cta-band g-tilt-in">
    <div class="g-cta-bg" style="background-image:url('/SIAdrafts/Frontend/Images/landing/cta.jpg')"></div>
    <h2>Ready to start?</h2>
    <p>Your reference number is a few minutes away.</p>
    <a href="/SIAdrafts/Frontend/View/Admission/online_admission.php" class="g-btn">Apply Now <span class="g-arrow">→</span></a>
  </section>

  <footer class="g-footer">
    © 2026 EduSchool. This is a preview mockup — replace placeholder content (including stock photography) before deploying.
  </footer>

  <div id="applyModal" class="g-modal-overlay">
    <div class="g-modal-box g-glass g-glass-strong">
      <h3 id="applyModalTitle">Apply for this program?</h3>
      <p>You'll be taken to the application form with this program pre-selected.</p>
      <div style="display:flex; gap:12px; justify-content:flex-end;">
        <button type="button" class="g-btn g-btn-outline" onclick="closeApplyModal()">Cancel</button>
        <a id="applyModalConfirm" href="#" class="g-btn">Continue</a>
      </div>
    </div>
  </div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
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
    document.querySelectorAll('.g-faq-btn').forEach(function (btn) {
      var body = btn.nextElementSibling;
      btn.addEventListener('click', function () {
        var item = btn.closest('.g-faq-item');
        var isOpen = item.classList.toggle('open');
        body.style.maxHeight = isOpen ? body.scrollHeight + 'px' : '0px';
        btn.querySelector('.g-faq-mark').textContent = isOpen ? '−' : '+';
      });
    });

    // Sticky nav gains a stronger glass background once scrolled past the hero.
    (function () {
      var navWrap = document.getElementById('gNavWrap');
      if (!navWrap) return;
      window.addEventListener('scroll', function () {
        navWrap.classList.toggle('scrolled', window.scrollY > 40);
      }, { passive: true });
    })();

    var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // Scroll parallax — fixed orb layer drifts at a fraction of scroll
    // speed per orb (via each orb's data-speed), giving depth behind the
    // glass panels. Skipped entirely under prefers-reduced-motion.
    (function () {
      var orbs = document.querySelectorAll('.g-orb');
      if (!orbs.length || reducedMotion) return;

      var ticking = false;
      function applyParallax() {
        var scrollY = window.scrollY;
        orbs.forEach(function (orb) {
          var speed = parseFloat(orb.getAttribute('data-speed')) || 0;
          orb.style.transform = 'translateY(' + (scrollY * speed) + 'px)';
        });
        ticking = false;
      }
      window.addEventListener('scroll', function () {
        if (!ticking) {
          window.requestAnimationFrame(applyParallax);
          ticking = true;
        }
      }, { passive: true });
    })();

    // GSAP ScrollTrigger — 3D tilt-in entrance for cards/panels, plus a
    // subtle hero background parallax. Falls back to fully-visible static
    // panels if GSAP failed to load (CDN outage) or reduced-motion is on.
    (function () {
      var panels = document.querySelectorAll('.g-tilt-in');
      if (!panels.length) return;

      if (reducedMotion || typeof gsap === 'undefined' || typeof ScrollTrigger === 'undefined') {
        panels.forEach(function (el) { el.style.opacity = 1; el.style.transform = 'none'; });
        return;
      }

      gsap.registerPlugin(ScrollTrigger);

      panels.forEach(function (el, i) {
        gsap.fromTo(el,
          { autoAlpha: 0, rotateX: 28, y: 50, transformPerspective: 900, transformOrigin: 'top center' },
          {
            autoAlpha: 1, rotateX: 0, y: 0, duration: 0.9, ease: 'power3.out',
            scrollTrigger: { trigger: el, start: 'top 88%' }
          }
        );
      });

      gsap.to('.g-hero-bg', {
        yPercent: 12,
        ease: 'none',
        scrollTrigger: { trigger: '.g-hero', start: 'top top', end: 'bottom top', scrub: true }
      });
    })();
  </script>

</body>
</html>
