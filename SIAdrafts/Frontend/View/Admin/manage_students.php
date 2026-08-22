<?php
$pageTitle  = "MANAGE STUDENTS";
$activePage = "manage_students";
$pageScript = "manage_students";

require_once '../../../Backend/auth.php';
require_once '../../../Backend/roles.php';
require_once '../../../Backend/require_role.php';
require_role([ROLE_ADMIN]);
require_once '../../../Backend/db.php';

$db   = new Database();
$conn = $db->connect();

$students = $conn->query("
    SELECT spa.student_portal_account_id, spa.student_no, spa.must_change_password, spa.last_login,
           a.first_name, a.middle_name, a.last_name
    FROM student_portal_account spa
    JOIN applicants a ON a.applicant_id = spa.applicant_id
    ORDER BY a.last_name, a.first_name
")->fetch_all(MYSQLI_ASSOC);

$db->close();

function student_account_fullname(array $s): string {
    $middle = !empty($s['middle_name']) ? ' ' . mb_substr($s['middle_name'], 0, 1) . '.' : '';
    return $s['last_name'] . ', ' . $s['first_name'] . $middle;
}

include '../Include/header.php';
?>

<div class="app-layout">

  <?php include '../Include/sidebar.php'; ?>

  <main class="page-content">

    <div class="panel">
      <div class="panel-header">
        <span class="panel-title">Student Accounts</span>
      </div>

      <div class="panel-body" style="padding:16px 24px 0;">
        <div class="filter-bar">
          <input type="text" class="form-input" id="studentAccountSearch" placeholder="Search name or student number…">
        </div>
      </div>

      <div class="panel-body" style="padding:0;">
        <div class="table-responsive">
          <table class="data-table" id="studentAccountTable">
            <thead>
              <tr>
                <th>Student No.</th>
                <th>Name</th>
                <th>Last Login</th>
                <th>Password Status</th>
                <th style="width:150px">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($students)): ?>
                <?php foreach ($students as $row): ?>
                  <tr data-search="<?= htmlspecialchars(strtolower(student_account_fullname($row) . ' ' . $row['student_no'])) ?>">
                    <td class="mono"><?= htmlspecialchars($row['student_no']) ?></td>
                    <td><?= htmlspecialchars(student_account_fullname($row)) ?></td>
                    <td class="mono">
                      <?= !empty($row['last_login'])
                          ? date('M d, Y h:i A', strtotime($row['last_login']))
                          : '<span class="text-never" style="font-family:var(--font-ui); font-style:italic;">Never logged in</span>'; ?>
                    </td>
                    <td>
                      <span class="status-pill status-pill--<?= $row['must_change_password'] ? 'pending' : 'approved' ?>">
                        <?= $row['must_change_password'] ? 'Temp password' : 'Set by student' ?>
                      </span>
                    </td>
                    <td>
                      <button type="button" class="btn btn-outline" style="padding:4px 10px;font-size:12px"
                        data-reset-student="<?= (int)$row['student_portal_account_id'] ?>"
                        data-student-name="<?= htmlspecialchars(student_account_fullname($row)) ?>"
                      >Reset Password</button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr><td colspan="5" style="text-align:center;">No student accounts found.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </main>
</div>

<?php
$extraScripts = [
    '/SIAdrafts/Frontend/Js/Admin/' . ($pageScript ?? 'manage_students') . '.js',
];
include '../Include/footer.php';
?>
