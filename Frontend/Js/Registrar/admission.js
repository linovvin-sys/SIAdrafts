document.addEventListener('DOMContentLoaded', function () {

  const searchEl = document.getElementById('admissionSearch');
  const statusEl = document.getElementById('admissionStatusFilter');
  const dateEl   = document.getElementById('admissionDateFilter');
  const body     = document.getElementById('admissionBody');
  const emptyEl  = document.getElementById('admissionEmptyState');

  if (!body) return;

  const rows = [...body.querySelectorAll('tr[data-search]')];
  if (!rows.length) return;

  function apply() {
    const search = (searchEl?.value || '').trim().toLowerCase();
    const status = statusEl?.value || '';
    const date   = dateEl?.value || '';
    let visible = 0;

    rows.forEach(row => {
      const matchesSearch = !search || row.dataset.search.includes(search);
      const matchesStatus = !status || row.dataset.status === status;
      const matchesDate   = !date || row.dataset.date === date;
      const show = matchesSearch && matchesStatus && matchesDate;
      row.style.display = show ? '' : 'none';
      if (show) visible++;
    });

    if (emptyEl) emptyEl.style.display = visible ? 'none' : 'block';
  }

  if (searchEl) searchEl.addEventListener('input', apply);
  if (statusEl) statusEl.addEventListener('change', apply);
  if (dateEl) dateEl.addEventListener('change', apply);

});
