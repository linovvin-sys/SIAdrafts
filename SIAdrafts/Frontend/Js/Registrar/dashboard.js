document.addEventListener('DOMContentLoaded', function () {

  function wireFilter({ searchId, statusId, bodyId, emptyId }) {
    const searchEl = document.getElementById(searchId);
    const statusEl = document.getElementById(statusId);
    const body     = document.getElementById(bodyId);
    const emptyEl  = document.getElementById(emptyId);

    if (!body) return;

    const rows = [...body.querySelectorAll('tr[data-search]')];
    if (!rows.length) return;

    function apply() {
      const search = (searchEl?.value || '').trim().toLowerCase();
      const status = statusEl?.value || '';
      let visible = 0;

      rows.forEach(row => {
        const matchesSearch = !search || row.dataset.search.includes(search);
        const matchesStatus = !status || row.dataset.status === status;
        const show = matchesSearch && matchesStatus;
        row.style.display = show ? '' : 'none';
        if (show) visible++;
      });

      if (emptyEl) emptyEl.style.display = visible ? 'none' : 'block';
    }

    if (searchEl) searchEl.addEventListener('input', apply);
    if (statusEl) statusEl.addEventListener('change', apply);
  }

  wireFilter({
    searchId: 'scheduleSearch',
    statusId: 'scheduleStatusFilter',
    bodyId: 'recentSchedulesBody',
    emptyId: 'scheduleEmptyState',
  });

  wireFilter({
    searchId: 'staffSearch',
    statusId: 'staffStatusFilter',
    bodyId: 'registrarStaffBody',
    emptyId: 'staffEmptyState',
  });

});
