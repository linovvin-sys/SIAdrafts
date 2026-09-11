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
<?php
// Cache-buster tied to the file's real last-modified time — every edit to
// this stylesheet changes the URL automatically, so the browser is forced
// to fetch the new version instead of serving a stale cached copy. This
// class of "I changed the CSS but the page still looks old" bug has come
// up repeatedly; this is the actual fix, not another reminder to hard-refresh.
$themeCssPath = __DIR__ . '/../Css/editorial-theme.css';
$themeCssVer  = file_exists($themeCssPath) ? filemtime($themeCssPath) : time();
?>
<link rel="stylesheet" href="/SIAdrafts/Frontend/Css/editorial-theme.css?v=<?= $themeCssVer ?>">
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
      <a class="e-brand" href="#top">
        <img class="e-brand-mark" src="/SIAdrafts/Frontend/assets/crest.svg" alt="" width="30" height="30">
        <span>Edu<em>School</em></span>
      </a>

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

  <div class="e-hero-photo" aria-hidden="true"></div>

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
        <div class="e-faq-body">There's no self-service way to do this yet, even at the Admissions counter — it currently needs a manual correction on our end. Contact the Admissions office and they'll get it sorted.</div>
      </div>
      <div class="e-faq-item e-reveal e-reveal--up-sm" style="transition-delay:240ms">
        <button type="button" class="e-faq-btn">
          <span>What programs do you offer?</span>
          <span class="e-faq-mark">+</span>
        </button>
        <div class="e-faq-body">See the Programs section above for the current list open for enrollment — it's kept up to date there directly.</div>
      </div>
      <div class="e-faq-item e-reveal e-reveal--up-sm" style="transition-delay:300ms">
        <button type="button" class="e-faq-btn">
          <span>How much is the tuition?</span>
          <span class="e-faq-mark">+</span>
        </button>
        <div class="e-faq-body">Tuition varies by program and year level. Your exact fee breakdown is generated once your enrollment is confirmed — Treasury or Admissions can also walk you through it beforehand.</div>
      </div>
      <div class="e-faq-item e-reveal e-reveal--up-sm" style="transition-delay:360ms">
        <button type="button" class="e-faq-btn">
          <span>Can I apply in person instead of online?</span>
          <span class="e-faq-mark">+</span>
        </button>
        <div class="e-faq-body">Yes — visit the Admissions counter and staff can take your application and documents in person, no online form required.</div>
      </div>
      <div class="e-faq-item e-reveal e-reveal--up-sm" style="transition-delay:420ms">
        <button type="button" class="e-faq-btn">
          <span>How do I check my application status?</span>
          <span class="e-faq-mark">+</span>
        </button>
        <div class="e-faq-body">Visit or contact the Admissions office with your reference number. Your status updates to "verified" once document verification is complete.</div>
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

  <!-- FAQ chat widget -->
  <button type="button" class="e-chat-toggle" id="chatToggle" aria-expanded="false" aria-controls="chatPanel" title="Ask a question">
    <span class="e-chat-toggle-face" aria-hidden="true">
      <span class="e-chat-socket"><span class="e-chat-eye"></span></span>
      <span class="e-chat-socket"><span class="e-chat-eye"></span></span>
    </span>
    <svg class="e-chat-close-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
  </button>

  <div class="e-chat-scrim" id="chatScrim" aria-hidden="true"></div>

  <div class="e-chat-panel" id="chatPanel" role="dialog" aria-label="Admissions FAQ chat">
    <div class="e-chat-head">
      <div class="e-chat-avatar" id="chatAvatar" aria-hidden="true">
        <span class="e-chat-socket"><span class="e-chat-eye"></span></span>
        <span class="e-chat-socket"><span class="e-chat-eye"></span></span>
      </div>
      <span class="e-chat-name">Dotty</span>
    </div>
    <div class="e-chat-log" id="chatLog"></div>
    <form class="e-chat-form" id="chatForm">
      <input type="text" class="e-chat-input" id="chatInput" placeholder="Type a question…" maxlength="500" autocomplete="off">
      <button type="submit" class="e-chat-send" id="chatSend">Send</button>
    </form>
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

    // FAQ chat widget — plain fetch to the server-side proxy, which holds
    // the Gemini key and grounds every answer in the FAQ content below.
    (function () {
      var toggle = document.getElementById('chatToggle');
      var panel  = document.getElementById('chatPanel');
      var log    = document.getElementById('chatLog');
      var form   = document.getElementById('chatForm');
      var input  = document.getElementById('chatInput');
      var send   = document.getElementById('chatSend');
      var avatar = document.getElementById('chatAvatar');
      var scrim  = document.getElementById('chatScrim');
      var opened = false;
      var sending = false;
      var history = []; // {role: 'user'|'model', text: string} — errors are never added, only real turns

      // Dotty reacts to what's actually happening instead of animating on a
      // fixed loop regardless of context — eyes glance toward the input
      // while you're typing (real signal: "I see you writing"), and a quick
      // double-blink fires the instant a message sends, before the typing
      // dots even appear.
      input.addEventListener('input', function () {
        avatar.classList.toggle('e-chat-avatar--attentive', input.value.length > 0);
      });
      input.addEventListener('blur', function () {
        avatar.classList.remove('e-chat-avatar--attentive');
      });
      function avatarBlink(afterCb) {
        // The burst animation (380ms, not infinite) overrides the idle loop
        // by specificity while this class is present — remove it once it's
        // done playing, or the idle blink would silently stop forever after
        // the very first message. afterCb (optional) runs once it's done —
        // used to sequence the send-blink before the thinking-drift starts,
        // since both target the same element/property and would otherwise
        // silently fight (equal specificity, one just wins by source order).
        avatar.classList.remove('e-chat-avatar--blink');
        void avatar.offsetWidth;
        avatar.classList.add('e-chat-avatar--blink');
        setTimeout(function () {
          avatar.classList.remove('e-chat-avatar--blink');
          if (afterCb) afterCb();
        }, 400);
      }

      // A slower, more deliberate blink for when he's declining/unsure —
      // distinct from the quick confident one above, so his face actually
      // tracks what he's saying instead of reacting identically either way.
      function avatarUnsure() {
        avatar.classList.remove('e-chat-avatar--unsure');
        void avatar.offsetWidth;
        avatar.classList.add('e-chat-avatar--unsure');
        setTimeout(function () { avatar.classList.remove('e-chat-avatar--unsure'); }, 700);
      }
      // Broad on purpose — the personality prompt varies his phrasing
      // ("not sure", "doesn't mention", "reach out to Admissions"...), so a
      // single exact string would miss most real declines. False positives
      // here just mean an occasional confident answer gets the slower
      // blink instead of the quick one — low stakes either way.
      var DECLINE_PATTERN = /not sure|don'?t know|do not know|doesn'?t (mention|cover|say|list)|reach out|contact.{0,30}admissions/i;

      // Idle micro-glances — a rare, small look to one side while the
      // panel's just sitting open with nothing happening, so he reads as
      // present rather than a static prop between messages. Paused
      // whenever he's actively reacting to something real (typing,
      // sending, thinking) so it never fights those states.
      var idleGlanceTimer = null;
      function scheduleIdleGlance() {
        clearTimeout(idleGlanceTimer);
        idleGlanceTimer = setTimeout(function () {
          if (opened && !sending && input.value === '') {
            var dir = Math.random() < 0.5 ? 'e-chat-avatar--glance-left' : 'e-chat-avatar--glance-right';
            avatar.classList.add(dir);
            setTimeout(function () { avatar.classList.remove(dir); }, 900);
          }
          scheduleIdleGlance();
        }, 9000 + Math.random() * 6000);
      }

      // Same socket+eye structure as the header avatar, just smaller — one
      // shared string so Dotty's face reads as the same character next to
      // every reply, not just once at the top of the panel.
      var MINI_AVATAR_HTML =
        '<span class="e-chat-avatar-mini" aria-hidden="true">' +
          '<span class="e-chat-socket"><span class="e-chat-eye"></span></span>' +
          '<span class="e-chat-socket"><span class="e-chat-eye"></span></span>' +
        '</span>';

      function addMessage(text, kind) {
        var msg = document.createElement('div');
        msg.className = 'e-chat-msg e-chat-msg--' + kind;
        msg.textContent = text;

        // User's own messages don't get an avatar — only Dotty's replies
        // (and the error state, since that's still "him" talking) do.
        if (kind === 'user') {
          msg.classList.add('e-chat-msg-in');
          log.appendChild(msg);
          log.scrollTop = log.scrollHeight;
          return msg;
        }

        var row = document.createElement('div');
        row.className = 'e-chat-row e-chat-msg-in';
        row.innerHTML = MINI_AVATAR_HTML;
        row.appendChild(msg);
        log.appendChild(row);
        log.scrollTop = log.scrollHeight;
        return row;
      }

      function addTyping() {
        var bubble = document.createElement('div');
        bubble.className = 'e-chat-typing';
        bubble.innerHTML = '<span></span><span></span><span></span>';

        var row = document.createElement('div');
        row.className = 'e-chat-row e-chat-msg-in';
        row.innerHTML = MINI_AVATAR_HTML;
        row.appendChild(bubble);
        log.appendChild(row);
        log.scrollTop = log.scrollHeight;
        return row;
      }

      toggle.addEventListener('click', function () {
        opened = !opened;
        toggle.classList.toggle('open', opened);
        toggle.setAttribute('aria-expanded', opened ? 'true' : 'false');
        panel.classList.toggle('open', opened);
        scrim.classList.toggle('open', opened);
        if (opened) {
          if (!log.children.length) {
            addMessage("Hi, I'm Dotty! Ask me anything about applying or enrolling — I can help with the basics on this page.", 'bot');
          }
          input.focus();
          scheduleIdleGlance();
        } else {
          clearTimeout(idleGlanceTimer);
        }
      });

      // Tapping the dimmed blue backdrop closes the chat, same as any modal.
      scrim.addEventListener('click', function () {
        if (opened) toggle.click();
      });

      form.addEventListener('submit', function (e) {
        e.preventDefault();
        var message = input.value.trim();
        if (!message || sending) return;

        addMessage(message, 'user');
        input.value = '';
        input.classList.remove('e-chat-sent');
        void input.offsetWidth; // restart the animation if fired again before it finished
        input.classList.add('e-chat-sent');
        avatar.classList.remove('e-chat-avatar--attentive');
        sending = true;
        avatarBlink(function () {
          // Guard: the response may have already arrived (and cleared
          // `sending`) before this 400ms delay elapses on a fast network —
          // don't turn "thinking" back on after the fact if so.
          if (sending) avatar.classList.add('e-chat-avatar--thinking');
        });
        send.disabled = true;
        var typing = addTyping();

        fetch('/SIAdrafts/Backend/api/Chat/ask_faq.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ message: message, history: history }),
        })
          .then(function (res) { return res.json(); })
          .then(function (data) {
            typing.remove();
            if (data.error) {
              // A failed turn isn't added to history — nothing for Gemini
              // to have "said", so there's nothing to remember here.
              addMessage(data.error, 'error');
              avatarUnsure();
            } else {
              addMessage(data.reply, 'bot');
              history.push({ role: 'user', text: message });
              history.push({ role: 'model', text: data.reply });
              // His face tracks what he actually said, not just that he
              // said something — a decline gets the slower, deliberate
              // reaction, a real answer gets the quick confident one.
              if (DECLINE_PATTERN.test(data.reply)) avatarUnsure();
              else avatarBlink();
            }
          })
          .catch(function () {
            typing.remove();
            addMessage("Hmm, I lost my train of thought there. Mind trying that again?", 'error');
            avatarUnsure();
          })
          .finally(function () {
            sending = false;
            send.disabled = false;
            avatar.classList.remove('e-chat-avatar--thinking');
            scheduleIdleGlance();
          });
      });
    })();

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
