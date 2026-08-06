function escHtml(s) {
  return String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

document.addEventListener('DOMContentLoaded', function () {

  function openModal(modal) {
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
  }
  function closeModal(modal) {
    modal.classList.remove('active');
    document.body.style.overflow = '';
  }

  document.querySelectorAll('[data-close]').forEach(btn => {
    btn.addEventListener('click', () => {
      const target = document.getElementById(btn.getAttribute('data-close'));
      if (target) closeModal(target);
    });
  });
  document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', (e) => {
      if (e.target === overlay) closeModal(overlay);
    });
  });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      document.querySelectorAll('.modal-overlay.active').forEach(closeModal);
    }
  });

  const API = '/SIAdrafts/Backend/api/';

  // ----- Search (plain show/hide, no DataTables — the section-group/child
  // row structure here mirrors Registrar/schedule.js's own hand-rolled
  // grouping, which intentionally avoids DataTables for the same reason) -----
  const searchEl = document.getElementById('classesSearch');
  const tableBody = document.querySelector('#classesTable tbody');
  if (searchEl && tableBody) {
    searchEl.addEventListener('input', () => {
      const q = searchEl.value.trim().toLowerCase();
      const rows = Array.from(tableBody.querySelectorAll('tr'));
      let currentGroupRow = null;
      let currentGroupMatches = false;
      let groupChildRows = [];

      function flushGroup() {
        if (!currentGroupRow) return;
        const anyChildMatch = groupChildRows.some(r => r.dataset.matched);
        const show = !q || currentGroupMatches || anyChildMatch;
        currentGroupRow.style.display = show ? '' : 'none';
        groupChildRows.forEach(r => {
          r.style.display = show && (!q || currentGroupMatches || r.dataset.matched) ? '' : 'none';
        });
      }

      rows.forEach(row => {
        if (row.classList.contains('section-group-row')) {
          flushGroup();
          currentGroupRow = row;
          currentGroupMatches = !q || (row.dataset.search || '').includes(q);
          groupChildRows = [];
        } else if (row.classList.contains('subject-row')) {
          const matched = !q || (row.dataset.search || '').includes(q);
          row.dataset.matched = matched ? '1' : '';
          groupChildRows.push(row);
        }
      });
      flushGroup();
    });
  }

  const rosterModal    = document.getElementById('rosterModal');
  const rosterSubtitle = document.getElementById('rosterSubtitle');
  const rosterBody     = document.getElementById('rosterTableBody');
  let lastRosterStudents = [];

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
        '<td>' + escHtml(s.student_no) + '</td>' +
        '<td>' + escHtml(name) + '</td>' +
        '</tr>';
    }).join('');
  }

  // ----- Per-class roster -----
  document.querySelectorAll('[data-view-roster]').forEach(btn => {
    btn.addEventListener('click', async () => {
      const scheduleId = btn.getAttribute('data-view-roster');
      const row = btn.closest('tr');
      const subjectLabel = row ? row.querySelector('.subject-name')?.textContent.trim() : '';

      rosterSubtitle.textContent = subjectLabel || 'Class roster';
      rosterBody.innerHTML = '<tr><td colspan="2" style="text-align:center;">Loading…</td></tr>';
      openModal(rosterModal);

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
      openModal(rosterModal);

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
