<?php
session_start();
require_once '../db.php';
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

$db   = new Database();
$conn = $db->connect();

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data)) {
    echo json_encode(['error' => 'Invalid request body.']);
    exit;
}

// `ids` = schedule_id rows being replaced when editing an existing block.
// Empty array means this is a brand new block.
$existing_ids = array_values(array_unique(array_filter(array_map('intval', $data['ids'] ?? []))));

$section_id   = (int)($data['section_id']   ?? 0);
$subject_id   = (int)($data['subject_id']   ?? 0);
$professor_id = (int)($data['professor_id'] ?? 0);
$room_id      = (int)($data['room_id']      ?? 0);
$time_start   = trim($data['time_start']    ?? '');
$time_end     = trim($data['time_end']      ?? '');
$school_year  = trim($data['school_year']   ?? '');
$semester     = (int)($data['semester']     ?? 0);

$valid_days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
$days = array_values(array_intersect($valid_days, $data['days'] ?? []));

if (!$section_id || !$subject_id || !$room_id || !$time_start || !$time_end
    || !$school_year || !$semester || empty($days)) {
    echo json_encode(['error' => 'Missing required fields.']);
    exit;
}

if ($time_start >= $time_end) {
    echo json_encode(['error' => 'End time must be after start time.']);
    exit;
}

if (!preg_match('/^\d{4}-\d{4}$/', $school_year)) {
    echo json_encode(['error' => 'Invalid school year format. Use YYYY-YYYY.']);
    exit;
}

$conn->begin_transaction();

try {
    // Conflict check: same room OR same section already booked on one of
    // these days with overlapping time, excluding the rows we're about to
    // replace (so editing a block doesn't collide with itself).
    $placeholders = implode(',', array_fill(0, count($days), '?'));
    $excludeClause = '';
    $excludeParams = [];
    $excludeTypes  = '';
    if ($existing_ids) {
        $excludePlaceholders = implode(',', array_fill(0, count($existing_ids), '?'));
        $excludeClause = " AND schedule_id NOT IN ($excludePlaceholders)";
        $excludeParams = $existing_ids;
        $excludeTypes  = str_repeat('i', count($existing_ids));
    }

    $conflictSql = "
        SELECT schedule_id, day, room_id, section_id
        FROM schedule
        WHERE school_year = ? AND semester = ?
          AND day IN ($placeholders)
          AND time_start < ? AND time_end > ?
          AND (room_id = ? OR section_id = ?)
          $excludeClause
    ";
    $conflictStmt = $conn->prepare($conflictSql);
    if (!$conflictStmt) {
        throw new RuntimeException('Database error: ' . $conn->error);
    }
    $types  = 'si' . str_repeat('s', count($days)) . 'ssii' . $excludeTypes;
    $params = array_merge(
        [$school_year, $semester],
        $days,
        [$time_end, $time_start, $room_id, $section_id],
        $excludeParams
    );
    $conflictStmt->bind_param($types, ...$params);
    $conflictStmt->execute();
    $conflicts = $conflictStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $conflictStmt->close();

    if (!empty($conflicts)) {
        throw new RuntimeException('This room or section is already booked on ' . $conflicts[0]['day'] . ' at an overlapping time.');
    }

    // Replace: delete the old rows for this block (edit case), then
    // insert one row per selected day.
    if ($existing_ids) {
        $delPlaceholders = implode(',', array_fill(0, count($existing_ids), '?'));
        $delStmt = $conn->prepare("DELETE FROM schedule WHERE schedule_id IN ($delPlaceholders)");
        if (!$delStmt) {
            throw new RuntimeException('Database error: ' . $conn->error);
        }
        $delStmt->bind_param(str_repeat('i', count($existing_ids)), ...$existing_ids);
        if (!$delStmt->execute()) {
            $delStmt->close();
            throw new RuntimeException('Database error: ' . $conn->error);
        }
        $delStmt->close();
    }

    $insStmt = $conn->prepare("
        INSERT INTO schedule
            (subject_id, professor_id, section_id, room_id, day, time_start, time_end, school_year, semester)
        VALUES (?,?,?,?,?,?,?,?,?)
    ");
    if (!$insStmt) {
        throw new RuntimeException('Database error: ' . $conn->error);
    }

    $new_ids = [];
    foreach ($days as $day) {
        // 9 params: subject_id, professor_id, section_id, room_id (i×4),
        // day, time_start, time_end, school_year (s×4), semester (i)
        $insStmt->bind_param(
            'iiiissssi',
            $subject_id, $professor_id, $section_id, $room_id, $day, $time_start, $time_end, $school_year, $semester
        );
        if (!$insStmt->execute()) {
            $insStmt->close();
            throw new RuntimeException('Database error: ' . $conn->error);
        }
        $new_ids[] = (int)$conn->insert_id;
    }
    $insStmt->close();

    $conn->commit();

    echo json_encode([
        'success' => true,
        'ids'     => $new_ids,
    ]);

} catch (RuntimeException $e) {
    $conn->rollback();
    echo json_encode(['error' => $e->getMessage()]);
} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    echo json_encode(['error' => 'A database error occurred. Please try again.']);
}

$db->close();