// Adds another academic-history row block, copying the same field structure.
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

//  AJAX form submission 
  const form = document.getElementById('admissionForm');
  const banner = document.getElementById('formBanner');
  const submitBtn = form.querySelector('.btn-submit');
 
  function showBanner(type, html) {
    banner.className = 'form-banner ' + type;
    banner.innerHTML = html;
    banner.style.display = 'block';
    banner.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }
 
  function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  }
 
  //  AJAX submission 
  form.addEventListener('submit', function (e) {
    e.preventDefault();
 
    banner.style.display = 'none';
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
          showBanner('error',
            '<div class="banner-title">Application incomplete</div>' +
            '<ul>' + items + '</ul>'
          );
          return;
        }
 
        // success
        const s = data.summary;
        const docsHtml = s.documents.length
          ? s.documents.map(function (d) { return escapeHtml(d); }).join(', ')
          : 'None checked';
 
        showBanner('success',
          '<div class="banner-title">Application saved — record #' + data.applicant_id + '</div>' +
          escapeHtml(s.name) + ' · ' + escapeHtml(s.program) + '<br>' +
          'Guardian: ' + escapeHtml(s.guardian) + ' · Verified by: ' + escapeHtml(s.verified_by) + '<br>' +
          'Documents: ' + docsHtml
        );
 
        form.reset();
        // Collapse any extra academic-history rows added back to just the first one
        const rows = document.getElementById('historyRows');
        while (rows.children.length > 1) {
          rows.removeChild(rows.lastChild);
        }
      })
      .catch(function (err) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Submit application <iconify-icon icon="mdi:arrow-right"></iconify-icon>';
        showBanner('error',
          '<div class="banner-title">Something went wrong</div>' +
          'Could not reach the server. Please try again.'
        );
        console.error(err);
      });
  });