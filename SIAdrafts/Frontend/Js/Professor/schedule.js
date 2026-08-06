// Tap/click a schedule block to bring it to the front at a legible size --
// copied verbatim from Frontend/Js/Student/schedule.js (data-shape agnostic,
// works on any .sp-sch-block regardless of what page rendered it).
(function () {
  var blocks = document.querySelectorAll('.sp-sch-block');
  function collapseAll() {
    blocks.forEach(function (b) { b.classList.remove('is-expanded'); b.setAttribute('aria-expanded', 'false'); });
  }
  function toggle(block) {
    var wasExpanded = block.classList.contains('is-expanded');
    collapseAll();
    if (!wasExpanded) { block.classList.add('is-expanded'); block.setAttribute('aria-expanded', 'true'); }
  }
  blocks.forEach(function (block) {
    block.setAttribute('tabindex', '0');
    block.setAttribute('role', 'button');
    block.setAttribute('aria-expanded', 'false');
    block.addEventListener('click', function () { toggle(block); });
    block.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggle(block); }
    });
  });
  document.addEventListener('click', function (e) {
    if (!e.target.closest('.sp-sch-block')) collapseAll();
  });
})();

// Term picker — navigates with school_year/semester query params.
(function () {
  var select = document.getElementById('termSelect');
  if (!select) return;
  select.addEventListener('change', function () {
    var parts = select.value.split('|');
    var params = new URLSearchParams({ school_year: parts[0], semester: parts[1] });
    window.location = '?' + params.toString();
  });
})();
