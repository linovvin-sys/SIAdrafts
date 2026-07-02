<?php
// ============================================================
// validation_rules.php
// Single source of truth for every field-level validation rule.
// Used by:
//   - validate_field.php   (per-field AJAX check on blur)
//   - admission_process.php (full-form check on submit)
//
// Each function returns null if valid, or an error message string
// if invalid. Returning null on an empty value is intentional —
// "required" is checked separately, so optional fields (like email)
// don't fail this layer just for being blank.
// ============================================================

function validate_name($value, $label) {
    if ($value === '') return null;
    if (!preg_match("/^[A-Za-zÀ-ÿ' \-\.]{2,100}$/u", $value)) {
        return "$label looks invalid — letters only, no numbers or symbols.";
    }
    return null;
}

function validate_ph_mobile($value, $label) {
    if ($value === '') return null;
    $digits = preg_replace('/[\s\-]/', '', $value);
    if (!preg_match('/^09\d{9}$/', $digits)) {
        return "$label must be a valid PH mobile number (e.g. 09123456789).";
    }
    return null;
}

function validate_email_field($value) {
    if ($value === '') return null;
    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
        return 'Email address looks invalid.';
    }
    return null;
}

function validate_birth_date($value) {
    if ($value === '') return null;
    $ts = strtotime($value);
    if ($ts === false) {
        return 'Date of birth is not a valid date.';
    }
    if ($ts > time()) {
        return 'Date of birth cannot be in the future.';
    }
    $age = floor((time() - $ts) / (365.25 * 24 * 60 * 60));
    if ($age < 15 || $age > 80) {
        return "Date of birth gives an unrealistic age ($age) — please double-check.";
    }
    return null;
}

function validate_guardian_id($value) {
    if ($value === '') return null;
    if (!preg_match('/^[A-Za-z0-9\- ]{5,30}$/', $value)) {
        return 'Guardian ID number looks invalid — check the number was copied correctly.';
    }
    return null;
}

function validate_address($value) {
    if ($value === '') return null;
    if (strlen($value) < 8 || !preg_match('/[A-Za-z]/', $value)) {
        return 'Home address looks too short or invalid — include street, barangay, and city.';
    }
    return null;
}

function validate_school_name($value) {
    if ($value === '') return null;
    if (!preg_match('/[A-Za-z]{2,}/', $value)) {
        return 'School name looks invalid.';
    }
    return null;
}

function validate_gpa($value) {
    if ($value === '') return null;
    if (strlen($value) > 10) {
        return 'GPA/grade value looks too long to be a real grade.';
    }
    return null;
}

// ---------- required-field labels, shared so both files agree on wording ----------
function required_field_labels() {
    return [
        'last_name' => 'Last name',
        'first_name' => 'First name',
        'birth_date' => 'Date of birth',
        'sex' => 'Sex',
        'contact_number' => "Student's contact number",
        'home_address' => 'Home address',
        'guardian_name' => 'Guardian full name',
        'guardian_relationship' => "Guardian's relationship to applicant",
        'guardian_contact' => "Guardian's contact number",
        'guardian_id_type' => 'Guardian ID type',
        'guardian_id_number' => 'Guardian ID number',
        'id_verified_by' => 'Staff ID verification initials',
        'year_level' => 'Year level',
        'start_term' => 'Intended start term',
    ];
}

// ---------- dispatch table: maps a form field name to its validator ----------
// Used by validate_field.php to look up "which function checks this field."
// Fields not listed here (selects, the readonly program field, etc.) are
// treated as always-valid at this layer — they're either constrained by
// the HTML itself (a <select>) or checked only for "required" elsewhere.
function get_field_validator($field_name) {
    $labels = required_field_labels();

    $map = [
        'last_name'           => fn($v) => validate_name($v, 'Last name'),
        'first_name'          => fn($v) => validate_name($v, 'First name'),
        'middle_name'         => fn($v) => validate_name($v, 'Middle name'),
        'guardian_name'       => fn($v) => validate_name($v, 'Guardian name'),
        'contact_number'      => fn($v) => validate_ph_mobile($v, "Student's contact number"),
        'guardian_contact'    => fn($v) => validate_ph_mobile($v, "Guardian's contact number"),
        'email'               => fn($v) => validate_email_field($v),
        'birth_date'          => fn($v) => validate_birth_date($v),
        'guardian_id_number'  => fn($v) => validate_guardian_id($v),
        'home_address'        => fn($v) => validate_address($v),
    ];

    return $map[$field_name] ?? null;
}