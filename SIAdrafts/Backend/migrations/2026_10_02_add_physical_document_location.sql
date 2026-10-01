-- Physical copies Admission staff receive face-to-face (or a "will submit
-- later" document later marked received) have nothing today pointing at
-- WHERE the hard copy actually got filed -- Admission could confirm a
-- document was received with no way to find it again later. storage_location
-- is a free-text spot (e.g. "Cabinet A, Box 3") staff fill in when they
-- physically file it; the applicant's own reference_id (already unique,
-- already printed on everything else) doubles as the tag to write on the
-- folder/box itself, so no second ID scheme is needed.
ALTER TABLE applicant_documents
    ADD COLUMN storage_location VARCHAR(150) NULL DEFAULT NULL AFTER status,
    ADD COLUMN storage_recorded_at TIMESTAMP NULL DEFAULT NULL AFTER storage_location;
