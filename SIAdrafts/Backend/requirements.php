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

/** Given a list of submitted requirement keys, return which groups are still missing. */
function missing_requirement_groups(array $submittedKeys): array
{
    $submittedGroups = [];
    foreach (REQUIREMENT_DEFINITIONS as $def) {
        if (in_array($def['key'], $submittedKeys, true)) {
            $submittedGroups[$def['group']] = true;
        }
    }
    return array_values(array_diff(requirement_groups(), array_keys($submittedGroups)));
}
