<?php
// Shared read-only indicator for pages Admin can view but not act on
// (Registrar/Head Registrar/Treasury/Admission monitoring). Include right
// after opening <main class="page-content">, on any page whose gate now
// includes ROLE_ADMIN for view-only oversight. Silently renders nothing
// for every other role, so it's safe to include unconditionally.
if (current_user_is(['Admin'])):
?>
<div class="admin-readonly-banner">
  <i class="bi bi-eye-fill"></i>
  <span>You're viewing this as <strong>Admin</strong> — this is a read-only monitoring view. Changes here are made by the staff who own this page.</span>
</div>
<style>
  .admin-readonly-banner {
    display: flex;
    align-items: center;
    gap: 10px;
    background: var(--sky-100, #e6f1fd);
    color: var(--sky-700, #1a5fa8);
    border: 1px solid var(--sky-200, #bcdcfb);
    border-radius: var(--radius-lg, 10px);
    padding: 10px 16px;
    font-size: 13.5px;
    font-weight: 500;
    margin-bottom: 20px;
  }
  .admin-readonly-banner i {
    font-size: 15px;
    flex-shrink: 0;
  }
</style>
<?php endif; ?>
