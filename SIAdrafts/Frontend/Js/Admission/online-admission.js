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
  const drop = row.querySelector('.file-drop');
  const textEl = drop ? drop.querySelector('.file-drop-text') : null;
  const iconEl = drop ? drop.querySelector('.file-drop-icon') : null;
  if (!fileInput || !laterCheckbox || !drop || !textEl) return;

  function renderFileState() {
    const file = fileInput.files[0];
    const existingRemove = drop.querySelector('.file-drop-remove');
    if (existingRemove) existingRemove.remove();

    if (file) {
      drop.classList.add('has-file');
      textEl.textContent = file.name;
      if (iconEl) iconEl.setAttribute('icon', 'mdi:file-check-outline');
      row.classList.add('has-file');
      const removeBtn = document.createElement('span');
      removeBtn.className = 'file-drop-remove';
      removeBtn.setAttribute('role', 'button');
      removeBtn.setAttribute('aria-label', 'Remove selected file');
      removeBtn.innerHTML = '<iconify-icon icon="mdi:close"></iconify-icon>';
      removeBtn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        fileInput.value = '';
        renderFileState();
      });
      drop.appendChild(removeBtn);
    } else {
      drop.classList.remove('has-file');
      textEl.textContent = 'Choose file or drag here';
      if (iconEl) iconEl.setAttribute('icon', 'mdi:tray-arrow-up');
      row.classList.remove('has-file');
    }
  }

  laterCheckbox.addEventListener('change', function () {
    fileInput.disabled = laterCheckbox.checked;
    if (laterCheckbox.checked) {
      fileInput.value = '';
      renderFileState();
    }
    drop.classList.toggle('is-disabled', laterCheckbox.checked);
    row.dispatchEvent(new Event('sp-progress-check', { bubbles: true }));
  });
  fileInput.addEventListener('change', function () {
    if (fileInput.files.length > 0) laterCheckbox.checked = false;
    renderFileState();
    row.dispatchEvent(new Event('sp-progress-check', { bubbles: true }));
  });

  // Drag-and-drop onto the label itself -- the hidden native input only
  // ever reacts to its own click/file-picker flow, not a dropped file, so
  // this wires the drop target's dataTransfer back into it manually.
  ['dragenter', 'dragover'].forEach(function (evt) {
    drop.addEventListener(evt, function (e) {
      e.preventDefault();
      if (!fileInput.disabled) drop.classList.add('is-dragover');
    });
  });
  ['dragleave', 'drop'].forEach(function (evt) {
    drop.addEventListener(evt, function (e) {
      e.preventDefault();
      drop.classList.remove('is-dragover');
    });
  });
  drop.addEventListener('drop', function (e) {
    if (fileInput.disabled) return;
    const dropped = e.dataTransfer && e.dataTransfer.files;
    if (dropped && dropped.length) {
      fileInput.files = dropped;
      laterCheckbox.checked = false;
      renderFileState();
      row.dispatchEvent(new Event('sp-progress-check', { bubbles: true }));
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
/* ===== Scroll-reveal entrance (one authored moment, reused per section) ===== */
(function () {
  const sections = document.querySelectorAll('.form-section');
  if (!sections.length) return;

  if (!('IntersectionObserver' in window)) {
    sections.forEach(function (s) { s.classList.add('is-revealed'); });
    return;
  }

  const revealObserver = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        entry.target.classList.add('is-revealed');
        revealObserver.unobserve(entry.target);
      }
    });
  }, { threshold: 0.15, rootMargin: '0px 0px -8% 0px' });

  sections.forEach(function (s) { revealObserver.observe(s); });
})();

/* ===== Progress rail: active-section tracking, real completion state,
   and click-to-scroll ===== */
(function () {
  const track = document.getElementById('progressTrack');
  if (!track) return;

  const steps = Array.from(track.querySelectorAll('.progress-step'));
  const sections = steps.map(function (step) {
    return document.getElementById(step.dataset.target);
  });

  // Jump nav -- offset accounts for the sticky rail itself (~60px) plus
  // breathing room, so the target section's heading doesn't land flush
  // against the rail.
  steps.forEach(function (step, i) {
    step.addEventListener('click', function () {
      const target = sections[i];
      if (!target) return;
      const railHeight = track.closest('.progress-rail').offsetHeight;
      const y = target.getBoundingClientRect().top + window.scrollY - railHeight - 28;
      window.scrollTo({ top: y, behavior: 'smooth' });
    });
  });

  // Active section -- whichever section currently owns the most of the
  // band just below the sticky rail, not simply "first one touching the
  // viewport" (which flickers between two adjacent sections at the
  // boundary).
  function updateActiveStep() {
    const railBottom = track.getBoundingClientRect().bottom;
    let activeIndex = 0;
    let bestScore = -Infinity;
    sections.forEach(function (section, i) {
      if (!section) return;
      const rect = section.getBoundingClientRect();
      const score = rect.top <= railBottom + 40 ? -(railBottom - rect.top) : -(rect.top - railBottom) - 100000;
      if (rect.top <= railBottom + 40 && rect.bottom > railBottom) {
        activeIndex = i;
        bestScore = Infinity;
      } else if (score > bestScore) {
        bestScore = score;
        activeIndex = i;
      }
    });
    steps.forEach(function (step, i) { step.classList.toggle('is-active', i === activeIndex); });
  }

  // Real completion -- every [required] field in the section is filled/
  // valid, with two section-specific exceptions where "required" doesn't
  // map cleanly onto the markup: Requirements (satisfied per-row by either
  // a file or "I'll submit later") and Academic History (optional overall,
  // signaled complete once the first school name is entered).
  function isRequirementsSectionComplete(section) {
    const rows = section.querySelectorAll('.requirement-row');
    if (!rows.length) return true;
    return Array.from(rows).every(function (row) {
      const fileInput = row.querySelector('.requirement-file');
      const later = row.querySelector('.requirement-later');
      return (fileInput && fileInput.files && fileInput.files.length > 0) || (later && later.checked);
    });
  }
  function isHistorySectionComplete(section) {
    const firstName = section.querySelector('input[name="school_name[]"]');
    return !!(firstName && firstName.value.trim() !== '');
  }
  function isSectionComplete(section) {
    if (!section) return false;
    if (section.id === 'section-requirements') return isRequirementsSectionComplete(section);
    if (section.id === 'section-history') return isHistorySectionComplete(section);
    const required = section.querySelectorAll('[required]');
    if (!required.length) return false;
    return Array.from(required).every(function (field) {
      if (field.type === 'checkbox' || field.type === 'radio') return field.checked;
      return field.value.trim() !== '' && field.checkValidity();
    });
  }
  function updateCompletion() {
    steps.forEach(function (step, i) {
      step.classList.toggle('is-complete', isSectionComplete(sections[i]));
    });
  }

  function updateAll() {
    updateActiveStep();
    updateCompletion();
  }

  let ticking = false;
  window.addEventListener('scroll', function () {
    if (ticking) return;
    ticking = true;
    requestAnimationFrame(function () { updateActiveStep(); ticking = false; });
  }, { passive: true });

  const formEl = document.getElementById('admissionForm');
  if (formEl) {
    formEl.addEventListener('input', updateCompletion);
    formEl.addEventListener('change', updateCompletion);
    formEl.addEventListener('sp-progress-check', updateCompletion);
  }

  window.addEventListener('resize', updateAll);
  updateAll();
})();
