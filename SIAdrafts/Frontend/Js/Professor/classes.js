function escHtml(s) {
  return String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

document.addEventListener('DOMContentLoaded', function () {

  const API = '/SIAdrafts/Backend/api/';

  // ----- Search (plain show/hide across section groups + their rows) -----
  const searchEl = document.getElementById('classesSearch');
  const list = document.getElementById('classesList');
  if (searchEl && list) {
    searchEl.addEventListener('input', () => {
      const q = searchEl.value.trim().toLowerCase();
      list.querySelectorAll('.sp-class-group').forEach(group => {
        const groupMatches = !q || (group.dataset.search || '').includes(q);
        let anyRowMatches = false;
        group.querySelectorAll('.sp-class-row').forEach(row => {
          const rowMatches = !q || groupMatches || (row.dataset.search || '').includes(q);
          row.style.display = rowMatches ? '' : 'none';
          if (rowMatches) anyRowMatches = true;
        });
        group.style.display = (groupMatches || anyRowMatches) ? '' : 'none';
      });
    });
  }

  // ----- Roster dialog (native <dialog>, same open/close mechanics as the shared spConfirm) -----
  const rosterDialog   = document.getElementById('rosterDialog');
  const rosterSubtitle = document.getElementById('rosterSubtitle');
  const rosterBody     = document.getElementById('rosterTableBody');
  let lastRosterStudents = [];

  function openRoster() {
    if (typeof rosterDialog.showModal === 'function') rosterDialog.showModal();
  }
  function closeRoster() {
    rosterDialog.close();
  }

  document.getElementById('closeRosterDialog')?.addEventListener('click', closeRoster);
  rosterDialog?.addEventListener('click', (e) => {
    if (e.target === rosterDialog) closeRoster();
  });

  function renderRoster(students) {
    lastRosterStudents = students || [];
    if (lastRosterStudents.length === 0) {
      rosterBody.innerHTML = '<tr><td colspan="2" style="text-align:center;">No students enrolled yet.</td></tr>';
      return;
    }
    rosterBody.innerHTML = lastRosterStudents.map(s => {
      const name = [s.last_name, s.first_name].filter(Boolean).join(', ') +
        (s.middle_name ? ' ' + s.middle_name.charAt(0) + '.' : '');
      return '<tr>' +
        '<td class="sp-num">' + escHtml(s.student_no) + '</td>' +
        '<td>' + escHtml(name) + '</td>' +
        '</tr>';
    }).join('');
  }

  // ----- Per-class roster -----
  document.querySelectorAll('[data-view-roster]').forEach(btn => {
    btn.addEventListener('click', async () => {
      const scheduleId = btn.getAttribute('data-view-roster');
      const row = btn.closest('tr');
      const subjectLabel = row ? row.querySelector('.sp-class-subject')?.textContent.trim() : '';

      rosterSubtitle.textContent = subjectLabel || 'Class roster';
      rosterBody.innerHTML = '<tr><td colspan="2" style="text-align:center;">Loading…</td></tr>';
      openRoster();

      try {
        const res = await fetch(API + 'get_professor_roster.php?schedule_id=' + encodeURIComponent(scheduleId));
        const data = await res.json();
        if (data.error) {
          rosterBody.innerHTML = '<tr><td colspan="2" style="text-align:center;">' + escHtml(data.error) + '</td></tr>';
          return;
        }
        renderRoster(data.students);
      } catch (err) {
        rosterBody.innerHTML = '<tr><td colspan="2" style="text-align:center;">Could not load roster.</td></tr>';
      }
    });
  });

  // ----- Section master list -----
  document.querySelectorAll('[data-view-section-roster]').forEach(btn => {
    btn.addEventListener('click', async () => {
      const sectionId   = btn.getAttribute('data-view-section-roster');
      const schoolYear  = btn.getAttribute('data-school-year');
      const semester    = btn.getAttribute('data-semester');
      const sectionName = btn.getAttribute('data-section-name');

      rosterSubtitle.textContent = sectionName + ' — Master List (' + schoolYear + ', Sem ' + semester + ')';
      rosterBody.innerHTML = '<tr><td colspan="2" style="text-align:center;">Loading…</td></tr>';
      openRoster();

      try {
        const params = new URLSearchParams({ section_id: sectionId, school_year: schoolYear, semester: semester });
        const res = await fetch(API + 'get_professor_section_roster.php?' + params.toString());
        const data = await res.json();
        if (data.error) {
          rosterBody.innerHTML = '<tr><td colspan="2" style="text-align:center;">' + escHtml(data.error) + '</td></tr>';
          return;
        }
        renderRoster(data.students);
      } catch (err) {
        rosterBody.innerHTML = '<tr><td colspan="2" style="text-align:center;">Could not load roster.</td></tr>';
      }
    });
  });

  // ----- CSV export (client-side only, from the roster already on screen) -----
  const exportBtn = document.getElementById('exportRosterCsv');
  if (exportBtn) {
    exportBtn.addEventListener('click', () => {
      if (!lastRosterStudents.length) return;

      function csvField(v) {
        const s = String(v ?? '');
        return /[",\n]/.test(s) ? '"' + s.replace(/"/g, '""') + '"' : s;
      }

      const header = ['Student No.', 'Last Name', 'First Name', 'Middle Name', 'Email', 'Contact'];
      const lines = [header.join(',')];
      lastRosterStudents.forEach(s => {
        lines.push([
          csvField(s.student_no), csvField(s.last_name), csvField(s.first_name),
          csvField(s.middle_name), csvField(s.email), csvField(s.contact_number),
        ].join(','));
      });

      const blob = new Blob([lines.join('\n')], { type: 'text/csv;charset=utf-8;' });
      const url  = URL.createObjectURL(blob);
      const a    = document.createElement('a');
      a.href = url;
      a.download = 'roster.csv';
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(url);
    });
  }

});
