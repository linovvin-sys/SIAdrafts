document.addEventListener('DOMContentLoaded', function () {

  const table = document.getElementById('admissionTable');
  if (!table) return;

  const dt = initDataTable('#admissionTable', { order: [[3, 'desc']] });

  const statusEl = document.getElementById('admissionStatusFilter');
  const dateEl   = document.getElementById('admissionDateFilter');

  $.fn.dataTable.ext.search.push(function (settings, searchRow, index, rowData, counter) {
    if (settings.nTable.id !== 'admissionTable') return true;
    const row = dt.row(index).node();
    if (!row) return true;

    const status = statusEl?.value || '';
    const date   = dateEl?.value || '';

    if (status && row.dataset.status !== status) return false;
    if (date && row.dataset.date !== date) return false;
    return true;
  });

  if (statusEl) statusEl.addEventListener('change', () => dt.draw());
  if (dateEl) dateEl.addEventListener('change', () => dt.draw());

});
