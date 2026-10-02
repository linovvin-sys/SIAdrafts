-- Real-world admission flow: the guardian is frequently absent (OFW,
-- different province, working) and was never actually verified in person
-- anyway (walk-in confirmation only physically checks the APPLICANT's own
-- documents -- see confirm_admission.php). Three additions to make that
-- explicit and auditable instead of silently assumed:
--
-- 1. An authorized representative (aunt/uncle/sibling/etc.) who may
--    enroll the student in the guardian's place -- distinct from
--    authorization_note/authorized_by/authorized_at, which are an
--    unrelated concept (a staff member's provisional sign-off to let an
--    applicant proceed despite a missing document).
-- 2. consent_given_at records when the applicant certified they have
--    their guardian's consent -- a timestamp carries more evidentiary
--    weight than a bare boolean ever could.
ALTER TABLE applicants
    ADD COLUMN representative_name VARCHAR(150) NULL DEFAULT NULL AFTER guardian_id_number,
    ADD COLUMN representative_relationship VARCHAR(50) NULL DEFAULT NULL AFTER representative_name,
    ADD COLUMN consent_given_at DATETIME NULL DEFAULT NULL AFTER representative_relationship;
