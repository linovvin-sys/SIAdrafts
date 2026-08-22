function initDataTable(selector, options) {
  const dt = $(selector).DataTable(Object.assign({
    pageLength: 10,
    lengthChange: false,
    language: { search: '', searchPlaceholder: 'Search...' },
    dom: '<"dt-toolbar"f>rt<"dt-footer"ip>',
  }, options || {}));

  // Several pages (manage_user.php, etc.) already have their own filter
  // row above the table (role/status dropdowns in .filter-bar or
  // .user-filter-bar). Left alone, DataTables' own search box renders as
  // a second, separately-padded, right-aligned toolbar directly under
  // it — two disconnected rows instead of one.
  const tableEl = $(selector).get(0);
  const panel = tableEl ? tableEl.closest('.panel') : null;
  const existingFilterBar = panel ? panel.querySelector('.filter-bar, .user-filter-bar') : null;
  const dtToolbar = panel ? panel.querySelector('.dt-toolbar') : null;
  if (existingFilterBar && dtToolbar) {
    // Some pages (sections.php, professors.php) already have their own
    // text input wired to table.search() by hand — DataTables' native
    // search box would just be a second, redundant field doing the same
    // thing. Only fold it in when the existing row is select-only
    // (manage_user.php's role/status filters), where there's nothing
    // else providing free-text search.
    const hasOwnSearchInput = !!existingFilterBar.querySelector('input[type="text"], input[type="search"]');
    const searchWrap = dtToolbar.querySelector('.dataTables_filter');
    if (searchWrap && !hasOwnSearchInput) {
      searchWrap.classList.add('dt-search-inline');
      existingFilterBar.appendChild(searchWrap);
    }
    dtToolbar.remove();
  }

  // Re-plays the row entrance on every redraw — initial load, page
  // change, search, sort — not just once. Safe now that every table
  // caps at 10 rows per page (see .dt-row-in in admin.css). Restarting a
  // CSS animation requires forcing a reflow between removing and
  // re-adding the class, or the browser just no-ops the re-add.
  dt.on('draw', function () {
    dt.rows({ page: 'current' }).nodes().each(function (tr, i) {
      tr.classList.remove('dt-row-in');
      tr.style.setProperty('--row-i', String(i));
      void tr.offsetWidth;
      tr.classList.add('dt-row-in');
    });
  });

  return dt;
}
