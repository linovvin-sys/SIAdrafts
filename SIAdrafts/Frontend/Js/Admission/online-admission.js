const unlockBtn = document.getElementById('unlockProgramBtn');
if (unlockBtn) {
  unlockBtn.addEventListener('click', function () {
    const wrap = document.getElementById('programFieldWrap');
    const template = document.getElementById('programOptionsTemplate');
    const select = document.createElement('select');
    select.className = 'form-control';
    select.id = 'course_id';
    select.name = 'course_id';
    select.required = true;
    select.innerHTML = template ? template.innerHTML : '<option value="">Select a program</option>';
    wrap.innerHTML = '<label class="form-label" for="course_id">Program</label>';
    wrap.appendChild(select);
  });
}

document.querySelectorAll('.requirement-row').forEach(function (row) {
  const fileInput = row.querySelector('.requirement-file');
  const laterCheckbox = row.querySelector('.requirement-later');
  if (!fileInput || !laterCheckbox) return;

  laterCheckbox.addEventListener('change', function () {
    fileInput.disabled = laterCheckbox.checked;
    if (laterCheckbox.checked) fileInput.value = '';
  });
  fileInput.addEventListener('change', function () {
    if (fileInput.files.length > 0) {
      laterCheckbox.checked = false;
    }
  });
});

function suggestApplicantType() {
  const typeSelect = document.getElementById('applicant_type');
  if (!typeSelect || typeSelect.dataset.userChanged === 'true') return;
  const hasHistory = Array.from(document.querySelectorAll('input[name="school_name[]"]'))
    .some(function (input) { return input.value.trim() !== ''; });
  typeSelect.value = hasHistory ? 'Transferee' : 'New';
}

const applicantTypeSelect = document.getElementById('applicant_type');
if (applicantTypeSelect) {
  applicantTypeSelect.addEventListener('change', function () {
    this.dataset.userChanged = 'true';
  });
}

document.getElementById('historyRows').addEventListener('input', function (e) {
  if (e.target.name === 'school_name[]') suggestApplicantType();
});

const form = document.getElementById('admissionForm');
const banner = document.getElementById('formBanner');
const refBanner = document.getElementById('referenceBanner');
const submitBtn = form.querySelector('.btn-submit');

// ---------- Draft autosave (survives closing the tab/browser) ----------
// localStorage, not the server: this form is filled out by anonymous
// applicants with no account/session yet, so there's nothing to attach a
// server-side draft to. Versioned key in case the field set here ever
// changes shape -- an old draft from a different version just won't match
// and gets ignored rather than partially, confusingly restored.
const DRAFT_KEY = 'sia_admission_draft_v1';
const DRAFT_MAX_AGE_MS = 7 * 24 * 60 * 60 * 1000; // a week-old draft is more likely to be stale (term/program list can change) than wanted back
const draftBanner = document.getElementById('draftBanner');
const draftSaveStatus = document.getElementById('draftSaveStatus');

// File inputs can't be serialized into localStorage at all (browsers
// never expose a file's actual bytes to JS for security reasons) -- every
// other field is fair game, including checkboxes/radios (kept only when
// checked, same as how a real form submission would omit them otherwise)
// and the repeatable school_name[]-style array fields (each input's own
// [name, value] pair, not collapsed into one entry).
function serializeDraft() {
  const pairs = [];
  for (const el of form.elements) {
    if (!el.name || el.type === 'file') continue;
    if (el.name === 'website' || el.name === 'g-recaptcha-response') continue;
    if ((el.type === 'checkbox' || el.type === 'radio') && !el.checked) continue;
    pairs.push([el.name, el.value]);
  }
  return pairs;
}

function hasMeaningfulData(pairs) {
  return pairs.some(function (p) { return p[1] && p[1].trim() !== ''; });
}

function saveDraft() {
  try {
    const pairs = serializeDraft();
    if (!hasMeaningfulData(pairs)) {
      localStorage.removeItem(DRAFT_KEY);
      if (draftSaveStatus) draftSaveStatus.textContent = '';
      return;
    }
    localStorage.setItem(DRAFT_KEY, JSON.stringify({ savedAt: Date.now(), pairs: pairs }));
    if (draftSaveStatus) draftSaveStatus.textContent = 'Draft saved just now.';
  } catch (e) {
    // Private browsing / storage disabled / quota exceeded -- the form
    // still works, just without the safety net. Nothing to show the
    // applicant here; failing loudly over an autosave would be worse.
  }
}

function clearDraft() {
  try { localStorage.removeItem(DRAFT_KEY); } catch (e) {}
  if (draftSaveStatus) draftSaveStatus.textContent = '';
}

function restoreDraft(pairs) {
  pairs.forEach(function (pair) {
    const name = pair[0];
    const value = pair[1];
    const els = form.querySelectorAll('[name="' + CSS.escape(name) + '"]');
    if (!els.length) return;
    if (els[0].type === 'checkbox' || els[0].type === 'radio') {
      els.forEach(function (el) { if (el.value === value) el.checked = true; });
    } else {
      els[0].value = value;
    }
  });
  // Re-sync UI state that depends on the values just restored, same as a
  // real user interacting with these controls would trigger.
  document.querySelectorAll('.requirement-row').forEach(function (row) {
    const fileInput = row.querySelector('.requirement-file');
    const laterCheckbox = row.querySelector('.requirement-later');
    if (fileInput && laterCheckbox) fileInput.disabled = laterCheckbox.checked;
  });
  suggestApplicantType();
}

(function initDraftBanner() {
  let saved = null;
  try {
    const raw = localStorage.getItem(DRAFT_KEY);
    if (raw) saved = JSON.parse(raw);
  } catch (e) {
    saved = null;
  }

  if (!saved || !saved.pairs || (Date.now() - saved.savedAt) > DRAFT_MAX_AGE_MS || !hasMeaningfulData(saved.pairs)) {
    return;
  }

  draftBanner.innerHTML =
    '<span><strong>You have an unfinished application.</strong> Pick up where you left off — you\'ll need to re-attach any documents, since browsers don\'t let a page remember files across visits.</span>' +
    '<span class="draft-actions">' +
      '<button type="button" class="btn btn-sm btn-outline-secondary" id="discardDraftBtn">Start fresh</button>' +
      '<button type="button" class="btn btn-sm btn-success" id="restoreDraftBtn">Restore draft</button>' +
    '</span>';
  draftBanner.style.display = 'flex';

  document.getElementById('restoreDraftBtn').addEventListener('click', function () {
    restoreDraft(saved.pairs);
    draftBanner.style.display = 'none';
    if (draftSaveStatus) draftSaveStatus.textContent = 'Draft restored.';
  });
  document.getElementById('discardDraftBtn').addEventListener('click', function () {
    clearDraft();
    draftBanner.style.display = 'none';
  });
})();

let draftSaveTimer = null;
form.addEventListener('input', function () {
  if (draftSaveStatus) draftSaveStatus.textContent = 'Saving…';
  clearTimeout(draftSaveTimer);
  draftSaveTimer = setTimeout(saveDraft, 600);
});

function showBanner(el, type, html) {
  el.className = 'form-banner ' + type;
  el.innerHTML = html;
  el.style.display = 'block';
  el.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function escapeHtml(str) {
  const div = document.createElement('div');
  div.textContent = str;
  return div.innerHTML;
}

function renderReferenceSlip(referenceId, summary) {
  refBanner.innerHTML =
    '<div class="reference-slip" id="printableSlip">' +
      '<div class="banner-title">Your reference ID: <strong>' + escapeHtml(referenceId) + '</strong></div>' +
      '<p>' + escapeHtml(summary.name) + ' &middot; ' + escapeHtml(summary.program) + '</p>' +
      '<p>Term: ' + escapeHtml(summary.school_year) + '</p>' +
      '<p>Bring this reference ID and your physical documents to the admissions counter to complete your application.</p>' +
      '<button type="button" class="btn" id="printSlipBtn">Print this slip</button>' +
    '</div>';
  refBanner.style.display = 'block';
  refBanner.scrollIntoView({ behavior: 'smooth', block: 'center' });

  document.getElementById('printSlipBtn').addEventListener('click', function () {
    window.print();
  });
}

function submitApplication() {
  banner.style.display = 'none';
  refBanner.style.display = 'none';
  submitBtn.disabled = true;
  submitBtn.innerHTML = 'Submitting…';

  const formData = new FormData(form);

  fetch(form.action, {
    method: 'POST',
    body: formData,
  })
    .then(function (res) { return res.json(); })
    .then(function (data) {
      submitBtn.disabled = false;
      submitBtn.innerHTML = 'Submit application <iconify-icon icon="mdi:arrow-right"></iconify-icon>';

      if (!data.success) {
        const items = data.errors.map(function (err) {
          return '<li>' + escapeHtml(err) + '</li>';
        }).join('');
        showBanner(banner, 'error',
          '<div class="banner-title">Application incomplete</div>' +
          '<ul>' + items + '</ul>'
        );
        return;
      }

      renderReferenceSlip(data.reference_id, data.summary);

      Swal.fire({
        icon: 'success',
        title: 'Application submitted',
        html: 'Your reference ID is <strong>' + escapeHtml(data.reference_id) + '</strong>.<br>' +
          (data.email_sent
            ? 'A printable admission slip was emailed to you.<br>'
            : '') +
          'Bring it and your documents to campus to finish your admission.',
        confirmButtonColor: '#1F2E28'
      });

      form.reset();
      const rows = document.getElementById('historyRows');
      while (rows.children.length > 1) {
        rows.removeChild(rows.lastChild);
      }
      clearDraft();
    })
    .catch(function (err) {
      submitBtn.disabled = false;
      submitBtn.innerHTML = 'Submit application <iconify-icon icon="mdi:arrow-right"></iconify-icon>';
      showBanner(banner, 'error',
        '<div class="banner-title">Something went wrong</div>' +
        'Could not reach the server. Please try again.'
      );
      console.error(err);
    });
}

form.addEventListener('submit', function (e) {
  e.preventDefault();

  // Only checks when the widget is actually on the page (RECAPTCHA_SITE_KEY
  // configured) -- window.grecaptcha won't exist at all otherwise. The real
  // enforcement is server-side either way; this just avoids a round trip
  // for the common case of a student just forgetting to check the box.
  if (window.grecaptcha && typeof grecaptcha.getResponse === 'function' && !grecaptcha.getResponse()) {
    Swal.fire({
      icon: 'warning',
      title: 'Verification required',
      text: 'Please complete the "I\'m not a robot" check before submitting.',
      confirmButtonColor: '#2f8f4e',
    });
    return;
  }

  Swal.fire({
    icon: 'question',
    title: 'Submit this application?',
    text: 'Make sure your details are correct — you\'ll need to bring matching documents on campus.',
    showCancelButton: true,
    confirmButtonText: 'Yes, submit',
    cancelButtonText: 'Cancel',
    confirmButtonColor: '#2f8f4e',
    cancelButtonColor: '#aaa',
    reverseButtons: true
  }).then(function (result) {
    if (result.isConfirmed) {
      submitApplication();
    }
  });
});