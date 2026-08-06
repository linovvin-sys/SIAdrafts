<?php
$pageTitle  = "PENDING DOCUMENTS";
$activePage = "pending_documents";

require_once '../../../Backend/auth.php';
require_once '../../../Backend/roles.php';
require_once '../../../Backend/require_role.php';
require_role([ROLE_ADMISSION, ROLE_ADMIN]);
$isAdminViewer = current_user_is(['Admin']);
require_once '../../../Backend/db.php';

$db   = new Database();
$conn = $db->connect();

// Grouped by applicant here (not per-document) so the table shows one row
// per student with a "still owed" count, instead of repeating the same
// name/reference/program on a separate row for every document they owe.
$pending = [];
$res = $conn->query("
    SELECT ad.document_id, ad.document_name, ad.uploaded_at,
           a.applicant_id, a.reference_id, a.first_name, a.last_name, a.program
    FROM applicant_documents ad
    JOIN applicants a ON a.applicant_id = ad.applicant_id
    WHERE ad.status = 'will_submit_later'
    ORDER BY ad.uploaded_at ASC
");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $aid = (int)$row['applicant_id'];
        if (!isset($pending[$aid])) {
            $pending[$aid] = [
                'reference_id' => $row['reference_id'],
                'first_name'   => $row['first_name'],
                'last_name'    => $row['last_name'],
                'program'      => $row['program'],
                'documents'    => [],
            ];
        }
        $pending[$aid]['documents'][] = [
            'document_id'   => $row['document_id'],
            'document_name' => $row['document_name'],
            'uploaded_at'   => $row['uploaded_at'],
        ];
    }
}
$db->close();

$totalDocs = array_sum(array_map(fn($s) => count($s['documents']), $pending));

include '../Include/header.php';
?>

<div class="app-layout">

    <?php include '../Include/sidebar.php'; ?>

    <main class="page-content">
        <?php include '../Include/readonly_banner.php'; ?>

        <div class="panel">

            <div class="panel-header">
                <span class="panel-title">Pending Documents</span>
                <span class="text-muted" style="font-size:12px;"><?= count($pending) ?> student<?= count($pending) === 1 ? '' : 's' ?> &middot; <?= $totalDocs ?> document<?= $totalDocs === 1 ? '' : 's' ?> outstanding</span>
            </div>

            <?php if (!empty($pending)): ?>
            <div class="panel-body" style="padding:16px 24px 0;">
                <div class="filter-bar">
                    <input type="text" class="form-input" id="pendingDocsSearch" placeholder="Search reference ID, name, or program…">
                </div>
            </div>
            <?php endif; ?>

            <div class="panel-body" style="padding:0;">
                <div class="table-responsive">
                <table class="data-table" id="pendingDocsTable">
                    <thead>
                        <tr>
                            <th style="width:36px;"></th>
                            <th>Reference ID</th>
                            <th>Applicant Name</th>
                            <th>Program</th>
                            <th>Documents Owed</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pending)): ?>
                            <tr><td colspan="5" style="text-align:center;">No outstanding documents right now.</td></tr>
                        <?php else: ?>
                            <?php foreach ($pending as $aid => $student): ?>
                                <?php $searchKey = strtolower($student['reference_id'] . ' ' . $student['last_name'] . ' ' . $student['first_name'] . ' ' . $student['program']); ?>
                                <tr class="pending-doc-summary" data-toggle-student="<?= $aid ?>" data-search="<?= htmlspecialchars($searchKey) ?>" style="cursor:pointer;">
                                    <td><span class="pending-doc-caret" id="caret-<?= $aid ?>">&#9656;</span></td>
                                    <td><?= htmlspecialchars($student['reference_id']) ?></td>
                                    <td><?= htmlspecialchars($student['last_name'] . ', ' . $student['first_name']) ?></td>
                                    <td><?= htmlspecialchars($student['program']) ?></td>
                                    <td><span class="status-pill status-pill--pending"><?= count($student['documents']) ?> document<?= count($student['documents']) === 1 ? '' : 's' ?></span></td>
                                </tr>
                                <tr class="pending-doc-detail" id="detail-<?= $aid ?>" style="display:none;">
                                    <td colspan="5" style="padding:0;">
                                        <table class="data-table" style="margin:0;">
                                            <thead>
                                                <tr>
                                                    <th style="padding-left:48px;">Document</th>
                                                    <th>Flagged On</th>
                                                    <?php if (!$isAdminViewer): ?><th style="width:140px;">Action</th><?php endif; ?>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($student['documents'] as $doc): ?>
                                                    <tr>
                                                        <td style="padding-left:48px;"><?= htmlspecialchars($doc['document_name']) ?></td>
                                                        <td><?= date('M d, Y', strtotime($doc['uploaded_at'])) ?></td>
                                                        <?php if (!$isAdminViewer): ?>
                                                        <td>
                                                            <button type="button" class="btn btn-outline btn-mark-received" style="padding:4px 10px;font-size:12px" data-document-id="<?= (int)$doc['document_id'] ?>">
                                                                Mark Received
                                                            </button>
                                                        </td>
                                                        <?php endif; ?>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
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

<style>
  .pending-doc-caret { display:inline-block; transition: transform 0.15s ease; }
  .pending-doc-summary.open .pending-doc-caret { transform: rotate(90deg); }
  .pending-doc-detail table.data-table thead th { background:#faf7f2; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
  // Each student's row pairs with a hidden detail row directly after it —
  // DataTables' generic per-row sort/search/paginate doesn't understand
  // that pairing, so this uses a simple custom filter instead of
  // initDataTable() to avoid splitting a summary from its detail rows.
  const searchInput = document.getElementById('pendingDocsSearch');
  if (searchInput) {
    searchInput.addEventListener('input', function () {
      const term = searchInput.value.trim().toLowerCase();
      document.querySelectorAll('.pending-doc-summary').forEach(function (row) {
        const match = !term || (row.dataset.search || '').includes(term);
        row.style.display = match ? '' : 'none';
        if (!match) {
          const detail = document.getElementById('detail-' + row.dataset.toggleStudent);
          if (detail) detail.style.display = 'none';
          row.classList.remove('open');
        }
      });
    });
  }

  document.querySelectorAll('.pending-doc-summary').forEach(function (row) {
    row.addEventListener('click', function () {
      const aid = row.dataset.toggleStudent;
      const detail = document.getElementById('detail-' + aid);
      if (!detail) return;
      const isOpen = detail.style.display !== 'none';
      detail.style.display = isOpen ? 'none' : '';
      row.classList.toggle('open', !isOpen);
    });
  });

  document.querySelectorAll('.btn-mark-received').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      const documentId = btn.dataset.documentId;
      const confirmFn = window.confirmAction || function (opts) {
        return Promise.resolve(window.confirm(opts.title || 'Are you sure?'));
      };

      confirmFn({
        title: 'Mark this document as received?',
        icon: 'question',
        confirmText: 'Yes, mark received',
      }).then(function (ok) {
        if (!ok) return;

        btn.disabled = true;
        btn.textContent = 'Saving…';

        fetch('/SIAdrafts/Backend/api/mark_document_received.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': document.body.dataset.csrf || '' },
          body: new URLSearchParams({ document_id: documentId }),
        })
          .then(function (res) { return res.json(); })
          .then(function (result) {
            if (!result.success) {
              alert(result.error || 'Could not update.');
              btn.disabled = false;
              btn.textContent = 'Mark Received';
              return;
            }
            const row = btn.closest('tr');
            const detailTable = row ? row.closest('table') : null;
            if (row) row.remove();
            // If that was the last remaining document for this student,
            // remove the whole summary + detail row pair.
            if (detailTable && !detailTable.querySelector('tbody tr')) {
              const detailWrap = detailTable.closest('.pending-doc-detail');
              if (detailWrap) {
                const summaryRow = detailWrap.previousElementSibling;
                detailWrap.remove();
                if (summaryRow) summaryRow.remove();
              }
            }
          })
          .catch(function () {
            alert('Could not reach the server. Please try again.');
            btn.disabled = false;
            btn.textContent = 'Mark Received';
          });
      });
    });
  });
});
</script>

<?php include '../Include/footer.php'; ?>
