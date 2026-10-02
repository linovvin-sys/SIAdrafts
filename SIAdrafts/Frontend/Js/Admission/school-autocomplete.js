/**
 * School-name type-ahead for online_admission.php's Academic History
 * rows. Backed by Backend/api/Admission/search_schools.php (an indexed
 * SQLite search over DepEd's school directory), not a hard picker -- the
 * underlying <input> stays a plain free-text field the whole time, so a
 * school that genuinely isn't in the directory (closed, renamed, abroad)
 * can still just be typed, never blocked.
 */
(function () {
  const DEBOUNCE_MS = 220;
  const MIN_CHARS = 2;

  function initAutocomplete(root) {
    const input = root.querySelector('input');
    const panel = root.querySelector('.school-suggestions');
    if (!input || !panel) return;

    let results = [];
    let activeIndex = -1;
    let debounceTimer = null;
    let requestToken = 0;

    function closePanel() {
      panel.hidden = true;
      panel.innerHTML = '';
      activeIndex = -1;
      input.setAttribute('aria-expanded', 'false');
      input.removeAttribute('aria-activedescendant');
    }

    function renderResults() {
      if (!results.length) {
        closePanel();
        return;
      }
      panel.innerHTML = results.map(function (school, i) {
        const where = [school.municipality, school.province].filter(Boolean).join(', ');
        return '<div class="school-suggestion" role="option" id="school-opt-' + i + '" data-index="' + i + '">' +
          '<iconify-icon icon="mdi:school-outline"></iconify-icon>' +
          '<div class="school-suggestion-text">' +
            '<div class="school-suggestion-name">' + escapeHtml(school.name) + '</div>' +
            (where ? '<div class="school-suggestion-where">' + escapeHtml(where) + '</div>' : '') +
          '</div>' +
        '</div>';
      }).join('');
      panel.hidden = false;
      input.setAttribute('aria-expanded', 'true');
      setActive(-1);

      Array.from(panel.querySelectorAll('.school-suggestion')).forEach(function (el) {
        el.addEventListener('mousedown', function (e) {
          // mousedown (not click) fires before the input's blur, so the
          // panel is still open and this selection wins the race.
          e.preventDefault();
          selectResult(parseInt(el.dataset.index, 10));
        });
      });
    }

    function setActive(index) {
      activeIndex = index;
      const options = panel.querySelectorAll('.school-suggestion');
      options.forEach(function (el, i) {
        el.classList.toggle('is-active', i === index);
      });
      if (index >= 0 && options[index]) {
        input.setAttribute('aria-activedescendant', options[index].id);
        options[index].scrollIntoView({ block: 'nearest' });
      } else {
        input.removeAttribute('aria-activedescendant');
      }
    }

    function selectResult(index) {
      const school = results[index];
      if (!school) return;
      input.value = school.name;
      closePanel();
    }

    function escapeHtml(str) {
      return String(str).replace(/[&<>"']/g, function (c) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
      });
    }

    function search(query) {
      const token = ++requestToken;
      fetch('/SIAdrafts/Backend/api/Admission/search_schools.php?q=' + encodeURIComponent(query))
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (token !== requestToken) return; // a newer keystroke's request already landed
          results = data.results || [];
          renderResults();
        })
        .catch(function () { /* silent -- free text still works either way */ });
    }

    input.addEventListener('input', function () {
      const query = input.value.trim();
      clearTimeout(debounceTimer);
      if (query.length < MIN_CHARS) {
        results = [];
        closePanel();
        return;
      }
      debounceTimer = setTimeout(function () { search(query); }, DEBOUNCE_MS);
    });

    input.addEventListener('keydown', function (e) {
      if (panel.hidden) return;
      if (e.key === 'ArrowDown') {
        e.preventDefault();
        setActive(Math.min(activeIndex + 1, results.length - 1));
      } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        setActive(Math.max(activeIndex - 1, 0));
      } else if (e.key === 'Enter') {
        if (activeIndex >= 0) {
          e.preventDefault();
          selectResult(activeIndex);
        }
      } else if (e.key === 'Escape') {
        closePanel();
      }
    });

    input.addEventListener('blur', function () {
      // Short delay so a suggestion's own mousedown (which already ran
      // preventDefault) isn't raced out by this closing the panel first.
      setTimeout(closePanel, 120);
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.school-autocomplete').forEach(initAutocomplete);
  });
})();
