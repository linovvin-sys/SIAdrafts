// ===== Tab switching =====
const tabQueue = document.getElementById('tabQueue');
const tabSetup = document.getElementById('tabSetup');
const tabSearch = document.getElementById('tabSearch');
const queuePanel = document.getElementById('queuePanel');
const setupPanel = document.getElementById('setupPanel');
const searchPanel = document.getElementById('searchPanel');

tabQueue.addEventListener('click', function () {
  tabQueue.classList.add('active');
  tabSetup.classList.remove('active');
  tabSearch.classList.remove('active');
  queuePanel.style.display = 'block';
  setupPanel.style.display = 'none';
  searchPanel.classList.remove('active');
});

tabSetup.addEventListener('click', function () {
  tabSetup.classList.add('active');
  tabQueue.classList.remove('active');
  tabSearch.classList.remove('active');
  setupPanel.style.display = 'block';
  queuePanel.style.display = 'none';
  searchPanel.classList.remove('active');
});

tabSearch.addEventListener('click', function () {
  tabSearch.classList.add('active');
  tabQueue.classList.remove('active');
  tabSetup.classList.remove('active');
  queuePanel.style.display = 'none';
  setupPanel.style.display = 'none';
  searchPanel.classList.add('active');
});

function escapeHtml(str) {
  const div = document.createElement('div');
  div.textContent = str;
  return div.innerHTML;
}

function fmtMoney(n) {
  return '₱' + Number(n).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
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
        return '<div class="pay-history-row"><span>' + date + ' &middot; ' + escapeHtml(staffName) + '</span><span>' + fmtMoney(h.amount) + '</span></div>';
      }).join('')
    : '<p style="color:var(--ink-soft); font-size:0.88rem;">No payments recorded yet.</p>';

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

      ${balance > 0 ? `
        <div class="pay-form">
          <div class="field">
            <label>Amount to record</label>
            <input type="number" id="payAmountInput" min="0.01" step="0.01" placeholder="0.00">
          </div>
          <div class="field">
            <label>Remarks (optional)</label>
            <input type="text" id="payRemarksInput" placeholder="e.g. Cash, OR #1234">
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

  const recordBtn = document.getElementById('recordPayBtn');
  if (recordBtn) {
    recordBtn.addEventListener('click', function () {
      const amount = document.getElementById('payAmountInput').value;
      const remarks = document.getElementById('payRemarksInput').value;
      const paymentId = recordBtn.dataset.paymentId;
      const banner = document.getElementById('payFormBanner');

      const amt = parseFloat(amount);
      const isFirstPayment = totalPaid <= 0;
      const MIN_DOWNPAYMENT = 3000;

      if (isFirstPayment && amt < MIN_DOWNPAYMENT && amt < balance) {
        banner.innerHTML = '<div class="t-banner error">The minimum down payment is ' + fmtMoney(MIN_DOWNPAYMENT) + '.</div>';
        return;
      }

      recordBtn.disabled = true;
      recordBtn.textContent = 'Recording…';

      const body = new URLSearchParams({ payment_id: paymentId, amount: amount, remarks: remarks });

      fetch('/SIAdrafts/Backend/api/record_payment.php', { method: 'POST', body: body })
        .then(function (res) { return res.json(); })
        .then(function (result) {
          if (!result.success) {
            banner.innerHTML = '<div class="t-banner error">' + result.errors.map(escapeHtml).join('<br>') + '</div>';
            recordBtn.disabled = false;
            recordBtn.textContent = 'Record payment';
            return;
          }
          // Re-fetch the full card to show updated totals + history
          fetchAndRenderByStudentId(s.student_id);
        })
        .catch(function () {
          banner.innerHTML = '<div class="t-banner error">Could not reach the server. Please try again.</div>';
          recordBtn.disabled = false;
          recordBtn.textContent = 'Record payment';
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

// ===== "Set Up" buttons inside the payment-setup queue table =====
document.querySelectorAll('.btn-setup-row').forEach(function (btn) {
  btn.addEventListener('click', function () {
    const row = btn.closest('tr');
    const enrollmentId = btn.dataset.enrollmentId;
    const amountInput = row.querySelector('.setup-amount');
    const dueDateInput = row.querySelector('.setup-due-date');
    const amountDue = amountInput.value;
    const dueDate = dueDateInput.value;

    if (!amountDue || parseFloat(amountDue) <= 0) {
      alert('Enter a valid amount due.');
      amountInput.focus();
      return;
    }
    if (!dueDate) {
      alert('Pick a due date.');
      dueDateInput.focus();
      return;
    }

    btn.disabled = true;
    btn.textContent = 'Setting up…';

    fetch('/SIAdrafts/Backend/api/setup_payment.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({
        enrollment_id: enrollmentId,
        amount_due:    parseFloat(amountDue),
        due_date:      dueDate,
      }),
    })
      .then(function (res) { return res.json(); })
      .then(function (result) {
        if (!result.success) {
          alert(result.error || 'Could not set up payment.');
          btn.disabled = false;
          btn.textContent = 'Set Up';
          return;
        }
        // This enrollment now has a payment row — drop it from the setup queue.
        // It'll appear under "Pending payments" on next page load.
        row.remove();
      })
      .catch(function () {
        alert('Could not reach the server. Please try again.');
        btn.disabled = false;
        btn.textContent = 'Set Up';
      });
  });
});