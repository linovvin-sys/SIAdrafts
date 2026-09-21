<?php
$pageTitle  = "DOCUMENT RECORDS";
$activePage = "document_records";
$pageScript = "document_records";

require_once '../../../Backend/auth.php';
require_once '../../../Backend/roles.php';
require_once '../../../Backend/require_role.php';
require_role([ROLE_ADMISSION, ROLE_ADMIN]);
require_once '../../../Backend/db.php';

$db   = new Database();
$conn = $db->connect();

// One query, grouped in PHP by applicant (not per-document) — same
// pattern as pending_documents.php — so the table shows one row per
// applicant with a submitted/total count, and each row expands into the
// individual documents via the modal below. Every applicant who has ever
// uploaded or been asked for a document shows up here, not just today's
// admission queue, so this is the actual permanent archive that
// admission_confirm.php (a "currently awaiting a decision" screen) isn't.
$rows = $conn->query("
    SELECT ad.document_id, ad.document_name, ad.status, ad.uploaded_at, ad.file_path,
           a.applicant_id, a.reference_id, a.first_name, a.last_name, a.middle_name,
           a.program, a.year_level, a.status AS applicant_status
    FROM applicant_documents ad
    JOIN applicants a ON a.applicant_id = ad.applicant_id
    ORDER BY a.last_name, a.first_name, ad.document_name
")->fetch_all(MYSQLI_ASSOC);

$programs = $conn->query("SELECT DISTINCT program FROM applicants WHERE program IS NOT NULL AND program <> '' ORDER BY program")->fetch_all(MYSQLI_ASSOC);

$db->close();

$applicants = [];
foreach ($rows as $row) {
    $aid = (int)$row['applicant_id'];
    if (!isset($applicants[$aid])) {
        $applicants[$aid] = [
            'applicant_id'     => $aid,
            'reference_id'     => $row['reference_id'],
            'name'             => trim($row['first_name'] . ' ' . ($row['middle_name'] ? mb_substr($row['middle_name'], 0, 1) . '. ' : '') . $row['last_name']),
            'program'          => $row['program'],
            'year_level'       => $row['year_level'],
            'applicant_status' => $row['applicant_status'],
            'documents'        => [],
            'submitted_count'  => 0,
            'total_count'      => 0,
            'latest_upload'    => null,
        ];
    }
    $applicants[$aid]['documents'][] = $row;
    $applicants[$aid]['total_count']++;
    if (in_array($row['status'], ['submitted', 'submitted_online'], true)) {
        $applicants[$aid]['submitted_count']++;
    }
    if ($applicants[$aid]['latest_upload'] === null || $row['uploaded_at'] > $applicants[$aid]['latest_upload']) {
        $applicants[$aid]['latest_upload'] = $row['uploaded_at'];
    }
}
$applicants = array_values($applicants);

// Stat strip — real counts from the data already loaded above, no extra query.
$totalApplicants = count($applicants);
$completeCount   = count(array_filter($applicants, fn($a) => $a['submitted_count'] === $a['total_count']));
$missingCount    = $totalApplicants - $completeCount;
$totalDocuments  = count($rows);

include '../Include/header.php';
?>

<div class="app-layout">

  <?php include '../Include/sidebar.php'; ?>

  <main class="page-content">
    <?php include '../Include/readonly_banner.php'; ?>

    <div class="clay-stat-grid clay-stat-grid--compact">
      <div class="clay-stat-card">
        <div class="clay-icon"><i class="bi bi-folder2-open"></i></div>
        <div>
          <div class="stat-value"><?= $totalApplicants ?></div>
          <div class="stat-label">Applicants on File</div>
        </div>
      </div>
      <div class="clay-stat-card">
        <div class="seal"><i class="bi bi-check-lg"></i></div>
        <div>
          <div class="stat-value"><?= $completeCount ?></div>
          <div class="stat-label">Complete</div>
        </div>
      </div>
      <div class="clay-stat-card">
        <div class="clay-icon"><i class="bi bi-hourglass-split"></i></div>
        <div>
          <div class="stat-value"><?= $missingCount ?></div>
          <div class="stat-label">Missing Something</div>
        </div>
      </div>
      <div class="clay-stat-card">
        <div class="clay-icon"><i class="bi bi-files"></i></div>
        <div>
          <div class="stat-value"><?= $totalDocuments ?></div>
          <div class="stat-label">Total Documents</div>
        </div>
      </div>
    </div>

    <div class="panel">
      <div class="panel-header">
        <span class="panel-title">Document Records</span>
      </div>

      <div class="panel-body clay-filter-bar" style="padding:16px 24px 0;">
        <div class="filter-bar">
          <input type="text" class="form-input" id="docSearch" placeholder="Search name or reference number…">
          <div class="select-wrapper">
            <select class="form-input form-select" id="docProgramFilter">
              <option value="">All Programs</option>
              <?php foreach ($programs as $p): ?>
                <option value="<?= htmlspecialchars($p['program']) ?>"><?= htmlspecialchars($p['program']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="select-wrapper">
            <select class="form-input form-select" id="docCompletenessFilter">
              <option value="">All</option>
              <option value="complete">Complete</option>
              <option value="missing">Missing Something</option>
            </select>
          </div>
        </div>
      </div>

      <div class="panel-body" style="padding:0;">
        <div class="table-wrap">
          <table class="data-table" id="documentRecordsTable">
            <thead>
              <tr>
                <th>Reference ID</th>
                <th>Name</th>
                <th>Program</th>
                <th>Documents</th>
                <th>Last Upload</th>
                <th style="width:120px">Actions</th>
              </tr>
            </thead>
            <tbody id="documentRecordsBody">
              <?php if (!empty($applicants)): ?>
                <?php foreach ($applicants as $rowIndex => $a): ?>
                  <?php
                    $isComplete = $a['submitted_count'] === $a['total_count'];
                    $searchKey  = strtolower($a['reference_id'] . ' ' . $a['name'] . ' ' . $a['program']);
                  ?>
                  <tr style="--row-i: <?= min((int)$rowIndex, 12) ?>;"
                      data-program="<?= htmlspecialchars($a['program']) ?>"
                      data-completeness="<?= $isComplete ? 'complete' : 'missing' ?>"
                      data-search="<?= htmlspecialchars($searchKey) ?>">
                    <td class="mono"><?= htmlspecialchars($a['reference_id']) ?></td>
                    <td><?= htmlspecialchars($a['name']) ?></td>
                    <td><?= htmlspecialchars($a['program']) ?> — Yr <?= (int)$a['year_level'] ?></td>
                    <td>
                      <span class="status-pill status-pill--<?= $isComplete ? 'approved' : 'pending' ?>">
                        <?= $a['submitted_count'] ?>/<?= $a['total_count'] ?> submitted
                      </span>
                    </td>
                    <td class="mono"><?= $a['latest_upload'] ? htmlspecialchars(date('M j, Y', strtotime($a['latest_upload']))) : '—' ?></td>
                    <td>
                      <button type="button" class="btn btn-outline" style="padding:4px 10px;font-size:12px"
                        data-view-documents="<?= (int)$a['applicant_id'] ?>"
                        data-applicant-name="<?= htmlspecialchars($a['name'], ENT_QUOTES) ?>">
                        View
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr><td colspan="6" style="text-align:center;">No documents on file yet.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- View Documents Modal -->
    <div id="viewDocumentsModal" class="modal-overlay">
      <div class="modal-box" style="max-width:560px;">
        <div class="modal-header">
          <div class="modal-header-left">
            <div class="modal-icon">📄</div>
            <div>
              <div class="modal-title">Documents on File</div>
              <div class="modal-subtitle" id="viewDocumentsSubtitle"></div>
            </div>
          </div>
          <button type="button" class="modal-close" data-close="viewDocumentsModal">✕</button>
        </div>

        <div class="modal-body" style="max-height:60vh; overflow-y:auto;">
          <div id="viewDocumentsList"></div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline" data-close="viewDocumentsModal">Close</button>
        </div>
      </div>
    </div>

  </main>
</div>

<script>
  // Handed to document_records.js as a plain object keyed by applicant_id —
  // this page already loaded every document row server-side for the stat
  // strip/table above, so the modal reuses that instead of a second fetch.
  window.APPLICANT_DOCUMENTS = <?= json_encode(array_column($applicants, 'documents', 'applicant_id'), JSON_UNESCAPED_SLASHES) ?>;
</script>

<?php
$extraScripts = [
    '/SIAdrafts/Frontend/Js/Admission/' . ($pageScript ?? 'document_records') . '.js',
];
include '../Include/footer.php';
?>
