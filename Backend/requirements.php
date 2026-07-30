<?php
/**
 * Requirement definitions for the online application's optional
 * requirements section and the walk-in confirm_admission.php checklist.
 * Entries sharing the same 'group' are alternatives — satisfying either
 * one satisfies the group's requirement (e.g. PSA or NSO birth certificate).
 */
const REQUIREMENT_DEFINITIONS = [
    ['key' => 'form_137',    'label' => 'Form 137 / SHS Card',              'group' => 'form_137'],
    ['key' => 'good_moral',  'label' => 'Certificate of Good Moral',        'group' => 'good_moral'],
    ['key' => 'birth_psa',   'label' => 'Birth Certificate (PSA)',          'group' => 'birth_cert'],
    ['key' => 'birth_nso',   'label' => 'Birth Certificate (NSO)',          'group' => 'birth_cert'],
    ['key' => 'photo_2x2',   'label' => '2x2 ID Photos',                    'group' => 'photo_2x2'],
    ['key' => 'form_138',    'label' => 'Form 138',                         'group' => 'form_138'],
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
