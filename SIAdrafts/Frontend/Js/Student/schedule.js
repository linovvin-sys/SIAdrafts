// Tap/click a schedule block to bring it to the front at a legible size --
// mainly for mobile, where there's no hover and block text runs small.
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
