document.addEventListener('DOMContentLoaded', function () {

  const searchEl = document.getElementById('courseSearch');
  const body     = document.getElementById('courseBody');
  const emptyEl  = document.getElementById('courseEmptyState');

  if (!body) return;

  const rows = [...body.querySelectorAll('tr[data-search]')];
  if (!rows.length) return;

  searchEl?.addEventListener('input', () => {
    const search = searchEl.value.trim().toLowerCase();
    let visible = 0;

    rows.forEach(row => {
      const show = !search || row.dataset.search.includes(search);
      row.style.display = show ? '' : 'none';
      if (show) visible++;
    });

    if (emptyEl) emptyEl.style.display = visible ? 'none' : 'block';
  });

});
