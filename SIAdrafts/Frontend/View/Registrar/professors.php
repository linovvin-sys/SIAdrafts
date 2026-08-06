<?php
$pageTitle  = "PROFESSORS";
$activePage = "professors";
$pageScript = "professors";

require_once '../../../Backend/auth.php';
require_once '../../../Backend/require_role.php';
require_role(['Registrar Staff', 'Head Registrar', 'Admin']);
require_once '../../../Backend/db.php';

$db   = new Database();
$conn = $db->connect();

$isAdminViewer = current_user_is(['Admin']);

$professors = $conn->query("
    SELECT p.professor_id, p.first_name, p.middle_name, p.last_name, p.username,
           d.department_code, d.department_name,
           p.status_id, st.status_name
    FROM professor p
    JOIN department d ON d.department_id = p.department_id
    JOIN statuses st  ON st.status_id = p.status_id
    ORDER BY p.last_name, p.first_name
")->fetch_all(MYSQLI_ASSOC);

$departments = $conn->query("
    SELECT department_id, department_code, department_name
    FROM department
    ORDER BY department_name
")->fetch_all(MYSQLI_ASSOC);

$db->close();

function professor_fullname(array $p): string {
    $middle = !empty($p['middle_name']) ? ' ' . mb_substr($p['middle_name'], 0, 1) . '.' : '';
    return $p['last_name'] . ', ' . $p['first_name'] . $middle;
}

include '../Include/header.php';
?>

<div class="app-layout">

  <?php include '../Include/sidebar.php'; ?>

  <main class="page-content">
    <?php include '../Include/readonly_banner.php'; ?>

    <div class="panel">
      <div class="panel-header">
        <span class="panel-title">Professors</span>
        <?php if (!$isAdminViewer): ?>
        <button type="button" class="btn btn-primary" data-open="addProfessorModal">+ Add Professor</button>
        <?php endif; ?>
      </div>

      <div class="panel-body" style="padding:16px 24px 0;">
        <div class="filter-bar">
          <input type="text" class="form-input" id="professorSearch" placeholder="Search name or department…">
        </div>
      </div>

      <div class="panel-body" style="padding:0;">
        <div class="table-responsive">
          <table class="data-table" id="professorTable">
            <thead>
              <tr>
                <th>Name</th>
                <th>Department</th>
                <th>Status</th>
                <th>Portal</th>
                <th style="width:150px">Actions</th>
              </tr>
            </thead>
            <tbody id="professorListBody">
              <?php if (!empty($professors)): ?>
                <?php foreach ($professors as $row): ?>
                  <tr data-row-id="<?= (int)$row['professor_id'] ?>" data-search="<?= htmlspecialchars(strtolower(professor_fullname($row) . ' ' . $row['department_code'] . ' ' . $row['department_name'])) ?>">
                    <td><?= htmlspecialchars(professor_fullname($row)) ?></td>
                    <td><?= htmlspecialchars($row['department_code']) ?>
                      <div class="text-muted"><?= htmlspecialchars($row['department_name']) ?></div>
                    </td>
                    <td><span class="status-pill status-pill--<?= $row['status_name'] === 'Active' ? 'approved' : 'rejected' ?>"><?= htmlspecialchars($row['status_name']) ?></span></td>
                    <td><span class="status-pill status-pill--<?= $row['username'] ? 'approved' : 'pending' ?>"><?= $row['username'] ? 'Enabled' : 'No account' ?></span></td>
                    <td>
                      <?php if (!$isAdminViewer): ?>
                      <div class="row-actions">
                        <?php if ($row['status_name'] === 'Active'): ?>
                          <button type="button" class="btn btn-outline" style="padding:4px 10px;font-size:12px" data-toggle-professor="<?= (int)$row['professor_id'] ?>" data-toggle-action="deactivate">Deactivate</button>
                        <?php else: ?>
                          <button type="button" class="btn btn-outline" style="padding:4px 10px;font-size:12px" data-toggle-professor="<?= (int)$row['professor_id'] ?>" data-toggle-action="activate">Activate</button>
                        <?php endif; ?>
                      </div>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr><td colspan="5" style="text-align:center;">No professors found.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Add Professor Modal -->
    <div id="addProfessorModal" class="modal-overlay">
      <div class="modal-box">
        <div class="modal-header">
          <div class="modal-header-left">
            <div class="modal-icon">🧑‍🏫</div>
            <div>
              <div class="modal-title">Add Professor</div>
              <div class="modal-subtitle">Create a new professor record</div>
            </div>
          </div>
          <button type="button" class="modal-close" data-close="addProfessorModal">✕</button>
        </div>

        <div class="modal-body">
          <div class="form-group">
            <label class="form-label">First Name<span class="required">*</span></label>
            <input type="text" id="newProfessorFirstName" class="form-input" placeholder="e.g. Maria" required>
          </div>
          <div class="form-group">
            <label class="form-label">Middle Name</label>
            <input type="text" id="newProfessorMiddleName" class="form-input" placeholder="Optional">
          </div>
          <div class="form-group">
            <label class="form-label">Last Name<span class="required">*</span></label>
            <input type="text" id="newProfessorLastName" class="form-input" placeholder="e.g. Reyes" required>
          </div>
          <div class="form-group">
            <label class="form-label">Department<span class="required">*</span></label>
            <div class="select-wrapper">
              <select id="newProfessorDepartment" class="form-input form-select" required>
                <option value="">-- Select Department --</option>
                <?php foreach ($departments as $d): ?>
                  <option value="<?= (int)$d['department_id'] ?>">
                    <?= htmlspecialchars($d['department_code']) ?> - <?= htmlspecialchars($d['department_name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <hr>
          <div class="form-group">
            <label class="form-label">Portal Account (optional)</label>
            <div class="text-muted" style="font-size:12px;margin-bottom:8px;">
              Fill these in to let this professor log in to the Professor Portal. Leave blank to add the record only.
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Email</label>
            <input type="email" id="newProfessorEmail" class="form-input" placeholder="professor@school.edu">
          </div>
          <div class="form-group">
            <label class="form-label">Username</label>
            <input type="text" id="newProfessorUsername" class="form-input" placeholder="e.g. mreyes">
          </div>
          <div class="form-group">
            <label class="form-label">Password</label>
            <input type="password" id="newProfessorPassword" class="form-input" placeholder="Min. 8 characters" minlength="8">
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline" data-close="addProfessorModal">Cancel</button>
          <button type="button" class="btn btn-primary" id="confirmAddProfessor">Save Professor</button>
        </div>
      </div>
    </div>

  </main>
</div>

<?php
$extraScripts = [
    '/SIAdrafts/Frontend/Js/Registrar/' . ($pageScript ?? 'registrar') . '.js',
];
include '../Include/footer.php';
?>
