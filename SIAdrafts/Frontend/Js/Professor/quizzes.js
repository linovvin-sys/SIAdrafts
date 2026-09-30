// escHtml()/spToast()/spConfirm() are shared globally via Frontend/Js/Student/shared.js.
document.addEventListener('DOMContentLoaded', function () {
  const API = '/SIAdrafts/Backend/api/';
  const csrfToken = document.body.dataset.csrf;

  const classSelect   = document.getElementById('quizClassSelect');
  const emptyState     = document.getElementById('quizEmptyState');
  const panel           = document.getElementById('quizPanel');
  const quizForm         = document.getElementById('quizForm');
  const quizList          = document.getElementById('quizList');
  const errBox              = document.getElementById('quizFormError');
  const errMsg               = document.getElementById('quizFormErrorMsg');
  const postBtn                = document.getElementById('quizPostBtn');
  const cancelEditBtn           = document.getElementById('cancelQuizEditBtn');

  if (!classSelect) return;

  let currentScheduleId = null;
  let editingQuizId = null;

  function showError(box, msgEl, message) {
    box.classList.remove('is-visible');
    void box.offsetWidth;
    msgEl.textContent = message;
    box.classList.add('is-visible');
  }

  function fmtWindow(from, until) {
    if (!from && !until) return 'Always open';
    const f = from ? new Date(from.replace(' ', 'T')).toLocaleString([], { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' }) : '…';
    const u = until ? new Date(until.replace(' ', 'T')).toLocaleString([], { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' }) : '…';
    return 'Opens ' + f + ' — Closes ' + u;
  }

  function renderQuizzes(quizzes) {
    if (!quizzes || quizzes.length === 0) {
      quizList.innerHTML = '<li class="sp-announce-empty">No quizzes posted for this class yet.</li>';
      return;
    }
    quizList.innerHTML = quizzes.map((q, i) => {
      const avg = q.avg_score_ratio !== null ? Math.round(q.avg_score_ratio * 100) + '% avg' : 'No attempts yet';
      const perAttempt = q.questions_per_attempt ? (q.questions_per_attempt + ' of ' + q.question_count + ' per attempt') : (q.question_count + ' question' + (q.question_count === 1 ? '' : 's'));
      const meta = perAttempt + ' · ' + q.time_limit_minutes + ' min · ' + fmtWindow(q.available_from, q.available_until) + ' · ' + avg;
      const statusPill = '<span class="sp-pill ' + (q.status === 'published' ? 'enrolled' : 'pending') + '" style="margin-right:8px;">' + (q.status === 'published' ? 'Published' : 'Draft') + '</span>';
      return '<li class="sp-announce-item" style="--row-i:' + i + '" data-quiz-id="' + q.quiz_id + '">' +
        '<div class="sp-announce-item-head">' +
          statusPill +
          '<strong>' + escHtml(q.title) + '</strong>' +
          '<span class="sp-announce-item-date">' + escHtml(meta) + '</span>' +
          '<button type="button" class="sp-announce-delete" data-edit-quiz="' + q.quiz_id + '" aria-label="Edit quiz">' +
            '<iconify-icon icon="mdi:pencil-outline"></iconify-icon>' +
          '</button>' +
          '<button type="button" class="sp-announce-delete" data-delete-quiz="' + q.quiz_id + '" aria-label="Delete quiz">' +
            '<iconify-icon icon="mdi:trash-can-outline"></iconify-icon>' +
          '</button>' +
        '</div>' +
        (q.instructions ? '<p>' + escHtml(q.instructions).replace(/\n/g, '<br>') + '</p>' : '') +
        '<div style="display:flex; gap:8px; margin-top:8px; flex-wrap:wrap;">' +
          '<button type="button" class="sp-table-action" data-manage-questions="' + q.quiz_id + '" data-quiz-title="' + escHtml(q.title) + '">' +
            '<iconify-icon icon="mdi:format-list-checks"></iconify-icon> Questions' +
          '</button>' +
          '<button type="button" class="sp-table-action" data-view-roster="' + q.quiz_id + '" data-quiz-title="' + escHtml(q.title) + '">' +
            '<iconify-icon icon="mdi:account-group-outline"></iconify-icon> Results (' + q.attempt_count + ')' +
          '</button>' +
          (q.status === 'published'
            ? '<button type="button" class="sp-table-action" data-unpublish-quiz="' + q.quiz_id + '"><iconify-icon icon="mdi:eye-off-outline"></iconify-icon> Unpublish</button>'
            : '<button type="button" class="sp-table-action is-primary" data-publish-quiz="' + q.quiz_id + '"><iconify-icon icon="mdi:check-circle-outline"></iconify-icon> Publish</button>') +
        '</div>' +
      '</li>';
    }).join('');
  }

  function loadQuizzes(scheduleId) {
    fetch(API + 'Quizzes/get_class_quizzes.php?schedule_id=' + encodeURIComponent(scheduleId))
      .then(r => r.json())
      .then(d => { if (d.error) { showError(errBox, errMsg, d.error); return; } renderQuizzes(d.quizzes); })
      .catch(() => showError(errBox, errMsg, 'Could not load quizzes.'));
  }

  function toDatetimeLocal(sqlDatetime) {
    if (!sqlDatetime) return '';
    return sqlDatetime.replace(' ', 'T').slice(0, 16);
  }

  function resetQuizForm() {
    editingQuizId = null;
    quizForm.reset();
    document.getElementById('quizTimeLimitInput').value = '10';
    document.getElementById('quizQuestionsPerAttemptInput').value = '';
    postBtn.querySelector('.sp-btn-label').textContent = 'Create quiz';
    cancelEditBtn.hidden = true;
  }

  classSelect.addEventListener('change', () => {
    window.spRememberClassSelection?.(classSelect);
    currentScheduleId = classSelect.value || null;
    resetQuizForm();
    if (!currentScheduleId) {
      panel.hidden = true;
      emptyState.hidden = false;
      return;
    }
    panel.hidden = false;
    emptyState.hidden = true;
    errBox.classList.remove('is-visible');
    loadQuizzes(currentScheduleId);
  });

  quizForm.addEventListener('submit', (e) => {
    e.preventDefault();
    if (!currentScheduleId) return;

    postBtn.disabled = true;
    postBtn.querySelector('.sp-btn-spinner').hidden = false;

    const body = new FormData();
    body.append('schedule_id', currentScheduleId);
    body.append('title', document.getElementById('quizTitleInput').value.trim());
    body.append('instructions', document.getElementById('quizInstructionsInput').value.trim());
    body.append('time_limit_minutes', document.getElementById('quizTimeLimitInput').value);
    body.append('available_from', document.getElementById('quizAvailableFromInput').value);
    body.append('available_until', document.getElementById('quizAvailableUntilInput').value);
    body.append('questions_per_attempt', document.getElementById('quizQuestionsPerAttemptInput').value);
    body.append('csrf_token', csrfToken);

    const endpoint = editingQuizId ? 'update_quiz.php' : 'post_quiz.php';
    if (editingQuizId) body.append('quiz_id', editingQuizId);

    fetch(API + 'Quizzes/' + endpoint, { method: 'POST', body })
      .then(r => r.json())
      .then(d => {
        if (d.error) { showError(errBox, errMsg, d.error); return; }
        renderQuizzes(d.quizzes);
        if (window.spToast) window.spToast(editingQuizId ? 'Quiz updated.' : 'Quiz created.', 'mdi:clipboard-check-outline');
        resetQuizForm();
      })
      .catch(() => showError(errBox, errMsg, 'Could not save the quiz. Please try again.'))
      .finally(() => {
        postBtn.disabled = false;
        postBtn.querySelector('.sp-btn-spinner').hidden = true;
      });
  });

  cancelEditBtn.addEventListener('click', resetQuizForm);

  // ---------- Generate quiz from file (primary path: one form does
  // create_quiz + generate_questions_from_text together, then drops the
  // professor straight into the review list) ----------
  const generateQuizDialog = document.getElementById('generateQuizDialog');
  const generateQuizForm = document.getElementById('generateQuizForm');
  const generateQuizBtn = document.getElementById('generateQuizBtn');
  const generateQuizFormError = document.getElementById('generateQuizFormError');
  const generateQuizFormErrorMsg = document.getElementById('generateQuizFormErrorMsg');

  document.getElementById('openGenerateQuizBtn').addEventListener('click', () => {
    if (!currentScheduleId) return;
    generateQuizForm.reset();
    document.getElementById('genQuizTimeLimitInput').value = '10';
    document.getElementById('genQuizQuestionsPerAttemptInput').value = '10';
    generateQuizFormError.classList.remove('is-visible');
    generateQuizDialog.showModal();
  });

  document.getElementById('cancelGenerateQuizBtn').addEventListener('click', () => generateQuizDialog.close());

  generateQuizForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!currentScheduleId) return;

    generateQuizBtn.disabled = true;
    generateQuizBtn.querySelector('.sp-btn-spinner').hidden = false;

    try {
      const createBody = new FormData();
      createBody.append('schedule_id', currentScheduleId);
      createBody.append('title', document.getElementById('genQuizTitleInput').value.trim());
      createBody.append('instructions', '');
      createBody.append('time_limit_minutes', document.getElementById('genQuizTimeLimitInput').value);
      createBody.append('available_from', '');
      createBody.append('available_until', '');
      createBody.append('questions_per_attempt', document.getElementById('genQuizQuestionsPerAttemptInput').value);
      createBody.append('csrf_token', csrfToken);

      const createRes = await fetch(API + 'Quizzes/post_quiz.php', { method: 'POST', body: createBody }).then(r => r.json());
      if (createRes.error) { showError(generateQuizFormError, generateQuizFormErrorMsg, createRes.error); return; }
      const quizId = createRes.quiz_id;
      const quizTitle = document.getElementById('genQuizTitleInput').value.trim();

      const genBody = new FormData();
      genBody.append('quiz_id', quizId);
      genBody.append('questions_per_attempt', document.getElementById('genQuizQuestionsPerAttemptInput').value);
      genBody.append('lesson_file', document.getElementById('genLessonFileInput').files[0]);
      genBody.append('csrf_token', csrfToken);

      const genRes = await fetch(API + 'Quizzes/generate_questions.php', { method: 'POST', body: genBody }).then(r => r.json());
      if (genRes.error) {
        // The quiz itself was already created successfully -- surface
        // that instead of leaving the professor thinking nothing
        // happened; they can retry generation from inside its Questions
        // dialog (the "Add more questions from another file" option).
        loadQuizzes(currentScheduleId);
        showError(generateQuizFormError, generateQuizFormErrorMsg, 'Quiz "' + quizTitle + '" was created, but generating questions failed: ' + genRes.error);
        return;
      }

      generateQuizDialog.close();
      loadQuizzes(currentScheduleId);
      const msg = 'Generated ' + genRes.generated_count + ' question' + (genRes.generated_count === 1 ? '' : 's') + '. Review below, then Publish when ready.';
      if (window.spToast) window.spToast(msg, 'mdi:file-upload-outline');
      openQuestionsDialog(quizId, quizTitle);
      if (genRes.warning) showError(questionFormError, questionFormErrorMsg, genRes.warning);
    } catch (err) {
      showError(generateQuizFormError, generateQuizFormErrorMsg, 'Something went wrong. Please try again.');
    } finally {
      generateQuizBtn.disabled = false;
      generateQuizBtn.querySelector('.sp-btn-spinner').hidden = true;
    }
  });

  quizList.addEventListener('click', async (e) => {
    const editBtn = e.target.closest('[data-edit-quiz]');
    const delBtn = e.target.closest('[data-delete-quiz]');
    const questionsBtn = e.target.closest('[data-manage-questions]');
    const rosterBtn = e.target.closest('[data-view-roster]');
    const publishBtn = e.target.closest('[data-publish-quiz]');
    const unpublishBtn = e.target.closest('[data-unpublish-quiz]');

    if (publishBtn) {
      const body = new FormData();
      body.append('quiz_id', publishBtn.dataset.publishQuiz);
      body.append('csrf_token', csrfToken);
      const res = await fetch(API + 'Quizzes/publish_quiz.php', { method: 'POST', body }).then(r => r.json());
      if (res.error) { showError(errBox, errMsg, res.error); return; }
      renderQuizzes(res.quizzes);
      if (window.spToast) window.spToast('Quiz published — students can now see it.', 'mdi:check-circle-outline');
      return;
    }

    if (unpublishBtn) {
      const body = new FormData();
      body.append('quiz_id', unpublishBtn.dataset.unpublishQuiz);
      body.append('csrf_token', csrfToken);
      const res = await fetch(API + 'Quizzes/unpublish_quiz.php', { method: 'POST', body }).then(r => r.json());
      if (res.error) { showError(errBox, errMsg, res.error); return; }
      renderQuizzes(res.quizzes);
      if (window.spToast) window.spToast('Quiz moved back to draft — hidden from students until republished.', 'mdi:eye-off-outline');
      return;
    }

    if (editBtn) {
      const quizId = editBtn.dataset.editQuiz;
      const res = await fetch(API + 'Quizzes/get_quiz_detail.php?quiz_id=' + encodeURIComponent(quizId)).then(r => r.json());
      if (res.error) { showError(errBox, errMsg, res.error); return; }
      editingQuizId = quizId;
      document.getElementById('quizTitleInput').value = res.quiz.title;
      document.getElementById('quizInstructionsInput').value = res.quiz.instructions || '';
      document.getElementById('quizTimeLimitInput').value = res.quiz.time_limit_minutes;
      document.getElementById('quizAvailableFromInput').value = toDatetimeLocal(res.quiz.available_from);
      document.getElementById('quizAvailableUntilInput').value = toDatetimeLocal(res.quiz.available_until);
      document.getElementById('quizQuestionsPerAttemptInput').value = res.quiz.questions_per_attempt || '';
      postBtn.querySelector('.sp-btn-label').textContent = 'Save changes';
      cancelEditBtn.hidden = false;
      document.getElementById('manualQuizDetails').open = true;
      quizForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
      return;
    }

    if (delBtn) {
      const quizId = delBtn.dataset.deleteQuiz;
      const ok = await window.spConfirm('Delete this quiz? This also deletes every student attempt and cannot be undone.', { confirmLabel: 'Delete' });
      if (!ok) return;
      const body = new FormData();
      body.append('quiz_id', quizId);
      body.append('csrf_token', csrfToken);
      const res = await fetch(API + 'Quizzes/delete_quiz.php', { method: 'POST', body }).then(r => r.json());
      if (res.error) { showError(errBox, errMsg, res.error); return; }
      if (window.spToast) window.spToast('Quiz deleted.', 'mdi:trash-can-outline');
      loadQuizzes(currentScheduleId);
      return;
    }

    if (questionsBtn) {
      openQuestionsDialog(questionsBtn.dataset.manageQuestions, questionsBtn.dataset.quizTitle);
      return;
    }

    if (rosterBtn) {
      openRosterDialog(rosterBtn.dataset.viewRoster, rosterBtn.dataset.quizTitle);
      return;
    }
  });

  // ---------- Question builder dialog ----------
  const questionsDialog = document.getElementById('questionsDialog');
  const questionsDialogTitle = document.getElementById('questionsDialogTitle');
  const questionForm = document.getElementById('questionForm');
  const questionFormError = document.getElementById('questionFormError');
  const questionFormErrorMsg = document.getElementById('questionFormErrorMsg');
  const choiceRows = document.getElementById('choiceRows');
  const addChoiceRowBtn = document.getElementById('addChoiceRowBtn');
  const questionSaveBtn = document.getElementById('questionSaveBtn');
  const cancelQuestionEditBtn = document.getElementById('cancelQuestionEditBtn');
  const questionList = document.getElementById('questionList');

  let activeQuizId = null;
  let editingQuestionId = null;

  function addChoiceRow(text = '', isCorrect = false) {
    const row = document.createElement('div');
    row.className = 'sp-choice-row';
    row.style.display = 'flex';
    row.style.gap = '8px';
    row.style.alignItems = 'center';
    row.innerHTML =
      '<input type="radio" name="correctChoice" ' + (isCorrect ? 'checked' : '') + '>' +
      '<input type="text" class="sp-table-input" data-choice-text maxlength="500" placeholder="Choice text" value="' + escHtml(text) + '" style="flex:1;">' +
      '<button type="button" class="sp-announce-delete" data-remove-choice aria-label="Remove choice"><iconify-icon icon="mdi:close"></iconify-icon></button>';
    choiceRows.appendChild(row);
  }

  addChoiceRowBtn.addEventListener('click', () => addChoiceRow());

  choiceRows.addEventListener('click', (e) => {
    const removeBtn = e.target.closest('[data-remove-choice]');
    if (removeBtn) removeBtn.closest('.sp-choice-row').remove();
  });

  function resetQuestionForm() {
    editingQuestionId = null;
    questionForm.reset();
    choiceRows.innerHTML = '';
    addChoiceRow();
    addChoiceRow();
    questionSaveBtn.querySelector('.sp-btn-label').textContent = 'Add question';
    cancelQuestionEditBtn.hidden = true;
  }

  cancelQuestionEditBtn.addEventListener('click', resetQuestionForm);

  function renderQuestionList(questions) {
    if (!questions || questions.length === 0) {
      questionList.innerHTML = '<li class="sp-announce-empty">No questions yet — add one above.</li>';
      return;
    }
    questionList.innerHTML = questions.map((q, i) => {
      const choicesHtml = (q.choices || []).map(c =>
        '<span class="sp-pill ' + (c.is_correct ? 'enrolled' : 'attention') + '" style="margin:2px 4px 0 0;">' + escHtml(c.choice_text) + '</span>'
      ).join('');
      return '<li class="sp-announce-item" style="--row-i:' + i + '" data-question-id="' + q.question_id + '">' +
        '<div class="sp-announce-item-head">' +
          '<strong>' + escHtml(q.question_text) + '</strong>' +
          '<span class="sp-announce-item-date">' + q.points + ' pt' + (q.points == 1 ? '' : 's') + '</span>' +
          '<button type="button" class="sp-announce-delete" data-edit-question="' + q.question_id + '" aria-label="Edit question">' +
            '<iconify-icon icon="mdi:pencil-outline"></iconify-icon>' +
          '</button>' +
          '<button type="button" class="sp-announce-delete" data-delete-question="' + q.question_id + '" aria-label="Delete question">' +
            '<iconify-icon icon="mdi:trash-can-outline"></iconify-icon>' +
          '</button>' +
        '</div>' +
        '<div>' + choicesHtml + '</div>' +
      '</li>';
    }).join('');
    questionList.dataset.raw = JSON.stringify(questions);
  }

  function openQuestionsDialog(quizId, quizTitle) {
    activeQuizId = quizId;
    questionsDialogTitle.textContent = 'Questions — ' + quizTitle;
    resetQuestionForm();
    fetch(API + 'Quizzes/get_quiz_detail.php?quiz_id=' + encodeURIComponent(quizId))
      .then(r => r.json())
      .then(d => { if (d.error) { showError(questionFormError, questionFormErrorMsg, d.error); return; } renderQuestionList(d.quiz.questions); })
      .catch(() => showError(questionFormError, questionFormErrorMsg, 'Could not load questions.'));
    questionsDialog.showModal();
  }

  document.getElementById('closeQuestionsDialog').addEventListener('click', () => {
    questionsDialog.close();
    loadQuizzes(currentScheduleId);
  });

  // ---------- Generate from lesson file (no AI -- cloze/fill-in-the-blank) ----------
  const generateForm = document.getElementById('generateForm');
  const generateFormError = document.getElementById('generateFormError');
  const generateFormErrorMsg = document.getElementById('generateFormErrorMsg');
  const generateBtn = document.getElementById('generateBtn');
  const lessonFileInput = document.getElementById('lessonFileInput');

  generateForm.addEventListener('submit', (e) => {
    e.preventDefault();
    if (!activeQuizId || !lessonFileInput.files[0]) return;

    generateBtn.disabled = true;
    generateBtn.querySelector('.sp-btn-spinner').hidden = false;

    const body = new FormData();
    body.append('quiz_id', activeQuizId);
    body.append('questions_per_attempt', document.getElementById('generateQuestionsPerAttemptInput').value);
    body.append('lesson_file', lessonFileInput.files[0]);
    body.append('csrf_token', csrfToken);

    fetch(API + 'Quizzes/generate_questions.php', { method: 'POST', body })
      .then(r => r.json())
      .then(d => {
        if (d.error) { showError(generateFormError, generateFormErrorMsg, d.error); return; }
        renderQuestionList(d.quiz.questions);
        generateForm.reset();
        document.getElementById('generateQuestionsPerAttemptInput').value = '10';
        const msg = 'Generated ' + d.generated_count + ' question' + (d.generated_count === 1 ? '' : 's') + '. Review them below, then Publish when ready.';
        if (window.spToast) window.spToast(msg, 'mdi:file-upload-outline');
        if (d.warning) showError(generateFormError, generateFormErrorMsg, d.warning);
      })
      .catch(() => showError(generateFormError, generateFormErrorMsg, 'Could not generate questions from that file. Please try again.'))
      .finally(() => {
        generateBtn.disabled = false;
        generateBtn.querySelector('.sp-btn-spinner').hidden = true;
      });
  });

  questionForm.addEventListener('submit', (e) => {
    e.preventDefault();
    const choices = Array.from(choiceRows.querySelectorAll('.sp-choice-row')).map(row => ({
      text: row.querySelector('[data-choice-text]').value.trim(),
      is_correct: row.querySelector('input[type="radio"]').checked,
    })).filter(c => c.text !== '');

    questionSaveBtn.disabled = true;
    questionSaveBtn.querySelector('.sp-btn-spinner').hidden = false;

    const body = new FormData();
    body.append('quiz_id', activeQuizId);
    body.append('question_text', document.getElementById('questionTextInput').value.trim());
    body.append('points', document.getElementById('questionPointsInput').value);
    body.append('choices', JSON.stringify(choices));
    body.append('csrf_token', csrfToken);

    const endpoint = editingQuestionId ? 'update_question.php' : 'post_question.php';
    if (editingQuestionId) body.append('question_id', editingQuestionId);

    fetch(API + 'Quizzes/' + endpoint, { method: 'POST', body })
      .then(r => r.json())
      .then(d => {
        if (d.error) { showError(questionFormError, questionFormErrorMsg, d.error); return; }
        renderQuestionList(d.quiz.questions);
        resetQuestionForm();
      })
      .catch(() => showError(questionFormError, questionFormErrorMsg, 'Could not save the question. Please try again.'))
      .finally(() => {
        questionSaveBtn.disabled = false;
        questionSaveBtn.querySelector('.sp-btn-spinner').hidden = true;
      });
  });

  questionList.addEventListener('click', async (e) => {
    const editBtn = e.target.closest('[data-edit-question]');
    const delBtn = e.target.closest('[data-delete-question]');

    if (editBtn) {
      const questions = JSON.parse(questionList.dataset.raw || '[]');
      const q = questions.find(x => String(x.question_id) === editBtn.dataset.editQuestion);
      if (!q) return;
      editingQuestionId = q.question_id;
      document.getElementById('questionTextInput').value = q.question_text;
      document.getElementById('questionPointsInput').value = q.points;
      choiceRows.innerHTML = '';
      (q.choices || []).forEach(c => addChoiceRow(c.choice_text, !!c.is_correct));
      questionSaveBtn.querySelector('.sp-btn-label').textContent = 'Save changes';
      cancelQuestionEditBtn.hidden = false;
      return;
    }

    if (delBtn) {
      const ok = await window.spConfirm('Delete this question and its choices?', { confirmLabel: 'Delete' });
      if (!ok) return;
      const body = new FormData();
      body.append('question_id', delBtn.dataset.deleteQuestion);
      body.append('quiz_id', activeQuizId);
      body.append('csrf_token', csrfToken);
      const res = await fetch(API + 'Quizzes/delete_question.php', { method: 'POST', body }).then(r => r.json());
      if (res.error) { showError(questionFormError, questionFormErrorMsg, res.error); return; }
      renderQuestionList(res.quiz.questions);
    }
  });

  // ---------- Roster / results dialog ----------
  const rosterDialog = document.getElementById('rosterDialog');
  const rosterDialogTitle = document.getElementById('rosterDialogTitle');
  const rosterTableBody = document.getElementById('rosterTableBody');

  const STATUS_LABEL = {
    in_progress: 'In progress',
    submitted: 'Submitted',
    auto_submitted_time: 'Auto-submitted (time)',
    auto_submitted_violations: 'Auto-submitted (violations)',
  };

  function openRosterDialog(quizId, quizTitle) {
    rosterDialogTitle.textContent = 'Results — ' + quizTitle;
    fetch(API + 'Quizzes/get_quiz_roster.php?quiz_id=' + encodeURIComponent(quizId))
      .then(r => r.json())
      .then(d => {
        if (d.error) { rosterTableBody.innerHTML = '<tr><td colspan="6">' + escHtml(d.error) + '</td></tr>'; return; }
        if (!d.roster.length) { rosterTableBody.innerHTML = '<tr><td colspan="6">No enrolled students.</td></tr>'; return; }
        rosterTableBody.innerHTML = d.roster.map(r => {
          const name = [r.last_name, r.first_name].filter(Boolean).join(', ') + (r.middle_name ? ' ' + r.middle_name.charAt(0) + '.' : '');
          const status = r.status ? (STATUS_LABEL[r.status] || r.status) : 'Not started';
          const score = (r.score !== null && r.max_score !== null) ? r.score + '/' + r.max_score : '—';
          const canReview = !!r.attempt_id && r.status !== 'in_progress';
          return '<tr>' +
            '<td class="sp-num">' + escHtml(r.student_no) + '</td>' +
            '<td>' + escHtml(name) + '</td>' +
            '<td>' + escHtml(status) + '</td>' +
            '<td class="sp-num">' + score + '</td>' +
            '<td class="sp-num">' + (r.violation_count || 0) + '</td>' +
            '<td>' + (canReview ? '<button type="button" class="sp-table-action" data-review-attempt="' + r.attempt_id + '">Review</button>' : '') + '</td>' +
          '</tr>';
        }).join('');
      })
      .catch(() => { rosterTableBody.innerHTML = '<tr><td colspan="6">Could not load results.</td></tr>'; });
    rosterDialog.showModal();
  }

  document.getElementById('closeRosterDialog').addEventListener('click', () => rosterDialog.close());

  rosterTableBody.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-review-attempt]');
    if (btn) openReviewDialog(btn.dataset.reviewAttempt);
  });

  // ---------- Attempt review dialog ----------
  const reviewDialog = document.getElementById('reviewDialog');
  const reviewDialogTitle = document.getElementById('reviewDialogTitle');
  const reviewQuestionList = document.getElementById('reviewQuestionList');
  const reviewViolationList = document.getElementById('reviewViolationList');
  const reviewViolationEmpty = document.getElementById('reviewViolationEmpty');

  function openReviewDialog(attemptId) {
    fetch(API + 'Quizzes/get_attempt_review.php?attempt_id=' + encodeURIComponent(attemptId))
      .then(r => r.json())
      .then(d => {
        if (d.error) { reviewQuestionList.innerHTML = '<p class="sp-announce-empty">' + escHtml(d.error) + '</p>'; return; }
        const a = d.attempt;
        reviewDialogTitle.textContent = a.quiz_title + ' — ' + a.first_name + ' ' + a.last_name + ' (' + a.score + '/' + a.max_score + ')';
        reviewQuestionList.innerHTML = (a.questions || []).map(q => {
          const correct = q.is_correct === 1 || q.is_correct === '1';
          return '<div class="sp-announce-item" style="margin-bottom:10px;">' +
            '<p style="margin:0 0 6px; font-weight:600;">' + escHtml(q.question_text) + '</p>' +
            '<p style="margin:0; font-size:13px;">Answered: <span class="sp-pill ' + (correct ? 'enrolled' : 'attention') + '">' + escHtml(q.chosen_choice_text || 'No answer') + '</span></p>' +
            (!correct ? '<p style="margin:4px 0 0; font-size:13px;">Correct answer: <span class="sp-pill enrolled">' + escHtml(q.correct_choice_text || '—') + '</span></p>' : '') +
          '</div>';
        }).join('');

        if (!a.violations || a.violations.length === 0) {
          reviewViolationList.innerHTML = '';
          reviewViolationEmpty.hidden = false;
        } else {
          reviewViolationEmpty.hidden = true;
          reviewViolationList.innerHTML = a.violations.map(v =>
            '<li class="sp-pill attention">' + escHtml(v.violation_type.replace('_', ' ')) + ' — ' + escHtml(v.created_at) + '</li>'
          ).join('');
        }
        reviewDialog.showModal();
      })
      .catch(() => { reviewQuestionList.innerHTML = '<p class="sp-announce-empty">Could not load this attempt.</p>'; reviewDialog.showModal(); });
  }

  document.getElementById('closeReviewDialog').addEventListener('click', () => reviewDialog.close());

  resetQuestionForm();

  if (window.spRestoreClassSelection?.(classSelect)) {
    classSelect.dispatchEvent(new Event('change'));
  }
});
