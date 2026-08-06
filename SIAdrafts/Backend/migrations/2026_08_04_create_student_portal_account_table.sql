-- Student portal login, kept fully separate from `users`/`roles` (staff auth)
-- so a student session can never be confused with, or escalated into, a
-- staff session. One row per student, created the moment student.student_no
-- is first minted (see save_enrollment.php).
CREATE TABLE IF NOT EXISTS student_portal_account (
  student_portal_account_id INT AUTO_INCREMENT PRIMARY KEY,
  applicant_id              INT NOT NULL,
  student_no                VARCHAR(20) NOT NULL,
  password_hash             VARCHAR(255) NOT NULL,
  must_change_password      TINYINT(1) NOT NULL DEFAULT 1,
  last_login                DATETIME NULL,
  created_at                TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at                TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_student_portal_applicant (applicant_id),
  UNIQUE KEY uq_student_portal_student_no (student_no),
  FOREIGN KEY (applicant_id) REFERENCES applicants(applicant_id)
);
