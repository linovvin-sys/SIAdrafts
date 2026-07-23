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
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@1,9..144,400;1,9..144,500;0,9..144,500&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/SIAdrafts/Frontend/Css/hallway-theme.css">
<script type="importmap">
{
  "imports": {
    "three": "https://unpkg.com/three@0.160.0/build/three.module.js",
    "three/addons/": "https://unpkg.com/three@0.160.0/examples/jsm/"
  }
}
</script>
</head>
<body class="hw-body">

  <canvas id="hwScene" class="hw-canvas" aria-hidden="true"></canvas>

  <div class="hw-nav-wrap" id="hwNavWrap">
    <nav class="hw-navbar">
      <a class="hw-brand" href="#top">Edu<em>School</em></a>
      <ul class="hw-nav-links">
        <li><a href="#academics">Academics</a></li>
        <li><a href="#life">Campus Life</a></li>
        <li><a href="#admissions">Admissions</a></li>
      </ul>
      <div class="hw-nav-actions">
        <a href="login.php" class="hw-link">Log in</a>
        <a href="/SIAdrafts/Frontend/View/Admission/online_admission.php" class="hw-btn">Apply Now</a>
      </div>
    </nav>
  </div>

  <div class="hw-hallway" id="hwHallway">
    <div class="hw-hallway-track">

      <div class="hw-window-stop hw-align-center hw-hero-stop" id="top">
        <div class="hw-card">
          <span class="hw-eyebrow">Admissions Open — SY 2026–2027</span>
          <h1 class="hw-h1">Walk the halls before you <em>apply</em>.</h1>
          <p>EduSchool runs four programs, a working library wing, and a fielded athletics program — this page is a short walk down that actual hallway before you fill out a form.</p>
          <div class="hw-stat-row">
            <div><div class="hw-stat-num"><?= count($courses) ?: '4' ?></div><div class="hw-stat-label">Programs Open</div></div>
            <div><div class="hw-stat-num">~10</div><div class="hw-stat-label">Min Application</div></div>
          </div>
          <div style="margin-top:26px; display:flex; gap:14px; flex-wrap:wrap;">
            <a href="/SIAdrafts/Frontend/View/Admission/online_admission.php" class="hw-btn">Apply Now</a>
            <a href="#academics" class="hw-btn hw-btn-glass">Keep scrolling</a>
          </div>
        </div>
        <div class="hw-scroll-cue">Scroll</div>
      </div>

      <div class="hw-window-stop hw-align-left" data-stop="1" style="top:20vh;">
        <div class="hw-card">
          <span class="hw-eyebrow">Academics</span>
          <h2>Every program keeps a seat count, not a waitlist trick.</h2>
          <p>Course loads are published with total units up front — no "TBA" units discovered after enrollment. Section assignment happens at document verification, not by chance.</p>
        </div>
      </div>

      <div class="hw-window-stop hw-align-right" data-stop="2" style="top:20vh;">
        <div class="hw-card">
          <span class="hw-eyebrow">The Library Wing</span>
          <h2>Open through both terms, past the last bell.</h2>
          <p>Reference copies for every enrolled subject stay on the reserve shelf — no library card grace period to sort out during your first week.</p>
        </div>
      </div>

      <div class="hw-window-stop hw-align-left" data-stop="3" style="top:20vh;">
        <div class="hw-card">
          <span class="hw-eyebrow">Arts &amp; Culture</span>
          <h2>One showcase a term, staged by students.</h2>
          <p>Lighting, program layout, and the door list — run by the arts committee, not outsourced to an events vendor.</p>
        </div>
      </div>

      <div class="hw-window-stop hw-align-right" data-stop="4" style="top:20vh;">
        <div class="hw-card">
          <span class="hw-eyebrow">Athletics</span>
          <h2>Tryouts posted the first week of term.</h2>
          <p>Practice schedules go up on the same board as the enrollment calendar — athletics isn't a separate office to track down.</p>
        </div>
      </div>

      <div class="hw-window-stop hw-align-center" id="admissions" data-stop="5" style="top:0;">
        <div class="hw-card">
          <span class="hw-eyebrow">Admissions</span>
          <h2>Ready to schedule a visit?</h2>
          <p>Bring your Form 137/SHS card, Certificate of Good Moral, PSA birth certificate, and 2x2 photos when you come in — or start the form now and finish enrollment on campus.</p>
          <div style="margin-top:22px;">
            <a href="/SIAdrafts/Frontend/View/Admission/online_admission.php" class="hw-btn">Schedule a Visit</a>
          </div>
        </div>
      </div>

    </div>
  </div>

  <section class="hw-mission">
    <span class="hw-eyebrow">What We Actually Do Differently</span>
    <h2>Your reference number comes with a real seat count behind it.</h2>
    <p>Most schools tell you a program is "open" and let you find out at enrollment whether that's true. We publish total units and program status before you apply, assign sections at document verification instead of after a waiting period, and post a fee breakdown you can read before you're standing at the Treasury window.</p>
  </section>

  <section id="programs" class="hw-mission" style="padding-top:0;">
    <span class="hw-eyebrow" style="color:var(--hw-accent);">Open For Enrollment</span>
    <h2 style="margin-bottom:36px;">Programs</h2>
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:18px; text-align:left;">
      <?php if (empty($courses)): ?>
        <p style="color:var(--hw-ink-soft);">No programs currently open for enrollment.</p>
      <?php else: ?>
        <?php foreach ($courses as $c): ?>
          <div style="border:1px solid rgba(19,27,58,0.14); border-radius:14px; padding:22px;" onclick="openApplyModal(<?= (int)$c['course_id'] ?>, '<?= htmlspecialchars(addslashes($c['course_name'])) ?>')">
            <div style="font-family:var(--hw-font-mono); font-size:11px; color:var(--hw-secondary-text); text-transform:uppercase; margin-bottom:8px;"><?= htmlspecialchars(course_monogram($c['course_name'])) ?></div>
            <div style="font-family:var(--hw-font-display); font-size:17px; margin-bottom:6px;"><?= htmlspecialchars($c['course_name']) ?></div>
            <div style="font-size:12.5px; color:var(--hw-ink-soft);"><?= (int)$c['total_units'] ?> Total Units</div>
            <button type="button" style="margin-top:14px; background:none; border:none; padding:0; font-weight:700; color:var(--hw-secondary-text); cursor:pointer; font-family:var(--hw-font-body);" onclick="event.stopPropagation(); openApplyModal(<?= (int)$c['course_id'] ?>, '<?= htmlspecialchars(addslashes($c['course_name'])) ?>')">Apply →</button>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>

  <section class="hw-testimonial-band" id="life">
    <div class="hw-testimonial-card hw-card" id="hwTestimonialCard">
      <div class="hw-quote-mark">"</div>
      <p id="hwTestimonialText">The online form took less time than I expected, and I had my reference number right away.</p>
      <cite id="hwTestimonialCite">— J. Mercado, BS Criminology, 1st Year</cite>
      <div class="hw-testimonial-dots" id="hwTestimonialDots"></div>
    </div>
  </section>

  <footer class="hw-footer">
    © 2026 EduSchool. This is a preview mockup — the 3D hallway is a stylized, generic corridor (not a scan of the real campus), and testimonials are placeholders. Replace both before deploying.
  </footer>

  <div id="applyModal" class="hw-modal-overlay">
    <div class="hw-modal-box hw-card">
      <h3 id="applyModalTitle">Apply for this program?</h3>
      <p>You'll be taken to the application form with this program pre-selected.</p>
      <div style="display:flex; gap:12px; justify-content:flex-end;">
        <button type="button" class="hw-btn hw-btn-outline" onclick="closeApplyModal()">Cancel</button>
        <a id="applyModalConfirm" href="#" class="hw-btn">Continue</a>
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

    (function () {
      var navWrap = document.getElementById('hwNavWrap');
      if (!navWrap) return;
      window.addEventListener('scroll', function () {
        navWrap.classList.toggle('scrolled', window.scrollY > 40);
      }, { passive: true });
    })();

    // Testimonial carousel — plain interval + fade, no library needed.
    (function () {
      var quotes = [
        { text: 'The online form took less time than I expected, and I had my reference number right away.', cite: '— J. Mercado, BS Criminology, 1st Year' },
        { text: 'Knowing the fee breakdown ahead of time meant no surprises when I got to Treasury.', cite: '— A. Reyes, Transferee' },
        { text: 'Admissions staff walked me through document verification without any back-and-forth.', cite: '— K. Santos, Returning Student' }
      ];
      var textEl = document.getElementById('hwTestimonialText');
      var citeEl = document.getElementById('hwTestimonialCite');
      var dotsEl = document.getElementById('hwTestimonialDots');
      if (!textEl || !dotsEl) return;

      var current = 0;
      quotes.forEach(function (q, i) {
        var dot = document.createElement('button');
        dot.type = 'button';
        dot.setAttribute('aria-label', 'Show testimonial ' + (i + 1));
        if (i === 0) dot.classList.add('active');
        dot.addEventListener('click', function () { show(i); });
        dotsEl.appendChild(dot);
      });

      function show(i) {
        current = i;
        textEl.textContent = quotes[i].text;
        citeEl.textContent = quotes[i].cite;
        Array.prototype.forEach.call(dotsEl.children, function (d, di) {
          d.classList.toggle('active', di === i);
        });
      }

      var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      if (!reducedMotion) {
        setInterval(function () { show((current + 1) % quotes.length); }, 6000);
      }
    })();
  </script>

  <script type="module">
    import * as THREE from 'three';
    import { RoomEnvironment } from 'three/addons/environments/RoomEnvironment.js';

    var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var isSmallScreen = window.innerWidth < 760;
    var canvas = document.getElementById('hwScene');
    var hallway = document.getElementById('hwHallway');

    if (reducedMotion || isSmallScreen) {
      document.body.classList.add('hw-reduced');
    }

    if (!canvas || !hallway) {
      // No canvas/hallway found: nothing to render, page still works.
    } else {
      run();
    }

    function run() {
      var renderer;
      try {
        renderer = new THREE.WebGLRenderer({ canvas: canvas, alpha: false, antialias: true });
      } catch (e) {
        return; // no WebGL support -- page still works without the scene
      }

      var scene = new THREE.Scene();
      var camera = new THREE.PerspectiveCamera(50, window.innerWidth / window.innerHeight, 0.1, 200);

      renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
      renderer.setSize(window.innerWidth, window.innerHeight);
      renderer.toneMapping = THREE.ACESFilmicToneMapping;
      renderer.toneMappingExposure = 1.1;

      // Procedural environment map (three.js's own RoomEnvironment technique)
      // so the glass panes refract something believable, without needing to
      // host and load an external .hdr file.
      var pmrem = new THREE.PMREMGenerator(renderer);
      var envTexture = pmrem.fromScene(new RoomEnvironment(), 0.04).texture;
      scene.environment = envTexture;

      var navy = 0x131b3a, navyWall = 0x1b2650, cream = 0xf7f9fc, amber = 0xe8a33d, sage = 0x6b8f71, glassTint = 0xeaf2ff;

      // ---------- corridor shell ----------
      var corridorLength = 70;
      var corridorWidth = 8;
      var corridorHeight = 6;

      var wallMat = new THREE.MeshStandardMaterial({ color: navyWall, roughness: 0.85, metalness: 0.05 });
      var floor = new THREE.Mesh(new THREE.BoxGeometry(corridorWidth, 0.3, corridorLength), wallMat);
      floor.position.set(0, -corridorHeight / 2, -corridorLength / 2);
      scene.add(floor);
      var ceiling = floor.clone();
      ceiling.position.y = corridorHeight / 2;
      scene.add(ceiling);
      var wallL = new THREE.Mesh(new THREE.BoxGeometry(0.3, corridorHeight, corridorLength), wallMat);
      wallL.position.set(-corridorWidth / 2, 0, -corridorLength / 2);
      scene.add(wallL);
      var wallR = wallL.clone();
      wallR.position.x = corridorWidth / 2;
      scene.add(wallR);

      // Thin emissive ceiling strip lights, spaced down the hallway.
      var stripMat = new THREE.MeshStandardMaterial({ color: cream, emissive: cream, emissiveIntensity: 1.4, roughness: 0.4 });
      for (var s = 0; s < 6; s++) {
        var strip = new THREE.Mesh(new THREE.BoxGeometry(1.6, 0.05, 1.6), stripMat);
        strip.position.set(0, corridorHeight / 2 - 0.05, -s * 12 - 4);
        scene.add(strip);
        var stripLight = new THREE.PointLight(0xffffff, 6, 14);
        stripLight.position.copy(strip.position).setY(corridorHeight / 2 - 0.6);
        scene.add(stripLight);
      }

      scene.add(new THREE.AmbientLight(0xffffff, 0.35));

      // ---------- glass window panes + vignette objects ----------
      // One pane per side per stop, alternating left/right down the hall,
      // each with a shallow lit alcove behind it holding a subject object.
      var glassMat = new THREE.MeshPhysicalMaterial({
        color: glassTint, transmission: 1, thickness: 0.6, roughness: 0.06,
        ior: 1.5, envMapIntensity: 1, metalness: 0
      });

      function addAlcove(zPos, side, accentColor, buildObject) {
        var wallX = side === 'left' ? -corridorWidth / 2 : corridorWidth / 2;
        var dir = side === 'left' ? -1 : 1;

        var alcoveDepth = 1.6;
        var alcove = new THREE.Mesh(
          new THREE.BoxGeometry(alcoveDepth, 3, 3),
          new THREE.MeshStandardMaterial({ color: navy, roughness: 0.8 })
        );
        alcove.position.set(wallX + dir * alcoveDepth / 2, 0, zPos);
        scene.add(alcove);

        var pane = new THREE.Mesh(new THREE.BoxGeometry(0.08, 2.6, 2.6), glassMat);
        pane.position.set(wallX + dir * 0.04, 0, zPos);
        scene.add(pane);

        var accentLight = new THREE.PointLight(accentColor, 4, 6);
        accentLight.position.set(wallX + dir * alcoveDepth * 0.8, 0, zPos);
        scene.add(accentLight);

        var obj = buildObject();
        obj.position.set(wallX + dir * alcoveDepth * 0.75, 0, zPos);
        scene.add(obj);
      }

      // Academics / geography — a small globe.
      addAlcove(-14, 'left', 0x93c5fd, function () {
        var group = new THREE.Group();
        var sphere = new THREE.Mesh(
          new THREE.IcosahedronGeometry(0.6, 2),
          new THREE.MeshStandardMaterial({ color: sage, roughness: 0.5, flatShading: true })
        );
        group.add(sphere);
        var ring = new THREE.Mesh(
          new THREE.TorusGeometry(0.78, 0.02, 8, 40),
          new THREE.MeshStandardMaterial({ color: cream, roughness: 0.4 })
        );
        ring.rotation.x = Math.PI / 2.4;
        group.add(ring);
        return group;
      });

      // Library — a small stack of books.
      addAlcove(-26, 'right', 0xf5ce8c, function () {
        var group = new THREE.Group();
        var bookColors = [amber, sage, navy, cream];
        bookColors.forEach(function (color, i) {
          var book = new THREE.Mesh(
            new THREE.BoxGeometry(1.0 - i * 0.08, 0.18, 0.7),
            new THREE.MeshStandardMaterial({ color: color, roughness: 0.6, flatShading: true })
          );
          book.position.y = -0.5 + i * 0.2;
          book.rotation.y = i * 0.12;
          group.add(book);
        });
        return group;
      });

      // Arts & culture — a small string of lights on a gentle curve.
      addAlcove(-38, 'left', 0xe8a33d, function () {
        var group = new THREE.Group();
        var curve = new THREE.QuadraticBezierCurve3(
          new THREE.Vector3(-0.7, 0.5, 0),
          new THREE.Vector3(0, -0.2, 0.3),
          new THREE.Vector3(0.7, 0.5, 0)
        );
        var bulbMat = new THREE.MeshStandardMaterial({ color: amber, emissive: amber, emissiveIntensity: 1.2 });
        for (var i = 0; i <= 8; i++) {
          var t = i / 8;
          var p = curve.getPoint(t);
          var bulb = new THREE.Mesh(new THREE.SphereGeometry(0.05, 8, 8), bulbMat);
          bulb.position.copy(p);
          group.add(bulb);
        }
        return group;
      });

      // Athletics — a simple trophy.
      addAlcove(-50, 'right', 0xf0be6e, function () {
        var group = new THREE.Group();
        var goldMat = new THREE.MeshStandardMaterial({ color: amber, roughness: 0.3, metalness: 0.6 });
        var cup = new THREE.Mesh(new THREE.SphereGeometry(0.35, 12, 12, 0, Math.PI * 2, 0, Math.PI * 0.7), goldMat);
        cup.position.y = 0.35;
        group.add(cup);
        var stem = new THREE.Mesh(new THREE.CylinderGeometry(0.05, 0.05, 0.5, 8), goldMat);
        stem.position.y = -0.1;
        group.add(stem);
        var base = new THREE.Mesh(new THREE.CylinderGeometry(0.28, 0.32, 0.14, 12), goldMat);
        base.position.y = -0.4;
        group.add(base);
        return group;
      });

      // Bright light at the far end -- "the hallway opens into light".
      var endLight = new THREE.PointLight(0xffffff, 12, 20);
      endLight.position.set(0, 0, -corridorLength + 4);
      scene.add(endLight);
      var endGlow = new THREE.Mesh(
        new THREE.PlaneGeometry(corridorWidth, corridorHeight),
        new THREE.MeshStandardMaterial({ color: 0xffffff, emissive: 0xffffff, emissiveIntensity: 1.6 })
      );
      endGlow.position.set(0, 0, -corridorLength + 0.2);
      scene.add(endGlow);

      camera.position.set(0, 0, 6);

      function resize() {
        camera.aspect = window.innerWidth / window.innerHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(window.innerWidth, window.innerHeight);
      }
      window.addEventListener('resize', resize, { passive: true });

      // ---------- scroll-driven camera dolly ----------
      // One waypoint per hallway stop; camera glides forward (-Z) and
      // yaws slightly toward whichever side window is active.
      var waypoints = [
        { pos: [0, 0, 6], look: [0, 0, -8] },
        { pos: [-0.6, 0, -8], look: [-2.4, 0, -14] },
        { pos: [0.6, 0, -20], look: [2.4, 0, -26] },
        { pos: [-0.6, 0, -32], look: [-2.4, 0, -38] },
        { pos: [0.6, 0, -44], look: [2.4, 0, -50] },
        { pos: [0, 0.4, -58], look: [0, 0, -corridorLength] }
      ];
      var cardCenters = [0, 0.18, 0.36, 0.54, 0.72, 0.92];
      var cardWindow = 0.16;
      var cards = Array.prototype.slice.call(document.querySelectorAll('.hw-window-stop .hw-card'));

      function sampleWaypoints(t) {
        var segCount = waypoints.length - 1;
        var scaled = Math.min(Math.max(t, 0), 1) * segCount;
        var idx = Math.min(Math.floor(scaled), segCount - 1);
        var localT = scaled - idx;
        var a = waypoints[idx], b = waypoints[idx + 1];
        return {
          pos: [0, 1, 2].map(function (k) { return a.pos[k] + (b.pos[k] - a.pos[k]) * localT; }),
          look: [0, 1, 2].map(function (k) { return a.look[k] + (b.look[k] - a.look[k]) * localT; })
        };
      }

      function applyFrame(t) {
        var frame = sampleWaypoints(t);
        camera.position.set(frame.pos[0], frame.pos[1], frame.pos[2]);
        camera.lookAt(frame.look[0], frame.look[1], frame.look[2]);
        cards.forEach(function (card, i) {
          var dist = Math.abs(t - cardCenters[i]);
          var opacity = Math.max(0, 1 - dist / cardWindow);
          card.style.opacity = opacity;
        });
      }

      if (document.body.classList.contains('hw-reduced')) {
        // Static single frame at the hero position; cards are shown fully
        // via CSS (position:static stacking), not animated here at all.
        applyFrame(0);
        renderer.render(scene, camera);
        return;
      }

      if (typeof gsap === 'undefined' || typeof ScrollTrigger === 'undefined') {
        // GSAP failed to load: still render a live scene, just driven by
        // plain scroll position instead of ScrollTrigger.
        var fallbackProgress = 0;
        window.addEventListener('scroll', function () {
          var max = hallway.offsetHeight - window.innerHeight;
          fallbackProgress = max > 0 ? Math.min(Math.max((window.scrollY - hallway.offsetTop) / max, 0), 1) : 0;
        }, { passive: true });
        (function tick() {
          applyFrame(fallbackProgress);
          renderer.render(scene, camera);
          requestAnimationFrame(tick);
        })();
        return;
      }

      gsap.registerPlugin(ScrollTrigger);
      var scrollProgress = 0;
      var targetProgress = 0;

      ScrollTrigger.create({
        trigger: hallway,
        start: 'top top',
        end: 'bottom bottom',
        scrub: 1,
        onUpdate: function (self) { targetProgress = self.progress; }
      });

      // Mission/testimonial/footer fade-ins after the hallway.
      gsap.utils.toArray('.hw-mission, .hw-testimonial-card').forEach(function (el) {
        gsap.from(el, {
          opacity: 0, y: 24, duration: 0.6, ease: 'power1.out',
          scrollTrigger: { trigger: el, start: 'top 85%', toggleActions: 'play none none reverse' }
        });
      });

      function tick() {
        scrollProgress += (targetProgress - scrollProgress) * 0.08;
        applyFrame(scrollProgress);
        renderer.render(scene, camera);
        requestAnimationFrame(tick);
      }
      tick();
    }
  </script>

</body>
</html>
