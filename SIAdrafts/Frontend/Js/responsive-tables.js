/**
 * Desktop-rows -> mobile-cards for every .data-table (Admin/Registrar/
 * Admission/Treasury) and .sp-table (Student/Professor) on the page.
 *
 * One shared script instead of hand-adding data-label attributes to
 * every <td> across 30+ table markups: reads each table's own <thead>
 * th text at runtime and stamps it onto the matching <td> as
 * data-label, which admin.css/student.css turn into a visible row label
 * via ::before once the layout switches to stacked cards below 768px.
 * Works whether or not DataTables has been applied (plain static tables
 * included).
 */
function applyResponsiveTableLabels(table) {
  if (!table) return;
  const headerCells = Array.from(table.querySelectorAll('thead th'));
  if (!headerCells.length) return;
  const labels = headerCells.map(function (th) { return th.textContent.trim(); });

  table.querySelectorAll('tbody tr').forEach(function (row) {
    Array.from(row.children).forEach(function (cell, i) {
      if (labels[i]) cell.setAttribute('data-label', labels[i]);
    });
  });
}

function initResponsiveTables(root) {
  (root || document).querySelectorAll('table.data-table, table.sp-table').forEach(function (table) {
    applyResponsiveTableLabels(table);
    // DataTables rebuilds <tbody> row/cell nodes from its own internal
    // cache on every redraw (pagination, search, sort) rather than just
    // hiding/showing the originals -- confirmed elsewhere in this app
    // (document_records.php's View button broke the same way for the
    // same reason) -- so rows past page 1, or any page reached only
    // after a search/sort, would otherwise never get labeled at all.
    if (window.jQuery && window.jQuery.fn && window.jQuery.fn.dataTable && window.jQuery.fn.dataTable.isDataTable(table)) {
      window.jQuery(table).on('draw.dt', function () { applyResponsiveTableLabels(table); });
    }
  });
}

document.addEventListener('DOMContentLoaded', function () { initResponsiveTables(document); });
