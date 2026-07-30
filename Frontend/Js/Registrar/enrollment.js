document.addEventListener('DOMContentLoaded', function () {

  const table = document.getElementById('enrollmentTable');
  if (!table) return;

  const dt = initDataTable('#enrollmentTable', { order: [] });

  const statusEl  = document.getElementById('enrollmentStatusFilter');
  const yearEl    = document.getElementById('enrollmentYearFilter');
  const semEl     = document.getElementById('enrollmentSemFilter');
  const sectionEl = document.getElementById('enrollmentSectionFilter');

  $.fn.dataTable.ext.search.push(function (settings, searchRow, index, rowData, counter) {
    if (settings.nTable.id !== 'enrollmentTable') return true;
    const row = dt.row(index).node();
    if (!row) return true;

    const status  = statusEl?.value || '';
    const year    = yearEl?.value || '';
    const sem     = semEl?.value || '';
    const section = sectionEl?.value || '';

    if (status && row.dataset.status !== status) return false;
    if (year && row.dataset.year !== year) return false;
    if (sem && row.dataset.sem !== sem) return false;
    if (section && row.dataset.section !== section) return false;
    return true;
  });

  [statusEl, yearEl, semEl, sectionEl].forEach(el => {
    if (el) el.addEventListener('change', () => dt.draw());
  });

});
