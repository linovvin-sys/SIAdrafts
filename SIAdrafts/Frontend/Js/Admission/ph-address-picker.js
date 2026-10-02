/**
 * Cascading Region -> Province -> City/Municipality picker, for any
 * element with class="address-picker" on the page (online_admission.php
 * uses it for Home Address and each Academic History row's School
 * Address). Data is a trimmed PSGC-derived set (Frontend/assets/
 * ph-locations/*.json, ~97KB total) fetched once and shared across every
 * picker instance on the page.
 *
 * Each .address-picker owns a hidden <input> (its first input[type=hidden]
 * descendant) that the rest of the form/backend sees as a single address
 * string -- assembled as "{detail}, {city}, {province}, {region}" on
 * every change, so no backend change was needed: it's still just one
 * text value in the same POST field it always was.
 */
(function () {
  const DATA_BASE = '/SIAdrafts/Frontend/assets/ph-locations/';
  let dataPromise = null;

  function loadData() {
    if (!dataPromise) {
      dataPromise = Promise.all([
        fetch(DATA_BASE + 'regions.json').then(r => r.json()),
        fetch(DATA_BASE + 'provinces.json').then(r => r.json()),
        fetch(DATA_BASE + 'cities.json').then(r => r.json()),
      ]).then(function ([regions, provinces, cities]) {
        return { regions, provinces, cities };
      });
    }
    return dataPromise;
  }

  // Exposed so school-autocomplete.js can cross-check a selected school's
  // real recorded municipality against this same region/province/city set
  // and auto-fill School Address from it -- same cached promise, not a
  // second fetch of the same ~97KB.
  window.PHLocations = { load: loadData };

  function fillSelect(select, items, placeholder) {
    select.innerHTML = '<option value="">' + placeholder + '</option>' +
      items.map(function (item) {
        return '<option value="' + item.code + '">' + item.name + '</option>';
      }).join('');
  }

  function initPicker(root, data) {
    const regionSelect   = root.querySelector('.address-region');
    const provinceSelect = root.querySelector('.address-province');
    const citySelect      = root.querySelector('.address-city');
    const detailInput     = root.querySelector('.address-detail');
    const hiddenInput     = root.querySelector('input[type="hidden"]');
    if (!regionSelect || !provinceSelect || !citySelect || !detailInput || !hiddenInput) return;

    // De-duplicate NCR's two entries sharing a code (a quirk of the
    // source dataset, "Ncr, City Of Manila, First District" and
    // "City Of Manila" both filed under 1339) -- first one wins.
    const seenProvinceCodes = new Set();
    const provincesClean = data.provinces.filter(function (p) {
      const key = p.code + '|' + p.region;
      if (seenProvinceCodes.has(key)) return false;
      seenProvinceCodes.add(key);
      return true;
    });

    fillSelect(regionSelect, data.regions, 'Region');

    function syncHidden() {
      const regionName   = regionSelect.selectedOptions[0] ? regionSelect.selectedOptions[0].textContent : '';
      const provinceName = provinceSelect.selectedOptions[0] ? provinceSelect.selectedOptions[0].textContent : '';
      const cityName      = citySelect.selectedOptions[0] ? citySelect.selectedOptions[0].textContent : '';
      const detail        = detailInput.value.trim();
      const parts = [detail, cityName, provinceName, regionName].filter(Boolean);
      hiddenInput.value = parts.join(', ');
      root.dispatchEvent(new Event('sp-progress-check', { bubbles: true }));
    }

    regionSelect.addEventListener('change', function () {
      const regionCode = regionSelect.value;
      provinceSelect.value = '';
      citySelect.innerHTML = '<option value="">City / Municipality</option>';
      citySelect.disabled = true;
      if (!regionCode) {
        provinceSelect.innerHTML = '<option value="">Province</option>';
        provinceSelect.disabled = true;
        syncHidden();
        return;
      }
      const provincesForRegion = provincesClean.filter(function (p) { return p.region === regionCode; });
      fillSelect(provinceSelect, provincesForRegion, 'Province / District');
      provinceSelect.disabled = false;
      syncHidden();
    });

    provinceSelect.addEventListener('change', function () {
      const provinceCode = provinceSelect.value;
      citySelect.value = '';
      if (!provinceCode) {
        citySelect.innerHTML = '<option value="">City / Municipality</option>';
        citySelect.disabled = true;
        syncHidden();
        return;
      }
      const citiesForProvince = data.cities.filter(function (c) { return c.province === provinceCode; });
      fillSelect(citySelect, citiesForProvince, 'City / Municipality');
      citySelect.disabled = false;
      syncHidden();
    });

    citySelect.addEventListener('change', syncHidden);
    detailInput.addEventListener('input', syncHidden);
  }

  document.addEventListener('DOMContentLoaded', function () {
    const pickers = document.querySelectorAll('.address-picker');
    if (!pickers.length) return;
    loadData().then(function (data) {
      pickers.forEach(function (root) { initPicker(root, data); });
    }).catch(function () {
      // Data fetch failed (offline, path issue) -- fall back to a plain
      // text field rather than leaving three dead, permanently-disabled
      // selects with no way to proceed.
      pickers.forEach(function (root) {
        const hiddenInput = root.querySelector('input[type="hidden"]');
        const detailInput = root.querySelector('.address-detail');
        if (!hiddenInput) return;
        root.innerHTML = '';
        const fallback = document.createElement('textarea');
        fallback.className = 'form-control';
        fallback.rows = 2;
        fallback.placeholder = 'Complete address';
        fallback.required = hiddenInput.hasAttribute('data-required');
        fallback.addEventListener('input', function () { hiddenInput.value = fallback.value; });
        root.appendChild(fallback);
        root.appendChild(hiddenInput);
      });
    });
  });
})();
