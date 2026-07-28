<?php
$pageTitle = "MANAGE USER";
$activePage = "manage_user";


require_once '../../../Backend/auth.php';
require_once '../../../Backend/roles.php';
require_once '../../../Backend/require_role.php';
require_role([ROLE_ADMIN]);
require_once __DIR__ . '/../../../Backend/admin/manage_user.php';

function role_badge_class(string $role): string {
    $role = strtolower($role);
    if (strpos($role, 'admin') !== false) return 'rd-role-admin';
    if (strpos($role, 'registrar') !== false) return 'rd-role-registrar';
    if (strpos($role, 'treasury') !== false || strpos($role, 'cashier') !== false) return 'rd-role-treasury';
    return 'rd-role-staff';
}

function avatar_tint(string $role): string {
    $role = strtolower($role);
    if (strpos($role, 'admin') !== false) return 'seal';
    if (strpos($role, 'registrar') !== false) return 'sky';
    if (strpos($role, 'treasury') !== false || strpos($role, 'cashier') !== false) return 'gold';
    return 'teal';
}

function user_initials(string $name): string {
    $parts = preg_split('/\s+/', trim($name));
    $letters = '';
    foreach ($parts as $p) {
        if ($p === '') continue;
        $letters .= strtoupper($p[0]);
        if (strlen($letters) >= 2) break;
    }
    return $letters ?: '?';
}

include '../Include/header.php';
?>

<div class="app-layout">

    <?php include '../Include/sidebar.php'; ?>

    <main class="page-content">

        <!-- Statistics -->
        <div class="stats-grid">

            <div class="stat-card">
                <div class="stat-icon gold"><i class="bi bi-people-fill"></i></div>
                <div>
                    <div class="stat-value"><?= $userStats['total']; ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon green"><i class="bi bi-person-check-fill"></i></div>
                <div>
                    <div class="stat-value"><?= $userStats['active']; ?></div>
                    <div class="stat-label">Active</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon blue"><i class="bi bi-person-dash"></i></div>
                <div>
                    <div class="stat-value"><?= $userStats['inactive']; ?></div>
                    <div class="stat-label">Inactive</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon purple"><i class="bi bi-tags-fill"></i></div>
                <div>
                    <div class="stat-value"><?= count($userStats['roles']); ?></div>
                    <div class="stat-label">Roles in Use</div>
                </div>
            </div>

        </div>

        <div class="panel">

            <div class="panel-header">
                <span class="panel-title">System Users</span>
                <button class="btn btn-primary" id="openAddUserModal">
                    + Add User
                </button>
            </div>

            <div class="panel-body" style="padding:16px 24px 0;">
                <div class="user-filter-bar">
                    <div class="select-wrapper">
                        <select class="form-input form-select" id="userRoleFilter">
                            <option value="">All Roles</option>
                            <?php foreach (array_keys($userStats['roles']) as $roleName): ?>
                                <option value="<?= htmlspecialchars($roleName) ?>"><?= htmlspecialchars($roleName) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="select-wrapper">
                        <select class="form-input form-select" id="userStatusFilter">
                            <option value="">All Statuses</option>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="panel-body" style="padding:0;">

                <table class="data-table" id="userTable">

                    <thead>
                        <tr>
                            <th>Staff ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Last Login</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody id="userTableBody">

                    <?php if (!empty($users)): ?>

                        <?php foreach ($users as $row): ?>

                            <?php
                            $status = strtolower($row['status_name']);

                            switch ($status) {
                                case 'active':
                                    $badge = 'success';
                                    break;

                                case 'inactive':
                                    $badge = 'pending';
                                    break;

                                default:
                                    $badge = 'secondary';
                            }
                            ?>

                            <tr data-role="<?= htmlspecialchars($row['role_name']) ?>" data-status="<?= htmlspecialchars($row['status_name']) ?>" data-search="<?= htmlspecialchars(strtolower($row['full_name'] . ' ' . $row['email'] . ' ' . ($row['staff_id'] ?? ''))) ?>">

                                <td class="mono"><?= htmlspecialchars($row['staff_id'] ?? '—'); ?></td>

                                <td>
                                    <div style="display:flex; align-items:center; gap:10px;">
                                        <span class="rd-avatar <?= avatar_tint($row['role_name']) ?>"><?= htmlspecialchars(user_initials($row['full_name'])) ?></span>
                                        <?= htmlspecialchars($row['full_name']); ?>
                                    </div>
                                </td>

                                <td><?= htmlspecialchars($row['email']); ?></td>

                                <td>
                                    <span class="rd-role-badge <?= role_badge_class($row['role_name']) ?>">
                                        <?= htmlspecialchars($row['role_name']); ?>
                                    </span>
                                </td>

                                <td class="mono">
                                    <?= !empty($row['last_login'])
                                        ? date('M d, Y h:i A', strtotime($row['last_login']))
                                        : '<span class="text-never" style="font-family:var(--font-ui); font-style:italic;">Never logged in</span>'; ?>
                                </td>

                                <td>
                                    <span class="stamp <?= $badge === 'success' ? 'approved' : ($badge === 'pending' ? 'pending' : '') ?>">
                                        <?= htmlspecialchars($row['status_name']); ?>
                                    </span>
                                </td>

                                <td>
                                    <button type="button"
                                       class="btn btn-outline btn-edit-user"
                                       style="padding:4px 10px;font-size:12px"
                                       data-id="<?= htmlspecialchars($row['user_id']); ?>"
                                       data-first_name="<?= htmlspecialchars($row['first_name']); ?>"
                                       data-middle_name="<?= htmlspecialchars($row['middle_name'] ?? ''); ?>"
                                       data-last_name="<?= htmlspecialchars($row['last_name']); ?>"
                                       data-email="<?= htmlspecialchars($row['email']); ?>"
                                       data-username="<?= htmlspecialchars($row['username']); ?>"
                                       data-phone="<?= htmlspecialchars($row['phone_number'] ?? ''); ?>"
                                       data-role="<?= htmlspecialchars($row['role_name']); ?>"
                                       data-status="<?= htmlspecialchars($row['status_name']); ?>">
                                        Edit
                                    </button>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="7" style="text-align:center;">
                                No users found.
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </main>

</div>

<!-- ===== ADD USER MODAL ===== -->

<div class="modal-overlay" id="addUserModal">

    <div class="modal-box">

        <form action="../../../Backend/admin/add_user.php" method="POST">

            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_pageCsrfToken, ENT_QUOTES) ?>">

            <!-- Header -->
            <div class="modal-header">

                <div class="modal-header-left">

                    <div class="modal-icon">👤</div>

                    <div>
                        <div class="modal-title">Add New User</div>
                        <div class="modal-subtitle">Fill in the details below</div>
                    </div>

                </div>

                <button type="button"
                        class="modal-close"
                        id="closeAddUserModal">
                    ✕
                </button>

            </div>

            <!-- Body -->
            <div class="modal-body">

                <div class="modal-form-row modal-form-row-3">

                    <div class="form-group">
                        <label class="form-label">First Name <span class="required">*</span></label>
                        <input type="text"
                               name="first_name"
                               class="form-input"
                               required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Last Name <span class="required">*</span></label>
                        <input type="text"
                               name="last_name"
                               class="form-input"
                               required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Middle Name</label>
                        <input
                            type="text"
                            name="middle_name"
                            class="form-input">
                    </div>

                </div>

                <div class="form-group">
                    <label class="form-label">Email Address <span class="required">*</span></label>
                    <input type="email"
                           name="email"
                           class="form-input"
                           required>
                </div>
                <div class="modal-form-row">

                    <div class="form-group">
                        <label class="form-label">Username <span class="required">*</span></label>
                        <input
                            type="text"
                            name="username"
                            class="form-input"
                            required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Phone Number</label>
                        <input
                            type="text"
                            name="phone_number"
                            class="form-input">
                    </div>

                </div>

                <div class="form-group">
                    <label class="form-label">Role <span class="required">*</span></label>

                    <div class="select-wrapper">
                        <select name="role"
                                class="form-input form-select"
                                required>

                            <option value="">Select role</option>
                            <option value="Admin">System Administrator</option>
                            <option value="Head Registrar">Head Registrar</option>
                            <option value="Registrar Staff">Registrar Staff</option>
                            <option value="Admission">Admission Staff</option>
                            <option value="Treasury">Cashier</option>
                            <option value="Staff">Staff (Enrollment)</option>

                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Password <span class="required">*</span></label>

                    <div class="pass-wrapper">

                        <input type="password"
                               name="password"
                               id="userPassword"
                               class="form-input"
                               minlength="8"
                               required>

                        <button type="button"
                                class="pass-toggle"
                                id="togglePassword">
                            👁
                        </button>

                    </div>

                </div>

                <div class="modal-divider"></div>

                <div class="modal-status-row">

                    <div>
                        <div class="status-label">Active Account</div>
                        <div class="status-desc">
                            User can log in immediately after creation
                        </div>
                    </div>

                    <label class="toggle-switch">
                        <input type="checkbox"
                               name="status"
                               value="Active"
                               checked>
                        <span class="toggle-slider"></span>
                    </label>

                </div>

            </div>

            <!-- Footer -->
            <div class="modal-footer">

                <button type="button"
                        class="btn btn-outline"
                        id="cancelAddUser">
                    Cancel
                </button>

                <button type="submit"
                        class="btn btn-primary">
                    + Add User
                </button>

            </div>

        </form>

    </div>

</div>

<!-- ===== EDIT USER MODAL ===== -->

<div class="modal-overlay" id="editUserModal">

    <div class="modal-box">

        <form action="../../../Backend/admin/edit_user.php" method="POST" id="editUserForm">

            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_pageCsrfToken, ENT_QUOTES) ?>">
            <input type="hidden" name="user_id" id="editUserId">

            <!-- Header -->
            <div class="modal-header">

                <div class="modal-header-left">

                    <div class="modal-icon">✏️</div>

                    <div>
                        <div class="modal-title">Edit User</div>
                        <div class="modal-subtitle">Update the details below</div>
                    </div>

                </div>

                <button type="button"
                        class="modal-close"
                        id="closeEditUserModal">
                    ✕
                </button>

            </div>

            <!-- Body -->
            <div class="modal-body">

                <div class="modal-form-row modal-form-row-3">

                    <div class="form-group">
                        <label class="form-label">First Name <span class="required">*</span></label>
                        <input type="text"
                               name="first_name"
                               id="editFirstName"
                               class="form-input"
                               required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Last Name <span class="required">*</span></label>
                        <input type="text"
                               name="last_name"
                               id="editLastName"
                               class="form-input"
                               required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Middle Name</label>
                        <input
                            type="text"
                            name="middle_name"
                            id="editMiddleName"
                            class="form-input">
                    </div>

                </div>

                <div class="form-group">
                    <label class="form-label">Email Address <span class="required">*</span></label>
                    <input type="email"
                           name="email"
                           id="editEmail"
                           class="form-input"
                           required>
                </div>
                <div class="modal-form-row">

                    <div class="form-group">
                        <label class="form-label">Username <span class="required">*</span></label>
                        <input
                            type="text"
                            name="username"
                            id="editUsername"
                            class="form-input"
                            required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Phone Number</label>
                        <input
                            type="text"
                            name="phone_number"
                            id="editPhone"
                            class="form-input">
                    </div>

                </div>

                <div class="form-group">
                    <label class="form-label">Role <span class="required">*</span></label>

                    <div class="select-wrapper">
                        <select name="role"
                                id="editRole"
                                class="form-input form-select"
                                required>

                            <option value="">Select role</option>
                            <option value="Admin">System Administrator</option>
                            <option value="Head Registrar">Head Registrar</option>
                            <option value="Registrar Staff">Registrar Staff</option>
                            <option value="Admission">Admission Staff</option>
                            <option value="Treasury">Cashier</option>
                            <option value="Staff">Staff (Enrollment)</option>

                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">New Password</label>

                    <div class="pass-wrapper">

                        <input type="password"
                               name="password"
                               id="editPassword"
                               class="form-input"
                               minlength="8"
                               placeholder="Leave blank to keep current password">

                        <button type="button"
                                class="pass-toggle"
                                id="toggleEditPassword">
                            👁
                        </button>

                    </div>

                </div>

                <div class="modal-divider"></div>

                <div class="modal-status-row">

                    <div>
                        <div class="status-label">Active Account</div>
                        <div class="status-desc">
                            User can log in while this is enabled
                        </div>
                    </div>

                    <label class="toggle-switch">
                        <input type="checkbox"
                               name="status"
                               id="editStatus"
                               value="Active">
                        <span class="toggle-slider"></span>
                    </label>

                </div>

            </div>

            <!-- Footer -->
            <div class="modal-footer">

                <button type="button"
                        class="btn btn-outline"
                        id="cancelEditUser">
                    Cancel
                </button>

                <button type="submit"
                        class="btn btn-primary">
                    Save Changes
                </button>

            </div>

        </form>

    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const table = initDataTable('#userTable', { order: [] });

  $.fn.dataTable.ext.search.push(function (settings, searchRow, index, rowData, counter) {
    if (settings.nTable.id !== 'userTable') return true;
    const row = table.row(index).node();
    if (!row) return true;

    const role   = document.getElementById('userRoleFilter').value;
    const status = document.getElementById('userStatusFilter').value;

    if (role && row.dataset.role !== role) return false;
    if (status && row.dataset.status !== status) return false;
    return true;
  });

  document.getElementById('userRoleFilter').addEventListener('change', () => table.draw());
  document.getElementById('userStatusFilter').addEventListener('change', () => table.draw());
});
</script>

<?php if (isset($_GET['added']) || isset($_GET['add_error'])): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
  <?php if (isset($_GET['added'])): ?>
    Swal.fire({
      icon: 'success',
      title: 'User added',
      text: 'The new account has been created.',
      confirmButtonColor: '#1c2b4a'
    });
  <?php elseif (isset($_GET['add_error'])): ?>
    Swal.fire({
      icon: 'error',
      title: 'Could not add user',
      text: <?= json_encode($_GET['add_error']) ?>,
      confirmButtonColor: '#1c2b4a'
    });
  <?php endif; ?>

  // Strip the query string so a page refresh doesn't re-show the alert
  const cleanUrl = window.location.pathname;
  window.history.replaceState({}, document.title, cleanUrl);
});
</script>
<?php endif; ?>

<?php include '../Include/footer.php'; ?>