<?php
require_once __DIR__ . '/../../../Backend/roles.php';
require_once __DIR__ . '/../../../Backend/require_role.php';
require_role([ROLE_ADMIN]);
require_once __DIR__ . '/../../../Backend/db.php';
require_once __DIR__ . '/../../../Backend/csrf.php';

$csrfToken = csrf_token();

$db   = new Database();
$conn = $db->connect();
$res = $conn->query(
    "SELECT r.id, r.applicant_id, r.request_type, r.status, r.notes, r.created_at, r.updated_at,
            a.first_name, a.last_name, a.reference_id
     FROM data_privacy_request r
     JOIN applicants a ON a.applicant_id = r.applicant_id
     ORDER BY (r.status = 'pending') DESC, r.created_at DESC"
);
$requests = $res->fetch_all(MYSQLI_ASSOC);
$db->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Data Privacy Requests — EduSchool</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,300;0,400;0,500;1,400;1,500&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  :root { --ink:#1c1b19; --paper:#f7f5f1; --card:#fff; --line:#e2ded4; --muted:#6b6459; --accent:#2f5d3f; --accent-soft:#e6efe6; --pending:#9a6a1c; --pending-bg:#faf1de; }
  * { box-sizing:border-box; }
  body { margin:0; background:var(--paper); color:var(--ink); font-family:'Inter',system-ui,sans-serif; padding:32px 20px 80px; }
  .wrap { max-width:900px; margin:0 auto; }
  h1 { font-family:'Fraunces',serif; font-weight:500; font-size:28px; margin:0 0 6px; }
  .sub { color:var(--muted); margin:0 0 24px; font-size:14px; }
  table { width:100%; border-collapse:collapse; background:var(--card); border:1px solid var(--line); border-radius:10px; overflow:hidden; }
  th, td { text-align:left; padding:10px 12px; font-size:13.5px; border-bottom:1px solid var(--line); vertical-align:top; }
  th { font-family:'IBM Plex Mono',monospace; font-size:10.5px; letter-spacing:.05em; text-transform:uppercase; color:var(--muted); background:var(--accent-soft); }
  tr:last-child td { border-bottom:none; }
  .status-pill { font-family:'IBM Plex Mono',monospace; font-size:10px; letter-spacing:.04em; text-transform:uppercase; padding:2px 8px; border-radius:99px; }
  .status-pill.pending { color:var(--pending); background:var(--pending-bg); }
  .status-pill.reviewed, .status-pill.completed { color:var(--accent); background:var(--accent-soft); }
  .status-pill.denied { color:#9a3324; background:#f6e8e5; }
  select, textarea, button { font-family:inherit; font-size:13px; padding:5px 8px; border-radius:6px; border:1px solid var(--line); }
  button { background:var(--accent); color:#fff; border:none; cursor:pointer; padding:6px 12px; }
  textarea { width:100%; min-height:36px; margin-top:4px; }
  .row-actions { display:flex; flex-direction:column; gap:4px; min-width:160px; }
  .empty { color:var(--muted); padding:24px; text-align:center; }
</style>
</head>
<body>
<div class="wrap">
  <h1>Data Privacy Requests</h1>
  <p class="sub">Student-submitted data export &amp; deletion requests under RA 10173. Review and act on each — nothing here deletes data automatically.</p>

  <?php if (empty($requests)): ?>
    <div class="empty">No requests yet.</div>
  <?php else: ?>
  <table>
    <tr><th>Student</th><th>Type</th><th>Status</th><th>Submitted</th><th>Notes</th><th>Action</th></tr>
    <?php foreach ($requests as $r): ?>
    <tr data-id="<?= (int)$r['id'] ?>">
      <td><?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name'], ENT_QUOTES) ?><br><span style="color:var(--muted);font-size:11.5px;"><?= htmlspecialchars($r['reference_id'], ENT_QUOTES) ?></span></td>
      <td><?= htmlspecialchars(ucfirst($r['request_type']), ENT_QUOTES) ?></td>
      <td><span class="status-pill <?= htmlspecialchars($r['status'], ENT_QUOTES) ?>"><?= htmlspecialchars($r['status'], ENT_QUOTES) ?></span></td>
      <td><?= htmlspecialchars($r['created_at'], ENT_QUOTES) ?></td>
      <td><?= htmlspecialchars($r['notes'] ?? '', ENT_QUOTES) ?></td>
      <td>
        <?php if ($r['status'] === 'pending' || $r['status'] === 'reviewed'): ?>
        <div class="row-actions">
          <textarea placeholder="Notes (optional)" class="notesInput"></textarea>
          <select class="statusSelect">
            <option value="reviewed">Mark reviewed</option>
            <option value="completed">Mark completed</option>
            <option value="denied">Deny</option>
          </select>
          <button type="button" class="saveBtn">Save</button>
        </div>
        <?php else: ?>
          <span style="color:var(--muted);">—</span>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>
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
</body>
</html>
