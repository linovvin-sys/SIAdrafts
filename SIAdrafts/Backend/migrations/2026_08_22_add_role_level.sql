-- Adds a numeric rank to `roles` so "who outranks whom" is data, not a
-- fact that lives only in which literal arrays get passed to require_role()
-- across 70+ call sites.
--
-- IMPORTANT: `level` ranks roles for account-provisioning purposes only
-- (see Backend/roles.php ROLE_LEVELS doc comment). It intentionally does
-- NOT imply Admin can reach Head-Registrar-only academic approval actions
-- (course/subject/schedule/section approve-reject-delete) — those stay
-- gated by the separate registrar_tier concept so today's separation of
-- duties (Admin manages accounts/settings; Head Registrar approves
-- academic records) is preserved exactly as-is.

ALTER TABLE roles
  ADD COLUMN level INT NOT NULL DEFAULT 0 AFTER role_name;

UPDATE roles SET level = 100 WHERE role_name = 'Admin';
UPDATE roles SET level = 80  WHERE role_name = 'Head Registrar';
UPDATE roles SET level = 60  WHERE role_name = 'Registrar Staff';
UPDATE roles SET level = 50  WHERE role_name = 'Admission';
UPDATE roles SET level = 50  WHERE role_name = 'Treasury';
UPDATE roles SET level = 50  WHERE role_name = 'Staff';
