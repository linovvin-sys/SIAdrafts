<?php
$pageTitle  = "Accountabilities";
$activePage = "accountabilities";

require_once __DIR__ . '/../../../Backend/require_student.php';
require_student();
require_once __DIR__ . '/../../../Backend/db.php';
require_once __DIR__ . '/../../../Backend/requirements.php';
require_once __DIR__ . '/../../../Backend/Student/accountabilities_data.php';

$db   = new Database();
$conn = $db->connect();

$applicantId = (int)$_SESSION['student_id'];

$accountabilitiesData = get_accountabilities_data($conn, $applicantId);
$enrollment = $accountabilitiesData['enrollment'];
$payment    = $accountabilitiesData['payment'];
$breakdown  = $accountabilitiesData['breakdown'];
$docRows    = $accountabilitiesData['docRows'];
$db->close();

$submittedLabels = array_column(array_filter($docRows, fn($d) => $d['status'] === 'submitted'), 'document_name');
$laterLabels      = array_column(array_filter($docRows, fn($d) => $d['status'] === 'will_submit_later'), 'document_name');

$labelToKey = [];
$keyToLabel = [];
foreach (REQUIREMENT_DEFINITIONS as $def) {
    $labelToKey[$def['label']] = $def['key'];
    $keyToLabel[$def['key']]   = $def['label'];
}
$submittedKeys = array_values(array_filter(array_map(fn($l) => $labelToKey[$l] ?? null, $submittedLabels)));
$missingGroups = missing_requirement_groups($submittedKeys);

// One representative requirement label per still-missing group.
$missingLabels = [];
foreach ($missingGroups as $group) {
    foreach (REQUIREMENT_DEFINITIONS as $def) {
        if ($def['group'] === $group) { $missingLabels[] = $def['label']; break; }
    }
}

$balance = $payment ? (float)$payment['balance'] : 0;

$dueLabel = null;
$dueOverdue = false;
if ($balance > 0 && $payment && !empty($payment['due_date'])) {
    $daysUntil = (int)ceil((strtotime($payment['due_date']) - strtotime(date('Y-m-d'))) / 86400);
    if ($daysUntil < 0) { $dueLabel = 'Overdue by ' . abs($daysUntil) . ' day' . (abs($daysUntil) === 1 ? '' : 's'); $dueOverdue = true; }
    elseif ($daysUntil === 0) { $dueLabel = 'Due today'; }
    else { $dueLabel = 'Due in ' . $daysUntil . ' day' . ($daysUntil === 1 ? '' : 's'); }
}

$totalReqGroups     = count(requirement_groups());
$submittedReqGroups = $totalReqGroups - count($missingGroups);

include __DIR__ . '/Include/header.php';
?>

<h1 class="sp-greeting">Accountabilities</h1>
<p class="sp-subline">Your outstanding balance and requirements for the current term.</p>

<?php if (!$enrollment): ?>
  <div class="sp-empty">
    <iconify-icon icon="mdi:cash-multiple"></iconify-icon>
    <p><strong>Nothing to show yet.</strong></p>
    <p>Your balance and requirements will appear here once you've enrolled for the term.</p>
  </div>
<?php else: ?>

<div class="sp-section">
  <h2 class="sp-section-title">Balance</h2>
  <?php if (!$payment): ?>
    <p style="color:var(--slate-500); font-size:14px;">No payment record on file for this term yet.</p>
  <?php else: ?>
    <div class="sp-balance-hero">
      <div>
        <p class="sp-field-label">Balance due</p>
        <p class="sp-balance-figure sp-vt-balance" style="color: <?= $balance > 0 ? 'var(--danger-600)' : 'var(--success-600)' ?>;">
          &#8369;<?= number_format($balance, 2) ?>
        </p>
        <?php if ($dueLabel): ?>
          <span class="sp-today-countdown sp-balance-due-chip <?= $dueOverdue ? 'is-overdue' : '' ?>"><?= htmlspecialchars($dueLabel, ENT_QUOTES) ?></span>
        <?php elseif ($balance <= 0): ?>
          <span class="sp-pill enrolled">Fully settled</span>
        <?php endif; ?>
      </div>
      <div class="sp-balance-meta">
        <div class="sp-field">
          <p class="sp-field-label">Total assessment</p>
          <p class="sp-field-value" style="font-family:var(--font-mono);">&#8369;<?= number_format((float)$payment['amount_due'], 2) ?></p>
        </div>
        <?php if (!empty($payment['due_date'])): ?>
        <div class="sp-field">
          <p class="sp-field-label">Due date</p>
          <p class="sp-field-value"><?= date('F j, Y', strtotime($payment['due_date'])) ?></p>
        </div>
        <?php endif; ?>
        <div class="sp-field">
          <p class="sp-field-label">Status</p>
          <p class="sp-field-value">
            <span class="sp-pill <?= $balance > 0 ? 'attention' : 'enrolled' ?>"><?= htmlspecialchars($payment['payment_status'], ENT_QUOTES) ?></span>
          </p>
        </div>
      </div>
    </div>

    <?php if (!empty($breakdown)): ?>
      <table class="sp-table sp-table-stagger">
        <thead><tr><th>Item</th><th style="text-align:right;">Amount</th></tr></thead>
        <tbody>
          <?php foreach ($breakdown as $i => $b): ?>
            <tr style="--row-i: <?= $i ?>;">
              <td><?= htmlspecialchars($b['label'], ENT_QUOTES) ?></td>
              <td class="sp-num" style="text-align:right;">&#8369;<?= number_format((float)$b['amount'], 2) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  <?php endif; ?>
</div>

<div class="sp-section">
  <div style="display:flex; align-items:baseline; justify-content:space-between; gap:12px; margin-bottom:18px;">
    <h2 class="sp-section-title" style="margin-bottom:0;">Requirements</h2>
    <p class="sp-card-hint sp-vt-requirements" style="margin:0;"><?= $submittedReqGroups ?>/<?= $totalReqGroups ?> submitted</p>
  </div>
  <?php if (empty($missingLabels)): ?>
    <div style="display:flex; align-items:center; gap:10px; color:var(--success-600); font-size:14px;">
      <iconify-icon icon="mdi:check-circle" style="font-size:18px;"></iconify-icon>
      All requirements are on file.
    </div>
  <?php else: ?>
    <table class="sp-table sp-table-stagger">
      <thead><tr><th>Requirement</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($missingLabels as $i => $label): ?>
          <tr style="--row-i: <?= $i ?>;">
            <td><?= htmlspecialchars($label, ENT_QUOTES) ?></td>
            <td>
              <?php if (in_array($label, $laterLabels, true)): ?>
                <span class="sp-pill pending">Will submit later</span>
              <?php else: ?>
                <span class="sp-pill attention">Missing</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <p class="sp-form-error-hint" style="margin-top:12px;">Missing requirements are submitted in person at the Registrar's Office — bring the physical document on your next visit.</p>
  <?php endif; ?>
</div>

<?php endif; ?>

<?php include __DIR__ . '/Include/footer.php'; ?>
