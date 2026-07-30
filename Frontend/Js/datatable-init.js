function initDataTable(selector, options) {
  return $(selector).DataTable(Object.assign({
    pageLength: 25,
    lengthChange: false,
    language: { search: '', searchPlaceholder: 'Search...' },
    dom: '<"dt-toolbar"f>rt<"dt-footer"ip>',
  }, options || {}));
}
