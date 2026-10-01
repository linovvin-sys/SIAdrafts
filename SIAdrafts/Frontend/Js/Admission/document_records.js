document.addEventListener('DOMContentLoaded', function () {

  function openModal(modal) {
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
  }
  function closeModal(modal) {
    modal.classList.remove('active');
    document.body.style.overflow = '';
  }

  function escapeHtml(str) {
    return String(str).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  const STATUS_LABEL = {
    submitted: 'Submitted',
    submitted_online: 'Submitted Online',
    will_submit_later: 'Will Submit Later',
    pending: 'Pending',
  };

  const tableEl = document.getElementById('documentRecordsTable');
  const table = tableEl ? initDataTable('#documentRecordsTable', { order: [] }) : null;

  const searchEl = document.getElementById('docSearch');
  const programFilterEl = document.getElementById('docProgramFilter');
  const completenessFilterEl = document.getElementById('docCompletenessFilter');

  if (table) {
    // Same pattern as Registrar/sections.js: drive both the text search and
    // the two dropdown filters through DataTables' own search API, so
    // filtering stays in sync with client-side pagination instead of just
    // hiding <tr> elements (which DataTables silently un-hides on redraw).
    $.fn.dataTable.ext.search.push(function (settings, searchData, index) {
      if (settings.nTable.id !== 'documentRecordsTable') return true;
      const row = table.row(index).node();
      if (!row) return true;

      const program = programFilterEl?.value || '';
      if (program && row.dataset.program !== program) return false;

      const completeness = completenessFilterEl?.value || '';
      if (completeness && row.dataset.completeness !== completeness) return false;

      return true;
    });

    if (searchEl) searchEl.addEventListener('input', () => table.search(searchEl.value).draw());
    if (programFilterEl) programFilterEl.addEventListener('change', () => table.draw());
    if (completenessFilterEl) completenessFilterEl.addEventListener('change', () => table.draw());
  }

  // ===== View Documents modal =====
  const viewModal = document.getElementById('viewDocumentsModal');
  const listEl = document.getElementById('viewDocumentsList');
  const subtitleEl = document.getElementById('viewDocumentsSubtitle');
  const docsByApplicant = window.APPLICANT_DOCUMENTS || {};

  // Delegated on document rather than bound per-button: DataTables rebuilds
  // the <tr>/<button> nodes from its own internal data cache on every
  // search and page change (it doesn't just hide/show the original
  // elements), which silently drops any listener attached directly to a
  // button here -- confirmed as exactly why this only ever worked on page 1
  // before any search/page interaction had triggered a redraw yet.
  document.addEventListener('click', function (e) {
    const btn = e.target.closest('[data-view-documents]');
    if (btn) {
      const applicantId = btn.dataset.viewDocuments;
      const docs = docsByApplicant[applicantId] || [];

      subtitleEl.textContent = btn.dataset.applicantName || '';
      listEl.innerHTML = docs.length ? docs.map(function (doc) {
        const hasFile = !!doc.file_path;
        const label = STATUS_LABEL[doc.status] || doc.status;
        const pillClass = hasFile ? 'approved' : 'pending';
        const uploaded = doc.uploaded_at ? new Date(doc.uploaded_at).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' }) : '—';
        // No soft copy to "View" -- this is the hard copy's whereabouts
        // instead, so reviewing staff aren't left with no way to find a
        // document that was handed over face-to-face or walked in later.
        const locationLine = (!hasFile && doc.storage_location)
          ? '<div class="text-muted" style="font-size:12px;"><iconify-icon icon="mdi:archive-outline"></iconify-icon> Filed at: ' + escapeHtml(doc.storage_location) + '</div>'
          : (!hasFile && doc.status === 'submitted'
            ? '<div class="text-muted" style="font-size:12px;">Received, filing location not recorded.</div>'
            : '');
        return '<div class="row-actions" style="justify-content:space-between;padding:12px 0;border-bottom:1px solid var(--line-200);">' +
          '<div>' +
            '<div style="font-weight:600;">' + escapeHtml(doc.document_name) + '</div>' +
            '<div class="text-muted" style="font-size:12px;">' + escapeHtml(uploaded) + '</div>' +
            locationLine +
          '</div>' +
          '<div style="display:flex;align-items:center;gap:10px;">' +
            '<span class="status-pill status-pill--' + pillClass + '">' + escapeHtml(label) + '</span>' +
            (hasFile
              ? '<a class="btn btn-outline" style="padding:4px 10px;font-size:12px" target="_blank" rel="noopener" href="/SIAdrafts/Backend/api/Admission/view_requirement_document.php?document_id=' + encodeURIComponent(doc.document_id) + '">View</a>'
              : '') +
          '</div>' +
        '</div>';
      }).join('') : '<p class="text-muted">No documents on file.</p>';

      openModal(viewModal);
    }
  });

  document.querySelectorAll('[data-close]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const target = document.getElementById(btn.getAttribute('data-close'));
      if (target) closeModal(target);
    });
  });
});
