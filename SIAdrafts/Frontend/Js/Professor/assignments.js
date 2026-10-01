// escHtml() is shared globally via Frontend/Js/Student/shared.js, loaded
// on every Professor page before this file (see Include/footer.php).
document.addEventListener('DOMContentLoaded', function () {

  const API = '/SIAdrafts/Backend/api/';
  const csrfToken = document.body.dataset.csrf;

  const classSelect          = document.getElementById('assignmentClassSelect');
  const emptyState           = document.getElementById('assignmentEmptyState');
  const panel                = document.getElementById('assignmentPanel');
  // Set only on Frontend/View/*/course_detail.php's Assignments tab, where
  // there's no dropdown -- the page is already scoped to one class, so
  // this script just needs to know which one instead of waiting for a
  // <select> change event.
  const fixedScheduleId      = document.body.dataset.scheduleId || null;
  const assignmentList       = document.getElementById('assignmentList');
  const assignmentForm       = document.getElementById('assignmentForm');
  const assignmentTitleEl    = document.getElementById('assignmentTitleInput');
  const assignmentInstrEl    = document.getElementById('assignmentInstructionsInput');
  const assignmentDueDateEl  = document.getElementById('assignmentDueDateInput');
  const assignmentMaxScoreEl = document.getElementById('assignmentMaxScoreInput');
  const assignmentErrBox     = document.getElementById('assignmentFormError');
  const assignmentErrMsg     = document.getElementById('assignmentFormErrorMsg');
  const assignmentPostBtn    = document.getElementById('assignmentPostBtn');
  const cancelAssignmentEditBtn = document.getElementById('cancelAssignmentEditBtn');
  const assignmentCategoryEl = document.getElementById('assignmentCategoryInput');
  const categoryNameInput    = document.getElementById('categoryNameInput');
  const categoryWeightInput  = document.getElementById('categoryWeightInput');
  const addCategoryBtn       = document.getElementById('addCategoryBtn');
  const categoryListEl       = document.getElementById('categoryList');

  if (!classSelect && !fixedScheduleId) return;

  let currentScheduleId = null;
  let lastAssignments = [];
  let editingAssignmentId = null;
  let currentCategories = [];

  function renderCategoryOptions() {
    assignmentCategoryEl.innerHTML = '<option value="">None</option>' +
      currentCategories.map(c => '<option value="' + c.category_id + '">' + escHtml(c.name) + ' (' + c.weight_percent + '%)</option>').join('');
  }

  function renderCategoryList() {
    if (currentCategories.length === 0) {
      categoryListEl.innerHTML = '<li style="color:var(--slate-300); font-size:12.5px;">No categories yet.</li>';
      return;
    }
    categoryListEl.innerHTML = currentCategories.map(c =>
      '<li style="display:flex; align-items:center; justify-content:space-between; font-size:13px;">' +
        '<span>' + escHtml(c.name) + ' — ' + c.weight_percent + '%</span>' +
        '<button type="button" class="sp-announce-delete" data-delete-category="' + c.category_id + '" aria-label="Delete category"><iconify-icon icon="mdi:trash-can-outline"></iconify-icon></button>' +
      '</li>'
    ).join('');
  }

  function loadCategories(scheduleId) {
    fetch(API + 'Categories/get_categories.php?schedule_id=' + encodeURIComponent(scheduleId))
      .then(r => r.json())
      .then(d => {
        currentCategories = d.categories || [];
        renderCategoryOptions();
        renderCategoryList();
      })
      .catch(() => {});
  }

  addCategoryBtn?.addEventListener('click', () => {
    if (!currentScheduleId) return;
    const name = categoryNameInput.value.trim();
    const weight = categoryWeightInput.value;
    if (!name || !weight) return;

    const body = new FormData();
    body.append('schedule_id', currentScheduleId);
    body.append('name', name);
    body.append('weight_percent', weight);
    body.append('csrf_token', csrfToken);

    fetch(API + 'Categories/save_category.php', { method: 'POST', body })
      .then(r => r.json())
      .then(d => {
        if (d.error) { if (window.spToast) window.spToast(d.error, 'mdi:alert-circle-outline'); return; }
        currentCategories = d.categories || [];
        renderCategoryOptions();
        renderCategoryList();
        categoryNameInput.value = '';
        categoryWeightInput.value = '';
      })
      .catch(() => { if (window.spToast) window.spToast('Could not add category.', 'mdi:alert-circle-outline'); });
  });

  categoryListEl?.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-delete-category]');
    if (!btn || !currentScheduleId) return;
    const body = new FormData();
    body.append('category_id', btn.getAttribute('data-delete-category'));
    body.append('schedule_id', currentScheduleId);
    body.append('csrf_token', csrfToken);

    fetch(API + 'Categories/delete_category.php', { method: 'POST', body })
      .then(r => r.json())
      .then(d => {
        if (d.error) { if (window.spToast) window.spToast(d.error, 'mdi:alert-circle-outline'); return; }
        currentCategories = d.categories || [];
        renderCategoryOptions();
        renderCategoryList();
      })
      .catch(() => {});
  });

  function resetAssignmentForm() {
    editingAssignmentId = null;
    assignmentForm.reset();
    assignmentPostBtn.querySelector('.sp-btn-label').textContent = 'Post assignment';
    cancelAssignmentEditBtn.hidden = true;
  }

  function startEditAssignment(assignmentId) {
    const a = lastAssignments.find(x => String(x.assignment_id) === String(assignmentId));
    if (!a) return;
    editingAssignmentId = assignmentId;
    assignmentTitleEl.value = a.title || '';
    assignmentInstrEl.value = a.instructions || '';
    assignmentDueDateEl.value = a.due_date || '';
    assignmentMaxScoreEl.value = a.max_score || '';
    assignmentCategoryEl.value = a.category_id || '';
    assignmentErrBox.classList.remove('is-visible');
    assignmentPostBtn.querySelector('.sp-btn-label').textContent = 'Save changes';
    cancelAssignmentEditBtn.hidden = false;
    assignmentTitleEl.focus();
  }

  cancelAssignmentEditBtn?.addEventListener('click', resetAssignmentForm);

  function showAssignmentError(message) {
    assignmentErrBox.classList.remove('is-visible');
    void assignmentErrBox.offsetWidth;
    assignmentErrMsg.textContent = message;
    assignmentErrBox.classList.add('is-visible');
  }

  function formatDue(dueDate) {
    if (!dueDate) return 'No due date';
    var d = new Date(dueDate + 'T00:00:00');
    return 'Due ' + d.toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric' });
  }

  function dueUrgencyClass(dueDate) {
    if (!dueDate) return '';
    var due = new Date(dueDate + 'T00:00:00');
    var days = Math.ceil((due - new Date()) / 86400000);
    if (days < 0) return '';
    if (days <= 1) return 'is-urgent';
    if (days <= 3) return 'is-soon';
    return '';
  }

  function dueBadgeText(dueDate) {
    if (!dueDate) return '';
    var due = new Date(dueDate + 'T00:00:00');
    var days = Math.ceil((due - new Date()) / 86400000);
    if (days < 0) return '';
    if (days === 0) return 'Due today';
    if (days === 1) return 'Due tomorrow';
    if (days <= 3) return days + ' days left';
    return '';
  }

  function renderAssignments(items) {
    lastAssignments = items || [];
    if (!items || items.length === 0) {
      assignmentList.innerHTML = '<li class="sp-announce-empty">No assignments posted for this class yet.</li>';
      return;
    }
    assignmentList.innerHTML = items.map((a, i) => {
      var meta = formatDue(a.due_date) + (a.max_score ? ' · ' + a.max_score + ' pts' : '') + (a.category_name ? ' · ' + a.category_name : '') + ' · ' + a.submission_count + ' submitted';
      var badge = dueBadgeText(a.due_date);
      return '<li class="sp-announce-item ' + dueUrgencyClass(a.due_date) + '"' + (badge ? ' data-badge="' + escHtml(badge) + '"' : '') + ' style="--row-i:' + i + '" data-assignment-id="' + a.assignment_id + '">' +
        '<div class="sp-announce-item-head">' +
          '<strong>' + escHtml(a.title) + '</strong>' +
          '<span class="sp-announce-item-date">' + escHtml(meta) + '</span>' +
          '<button type="button" class="sp-announce-delete" data-edit-assignment="' + a.assignment_id + '" aria-label="Edit assignment">' +
            '<iconify-icon icon="mdi:pencil-outline"></iconify-icon>' +
          '</button>' +
          '<button type="button" class="sp-announce-delete" data-delete-assignment="' + a.assignment_id + '" aria-label="Delete assignment">' +
            '<iconify-icon icon="mdi:trash-can-outline"></iconify-icon>' +
          '</button>' +
        '</div>' +
        '<p>' + escHtml(a.instructions).replace(/\n/g, '<br>') + '</p>' +
        '<button type="button" class="sp-table-action" data-view-submissions="' + a.assignment_id + '" data-assignment-title="' + escHtml(a.title) + '">' +
          '<iconify-icon icon="mdi:file-check-outline"></iconify-icon> View submissions' +
        '</button>' +
      '</li>';
    }).join('');
  }

  async function loadAssignments(scheduleId) {
    assignmentList.innerHTML = '<li class="sp-announce-empty"><span class="sp-loading-dots"><span></span><span></span><span></span></span></li>';
    try {
      const res = await fetch(API + 'Assignments/get_class_assignments.php?schedule_id=' + encodeURIComponent(scheduleId));
      const data = await res.json();
      if (data.error) {
        assignmentList.innerHTML = '<li class="sp-announce-empty">' + escHtml(data.error) + '</li>';
        return;
      }
      renderAssignments(data.assignments);
    } catch (err) {
      assignmentList.innerHTML = '<li class="sp-announce-empty">Could not load assignments.</li>';
    }
  }

  function initForClass(scheduleId) {
    currentScheduleId = scheduleId;
    if (panel) panel.hidden = false;
    if (emptyState) emptyState.hidden = true;
    resetAssignmentForm();
    assignmentErrBox.classList.remove('is-visible');
    loadAssignments(currentScheduleId);
    loadCategories(currentScheduleId);
  }

  classSelect?.addEventListener('change', () => {
    window.spRememberClassSelection?.(classSelect);
    const scheduleId = classSelect.value || null;
    if (!scheduleId) {
      if (panel) panel.hidden = true;
      if (emptyState) emptyState.hidden = false;
      currentScheduleId = null;
      return;
    }
    initForClass(scheduleId);
  });

  assignmentForm?.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!currentScheduleId) return;

    const isEditing = editingAssignmentId !== null;
    assignmentPostBtn.disabled = true;
    assignmentPostBtn.querySelector('.sp-btn-spinner').hidden = false;
    assignmentPostBtn.querySelector('.sp-btn-label').textContent = isEditing ? 'Saving…' : 'Posting…';

    const body = new FormData();
    if (isEditing) body.append('assignment_id', editingAssignmentId);
    body.append('schedule_id', currentScheduleId);
    body.append('title', assignmentTitleEl.value);
    body.append('instructions', assignmentInstrEl.value);
    body.append('due_date', assignmentDueDateEl.value);
    body.append('max_score', assignmentMaxScoreEl.value);
    body.append('category_id', assignmentCategoryEl.value);
    body.append('csrf_token', csrfToken);

    try {
      const endpoint = isEditing ? 'Assignments/update_assignment.php' : 'Assignments/post_assignment.php';
      const res = await fetch(API + endpoint, { method: 'POST', body });
      const data = await res.json();
      if (data.error) {
        showAssignmentError(data.error);
        return;
      }
      renderAssignments(data.assignments);
      resetAssignmentForm();
      if (window.spToast) window.spToast(isEditing ? 'Assignment updated.' : 'Assignment posted.', 'mdi:file-document-edit-outline');
    } catch (err) {
      showAssignmentError('Something went wrong. Please try again.');
    } finally {
      assignmentPostBtn.disabled = false;
      assignmentPostBtn.querySelector('.sp-btn-spinner').hidden = true;
      assignmentPostBtn.querySelector('.sp-btn-label').textContent = editingAssignmentId ? 'Save changes' : 'Post assignment';
    }
  });

  assignmentList?.addEventListener('click', async (e) => {
    const delBtn = e.target.closest('[data-delete-assignment]');
    if (delBtn) {
      const assignmentId = delBtn.getAttribute('data-delete-assignment');
      const confirmed = window.spConfirm
        ? await window.spConfirm('This assignment and every student\'s submission for it will be removed.', { title: 'Delete this assignment?', confirmLabel: 'Delete' })
        : window.confirm('Delete this assignment?');
      if (!confirmed) return;

      const body = new FormData();
      body.append('assignment_id', assignmentId);
      body.append('csrf_token', csrfToken);

      try {
        const res = await fetch(API + 'Assignments/delete_assignment.php', { method: 'POST', body });
        const data = await res.json();
        if (data.error) {
          if (window.spToast) window.spToast(data.error, 'mdi:alert-circle-outline');
          return;
        }
        loadAssignments(currentScheduleId);
        if (window.spToast) window.spToast('Assignment deleted.', 'mdi:trash-can-outline');
      } catch (err) {
        if (window.spToast) window.spToast('Could not delete assignment.', 'mdi:alert-circle-outline');
      }
      return;
    }

    const editBtn = e.target.closest('[data-edit-assignment]');
    if (editBtn) {
      startEditAssignment(editBtn.getAttribute('data-edit-assignment'));
      return;
    }

    const viewBtn = e.target.closest('[data-view-submissions]');
    if (viewBtn) {
      openSubmissions(viewBtn.getAttribute('data-view-submissions'), viewBtn.getAttribute('data-assignment-title'));
    }
  });

  // ----- Assignment submissions -----
  const submissionsDialog     = document.getElementById('submissionsDialog');
  const submissionsSubtitle   = document.getElementById('submissionsSubtitle');
  const submissionsBody       = document.getElementById('submissionsTableBody');
  const submissionsErrBox     = document.getElementById('submissionsFormError');
  const submissionsErrMsg     = document.getElementById('submissionsFormErrorMsg');
  const submissionsSuccessBox = document.getElementById('submissionsFormSuccess');
  const submissionsSuccessMsg = document.getElementById('submissionsFormSuccessMsg');
  const saveSubmissionScoresBtn = document.getElementById('saveSubmissionScoresBtn');
  let currentAssignmentId = null;

  function openSubmissionsDialog() {
    if (typeof submissionsDialog.showModal === 'function') submissionsDialog.showModal();
  }
  function closeSubmissionsDialog() { submissionsDialog.close(); }
  document.getElementById('closeSubmissionsDialog')?.addEventListener('click', closeSubmissionsDialog);
  submissionsDialog?.addEventListener('click', (e) => {
    if (e.target === submissionsDialog) closeSubmissionsDialog();
  });

  function hideSubmissionsNotices() {
    submissionsErrBox.classList.remove('is-visible');
    submissionsSuccessBox.classList.remove('is-visible');
  }
  function showSubmissionsError(message) {
    submissionsSuccessBox.classList.remove('is-visible');
    submissionsErrBox.classList.remove('is-visible');
    void submissionsErrBox.offsetWidth;
    submissionsErrMsg.textContent = message;
    submissionsErrBox.classList.add('is-visible');
  }
  function showSubmissionsSuccess(message) {
    submissionsErrBox.classList.remove('is-visible');
    submissionsSuccessBox.classList.remove('is-visible');
    void submissionsSuccessBox.offsetWidth;
    submissionsSuccessMsg.textContent = message;
    submissionsSuccessBox.classList.add('is-visible');
  }

  function renderSubmissions(students) {
    if (!students || students.length === 0) {
      submissionsBody.innerHTML = '<tr><td colspan="7" style="text-align:center;">No students enrolled yet.</td></tr>';
      return;
    }
    submissionsBody.innerHTML = students.map(s => {
      const name = [s.last_name, s.first_name].filter(Boolean).join(', ') +
        (s.middle_name ? ' ' + s.middle_name.charAt(0) + '.' : '');
      const fileCell = s.submission_id
        ? '<a href="' + API + 'Assignments/download_submission.php?submission_id=' + s.submission_id + '" target="_blank" rel="noopener">' + escHtml(s.file_name) + '</a>'
        : '<span style="color:var(--slate-300);">Not submitted</span>';
      let submittedCell = '<span style="color:var(--slate-300);">—</span>';
      if (s.submitted_at) {
        const when = new Date(s.submitted_at.replace(' ', 'T')).toLocaleDateString([], { month: 'short', day: 'numeric' });
        submittedCell = when + (s.is_late ? ' <span class="sp-pill" style="background:var(--danger-100,#fee2e2); color:var(--danger-600,#dc2626);">Late</span>' : '');
      }
      const disabled = s.submission_id ? '' : 'disabled';
      const commentsCell = s.submission_id
        ? '<button type="button" class="sp-table-action" data-view-comments="' + s.submission_id + '" data-student-name="' + escHtml(name) + '"><iconify-icon icon="mdi:comment-outline"></iconify-icon></button>'
        : '<span style="color:var(--slate-300);">—</span>';
      return '<tr' + (s.submission_id ? ' data-submission-id="' + s.submission_id + '"' : '') + '>' +
        '<td class="sp-num">' + escHtml(s.student_no) + '</td>' +
        '<td>' + escHtml(name) + '</td>' +
        '<td>' + fileCell + '</td>' +
        '<td>' + submittedCell + '</td>' +
        '<td><input type="number" class="sp-table-input" data-score-input min="0" step="0.01" style="width:80px;" value="' + (s.score !== null && s.score !== undefined ? s.score : '') + '" ' + disabled + '></td>' +
        '<td><input type="text" class="sp-table-input" data-feedback-input maxlength="500" value="' + escHtml(s.feedback || '') + '" ' + disabled + '></td>' +
        '<td>' + commentsCell + '</td>' +
        '</tr>';
    }).join('');
  }

  async function loadSubmissions(assignmentId) {
    submissionsBody.innerHTML = '<tr><td colspan="7" style="text-align:center;"><span class="sp-loading-dots"><span></span><span></span><span></span></span></td></tr>';
    try {
      const res = await fetch(API + 'Assignments/get_assignment_submissions.php?assignment_id=' + encodeURIComponent(assignmentId));
      const data = await res.json();
      if (data.error) {
        submissionsBody.innerHTML = '<tr><td colspan="7" style="text-align:center;">' + escHtml(data.error) + '</td></tr>';
        return;
      }
      renderSubmissions(data.students);
    } catch (err) {
      submissionsBody.innerHTML = '<tr><td colspan="7" style="text-align:center;">Could not load submissions.</td></tr>';
    }
  }

  function openSubmissions(assignmentId, title) {
    currentAssignmentId = assignmentId;
    submissionsSubtitle.textContent = title || 'Assignment submissions';
    hideSubmissionsNotices();
    commentThreadPanel.hidden = true;
    openSubmissionsDialog();
    loadSubmissions(assignmentId);
  }

  // ----- Submission comment threads -----
  const commentThreadPanel = document.getElementById('commentThreadPanel');
  const commentThreadTitle = document.getElementById('commentThreadTitle');
  const commentThreadList  = document.getElementById('commentThreadList');
  const commentThreadInput = document.getElementById('commentThreadInput');
  const commentThreadSendBtn = document.getElementById('commentThreadSendBtn');
  let currentCommentSubmissionId = null;

  function renderComments(comments) {
    if (!comments || comments.length === 0) {
      commentThreadList.innerHTML = '<li style="color:var(--slate-300); font-size:12.5px;">No comments yet.</li>';
      return;
    }
    commentThreadList.innerHTML = comments.map(c => {
      const who = c.author_role === 'professor' ? 'You' : 'Student';
      const when = new Date(c.created_at.replace(' ', 'T')).toLocaleDateString([], { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' });
      return '<li style="font-size:12.5px;"><strong>' + escHtml(who) + '</strong> <span style="color:var(--slate-300);">' + when + '</span><br>' + escHtml(c.body) + '</li>';
    }).join('');
    commentThreadList.scrollTop = commentThreadList.scrollHeight;
  }

  function loadComments(submissionId) {
    fetch(API + 'Comments/get_comments.php?submission_id=' + encodeURIComponent(submissionId))
      .then(r => r.json())
      .then(d => {
        if (d.error) { renderComments([]); return; }
        renderComments(d.comments);
      })
      .catch(() => renderComments([]));
  }

  submissionsBody?.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-view-comments]');
    if (!btn) return;
    currentCommentSubmissionId = btn.getAttribute('data-view-comments');
    commentThreadTitle.textContent = 'Comments — ' + btn.getAttribute('data-student-name');
    commentThreadPanel.hidden = false;
    commentThreadInput.value = '';
    loadComments(currentCommentSubmissionId);
  });

  commentThreadSendBtn?.addEventListener('click', () => {
    const body = commentThreadInput.value.trim();
    if (!body || !currentCommentSubmissionId) return;

    const formData = new FormData();
    formData.append('submission_id', currentCommentSubmissionId);
    formData.append('body', body);
    formData.append('csrf_token', csrfToken);

    fetch(API + 'Comments/post_comment.php', { method: 'POST', body: formData })
      .then(r => r.json())
      .then(d => {
        if (d.error) { if (window.spToast) window.spToast(d.error, 'mdi:alert-circle-outline'); return; }
        renderComments(d.comments);
        commentThreadInput.value = '';
      })
      .catch(() => { if (window.spToast) window.spToast('Could not post comment.', 'mdi:alert-circle-outline'); });
  });

  saveSubmissionScoresBtn?.addEventListener('click', async () => {
    if (!currentAssignmentId) return;

    const rows = Array.from(submissionsBody.querySelectorAll('tr[data-submission-id]'));
    const scores = rows.map(row => ({
      submission_id: row.getAttribute('data-submission-id'),
      score: row.querySelector('[data-score-input]')?.value.trim() || '',
      feedback: row.querySelector('[data-feedback-input]')?.value.trim() || '',
    }));

    if (scores.length === 0) {
      showSubmissionsError('No submissions to score yet.');
      return;
    }

    saveSubmissionScoresBtn.disabled = true;
    saveSubmissionScoresBtn.querySelector('.sp-btn-spinner').hidden = false;
    saveSubmissionScoresBtn.querySelector('.sp-btn-label').textContent = 'Saving…';

    const body = new FormData();
    body.append('assignment_id', currentAssignmentId);
    body.append('scores', JSON.stringify(scores));
    body.append('csrf_token', csrfToken);

    try {
      const res = await fetch(API + 'Assignments/save_submission_score.php', { method: 'POST', body });
      const data = await res.json();
      if (data.error) {
        showSubmissionsError(data.error);
        return;
      }
      showSubmissionsSuccess('Scores saved.');
      loadSubmissions(currentAssignmentId);
    } catch (err) {
      showSubmissionsError('Something went wrong. Please try again.');
    } finally {
      saveSubmissionScoresBtn.disabled = false;
      saveSubmissionScoresBtn.querySelector('.sp-btn-spinner').hidden = true;
      saveSubmissionScoresBtn.querySelector('.sp-btn-label').textContent = 'Save scores';
    }
  });

  if (classSelect) {
    if (window.spRestoreClassSelection?.(classSelect)) {
      classSelect.dispatchEvent(new Event('change'));
    }
  } else if (fixedScheduleId) {
    initForClass(fixedScheduleId);
  }

});
