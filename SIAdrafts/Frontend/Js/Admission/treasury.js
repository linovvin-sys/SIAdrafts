// ===== Tab switching =====
const tabQueue = document.getElementById('tabQueue');
const tabSetup = document.getElementById('tabSetup');
const tabSearch = document.getElementById('tabSearch');
const tabRevenue = document.getElementById('tabRevenue');
const queuePanel = document.getElementById('queuePanel');
const setupPanel = document.getElementById('setupPanel');
const searchPanel = document.getElementById('searchPanel');
const revenuePanel = document.getElementById('revenuePanel');

// ===== DataTables =====
// Revenue/Queue/Setup panels all start hidden except the active tab (plain
// display:none, not removed from the DOM), so a table initialized while its
// panel is hidden gets a 0-width layout from DataTables — columns.adjust()
// on the table's own panel becoming visible fixes that up.
const treasuryTables = {};
function initTreasuryTable(id, options) {
  const el = document.getElementById(id);
  if (!el) return;
  treasuryTables[id] = initDataTable('#' + id, options);
}
initTreasuryTable('revenueByCourseTable', { order: [], paging: false, info: false, dom: '<"dt-toolbar"f>rt' });
initTreasuryTable('paymentQueueTable', { order: [] });
initTreasuryTable('feeQueueTable', { order: [] });
initTreasuryTable('setupQueueTable', { order: [] });

function adjustTreasuryTable(id) {
  if (treasuryTables[id]) treasuryTables[id].columns.adjust();
}

function showTab(tab) {
  tabQueue.classList.toggle('active', tab === tabQueue);
  tabSetup.classList.toggle('active', tab === tabSetup);
  tabSearch.classList.toggle('active', tab === tabSearch);
  tabRevenue.classList.toggle('active', tab === tabRevenue);
  queuePanel.style.display = tab === tabQueue ? 'block' : 'none';
  setupPanel.style.display = tab === tabSetup ? 'block' : 'none';
  searchPanel.classList.toggle('active', tab === tabSearch);
  revenuePanel.style.display = tab === tabRevenue ? 'block' : 'none';

  if (tab === tabQueue) { adjustTreasuryTable('paymentQueueTable'); adjustTreasuryTable('feeQueueTable'); }
  if (tab === tabSetup) adjustTreasuryTable('setupQueueTable');
  if (tab === tabRevenue) adjustTreasuryTable('revenueByCourseTable');
}

tabQueue.addEventListener('click', function () { showTab(tabQueue); });
tabSetup.addEventListener('click', function () { showTab(tabSetup); });
tabSearch.addEventListener('click', function () { showTab(tabSearch); });
tabRevenue.addEventListener('click', function () { showTab(tabRevenue); });

function escapeHtml(str) {
  const div = document.createElement('div');
  div.textContent = str;
  return div.innerHTML;
}

function fmtMoney(n) {
  return '₱' + Number(n).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// OR/receipt number is just the transaction's own auto-increment ID,
// zero-padded — nothing to type in, nothing else to keep in sync.
function fmtOrNumber(transactionId) {
  return 'OR-' + String(transactionId).padStart(6, '0');
}

// "Last, First Middle" -> "LF". Falls back to "?" for anything unparseable
// rather than showing a blank circle.
function getInitials(fullName) {
  const parts = String(fullName || '').split(',').map(function (s) { return s.trim(); });
  const initials = (parts[0] ? parts[0].charAt(0) : '') + (parts[1] ? parts[1].charAt(0) : '');
  return initials.toUpperCase() || '?';
}

// Deterministic tint per student so the same person's avatar looks the same
// across searches, without needing a stored color.
const AVATAR_TINTS = ['amber', 'sage', 'ink'];
function avatarTintFor(str) {
  let hash = 0;
  for (let i = 0; i < str.length; i++) hash = (hash * 31 + str.charCodeAt(i)) >>> 0;
  return AVATAR_TINTS[hash % AVATAR_TINTS.length];
}

const IS_READONLY = document.body.dataset.readonly === '1';

// ===== Payment terminal =====
// Orchestrates the processing overlay for both record_payment.php and
// record_subject_fee_payment.php. `run` performs the actual network call;
// this only sequences the perceived steps around it and reflects the real
// result — it never fabricates success.
const STEP_MS = 480;

function runPaymentTerminal(steps, run) {
  const terminal = document.getElementById('payTerminal');
  const stage = terminal.querySelector('.pay-terminal-stage');
  const stepEl = document.getElementById('payTerminalStep');
  const fillEl = document.getElementById('payTerminalFill');
  const resultEl = document.getElementById('payTerminalResult');

  stage.classList.remove('is-success', 'is-error');
  resultEl.innerHTML = '';
  fillEl.style.transition = 'none';
  fillEl.style.width = '0%';
  stepEl.textContent = steps[0];
  terminal.classList.add('is-open');
  terminal.setAttribute('aria-hidden', 'false');
  document.body.style.overflow = 'hidden';

  requestAnimationFrame(function () {
    fillEl.style.transition = 'width ' + (steps.length * STEP_MS) + 'ms ' + 'var(--ease-in-out)';
    fillEl.style.width = '92%';
  });

  let i = 0;
  const stepTimer = setInterval(function () {
    i++;
    if (i < steps.length) stepEl.textContent = steps[i];
  }, STEP_MS);

  const minWait = new Promise(function (resolve) { setTimeout(resolve, steps.length * STEP_MS); });

  return Promise.all([run().catch(function () { return { success: false, errors: ['Could not reach the server. Please try again.'] }; }), minWait])
    .then(function (results) {
      const result = results[0];
      clearInterval(stepTimer);
      fillEl.style.transition = 'width 200ms ease-out';
      fillEl.style.width = '100%';

      return new Promise(function (resolve) {
        setTimeout(function () {
          if (result && result.success) {
            stepEl.textContent = 'Payment recorded';
            stage.classList.add('is-success');
          } else {
            stepEl.textContent = 'Payment failed';
            stage.classList.add('is-error');
            const errors = (result && result.errors) || ['Something went wrong. Please try again.'];
            resultEl.innerHTML = '<span>' + errors.map(escapeHtml).join('<br>') + '</span>' +
              '<button type="button" class="retry-btn" data-terminal-dismiss>Close</button>';
          }
          resolve(result);
        }, 220);
      });
    });
}

function closePayTerminal() {
  const terminal = document.getElementById('payTerminal');
  terminal.classList.remove('is-open');
  terminal.setAttribute('aria-hidden', 'true');
  document.body.style.overflow = '';
}

document.getElementById('payTerminal').addEventListener('click', function (e) {
  if (e.target.closest('[data-terminal-dismiss]')) closePayTerminal();
});

// ===== Renders the search-result payment card (also reused after "Pay" from queue) =====
function renderPayCard(data) {
  const resultDiv = document.getElementById('payResult');

  if (data.error) {
    resultDiv.innerHTML = '<div class="t-banner error">' + escapeHtml(data.error) + '</div>';
    return;
  }

  const p = data.payment;
  const s = data.student;
  const balance = parseFloat(p.balance);
  const totalPaid = parseFloat(p.amount_due) - balance;
  const pct = parseFloat(p.amount_due) > 0 ? Math.min(100, Math.round((totalPaid / parseFloat(p.amount_due)) * 100)) : 0;
  const isOverdue = balance > 0 && !!p.due_date && new Date(p.due_date) < new Date(new Date().toDateString());
  const initials = getInitials(s.full_name);
  const avatarTint = avatarTintFor(String(s.full_name));

  const historyHtml = data.history.length
    ? data.history.map(function (h) {
        const staffName = (h.first_name || h.last_name)
          ? (h.first_name + ' ' + h.last_name).trim()
          : 'Staff';
        const date = new Date(h.paid_at).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
        const orNumber = fmtOrNumber(h.transaction_id);
        return '<div class="pay-history-row"><span><span class="pay-history-dot"></span>' + date + ' &middot; ' + escapeHtml(staffName) + ' &middot; ' + orNumber + '</span><span>' + fmtMoney(h.amount) + '</span></div>';
      }).join('')
    : '<p style="color:var(--ink-soft); font-size:0.88rem;">No payments recorded yet.</p>';

  const pendingFees = data.pending_fees || [];
  const pendingFeesHtml = pendingFees.length
    ? pendingFees.map(function (f) {
        const actionCls = f.action === 'Add' ? 'down' : 'unpaid';
        return '<div class="pending-fee-row">' +
          '<div class="pending-fee-info">' +
            '<span class="status-pill ' + actionCls + '">' + escapeHtml(f.action) + '</span>' +
            '<div class="pending-fee-subject">' +
              '<div class="pending-fee-code">' + escapeHtml(f.subject_code) + ' — ' + escapeHtml(f.subject_name) + '</div>' +
              '<div class="pending-fee-units">' + escapeHtml(String(f.units)) + ' units</div>' +
            '</div>' +
          '</div>' +
          '<div class="pending-fee-action">' +
            '<span class="pending-fee-amount">' + fmtMoney(f.amount) + '</span>' +
            (IS_READONLY ? '' : '<button type="button" class="btn-record-fee" data-fee-id="' + f.fee_id + '">Record payment</button>') +
          '</div>' +
        '</div>';
      }).join('')
    : '';

  resultDiv.innerHTML = `
    <div class="pay-student-card">
      <div class="psc-head">
        <div class="psc-avatar tint-${avatarTint}">${escapeHtml(initials)}</div>
        <div class="psc-head-text">
          <h3 class="pay-student-name">${escapeHtml(s.full_name)}</h3>
          <div class="pay-student-meta">${escapeHtml(String(s.student_id))} &middot; ${escapeHtml(p.school_year)} &middot; Sem ${escapeHtml(String(p.semester))}</div>
        </div>
        ${isOverdue ? '<span class="psc-flag">Overdue</span>' : ''}
      </div>

      <div class="pay-stats">
        <div class="pay-stat">
          <div class="pay-stat-icon tint-ink">₱</div>
          <div class="pay-stat-text">
            <div class="k">Amount due</div>
            <div class="v">${fmtMoney(p.amount_due)}</div>
          </div>
        </div>
        <div class="pay-stat">
          <div class="pay-stat-icon tint-sage">&#10003;</div>
          <div class="pay-stat-text">
            <div class="k">Total paid</div>
            <div class="v">${fmtMoney(totalPaid)}</div>
          </div>
        </div>
        <div class="pay-stat${isOverdue ? ' is-overdue' : ''}">
          <div class="pay-stat-icon ${isOverdue ? 'tint-rose' : 'tint-amber'}">${isOverdue ? '!' : '₱'}</div>
          <div class="pay-stat-text">
            <div class="k">Balance</div>
            <div class="v balance">${fmtMoney(balance)}</div>
          </div>
        </div>
      </div>

      <div class="pay-progress">
        <div class="pay-progress-track"><div class="pay-progress-fill${pct < 40 ? ' is-partial' : ''}" style="width:${pct}%"></div></div>
        <div class="pay-progress-label">${pct}% paid</div>
      </div>

      <div id="payFormBanner"></div>

      ${pendingFees.length ? `
        <div class="pending-fees" id="pendingFeesSection">
          <h4>Pending Subject Changes</h4>
          ${pendingFeesHtml}
        </div>
      ` : ''}

      ${balance > 0 ? (IS_READONLY ? '' : `
        <div class="pay-form">
          <div class="field">
            <label for="payAmountInput">Amount to record</label>
            <div class="money-input">
              <span class="money-prefix">₱</span>
              <input type="number" id="payAmountInput" min="0.01" step="0.01" placeholder="0.00">
            </div>
          </div>
          <div class="field">
            <label>Payment method</label>
            <div class="method-pill">Cash</div>
          </div>
          <button class="btn-record-pay" id="recordPayBtn" data-payment-id="${p.payment_id}">
            Record payment
          </button>
          <button class="btn-pay-online" id="payOnlineBtn" type="button" data-payment-id="${p.payment_id}">
            <iconify-icon icon="mdi:cellphone-check" aria-hidden="true"></iconify-icon>
            Pay online (GCash)
          </button>
        </div>
      `) : `<div class="t-banner success">Fully paid. No balance remaining.</div>`}

      <div class="pay-history">
        <h4>Payment history</h4>
        ${historyHtml}
      </div>
    </div>
  `;


  document.querySelectorAll('.btn-record-fee').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const feeId = btn.dataset.feeId;
      const confirmFn = window.confirmAction || function (opts) {
        return Promise.resolve(window.confirm(opts.title || 'Are you sure?'));
      };

      confirmFn({
        title: 'Record this subject-change fee as paid?',
        icon: 'question',
        confirmText: 'Yes, record payment',
      }).then(function (ok) {
        if (!ok) return;

        btn.disabled = true;

        runPaymentTerminal(
          ['Verifying fee…', 'Posting to ledger…'],
          function () {
            return fetch('/SIAdrafts/Backend/api/Treasury/record_subject_fee_payment.php', {
              method: 'POST',
              body: new URLSearchParams({ fee_id: feeId, csrf_token: document.body.dataset.csrf || '' }),
            }).then(function (res) { return res.json(); });
          }
        ).then(function (result) {
          btn.disabled = false;
          if (result && result.success) {
            setTimeout(function () {
              closePayTerminal();
              fetchAndRenderByPaymentId(p.payment_id);
            }, 900);
          }
        });
      });
    });
  });

  const recordBtn = document.getElementById('recordPayBtn');
  if (recordBtn) {
    recordBtn.addEventListener('click', function () {
      const amount = document.getElementById('payAmountInput').value;
      const paymentId = recordBtn.dataset.paymentId;
      const banner = document.getElementById('payFormBanner');

      const amt = parseFloat(amount);
      const isFirstPayment = totalPaid <= 0;
      const MIN_DOWNPAYMENT = 3000;

      if (isFirstPayment && amt < MIN_DOWNPAYMENT && amt < balance) {
        banner.innerHTML = '<div class="t-banner error">The minimum down payment is ' + fmtMoney(MIN_DOWNPAYMENT) + '.</div>';
        return;
      }

      if (!amount || isNaN(amt) || amt <= 0) {
        banner.innerHTML = '<div class="t-banner error">Enter a valid amount.</div>';
        return;
      }

      const doRecord = function () {
        recordBtn.disabled = true;

        runPaymentTerminal(
          ['Verifying amount…', 'Checking balance…', 'Posting to ledger…'],
          function () {
            const body = new URLSearchParams({ payment_id: paymentId, amount: amount, csrf_token: document.body.dataset.csrf || '' });
            return fetch('/SIAdrafts/Backend/api/Treasury/record_payment.php', { method: 'POST', body: body })
              .then(function (res) { return res.json(); });
          }
        ).then(function (result) {
          recordBtn.disabled = false;
          if (result && result.success) {
            const resultEl = document.getElementById('payTerminalResult');
            if (result.or_number) {
              resultEl.innerHTML = '<span>Receipt</span><span class="or-number">' + escapeHtml(result.or_number) + '</span>';
            }
            setTimeout(function () {
              closePayTerminal();
              fetchAndRenderByPaymentId(p.payment_id);
            }, 1100);
          }
        });
      };

      const confirmFn = window.confirmAction || function (opts) {
        return Promise.resolve(window.confirm(opts.title || 'Are you sure?'));
      };

      confirmFn({
        title: 'Record this payment?',
        html: '<div style="text-align:left;font-size:14px;line-height:1.7;">' +
          '<div><strong>Student:</strong> ' + escapeHtml(s.full_name) + '</div>' +
          '<div><strong>Amount:</strong> ' + fmtMoney(amt) + '</div>' +
          '</div>',
        icon: 'question',
        confirmText: 'Yes, record payment',
      }).then(function (ok) {
        if (ok) doRecord();
      });
    });
  }

  // Pay online: hands off to PayMongo's hosted checkout (GCash test flow on
  // localhost). Same amount input + down-payment rule as the cash path; the
  // ledger post happens back in paymongo_return.php once PayMongo confirms.
  const payOnlineBtn = document.getElementById('payOnlineBtn');
  if (payOnlineBtn) {
    payOnlineBtn.addEventListener('click', function () {
      const amount = document.getElementById('payAmountInput').value;
      const paymentId = payOnlineBtn.dataset.paymentId;
      const banner = document.getElementById('payFormBanner');
      const amt = parseFloat(amount);
      const isFirstPayment = totalPaid <= 0;
      const MIN_DOWNPAYMENT = 3000;

      if (!amount || isNaN(amt) || amt <= 0) {
        banner.innerHTML = '<div class="t-banner error">Enter a valid amount.</div>';
        return;
      }
      if (amt < 100) {
        banner.innerHTML = '<div class="t-banner error">Online payments must be at least ' + fmtMoney(100) + '.</div>';
        return;
      }
      if (isFirstPayment && amt < MIN_DOWNPAYMENT && amt < balance) {
        banner.innerHTML = '<div class="t-banner error">The minimum down payment is ' + fmtMoney(MIN_DOWNPAYMENT) + '.</div>';
        return;
      }

      const confirmFn = window.confirmAction || function (opts) {
        return Promise.resolve(window.confirm(opts.title || 'Are you sure?'));
      };
      confirmFn({
        title: 'Pay this online via GCash?',
        html: '<div style="text-align:left;font-size:14px;line-height:1.7;">' +
          '<div><strong>Student:</strong> ' + escapeHtml(s.full_name) + '</div>' +
          '<div><strong>Amount:</strong> ' + fmtMoney(amt) + '</div>' +
          '<div style="margin-top:6px;color:var(--ink-soft);">You\'ll be taken to PayMongo to complete payment.</div>' +
          '</div>',
        icon: 'question',
        confirmText: 'Continue to PayMongo',
      }).then(function (ok) {
        if (!ok) return;
        payOnlineBtn.disabled = true;
        banner.innerHTML = '<div class="t-banner success">Opening secure checkout…</div>';
        const body = new URLSearchParams({
          payment_id: paymentId,
          amount: amount,
          csrf_token: document.body.dataset.csrf || '',
        });
        fetch('/SIAdrafts/Backend/api/Treasury/paymongo_create_checkout.php', { method: 'POST', body: body })
          .then(function (res) { return res.json(); })
          .then(function (result) {
            if (result && result.success && result.checkout_url) {
              window.location.href = result.checkout_url;
            } else {
              payOnlineBtn.disabled = false;
              banner.innerHTML = '<div class="t-banner error">' + escapeHtml((result && result.error) || 'Could not start online payment.') + '</div>';
            }
          })
          .catch(function () {
            payOnlineBtn.disabled = false;
            banner.innerHTML = '<div class="t-banner error">Could not reach the server. Please try again.</div>';
          });
      });
    });
  }
}

function fetchAndRenderByStudentId(studentId) {
  const resultDiv = document.getElementById('payResult');
  fetch('/SIAdrafts/Backend/api/Treasury/get_payment_info.php?q=' + encodeURIComponent(studentId))
    .then(function (res) {
      if (!res.ok) {
        return res.text().then(function (body) {
          throw new Error('HTTP ' + res.status + ': ' + body);
        });
      }
      return res.json();
    })
    .then(renderPayCard)
    .catch(function (err) {
      console.error('get_payment_info failed:', err);
      resultDiv.innerHTML = '<div class="t-banner error">Could not load student. Check the console for details.</div>';
    });
}

// Loads by the exact payment record, not a name/ID re-search — used by the
// queue's Pay button and by post-payment refreshes, so a same-named student
// or an ambiguous search never swaps in the wrong record.
function fetchAndRenderByPaymentId(paymentId) {
  const resultDiv = document.getElementById('payResult');
  fetch('/SIAdrafts/Backend/api/Treasury/get_payment_info.php?payment_id=' + encodeURIComponent(paymentId))
    .then(function (res) {
      if (!res.ok) {
        return res.text().then(function (body) {
          throw new Error('HTTP ' + res.status + ': ' + body);
        });
      }
      return res.json();
    })
    .then(renderPayCard)
    .catch(function (err) {
      console.error('get_payment_info failed:', err);
      resultDiv.innerHTML = '<div class="t-banner error">Could not load student. Check the console for details.</div>';
    });
}

// ===== Search box =====
document.getElementById('studentSearchBtn').addEventListener('click', function () {
  const q = document.getElementById('studentSearchInput').value.trim();
  if (!q) return;
  fetchAndRenderByStudentId(q);
});

document.getElementById('studentSearchInput').addEventListener('keydown', function (e) {
  if (e.key === 'Enter') document.getElementById('studentSearchBtn').click();
});

// ===== "Pay" buttons inside the queue table =====
document.querySelectorAll('.btn-pay-row').forEach(function (btn) {
  btn.addEventListener('click', function () {
    tabSearch.click();
    document.getElementById('studentSearchInput').value = btn.dataset.student || '';
    if (btn.dataset.paymentId) {
      // Payment queue row — load this exact payment record, not a name
      // re-search, which could match the wrong student if two share a name.
      fetchAndRenderByPaymentId(btn.dataset.paymentId);
    } else {
      // Subject-change fee queue row — no payment_id on this button, fall
      // back to the name search (unchanged from prior behavior).
      fetchAndRenderByStudentId(btn.dataset.student);
    }
  });
});
