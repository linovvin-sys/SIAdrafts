(function () {
  var els = document.querySelectorAll('.sp-progress-fill');
  requestAnimationFrame(function () {
    els.forEach(function (el) { el.style.width = el.dataset.pct + '%'; });
  });

  var countdown = document.querySelector('.sp-today-countdown');
  if (!countdown) return;
  var start = parseInt(countdown.dataset.start, 10);
  var end = parseInt(countdown.dataset.end, 10);

  function tick() {
    var now = Date.now();
    var live = now >= start && now < end;
    countdown.closest('.sp-today').classList.toggle('is-live', live);
    countdown.closest('.sp-today').querySelector('.sp-today-status-label').textContent = live ? 'Happening now' : 'Next class';

    var target = live ? end : start;
    var diffMin = Math.max(0, Math.round((target - now) / 60000));
    var label = live
      ? (diffMin <= 0 ? 'Ending now' : 'Ends in ' + diffMin + ' min')
      : (diffMin <= 0 ? 'Starting now' : diffMin < 60 ? 'Starts in ' + diffMin + ' min' : 'Starts in ' + Math.round(diffMin / 60) + ' hr');
    countdown.textContent = label;
  }
  tick();
  setInterval(tick, 30000);
})();

// A quiet, one-time nudge when a real state actually improves (balance
// settles, requirements complete) -- compares against the value seen last
// visit, so it only fires on the actual transition, never on a page a
// student was already looking at. Separate IIFE from the block above,
// which can return early when there's no next class.
(function () {
  function pulse(card) {
    if (!card) return;
    card.classList.add('sp-celebrate');
    setTimeout(function () { card.classList.remove('sp-celebrate'); }, 700);
  }

  var balanceCard = document.querySelector('[data-celebrate="balance"]');
  if (balanceCard) {
    var balance = parseFloat(balanceCard.dataset.value);
    var prevBalance = sessionStorage.getItem('sp_prev_balance');
    if (prevBalance !== null && parseFloat(prevBalance) > 0 && balance <= 0) {
      pulse(balanceCard);
      if (window.spToast) window.spToast('Balance fully settled — nice.', 'mdi:check-circle-outline');
    }
    sessionStorage.setItem('sp_prev_balance', String(balance));
  }

  var reqCard = document.querySelector('[data-celebrate="requirements"]');
  if (reqCard) {
    var submitted = parseInt(reqCard.dataset.value, 10);
    var total = parseInt(reqCard.dataset.total, 10);
    var prevSubmitted = sessionStorage.getItem('sp_prev_req');
    if (prevSubmitted !== null && parseInt(prevSubmitted, 10) < total && submitted === total) {
      pulse(reqCard);
      if (window.spToast) window.spToast('All requirements submitted.', 'mdi:check-circle-outline');
    }
    sessionStorage.setItem('sp_prev_req', String(submitted));
  }
})();
