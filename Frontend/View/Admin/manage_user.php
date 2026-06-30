<?php
$pageTitle = "MANAGE USER";
$activePage = "manage_user";

include 'Include/header.php';
?>


  <?php include 'Include/sidebar.php'; ?>

    <main class="page-content">
      <div class="panel">
        <div class="panel-header">
          <span class="panel-title">System Users</span>
          <button class="btn btn-primary" id="openAddUserModal">+ Add User</button>
        </div>
        <div class="panel-body" style="padding:0">
          <table class="data-table">
            <thead>
              <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Last Login</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>Admin User</td>
                <td>admin@eduschool.edu</td>
                <td>System Administrator</td>
                <td>Today, 9:00 AM</td>
                <td><span class="badge badge-success">Active</span></td>
                <td>
                  <button class="btn btn-outline" style="padding:4px 10px;font-size:12px">Edit</button>
                </td>
              </tr>
              <tr>
                <td>Registrar Staff</td>
                <td>registrar@eduschool.edu</td>
                <td>Registrar</td>
                <td>Today, 8:32 AM</td>
                <td><span class="badge badge-success">Active</span></td>
                <td>
                  <button class="btn btn-outline" style="padding:4px 10px;font-size:12px">Edit</button>
                </td>
              </tr>
              <tr>
                <td>Cashier Staff</td>
                <td>cashier@eduschool.edu</td>
                <td>Cashier</td>
                <td>Yesterday</td>
                <td><span class="badge badge-pending">Inactive</span></td>
                <td>
                  <button class="btn btn-outline" style="padding:4px 10px;font-size:12px">Edit</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</div>

<!-- ===== ADD USER MODAL ===== -->
<div class="modal-overlay" id="addUserModal">
  <div class="modal-box">

    <!-- Header -->
    <div class="modal-header">
      <div class="modal-header-left">
        <div class="modal-icon">👤</div>
        <div>
          <div class="modal-title">Add New User</div>
          <div class="modal-subtitle">Fill in the details below</div>
        </div>
      </div>
      <button class="modal-close" id="closeAddUserModal" aria-label="Close modal">✕</button>
    </div>

    <!-- Body -->
    <div class="modal-body">

      <!-- Row 1: First + Last Name -->
      <div class="modal-form-row">
        <div class="form-group">
          <label class="form-label">First Name <span class="required">*</span></label>
          <input class="form-input" type="text" id="firstName" placeholder="Juan" />
        </div>
        <div class="form-group">
          <label class="form-label">Last Name <span class="required">*</span></label>
          <input class="form-input" type="text" id="lastName" placeholder="Dela Cruz" />
        </div>
      </div>

      <!-- Row 2: Email -->
      <div class="form-group">
        <label class="form-label">Email Address <span class="required">*</span></label>
        <input class="form-input" type="email" id="userEmail" placeholder="juan@eduschool.edu" />
      </div>

      <!-- Row 3: Role + Department -->
      <div class="modal-form-row">
        <div class="form-group">
          <label class="form-label">Role <span class="required">*</span></label>
          <div class="select-wrapper">
            <select class="form-input form-select" id="userRole">
              <option value="" disabled selected>Select role</option>
              <option value="admin">System Administrator</option>
              <option value="registrar">Registrar</option>
              <option value="cashier">Cashier</option>
              <option value="teacher">Teacher</option>
              <option value="staff">Staff</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Department</label>
          <div class="select-wrapper">
            <select class="form-input form-select" id="userDept">
              <option value="" disabled selected>Select dept.</option>
              <option value="admin">Administration</option>
              <option value="academic">Academic Affairs</option>
              <option value="finance">Finance</option>
              <option value="student">Student Services</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Row 4: Password -->
      <div class="form-group">
        <label class="form-label">Password <span class="required">*</span></label>
        <div class="pass-wrapper">
          <input class="form-input" type="password" id="userPassword" placeholder="Minimum 8 characters" />
          <button class="pass-toggle" id="togglePassword" type="button" aria-label="Toggle password visibility">👁</button>
        </div>
      </div>

      <div class="modal-divider"></div>

      <!-- Row 5: Active toggle -->
      <div class="modal-status-row">
        <div>
          <div class="status-label">Active Account</div>
          <div class="status-desc">User can log in immediately after creation</div>
        </div>
        <label class="toggle-switch">
          <input type="checkbox" id="userActive" checked />
          <span class="toggle-slider"></span>
        </label>
      </div>

    </div><!-- /modal-body -->

    <!-- Footer -->
    <div class="modal-footer">
      <button class="btn btn-outline" id="cancelAddUser">Cancel</button>
      <button class="btn btn-primary" id="submitAddUser">+ Add User</button>
    </div>

  </div><!-- /modal-box -->
</div><!-- /modal-overlay -->

<?php include 'Include/footer.php'?>