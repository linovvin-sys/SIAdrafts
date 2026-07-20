ALTER TABLE applicants
  ADD COLUMN nationality VARCHAR(50) NOT NULL DEFAULT 'Filipino' AFTER sex,
  ADD COLUMN school_year VARCHAR(9) NULL AFTER applicant_type,
  ADD COLUMN semester TINYINT NULL AFTER school_year,
  ADD COLUMN possible_duplicate_student_id INT NULL AFTER admission_status,
  ADD COLUMN duplicate_match_status ENUM('none','pending_review','confirmed','dismissed') NOT NULL DEFAULT 'none' AFTER possible_duplicate_student_id,
  ADD COLUMN authorization_note TEXT NULL AFTER duplicate_match_status,
  ADD COLUMN authorized_by INT NULL AFTER authorization_note,
  ADD COLUMN authorized_at TIMESTAMP NULL AFTER authorized_by,
  ADD COLUMN cleared_by INT NULL AFTER authorized_at,
  ADD COLUMN cleared_at TIMESTAMP NULL AFTER cleared_by,
  ADD CONSTRAINT fk_applicants_dup_student FOREIGN KEY (possible_duplicate_student_id) REFERENCES student(student_id),
  ADD CONSTRAINT fk_applicants_authorized_by FOREIGN KEY (authorized_by) REFERENCES users(user_id),
  ADD CONSTRAINT fk_applicants_cleared_by FOREIGN KEY (cleared_by) REFERENCES users(user_id);

-- file_path already exists on applicant_documents (pre-existing column) — only
-- add 'source', and extend the status enum with the two applicant-declared
-- statuses used by the online application form's optional requirements section.
ALTER TABLE applicant_documents
  ADD COLUMN source ENUM('applicant','staff') NOT NULL DEFAULT 'staff' AFTER file_path,
  MODIFY COLUMN status ENUM('pending','submitted','will_submit_later','submitted_online') NOT NULL DEFAULT 'pending';
