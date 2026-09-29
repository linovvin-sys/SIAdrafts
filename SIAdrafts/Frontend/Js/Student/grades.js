// Term picker — navigates with year_level/semester query params. Same
// pattern as Professor/schedule.js's termSelect, just keyed on year_level
// instead of school_year since grades.php filters by academic year, not
// a specific school_year string.
(function () {
  var select = document.getElementById('termSelect');
  if (!select) return;
  select.addEventListener('change', function () {
    var parts = select.value.split('|');
    var params = new URLSearchParams({ year_level: parts[0], semester: parts[1] });
    window.location = '?' + params.toString();
  });
})();
