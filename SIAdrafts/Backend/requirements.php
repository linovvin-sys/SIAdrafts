<?php
/**
 * Requirement definitions for the online application's optional
 * requirements section and the walk-in confirm_admission.php checklist.
 * Entries sharing the same 'group' are alternatives — satisfying either
 * one satisfies the group's requirement (e.g. PSA or NSO birth certificate).
 */
// 'critical' groups (PSA/NSO birth certificate, Good Moral) can never be
// deferred at walk-in confirmation — the applicant must have them in hand.
// Everything else can be marked "will submit later" instead of blocking
// confirmation outright (see confirm_admission.php).
const REQUIREMENT_DEFINITIONS = [
    ['key' => 'good_moral',  'label' => 'Certificate of Good Moral',        'group' => 'good_moral',  'critical' => true],
    ['key' => 'birth_psa',   'label' => 'Birth Certificate (PSA)',          'group' => 'birth_cert',  'critical' => true],
    ['key' => 'birth_nso',   'label' => 'Birth Certificate (NSO)',          'group' => 'birth_cert',  'critical' => true],
    ['key' => 'photo_2x2',   'label' => '2x2 ID Photos',                    'group' => 'photo_2x2',  'critical' => false],
    ['key' => 'form_138',    'label' => 'Form 138',                         'group' => 'form_138',   'critical' => false],
    // The guardian's typed ID number was never actually cross-checked
    // against anything physical -- a photo of the real card makes that
    // verification real instead of a format guess. Never critical: making
    // it mandatory would block the exact absent-guardian case it exists
    // to help with.
    ['key' => 'guardian_id_copy',      'label' => "Guardian's Valid ID (copy)",                         'group' => 'guardian_id_copy',      'critical' => false, 'track_as_outstanding' => false],
    // Standard practice when the guardian can't be present for enrollment
    // (OFW, different province, work) -- a signed note standing in for
    // their in-person presence, which this flow never actually required
    // to begin with (see confirm_admission.php: only the applicant's own
    // documents get physically checked).
    ['key' => 'authorization_letter',  'label' => 'Authorization Letter from Guardian',                 'group' => 'authorization_letter',  'critical' => false, 'track_as_outstanding' => false],
    // Only relevant when someone other than the guardian (aunt, uncle,
    // sibling) is the one actually enrolling the student -- see
    // representative_name/representative_relationship on applicants.
    // Left optional/always-visible rather than conditionally shown: no
    // way to hide a requirement-checklist item specifically on this page
    // without a parallel "critical except when representative is unused"
    // rule the walk-in checklist would also need to know about.
    ['key' => 'representative_id',     'label' => "Authorized Representative's Valid ID (if applicable)", 'group' => 'representative_id',     'critical' => false, 'track_as_outstanding' => false],
];

/** Distinct requirement groups — one entry per group is enough to satisfy it. */
function requirement_groups(): array
{
    $groups = [];
    foreach (REQUIREMENT_DEFINITIONS as $def) {
        $groups[$def['group']] = true;
    }
    return array_keys($groups);
}

/** Groups that can never be deferred — must be physically submitted to confirm. */
function critical_requirement_groups(): array
{
    $groups = [];
    foreach (REQUIREMENT_DEFINITIONS as $def) {
        if (!empty($def['critical'])) {
            $groups[$def['group']] = true;
        }
    }
    return array_keys($groups);
}

/**
 * Groups worth nagging a student about as "still outstanding" on their
 * dashboard/accountabilities page -- i.e. everything EXCEPT the
 * situational ones (guardian_id_copy, authorization_letter,
 * representative_id) that most students will legitimately never submit
 * at all (no representative was ever involved, etc.). Defaults to true
 * for any entry that doesn't set the flag, so a future addition has to
 * opt OUT of being tracked, not opt in -- the safer default for "did
 * this get silently missed."
 */
function trackable_requirement_groups(): array
{
    $groups = [];
    foreach (REQUIREMENT_DEFINITIONS as $def) {
        if ($def['track_as_outstanding'] ?? true) {
            $groups[$def['group']] = true;
        }
    }
    return array_keys($groups);
}

/** Given a list of submitted requirement keys, return which trackable groups are still missing. */
function missing_requirement_groups(array $submittedKeys): array
{
    $submittedGroups = [];
    foreach (REQUIREMENT_DEFINITIONS as $def) {
        if (in_array($def['key'], $submittedKeys, true)) {
            $submittedGroups[$def['group']] = true;
        }
    }
    return array_values(array_diff(trackable_requirement_groups(), array_keys($submittedGroups)));
}
