// Adds another academic-history row block, same as the staff form.
document.getElementById('addHistoryRow').addEventListener('click', function () {
  const rows = document.getElementById('historyRows');
  const count = rows.querySelectorAll('.history-row').length;
  const block = document.createElement('div');
  block.className = 'history-row';
  block.innerHTML = `
    <span class="row-tag">Previous ${count}</span>
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">School name</label>
        <input type="text" class="form-control" name="school_name[]">
      </div>
      <div class="col-md-6">
        <label class="form-label">School address</label>
        <input type="text" class="form-control" name="school_address[]">
      </div>
      <div class="col-md-4">
        <label class="form-label">Year graduated / last attended</label>
        <input type="text" class="form-control" name="school_year[]">
      </div>
      <div class="col-md-4">
        <label class="form-label">Strand / track (if SHS)</label>
        <input type="text" class="form-control" name="school_strand[]">
      </div>
      <div class="col-md-4">
        <label class="form-label">General average / GPA</label>
        <input type="text" class="form-control" name="school_gpa[]">
      </div>
    </div>
  `;
  rows.appendChild(block);
});

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
        html: 'Your reference ID is <strong>' + escapeHtml(data.reference_id) + '</strong>.<br>Bring it and your documents to campus to finish your admission.',
        confirmButtonColor: '#2f8f4e'
      });

      form.reset();
      const rows = document.getElementById('historyRows');
      while (rows.children.length > 1) {
        rows.removeChild(rows.lastChild);
      }
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