<?php
// Shared parse + validate logic for the bulk curriculum CSV importer, used
// by BOTH preview_curriculum_import.php (read-only) and
// import_curriculum.php (which re-runs this from scratch on confirm rather
// than trusting whatever the client echoes back from the preview response).

const CURRICULUM_CSV_HEADERS = [
    'course_code', 'subject_code', 'subject_name', 'units',
    'category_name', 'year_level', 'semester', 'prerequisite_code',
];

/**
 * Parses and validates an uploaded curriculum CSV.
 *
 * Returns:
 *   [
 *     'rows' => [
 *       [
 *         'line' => int,
 *         'action' => 'insert' | 'cross_list' | 'no_action' | 'error',
 *         'message' => string,
 *         'course_code' => string, 'course_id' => int|null,
 *         'subject_code' => string, 'subject_name' => string,
 *         'units' => float, 'category_id' => int|null, 'category_name' => string,
 *         'year_level' => int, 'semester' => int,
 *         'prerequisite_code' => string,
 *       ],
 *       ...
 *     ],
 *     'has_errors' => bool,
 *   ]
 */
function parse_and_validate_curriculum_csv(mysqli $conn, string $filePath): array
{
    $rows = [];
    $hasErrors = false;

    $handle = fopen($filePath, 'r');
    if (!$handle) {
        return ['rows' => [], 'has_errors' => true, 'file_error' => 'Could not read the uploaded file.'];
    }

    $header = fgetcsv($handle);
    if (!$header) {
        fclose($handle);
        return ['rows' => [], 'has_errors' => true, 'file_error' => 'The file is empty.'];
    }
    $header = array_map(fn($h) => strtolower(trim($h)), $header);

    $missingCols = array_diff(CURRICULUM_CSV_HEADERS, $header);
    if (!empty($missingCols)) {
        fclose($handle);
        return [
            'rows' => [],
            'has_errors' => true,
            'file_error' => 'Missing required column(s): ' . implode(', ', $missingCols),
        ];
    }

    // Reference data, loaded once.
    $courseMap = [];
    $cRes = $conn->query("SELECT course_id, course_code FROM course");
    while ($c = $cRes->fetch_assoc()) $courseMap[strtoupper(trim($c['course_code']))] = (int)$c['course_id'];

    $categoryMap = [];
    $catRes = $conn->query("SELECT category_id, category_name FROM subject_category");
    while ($cat = $catRes->fetch_assoc()) $categoryMap[strtoupper(trim($cat['category_name']))] = (int)$cat['category_id'];

    $existingSubjects = []; // subject_code (upper) => row
    $subRes = $conn->query("SELECT subject_id, subject_code, subject_name, units, course_id, category_id, year_level, semester FROM subject");
    while ($s = $subRes->fetch_assoc()) $existingSubjects[strtoupper(trim($s['subject_code']))] = $s;

    $seenInFile = []; // subject_code (upper) => line, to catch duplicates within the CSV itself

    $line = 1; // header is line 1
    while (($data = fgetcsv($handle)) !== false) {
        $line++;
        if (count(array_filter($data, fn($v) => trim((string)$v) !== '')) === 0) continue; // skip blank lines

        $row = array_combine($header, array_pad($data, count($header), ''));
        $courseCode   = strtoupper(trim($row['course_code'] ?? ''));
        $subjectCode  = trim($row['subject_code'] ?? '');
        $subjectName  = trim($row['subject_name'] ?? '');
        $unitsRaw     = trim($row['units'] ?? '');
        $categoryName = strtoupper(trim($row['category_name'] ?? ''));
        $yearRaw      = trim($row['year_level'] ?? '');
        $semRaw       = trim($row['semester'] ?? '');
        $prereqCode   = trim($row['prerequisite_code'] ?? '');

        $entry = [
            'line' => $line,
            'action' => 'error',
            'message' => '',
            'course_code' => $courseCode,
            'course_id' => $courseMap[$courseCode] ?? null,
            'subject_code' => $subjectCode,
            'subject_name' => $subjectName,
            'units' => is_numeric($unitsRaw) ? (float)$unitsRaw : null,
            'category_name' => $categoryName,
            'category_id' => $categoryMap[$categoryName] ?? null,
            'year_level' => ctype_digit($yearRaw) ? (int)$yearRaw : null,
            'semester' => ctype_digit($semRaw) ? (int)$semRaw : null,
            'prerequisite_code' => $prereqCode,
        ];

        $errors = [];
        if ($subjectCode === '') $errors[] = 'subject_code is required';
        if ($subjectName === '') $errors[] = 'subject_name is required';
        if ($entry['course_id'] === null) $errors[] = "course_code \"$courseCode\" not found";
        if ($entry['category_id'] === null) $errors[] = "category_name \"$categoryName\" not found";
        if ($entry['units'] === null || $entry['units'] <= 0) $errors[] = 'units must be a positive number';
        if ($entry['year_level'] === null || $entry['year_level'] < 1 || $entry['year_level'] > 4) $errors[] = 'year_level must be 1-4';
        if ($entry['semester'] === null || $entry['semester'] < 1 || $entry['semester'] > 2) $errors[] = 'semester must be 1 or 2';

        $codeKey = strtoupper($subjectCode);
        if ($subjectCode !== '') {
            if (isset($seenInFile[$codeKey])) {
                $errors[] = "subject_code \"$subjectCode\" is duplicated in this file (also on line {$seenInFile[$codeKey]})";
            }
            $seenInFile[$codeKey] = $line;
        }

        if (empty($errors) && isset($existingSubjects[$codeKey])) {
            $existing = $existingSubjects[$codeKey];
            $matches = (float)$existing['units'] === $entry['units']
                && (int)$existing['category_id'] === $entry['category_id']
                && (int)$existing['year_level'] === $entry['year_level']
                && (int)$existing['semester'] === $entry['semester'];

            if (!$matches) {
                $errors[] = "subject_code \"$subjectCode\" already exists with different units/category/year/semester — fix the conflict or use a different code";
            } else {
                $alreadyLinked = subject_belongs_to_course($conn, (int)$existing['subject_id'], $entry['course_id']);
                if ($alreadyLinked) {
                    $entry['action'] = 'no_action';
                    $entry['message'] = "Already available to {$courseCode} — nothing to do.";
                } else {
                    $entry['action'] = 'cross_list';
                    $entry['message'] = "Already exists — will cross-list to {$courseCode} instead of creating a duplicate.";
                    $entry['existing_subject_id'] = (int)$existing['subject_id'];
                }
            }
        } elseif (empty($errors)) {
            $entry['action'] = 'insert';
            $entry['message'] = 'Will be added as a new subject.';
        }

        if (!empty($errors)) {
            $entry['action'] = 'error';
            $entry['message'] = implode('; ', $errors);
            $hasErrors = true;
        }

        $rows[] = $entry;
    }
    fclose($handle);

    // Second pass: resolve prerequisite_code against either an existing DB
    // subject or another row in this same file (regardless of file order).
    $newCodesByLine = [];
    foreach ($rows as $r) {
        if (in_array($r['action'], ['insert', 'cross_list', 'no_action'], true)) {
            $newCodesByLine[strtoupper($r['subject_code'])] = true;
        }
    }
    foreach ($rows as &$r) {
        if ($r['action'] === 'error' || $r['prerequisite_code'] === '') continue;
        $prereqKey = strtoupper($r['prerequisite_code']);
        $existsInDb = isset($existingSubjects[$prereqKey]);
        $existsInFile = isset($newCodesByLine[$prereqKey]);
        if (!$existsInDb && !$existsInFile) {
            $r['action'] = 'error';
            $r['message'] = "prerequisite_code \"{$r['prerequisite_code']}\" not found in database or this file";
            $hasErrors = true;
        }
    }
    unset($r);

    return ['rows' => $rows, 'has_errors' => $hasErrors];
}
