<?php
$pageTitle = "MANAGE USER";
$activePage = "manage_user";


require_once '../../../Backend/auth.php';
require_once __DIR__ . '/../../../Backend/admin/manage_user.php';

include 'Include/header.php';
?>

<div class="app-layout">

    <?php include 'Include/sidebar.php'; ?>

    <main class="page-content">

        <div class="panel">

            <div class="panel-header">
                <span class="panel-title">System Users</span>
                <button class="btn btn-primary" id="openAddUserModal">
                    + Add User
                </button>
            </div>

            <div class="panel-body" style="padding:0;">

                <table class="data-table">

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

                    <tbody>

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

                            <tr>

                                <td><?= htmlspecialchars($row['staff_id'] ?? '—'); ?></td>

                                <td><?= htmlspecialchars($row['full_name']); ?></td>

                                <td><?= htmlspecialchars($row['email']); ?></td>

                                <td><?= htmlspecialchars($row['role_name']); ?></td>

                                <td>
                                    <?= !empty($row['last_login'])
                                        ? date('M d, Y h:i A', strtotime($row['last_login']))
                                        : 'Never'; ?>
                                </td>

                                <td>
                                    <span class="badge badge-<?= $badge; ?>">
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
                            <option value="Administrator">System Administrator</option>
                            <option value="Admission">Admission Staff</option>
                            <option value="Treasury">Cashier</option>
                            <option value="Teacher">Teacher</option>
                            <option value="Staff">Staff</option>

                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Password <span class="required">*</span></label>

                    <div class="pass-wrapper">

                        <input type="password"
                               name="password"
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
                            <option value="Administrator">System Administrator</option>
                            <option value="Admission">Admission Staff</option>
                            <option value="Treasury">Cashier</option>
                            <option value="Teacher">Teacher</option>
                            <option value="Staff">Staff</option>

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

<?php include 'Include/footer.php'; ?>