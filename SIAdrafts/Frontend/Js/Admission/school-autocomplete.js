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
      applySchoolLocation(school);
    }

    // The thing this was actually built to fix: School Name and School
    // Address were two completely independent fields, so nothing stopped
    // picking a real school and a real city that have nothing to do with
    // each other (confirmed live: "National College of Science and
    // Technology" picked alongside "Manila" -- its actual campus is in
    // Dasmariñas, Cavite). Once a school is chosen from the directory, its
    // own recorded municipality drives the address picker directly,
    // instead of asking the applicant to separately know and re-select
    // where their own school is.
    function applySchoolLocation(school) {
      const historyRow = root.closest('.history-row');
      const addressPicker = historyRow && historyRow.querySelector('.address-picker');
      if (!addressPicker || !window.PHLocations) return;

      const regionSelect   = addressPicker.querySelector('.address-region');
      const provinceSelect = addressPicker.querySelector('.address-province');
      const citySelect      = addressPicker.querySelector('.address-city');
      const muni = (school.municipality || '').trim().toLowerCase();
      if (!regionSelect || !provinceSelect || !citySelect || !muni) return;

      window.PHLocations.load().then(function (data) {
        const cityMatch = data.cities.find(function (c) { return c.name.toLowerCase() === muni; });
        if (!cityMatch) return; // no confident match -- leave the picker alone rather than guess
        const provinceMatch = data.provinces.find(function (p) { return p.code === cityMatch.province; });
        const regionMatch = provinceMatch && data.regions.find(function (r) { return r.code === provinceMatch.region; });
        if (!provinceMatch || !regionMatch) return;

        // Each assignment's change event is handled synchronously by
        // ph-address-picker.js (it repopulates the next select's options
        // in-place, no async step), so setting the next value immediately
        // after dispatching is safe and always lands on real options.
        regionSelect.value = regionMatch.code;
        regionSelect.dispatchEvent(new Event('change', { bubbles: true }));
        provinceSelect.value = provinceMatch.code;
        provinceSelect.dispatchEvent(new Event('change', { bubbles: true }));
        citySelect.value = cityMatch.code;
        citySelect.dispatchEvent(new Event('change', { bubbles: true }));

        showAutoFillNote(addressPicker, school);
      });
    }

    function showAutoFillNote(addressPicker, school) {
      let note = addressPicker.querySelector('.address-autofill-note');
      if (!note) {
        note = document.createElement('div');
        note.className = 'address-autofill-note';
        addressPicker.insertBefore(note, addressPicker.firstChild);
      }
      note.innerHTML = '<iconify-icon icon="mdi:check-decagram-outline"></iconify-icon> Location filled in from the school directory — adjust it below if this isn\'t right.';
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
