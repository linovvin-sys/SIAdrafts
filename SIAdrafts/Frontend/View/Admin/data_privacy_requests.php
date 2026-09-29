<?php
$pageTitle  = "DATA PRIVACY REQUESTS";
$activePage = "data_privacy_requests";

require_once '../../../Backend/auth.php';
require_once '../../../Backend/roles.php';
require_once '../../../Backend/require_role.php';
require_role([ROLE_ADMIN]);
require_once '../../../Backend/db.php';
require_once '../../../Backend/csrf.php';

$csrfToken = csrf_token();

$db   = new Database();
$conn = $db->connect();
$requests = $conn->query(
    "SELECT r.id, r.applicant_id, r.request_type, r.status, r.notes, r.created_at, r.updated_at,
            a.first_name, a.last_name, a.reference_id
     FROM data_privacy_request r
     JOIN applicants a ON a.applicant_id = r.applicant_id
     ORDER BY (r.status = 'pending') DESC, r.created_at DESC"
)->fetch_all(MYSQLI_ASSOC);
$db->close();

// pending/reviewed still need action, so both read as "in progress"
// (pending pill); completed/denied map onto the shared approved/rejected
// pills already used elsewhere in the Admin/Registrar portals.
$statusPillClass = [
    'pending'   => 'status-pill--pending',
    'reviewed'  => 'status-pill--pending',
    'completed' => 'status-pill--approved',
    'denied'    => 'status-pill--rejected',
];

include '../Include/header.php';
?>

<div class="app-layout">
  <?php include '../Include/sidebar.php'; ?>

  <main class="page-content">
    <div class="panel">
      <div class="panel-header">
        <span class="panel-title">Data Privacy Requests</span>
      </div>
      <p class="row-secondary" style="padding:0 24px; margin:0 0 4px;">
        Student-submitted data export &amp; deletion requests under RA 10173. Review and act on each — nothing here deletes data automatically.
      </p>

      <div class="panel-body" style="padding:0;">
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr><th>Student</th><th>Type</th><th>Status</th><th>Submitted</th><th>Notes</th><th style="width:180px">Action</th></tr>
            </thead>
            <tbody>
              <?php if (empty($requests)): ?>
                <tr><td colspan="6" style="text-align:center; padding:32px;">No requests yet.</td></tr>
              <?php else: ?>
                <?php foreach ($requests as $r): ?>
                <tr data-id="<?= (int)$r['id'] ?>">
                  <td>
                    <?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name'], ENT_QUOTES) ?>
                    <div class="text-muted"><?= htmlspecialchars($r['reference_id'], ENT_QUOTES) ?></div>
                  </td>
                  <td><?= htmlspecialchars(ucfirst($r['request_type']), ENT_QUOTES) ?></td>
                  <td><span class="status-pill <?= $statusPillClass[$r['status']] ?? '' ?>"><?= htmlspecialchars($r['status'], ENT_QUOTES) ?></span></td>
                  <td class="mono"><?= htmlspecialchars($r['created_at'], ENT_QUOTES) ?></td>
                  <td><?= htmlspecialchars($r['notes'] ?? '', ENT_QUOTES) ?></td>
                  <td>
                    <?php if ($r['status'] === 'pending' || $r['status'] === 'reviewed'): ?>
                    <div class="row-actions">
                      <textarea placeholder="Notes (optional)" class="notesInput" style="width:100%; min-height:36px;"></textarea>
                      <select class="statusSelect form-input">
                        <option value="reviewed">Mark reviewed</option>
                        <option value="completed">Mark completed</option>
                        <option value="denied">Deny</option>
                      </select>
                      <button type="button" class="btn-primary saveBtn">Save</button>
                    </div>
                    <?php else: ?>
                      <span class="text-muted">—</span>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>
</div>

<script>
const CSRF_TOKEN = <?= json_encode($csrfToken) ?>;
document.querySelectorAll('.saveBtn').forEach(btn => {
  btn.addEventListener('click', async () => {
    const row = btn.closest('tr');
    const id = row.dataset.id;
    const status = row.querySelector('.statusSelect').value;
    const notes = row.querySelector('.notesInput').value;
    const body = new URLSearchParams({ csrf_token: CSRF_TOKEN, id, status, notes });
    const res = await fetch('/SIAdrafts/Backend/api/Accounts/update_privacy_request.php', { method: 'POST', body });
    const result = await res.json();
    if (result.success) {
      window.location.reload();
    } else {
      alert(result.error || 'Failed to update.');
    }
  });
});
</script>

<?php include '../Include/footer.php'; ?>
