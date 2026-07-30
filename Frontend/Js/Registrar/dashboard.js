document.addEventListener('DOMContentLoaded', function () {

  function wireTableFilter({ tableId, statusId }) {
    const tableEl = document.getElementById(tableId);
    if (!tableEl) return;

    const dt = initDataTable('#' + tableId, { order: [] });
    const statusEl = document.getElementById(statusId);

    $.fn.dataTable.ext.search.push(function (settings, searchRow, index, rowData, counter) {
      if (settings.nTable.id !== tableId) return true;
      const row = dt.row(index).node();
      if (!row) return true;

      const status = statusEl?.value || '';
      if (status && row.dataset.status !== status) return false;
      return true;
    });

    if (statusEl) statusEl.addEventListener('change', () => dt.draw());
  }

  wireTableFilter({ tableId: 'recentSchedulesTable', statusId: 'scheduleStatusFilter' });
  wireTableFilter({ tableId: 'registrarStaffTable', statusId: 'staffStatusFilter' });

});
