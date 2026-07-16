document.addEventListener('DOMContentLoaded', function () {

  const searchEl  = document.getElementById('enrollmentSearch');
  const statusEl  = document.getElementById('enrollmentStatusFilter');
  const yearEl    = document.getElementById('enrollmentYearFilter');
  const semEl     = document.getElementById('enrollmentSemFilter');
  const sectionEl = document.getElementById('enrollmentSectionFilter');
  const body      = document.getElementById('enrollmentBody');
  const emptyEl   = document.getElementById('enrollmentEmptyState');

  if (!body) return;

  const rows = [...body.querySelectorAll('tr[data-search]')];
  if (!rows.length) return;

  function apply() {
    const search  = (searchEl?.value || '').trim().toLowerCase();
    const status  = statusEl?.value || '';
    const year    = yearEl?.value || '';
    const sem     = semEl?.value || '';
    const section = sectionEl?.value || '';
    let visible = 0;

    rows.forEach(row => {
      const matchesSearch  = !search || row.dataset.search.includes(search);
      const matchesStatus  = !status || row.dataset.status === status;
      const matchesYear    = !year || row.dataset.year === year;
      const matchesSem     = !sem || row.dataset.sem === sem;
      const matchesSection = !section || row.dataset.section === section;
      const show = matchesSearch && matchesStatus && matchesYear && matchesSem && matchesSection;
      row.style.display = show ? '' : 'none';
      if (show) visible++;
    });

    if (emptyEl) emptyEl.style.display = visible ? 'none' : 'block';
  }

  [searchEl, statusEl, yearEl, semEl, sectionEl].forEach(el => {
    if (!el) return;
    el.addEventListener(el.tagName === 'SELECT' ? 'change' : 'input', apply);
  });

});
