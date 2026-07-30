// ===== Tab switching =====
const tabQueue = document.getElementById('tabQueue');
const tabSetup = document.getElementById('tabSetup');
const tabSearch = document.getElementById('tabSearch');
const tabRevenue = document.getElementById('tabRevenue');
const queuePanel = document.getElementById('queuePanel');
const setupPanel = document.getElementById('setupPanel');
const searchPanel = document.getElementById('searchPanel');
const revenuePanel = document.getElementById('revenuePanel');

function showTab(tab) {
  tabQueue.classList.toggle('active', tab === tabQueue);
  tabSetup.classList.toggle('active', tab === tabSetup);
  tabSearch.classList.toggle('active', tab === tabSearch);
  tabRevenue.classList.toggle('active', tab === tabRevenue);
  queuePanel.style.display = tab === tabQueue ? 'block' : 'none';
  setupPanel.style.display = tab === tabSetup ? 'block' : 'none';
  searchPanel.classList.toggle('active', tab === tabSearch);
  revenuePanel.style.display = tab === tabRevenue ? 'block' : 'none';
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

  const historyHtml = data.history.length
    ? data.history.map(function (h) {
        const staffName = (h.first_name || h.last_name)
          ? (h.first_name + ' ' + h.last_name).trim()
          : 'Staff';
        const date = new Date(h.paid_at).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
        const orNumber = fmtOrNumber(h.transaction_id);
        return '<div class="pay-history-row"><span>' + date + ' &middot; ' + escapeHtml(staffName) + ' &middot; ' + orNumber + '</span><span>' + fmtMoney(h.amount) + '</span></div>';
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
            '<button type="button" class="btn-record-fee" data-fee-id="' + f.fee_id + '">Record payment</button>' +
          '</div>' +
        '</div>';
      }).join('')
    : '';

  resultDiv.innerHTML = `
    <div class="pay-student-card">
      <h3 class="pay-student-name">${escapeHtml(s.full_name)}</h3>
      <div class="pay-student-meta">${escapeHtml(String(s.student_id))} &middot; ${escapeHtml(p.school_year)} &middot; Sem ${escapeHtml(String(p.semester))}</div>

      <div class="pay-stats">
        <div class="pay-stat">
          <div class="k">Amount due</div>
          <div class="v">${fmtMoney(p.amount_due)}</div>
        </div>
        <div class="pay-stat">
          <div class="k">Total paid</div>
          <div class="v">${fmtMoney(totalPaid)}</div>
        </div>
        <div class="pay-stat">
          <div class="k">Balance</div>
          <div class="v balance">${fmtMoney(balance)}</div>
        </div>
      </div>

      <div id="payFormBanner"></div>

      ${pendingFees.length ? `
        <div class="pending-fees" id="pendingFeesSection">
          <h4>Pending Subject Changes</h4>
          ${pendingFeesHtml}
        </div>
      ` : ''}

      ${balance > 0 ? `
        <div class="pay-form">
          <div class="field">
            <label>Amount to record</label>
            <input type="number" id="payAmountInput" min="0.01" step="0.01" placeholder="0.00">
          </div>
          <div class="field">
            <label>Payment method</label>
            <input type="text" value="Cash" disabled>
          </div>
          <button class="btn-record-pay" id="recordPayBtn" data-payment-id="${p.payment_id}">
            Record payment
          </button>
        </div>
      ` : `<div class="t-banner success">Fully paid. No balance remaining.</div>`}

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
        btn.textContent = 'Recording…';

        fetch('/SIAdrafts/Backend/api/record_subject_fee_payment.php', {
          method: 'POST',
          body: new URLSearchParams({ fee_id: feeId }),
        })
          .then(function (res) { return res.json(); })
          .then(function (result) {
            if (!result.success) {
              Swal.fire({ icon: 'error', title: 'Could not record payment', text: (result.errors || []).join(' ') });
              btn.disabled = false;
              btn.textContent = 'Record payment';
              return;
            }
            fetchAndRenderByStudentId(s.student_id);
          })
          .catch(function () {
            Swal.fire({ icon: 'error', title: 'Could not reach the server. Please try again.' });
            btn.disabled = false;
            btn.textContent = 'Record payment';
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
        recordBtn.textContent = 'Recording…';

        const body = new URLSearchParams({ payment_id: paymentId, amount: amount });

        fetch('/SIAdrafts/Backend/api/record_payment.php', { method: 'POST', body: body })
          .then(function (res) { return res.json(); })
          .then(function (result) {
            if (!result.success) {
              banner.innerHTML = '<div class="t-banner error">' + result.errors.map(escapeHtml).join('<br>') + '</div>';
              recordBtn.disabled = false;
              recordBtn.textContent = 'Record payment';
              return;
            }
            fetchAndRenderByStudentId(s.student_id);
          })
          .catch(function () {
            banner.innerHTML = '<div class="t-banner error">Could not reach the server. Please try again.</div>';
            recordBtn.disabled = false;
            recordBtn.textContent = 'Record payment';
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
}

function fetchAndRenderByStudentId(studentId) {
  const resultDiv = document.getElementById('payResult');
  fetch('/SIAdrafts/Backend/api/get_payment_info.php?q=' + encodeURIComponent(studentId))
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
    // Switch to search tab and load this student directly by their queue row's student name search
    tabSearch.click();
    const studentName = btn.dataset.student;
    document.getElementById('studentSearchInput').value = studentName;
    fetchAndRenderByStudentId(studentName);
  });
});
