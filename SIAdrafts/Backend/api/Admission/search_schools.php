<?php
/**
 * School name autocomplete for the public online admission form. Public
 * (no auth) -- this endpoint only ever reads a bundled copy of DepEd's
 * own public school directory, nothing about an applicant.
 *
 * Backed by Backend/data/ph_schools.sqlite (60,924 DepEd basic-ed
 * schools, built from the public masterlist -- see the dataset's own
 * source for provenance) instead of shipping that whole dataset to the
 * browser: a flat JSON fetch of it would be several MB on every visit,
 * on a form people often fill out on mobile data. A few-millisecond
 * indexed SQLite query per keystroke (debounced client-side) is both
 * lighter and faster than that would ever be.
 */
header('Content-Type: application/json');

$q = trim($_GET['q'] ?? '');
if (mb_strlen($q) < 2) {
    echo json_encode(['results' => []]);
    exit;
}

$dbPath = __DIR__ . '/../../data/ph_schools.sqlite';
if (!is_file($dbPath)) {
    http_response_code(500);
    echo json_encode(['error' => 'School directory is unavailable.', 'results' => []]);
    exit;
}

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $needle = mb_strtolower($q);
    // Ranked so an exact or prefix match always surfaces before a
    // same-substring-anywhere match -- "do a perfect match for it" --
    // e.g. searching "San Jose Elementary School" puts that literal
    // school above every other school that merely contains those words.
    $stmt = $pdo->prepare("
        SELECT name, municipality, province, region,
            CASE
                WHEN name_lower = :exact THEN 0
                WHEN name_lower LIKE :prefix THEN 1
                ELSE 2
            END AS rank
        FROM schools
        WHERE name_lower LIKE :contains
        ORDER BY rank, name
        LIMIT 8
    ");
    $stmt->bindValue(':exact', $needle);
    $stmt->bindValue(':prefix', $needle . '%');
    $stmt->bindValue(':contains', '%' . $needle . '%');
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('search_schools.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Search failed.', 'results' => []]);
    exit;
}

echo json_encode(['results' => $results]);
