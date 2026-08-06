// Countdown tick for the "next class" hero card — mirrors
// Frontend/Js/Student/dashboard.js's countdown IIFE (generic, keyed off
// data-start/data-end epoch-ms attributes), copied rather than cross-loaded
// to keep each portal's Js/<Role>/ folder self-contained.
(function tickCountdown() {
  var el = document.querySelector('.sp-today-countdown');
  if (!el) return;

  var start = Number(el.dataset.start);
  var end   = Number(el.dataset.end);
  if (!start || !end) return;

  var todayCard = document.querySelector('.sp-today');

  function render() {
    var now = Date.now();
    if (now < start) {
      var mins = Math.ceil((start - now) / 60000);
      el.textContent = mins <= 1 ? 'Starting soon' : 'Starts in ' + mins + ' min';
      if (todayCard) todayCard.classList.remove('is-live');
    } else if (now < end) {
      var left = Math.ceil((end - now) / 60000);
      el.textContent = left <= 1 ? 'Ending soon' : left + ' min remaining';
      if (todayCard) todayCard.classList.add('is-live');
    } else {
      el.textContent = '';
      if (todayCard) todayCard.classList.remove('is-live');
    }
  }

  render();
  setInterval(render, 30000);
})();
