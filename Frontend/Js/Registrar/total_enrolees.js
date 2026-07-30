document.addEventListener('DOMContentLoaded', function () {

  // Pages migrated to the DataTables baseline (Task 13) render the course
  // table with id="enroleesTable" and get search/sort/pagination for free
  // via initDataTable(). Skip the legacy manual filter below in that case
  // to avoid two competing search UIs on the same table.
  if (document.getElementById('enroleesTable')) {
    initDataTable('#enroleesTable', {
      order: [[0, 'asc']],
    });
    return;
  }

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
