// escHtml()/spToast() are shared globally via Frontend/Js/Student/shared.js.
document.addEventListener('DOMContentLoaded', function () {
  const API = '/SIAdrafts/Backend/api/';
  const csrfToken = document.body.dataset.csrf;

  const shell = document.getElementById('quizAttemptShell');
  if (!shell) return;
  const attemptId = shell.dataset.attemptId;

  const STATUS_RESULT_LABEL = {
    submitted: 'Submitted',
    auto_submitted_time: "Time's up — auto-submitted",
    auto_submitted_violations: 'Auto-submitted after repeated tab/fullscreen violations',
  };

  const VIOLATION_LABEL = {
    tab_switch: 'switched to another tab',
    window_blur: 'clicked away from this window',
    fullscreen_exit: 'exited fullscreen',
  };

  // A dedicated <dialog>, not a toast -- a toast auto-dismisses in ~3s and
  // is easy to miss entirely if the student is mid-alt-tab; this has to
  // actually be seen and acknowledged, since it's the thing telling the
  // student the system caught them and how many strikes are left. Built
  // once and appended to <body> (outside #quizAttemptShell) so it
  // survives every renderQuestion() innerHTML replacement.
  function ensureViolationDialog() {
    let dialog = document.getElementById('quizViolationDialog');
    if (dialog) return dialog;
    dialog = document.createElement('dialog');
    dialog.className = 'sp-dialog';
    dialog.id = 'quizViolationDialog';
    dialog.innerHTML =
      '<div class="sp-dialog-body">' +
        '<p class="sp-dialog-title" id="quizViolationTitle"></p>' +
        '<p class="sp-dialog-message" id="quizViolationMessage"></p>' +
        '<div class="sp-quiz-strike-indicator" id="quizViolationDots" style="margin-top:12px;"></div>' +
        '<div class="sp-dialog-actions">' +
          '<button type="button" class="sp-btn sp-btn-primary" id="quizViolationOkBtn">I understand</button>' +
        '</div>' +
      '</div>';
    document.body.appendChild(dialog);
    return dialog;
  }

  function showViolationModal(violationType, count, status) {
    const dialog = ensureViolationDialog();
    const title = document.getElementById('quizViolationTitle');
    const message = document.getElementById('quizViolationMessage');
    const dots = document.getElementById('quizViolationDots');
    const okBtn = document.getElementById('quizViolationOkBtn');

    const action = VIOLATION_LABEL[violationType] || 'left this quiz screen';
    let dotsHtml = '';
    for (let i = 0; i < 3; i++) dotsHtml += '<span class="sp-quiz-strike-dot' + (i < count ? ' is-filled' : '') + '"></span>';
    dots.innerHTML = dotsHtml;

    if (status !== 'in_progress') {
      title.textContent = 'Quiz submitted automatically';
      message.textContent = 'You ' + action + ' one too many times (strike ' + count + ' of 3). Your quiz was submitted automatically with whatever you had answered.';
      okBtn.onclick = () => { dialog.close(); fetchLatestStateAndRenderResult(); };
    } else {
      title.textContent = 'We noticed you ' + action;
      message.textContent = 'This counts as strike ' + count + ' of 3. Reaching 3 strikes automatically submits your quiz as-is, so please stay on this tab and in fullscreen until you submit.';
      okBtn.onclick = () => { dialog.close(); };
    }

    if (!dialog.open) dialog.showModal();
  }

  let deadlineMs = null;
  let countdownTimer = null;
  let questions = [];
  let answers = {};
  let currentIndex = 0;
  let violationCount = 0;
  let fullscreenWasEntered = false;
  let lastViolationAt = 0;
  let inFlightSaves = 0;
  let locked = false;

  // ---------- Single-tab lock (same-browser only; not real multi-device proctoring) ----------
  const tabId = (window.crypto && crypto.randomUUID) ? crypto.randomUUID() : String(Math.random());
  const channel = ('BroadcastChannel' in window) ? new BroadcastChannel('sia-quiz-attempt-' + attemptId) : null;
  let isActiveTab = true;

  function renderLockedScreen() {
    isActiveTab = false;
    locked = true;
    stopCountdown();
    shell.innerHTML =
      '<div class="sp-quiz-locked-notice">' +
        '<iconify-icon icon="mdi:tab-plus"></iconify-icon>' +
        '<p><strong>This quiz is already open in another tab.</strong></p>' +
        '<p>Close this tab and return to the other one to continue.</p>' +
        '<button type="button" class="sp-btn sp-btn-secondary" onclick="window.location.reload()">Reload</button>' +
      '</div>';
  }

  if (channel) {
    channel.addEventListener('message', (e) => {
      const msg = e.data || {};
      if (msg.from === tabId) return;
      if (msg.type === 'ping' && isActiveTab && !locked) {
        channel.postMessage({ type: 'pong', from: tabId });
      } else if (msg.type === 'pong' && isActiveTab && currentIndex === 0 && !document.getElementById('quizQuestionCard')) {
        // Only a tab that hasn't started rendering yet defers to an
        // existing active tab -- prevents a race where two tabs opened at
        // nearly the same instant both back off.
        renderLockedScreen();
      }
    });
    channel.postMessage({ type: 'ping', from: tabId });
  }

  setTimeout(() => {
    if (!locked) initAttempt();
  }, 300);

  // ---------- Loading + rendering ----------
  function initAttempt() {
    fetch(API + 'Quizzes/get_attempt_state.php?attempt_id=' + encodeURIComponent(attemptId))
      .then(r => r.json())
      .then(d => {
        if (d.error) {
          shell.innerHTML = '<div class="sp-empty"><iconify-icon icon="mdi:alert-circle-outline"></iconify-icon><p>' + escHtml(d.error) + '</p></div>';
          return;
        }
        const a = d.attempt;
        questions = a.questions || [];
        answers = a.answers || {};
        violationCount = a.violation_count || 0;

        if (a.status !== 'in_progress') {
            renderResultScreen(a);
            return;
        }

        syncDeadline(a.deadline_at);
        renderBeginScreen();
      })
      .catch(() => {
        shell.innerHTML = '<div class="sp-empty"><iconify-icon icon="mdi:alert-circle-outline"></iconify-icon><p>Could not load this quiz. Please reload.</p></div>';
      });
  }

  function renderBeginScreen() {
    shell.innerHTML =
      '<div class="sp-empty">' +
        '<iconify-icon icon="mdi:timer-outline"></iconify-icon>' +
        '<p><strong>' + questions.length + ' question' + (questions.length === 1 ? '' : 's') + '.</strong></p>' +
        '<p>Once you begin, stay on this tab. Switching tabs, minimizing the window, or leaving fullscreen counts as a strike — 3 strikes auto-submits your quiz.</p>' +
        '<button type="button" class="sp-btn sp-btn-primary" id="beginQuizBtn">Begin quiz</button>' +
      '</div>';
    document.getElementById('beginQuizBtn').addEventListener('click', () => {
      if (document.documentElement.requestFullscreen) {
        document.documentElement.requestFullscreen().then(() => { fullscreenWasEntered = true; }).catch(() => {});
      }
      currentIndex = 0;
      startCountdown();
      wireViolationListeners();
      renderQuestion();
    });
  }

  function renderQuestion() {
    const q = questions[currentIndex];
    const selected = answers[q.question_id] || null;
    const choicesHtml = (q.choices || []).map(c =>
      '<label class="sp-quiz-choice">' +
        '<input type="radio" name="quizChoice" value="' + c.choice_id + '" ' + (String(selected) === String(c.choice_id) ? 'checked' : '') + '>' +
        '<span>' + escHtml(c.choice_text) + '</span>' +
      '</label>'
    ).join('');

    shell.innerHTML =
      '<div class="sp-quiz-attempt-topbar">' +
        '<span class="sp-quiz-progress-stepper">Question ' + (currentIndex + 1) + ' of ' + questions.length + '</span>' +
        '<span class="sp-quiz-strike-indicator" id="strikeIndicator"></span>' +
        '<span class="sp-quiz-timer" id="quizTimer">--:--</span>' +
      '</div>' +
      '<div class="sp-card" id="quizQuestionCard" style="max-width:640px; margin:0 auto;">' +
        '<p style="font-family:var(--font-display); font-size:18px; margin:0 0 16px;">' + escHtml(q.question_text) + '</p>' +
        '<div class="sp-quiz-choice-list">' + choicesHtml + '</div>' +
        '<p class="sp-quiz-save-indicator" id="quizSaveIndicator" hidden>Saved</p>' +
      '</div>' +
      '<div class="sp-quiz-attempt-nav">' +
        '<button type="button" class="sp-btn sp-btn-secondary" id="prevQuestionBtn" ' + (currentIndex === 0 ? 'disabled' : '') + '>Back</button>' +
        (currentIndex < questions.length - 1
          ? '<button type="button" class="sp-btn sp-btn-primary" id="nextQuestionBtn">Next</button>'
          : '<button type="button" class="sp-btn sp-btn-primary" id="submitQuizBtn">Submit quiz</button>') +
      '</div>';

    renderStrikeIndicator();
    renderTimer();

    document.querySelectorAll('input[name="quizChoice"]').forEach(input => {
      input.addEventListener('change', () => saveAnswer(q.question_id, input.value));
    });
    document.getElementById('prevQuestionBtn').addEventListener('click', () => { currentIndex--; renderQuestion(); });
    const nextBtn = document.getElementById('nextQuestionBtn');
    if (nextBtn) nextBtn.addEventListener('click', () => { currentIndex++; renderQuestion(); });
    const submitBtn = document.getElementById('submitQuizBtn');
    if (submitBtn) submitBtn.addEventListener('click', () => submitAttempt('manual'));
  }

  function renderStrikeIndicator() {
    const el = document.getElementById('strikeIndicator');
    if (!el) return;
    let dots = '';
    for (let i = 0; i < 3; i++) {
      dots += '<span class="sp-quiz-strike-dot' + (i < violationCount ? ' is-filled' : '') + '"></span>';
    }
    el.innerHTML = dots;
  }

  function renderResultScreen(a) {
    stopCountdown();
    const label = STATUS_RESULT_LABEL[a.status] || 'Submitted';
    shell.innerHTML =
      '<div class="sp-empty">' +
        '<iconify-icon icon="' + (a.status === 'submitted' ? 'mdi:check-circle-outline' : 'mdi:alert-outline') + '"></iconify-icon>' +
        '<p><strong>' + escHtml(label) + '</strong></p>' +
        '<p>Score: ' + a.score + ' / ' + a.max_score + '</p>' +
        '<a class="sp-btn sp-btn-primary" href="/SIAdrafts/Frontend/View/Student/my_courses.php">Back to My Courses</a>' +
      '</div>';
    if (document.fullscreenElement) document.exitFullscreen().catch(() => {});
  }

  // ---------- Autosave ----------
  function saveAnswer(questionId, choiceId) {
    answers[questionId] = choiceId;
    inFlightSaves++;
    const indicator = document.getElementById('quizSaveIndicator');
    const body = new FormData();
    body.append('attempt_id', attemptId);
    body.append('question_id', questionId);
    body.append('choice_id', choiceId);
    body.append('csrf_token', csrfToken);

    fetch(API + 'Quizzes/autosave_answer.php', { method: 'POST', body })
      .then(r => r.json())
      .then(d => {
        if (d.error) {
          if (d.status) { fetchLatestStateAndRenderResult(); return; }
          if (window.spToast) window.spToast(d.error, 'mdi:alert-circle-outline');
          return;
        }
        if (d.deadline_at) syncDeadline(d.deadline_at);
        if (indicator) {
          indicator.hidden = false;
          setTimeout(() => { indicator.hidden = true; }, 1200);
        }
      })
      .catch(() => { if (window.spToast) window.spToast('Could not save your answer.', 'mdi:alert-circle-outline'); })
      .finally(() => { inFlightSaves--; });
  }

  function fetchLatestStateAndRenderResult() {
    fetch(API + 'Quizzes/get_attempt_state.php?attempt_id=' + encodeURIComponent(attemptId))
      .then(r => r.json())
      .then(d => { if (d.attempt) renderResultScreen(d.attempt); });
  }

  // ---------- Server-authoritative countdown (client is a UX nicety only) ----------
  function syncDeadline(deadlineAt) {
    // MySQL DATETIME strings have no timezone marker; the server's own
    // config.php sets Asia/Manila, so treat this as a local wall-clock
    // string rather than parsing it as UTC.
    deadlineMs = new Date(deadlineAt.replace(' ', 'T')).getTime();
  }

  function startCountdown() {
    stopCountdown();
    countdownTimer = setInterval(tick, 250);
    tick();
  }

  function stopCountdown() {
    if (countdownTimer) { clearInterval(countdownTimer); countdownTimer = null; }
  }

  function tick() {
    renderTimer();
    if (deadlineMs !== null && Date.now() >= deadlineMs) {
      stopCountdown();
      submitAttempt('time_expired');
    }
  }

  function renderTimer() {
    const el = document.getElementById('quizTimer');
    if (!el || deadlineMs === null) return;
    const remainingMs = Math.max(0, deadlineMs - Date.now());
    const totalSeconds = Math.floor(remainingMs / 1000);
    const mm = String(Math.floor(totalSeconds / 60)).padStart(2, '0');
    const ss = String(totalSeconds % 60).padStart(2, '0');
    el.textContent = mm + ':' + ss;
    el.classList.toggle('is-warning', totalSeconds <= 60);
  }

  // ---------- Violation detection ----------
  function reportViolation(type) {
    const now = Date.now();
    if (now - lastViolationAt < 1000) return;
    lastViolationAt = now;

    const body = new FormData();
    body.append('attempt_id', attemptId);
    body.append('violation_type', type);
    body.append('csrf_token', csrfToken);

    fetch(API + 'Quizzes/log_violation.php', { method: 'POST', body })
      .then(r => r.json())
      .then(d => {
        if (d.error) return;
        violationCount = d.violation_count;
        renderStrikeIndicator();
        showViolationModal(type, violationCount, d.status);
      })
      .catch(() => {});
  }

  function wireViolationListeners() {
    document.addEventListener('visibilitychange', onVisibilityChange);
    window.addEventListener('blur', onWindowBlur);
    document.addEventListener('fullscreenchange', onFullscreenChange);
  }

  function onVisibilityChange() { if (document.hidden && !locked) reportViolation('tab_switch'); }
  function onWindowBlur() { if (!locked) reportViolation('window_blur'); }
  function onFullscreenChange() {
    if (!document.fullscreenElement && fullscreenWasEntered && !locked) reportViolation('fullscreen_exit');
  }

  // ---------- Submit ----------
  function submitAttempt(reason) {
    stopCountdown();
    const body = new FormData();
    body.append('attempt_id', attemptId);
    body.append('reason', reason);
    body.append('csrf_token', csrfToken);

    fetch(API + 'Quizzes/submit_attempt.php', { method: 'POST', body })
      .then(r => r.json())
      .then(d => {
        if (d.error && d.status === undefined) {
          if (window.spToast) window.spToast(d.error, 'mdi:alert-circle-outline');
          return;
        }
        renderResultScreen(d);
      })
      .catch(() => { if (window.spToast) window.spToast('Could not submit your quiz. Please try again.', 'mdi:alert-circle-outline'); });
  }

  window.addEventListener('beforeunload', (e) => {
    if (!locked && document.getElementById('quizQuestionCard')) {
      e.preventDefault();
      e.returnValue = '';
    }
  });
});
