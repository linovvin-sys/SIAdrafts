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
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,300;0,400;0,500;1,400;1,500&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/SIAdrafts/Frontend/Css/editorial-theme.css">
</head>
<body class="editorial-body">

  <div class="e-parallax-layer" aria-hidden="true">
    <div class="e-blob e-blob--1" data-speed="0.08"></div>
    <div class="e-blob e-blob--2" data-speed="0.14"></div>
    <div class="e-blob e-blob--3" data-speed="0.05"></div>
    <div class="e-blob e-blob--4" data-speed="0.18"></div>
  </div>

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
    <div class="e-folio e-reveal" style="transition-delay:0ms">
      <span>Vol. 01 — Admissions Prospectus, SY 2026–2027</span>
      <span class="e-folio-page">01</span>
    </div>
    <span class="e-eyebrow e-reveal" style="transition-delay:40ms">Admissions Open — SY 2026–2027</span>
    <h1 class="e-reveal" style="transition-delay:80ms">Begin your <em>education</em>, one form at a time.</h1>
    <p class="e-lede e-reveal" style="transition-delay:180ms">Fill out the online application in about ten minutes. We'll issue a reference number — bring it, along with your documents, when you visit us to complete enrollment.</p>

    <div class="e-hero-actions e-reveal" style="transition-delay:280ms">
      <a href="/SIAdrafts/Frontend/View/Admission/online_admission.php" class="e-btn">Apply Now</a>
      <a href="#steps" class="e-link">See how it works</a>
    </div>

    <div class="e-ref-note e-reveal" style="transition-delay:360ms">
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
      <div class="e-step e-reveal e-reveal--right" style="transition-delay:0ms">
        <div class="e-step-num">01</div>
        <div>
          <h3>Apply</h3>
          <p>Submit your personal, guardian, and academic history details online. Receive a reference number instantly.</p>
        </div>
      </div>
      <div class="e-step e-reveal e-reveal--right" style="transition-delay:80ms">
        <div class="e-step-num">02</div>
        <div>
          <h3>Verify documents</h3>
          <p>Bring your Certificate of Good Moral, PSA birth certificate, Form 138, and 2x2 photos to campus.</p>
        </div>
      </div>
      <div class="e-step e-reveal e-reveal--right" style="transition-delay:160ms">
        <div class="e-step-num">03</div>
        <div>
          <h3>Enlist subjects</h3>
          <p>Our staff will confirm your section and subject load for the term.</p>
        </div>
      </div>
      <div class="e-step e-reveal e-reveal--right" style="transition-delay:240ms">
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
    <div class="e-program-index">
      <?php if (empty($courses)): ?>
        <p style="color:var(--ink-soft);">No programs currently open for enrollment.</p>
      <?php else: ?>
        <?php foreach ($courses as $i => $c): ?>
          <div class="e-index-row e-reveal e-reveal--right" style="transition-delay:<?= $i * 60 ?>ms" onclick="openApplyModal(<?= (int)$c['course_id'] ?>, '<?= htmlspecialchars(addslashes($c['course_name'])) ?>')">
            <span class="e-index-num"><?= sprintf('%02d', $i + 1) ?></span>
            <div class="e-index-body">
              <div class="e-index-name"><?= htmlspecialchars($c['course_name']) ?></div>
              <div class="e-index-tag"><?= htmlspecialchars(course_monogram($c['course_name'])) ?> · <?= (int)$c['total_units'] ?> Total Units</div>
            </div>
            <button type="button" class="e-index-apply" onclick="event.stopPropagation(); openApplyModal(<?= (int)$c['course_id'] ?>, '<?= htmlspecialchars(addslashes($c['course_name'])) ?>')">
              Apply <span class="e-arrow">→</span>
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
      <blockquote class="e-margin-note e-reveal e-reveal--left" style="transition-delay:0ms">
        <p>"The online form took less time than I expected, and I had my reference number right away."</p>
        <cite>— J. Mercado, BS Criminology, 1st Year</cite>
      </blockquote>
      <blockquote class="e-margin-note e-reveal e-reveal--left" style="transition-delay:100ms">
        <p>"Knowing the fee breakdown ahead of time meant no surprises when I got to Treasury."</p>
        <cite>— A. Reyes, Transferee</cite>
      </blockquote>
      <blockquote class="e-margin-note e-reveal e-reveal--left" style="transition-delay:200ms">
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
      <div class="e-faq-item e-reveal e-reveal--up-sm" style="transition-delay:0ms">
        <button type="button" class="e-faq-btn">
          <span>Do I need to bring documents to apply online?</span>
          <span class="e-faq-mark">+</span>
        </button>
        <div class="e-faq-body">No — the online form only needs your details. You can upload requirements now or mark them "submit at campus" and bring them in person.</div>
      </div>
      <div class="e-faq-item e-reveal e-reveal--up-sm" style="transition-delay:60ms">
        <button type="button" class="e-faq-btn">
          <span>How long does the whole process take?</span>
          <span class="e-faq-mark">+</span>
        </button>
        <div class="e-faq-body">The online form takes about ten minutes. The on-campus steps (document verification, enlistment, and payment) depend on how busy the line is that day.</div>
      </div>
      <div class="e-faq-item e-reveal e-reveal--up-sm" style="transition-delay:120ms">
        <button type="button" class="e-faq-btn">
          <span>I lost my reference number — what do I do?</span>
          <span class="e-faq-mark">+</span>
        </button>
        <div class="e-faq-body">Visit the Admissions counter with a valid ID and the staff can look up your application by name and birth date.</div>
      </div>
      <div class="e-faq-item e-reveal e-reveal--up-sm" style="transition-delay:180ms">
        <button type="button" class="e-faq-btn">
          <span>Can I change my program after applying?</span>
          <span class="e-faq-mark">+</span>
        </button>
        <div class="e-faq-body">Yes — let Admissions staff know when you come in for document verification, and they can update it before you're enlisted.</div>
      </div>
    </div>
  </section>

  <section class="e-cta-band e-reveal e-reveal--zoom">
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

    // Scroll reveal — fade+rise, staggered via each element's own
    // transition-delay (set inline per group). Defaults to visible (CSS
    // .e-reveal has opacity:0 only as a progressive enhancement) if
    // IntersectionObserver isn't available.
    (function () {
      var items = document.querySelectorAll('.e-reveal');
      if (!items.length) return;

      if (!('IntersectionObserver' in window) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        items.forEach(function (el) { el.classList.add('in'); });
        return;
      }

      var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add('in');
            observer.unobserve(entry.target);
          }
        });
      }, { threshold: 0.15 });

      items.forEach(function (el) { observer.observe(el); });
    })();

    // Scroll parallax — fixed blob layer drifts at a fraction of scroll
    // speed per blob (via each blob's data-speed), giving a sense of
    // depth behind the content. Skipped entirely under
    // prefers-reduced-motion, matching the reveal/rule scripts above.
    (function () {
      var blobs = document.querySelectorAll('.e-blob');
      if (!blobs.length) return;

      if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
      }

      var ticking = false;

      function applyParallax() {
        var scrollY = window.scrollY;
        blobs.forEach(function (blob) {
          var speed = parseFloat(blob.getAttribute('data-speed')) || 0;
          blob.style.transform = 'translateY(' + (scrollY * speed) + 'px)';
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
  </script>

</body>
</html>
