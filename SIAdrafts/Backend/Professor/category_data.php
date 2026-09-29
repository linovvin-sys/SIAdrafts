<?php
/**
 * Assignment categories (e.g. "Quizzes" 20%, "Projects" 30%) and the
 * weighted-average computation built on top of them. Purely additive on
 * top of the existing manual per-period `grade` table -- this never writes
 * to `grade`, it's a second, auto-computed figure shown alongside it.
 */

function get_categories(mysqli $conn, int $scheduleId, int $professorId): array
{
    $stmt = $conn->prepare(
        "SELECT c.category_id, c.name, c.weight_percent
         FROM assignment_category c
         JOIN schedule s ON s.schedule_id = c.schedule_id
         WHERE c.schedule_id = ? AND s.professor_id = ?
         ORDER BY c.category_id"
    );
    $stmt->bind_param('ii', $scheduleId, $professorId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

/** Returns null on success, or an error string. */
function save_category(mysqli $conn, int $scheduleId, int $professorId, string $name, $weightPercent): ?string
{
    $name = trim($name);
    if ($name === '') {
        return 'Category name is required.';
    }
    if (!is_numeric($weightPercent) || (float)$weightPercent <= 0 || (float)$weightPercent > 100) {
        return 'Weight must be a number between 0 and 100.';
    }

    $ownerStmt = $conn->prepare("SELECT 1 FROM schedule WHERE schedule_id = ? AND professor_id = ?");
    $ownerStmt->bind_param('ii', $scheduleId, $professorId);
    $ownerStmt->execute();
    $owns = (bool)$ownerStmt->get_result()->fetch_row();
    $ownerStmt->close();
    if (!$owns) {
        return 'That class does not belong to you.';
    }

    try {
        $stmt = $conn->prepare("INSERT INTO assignment_category (schedule_id, name, weight_percent) VALUES (?, ?, ?)");
        $weight = (float)$weightPercent;
        $stmt->bind_param('isd', $scheduleId, $name, $weight);
        $stmt->execute();
        $stmt->close();
        return null;
    } catch (mysqli_sql_exception $e) {
        error_log('save_category: ' . $e->getMessage());
        return 'A database error occurred. Please try again.';
    }
}

/** Returns null on success, or an error string. Un-categorizes (not deletes) any assignment using it. */
function delete_category(mysqli $conn, int $categoryId, int $professorId): ?string
{
    $stmt = $conn->prepare(
        "SELECT c.category_id FROM assignment_category c
         JOIN schedule s ON s.schedule_id = c.schedule_id
         WHERE c.category_id = ? AND s.professor_id = ?"
    );
    $stmt->bind_param('ii', $categoryId, $professorId);
    $stmt->execute();
    $owns = (bool)$stmt->get_result()->fetch_row();
    $stmt->close();
    if (!$owns) {
        return 'Category not found.';
    }

    try {
        // assignment.category_id is ON DELETE SET NULL, so this alone
        // un-categorizes affected assignments rather than deleting them.
        $stmt = $conn->prepare("DELETE FROM assignment_category WHERE category_id = ?");
        $stmt->bind_param('i', $categoryId);
        $stmt->execute();
        $stmt->close();
        return null;
    } catch (mysqli_sql_exception $e) {
        error_log('delete_category: ' . $e->getMessage());
        return 'A database error occurred. Please try again.';
    }
}

/**
 * Weighted average across categorized, graded assignments for one
 * enrollment_subject_id. Normalizes over only the weight of categories
 * that actually have a graded submission yet, so a category with no
 * grades yet doesn't drag the average toward 0. Returns null if the class
 * has no categories defined at all (feature inactive for that class).
 */
function compute_weighted_grade(mysqli $conn, int $scheduleId, int $enrollmentSubjectId): ?array
{
    $categories = $conn->prepare(
        "SELECT category_id, name, weight_percent FROM assignment_category WHERE schedule_id = ?"
    );
    $categories->bind_param('i', $scheduleId);
    $categories->execute();
    $cats = $categories->get_result()->fetch_all(MYSQLI_ASSOC);
    $categories->close();

    if (empty($cats)) {
        return null;
    }

    $stmt = $conn->prepare(
        "SELECT a.category_id, AVG(sub.score / a.max_score * 100) AS avg_percent
         FROM assignment a
         JOIN assignment_submission sub ON sub.assignment_id = a.assignment_id
         WHERE a.schedule_id = ? AND sub.enrollment_subject_id = ?
           AND a.category_id IS NOT NULL AND a.max_score IS NOT NULL AND a.max_score > 0
           AND sub.score IS NOT NULL
         GROUP BY a.category_id"
    );
    $stmt->bind_param('ii', $scheduleId, $enrollmentSubjectId);
    $stmt->execute();
    $scored = [];
    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
        $scored[(int)$row['category_id']] = (float)$row['avg_percent'];
    }
    $stmt->close();

    $breakdown = [];
    $weightedSum = 0.0;
    $weightTotal = 0.0;
    foreach ($cats as $cat) {
        $cid = (int)$cat['category_id'];
        $weight = (float)$cat['weight_percent'];
        $avg = $scored[$cid] ?? null;
        $breakdown[] = ['name' => $cat['name'], 'weight_percent' => $weight, 'average_percent' => $avg];
        if ($avg !== null) {
            $weightedSum += $avg * $weight;
            $weightTotal += $weight;
        }
    }

    $overall = $weightTotal > 0 ? round($weightedSum / $weightTotal, 1) : null;
    return ['categories' => $breakdown, 'overall_percent' => $overall];
}

/** Weighted average for every currently-enrolled student in a class, for the professor's own roster view. */
function get_class_weighted_grades(mysqli $conn, int $scheduleId, int $professorId): array
{
    $ownerStmt = $conn->prepare("SELECT 1 FROM schedule WHERE schedule_id = ? AND professor_id = ?");
    $ownerStmt->bind_param('ii', $scheduleId, $professorId);
    $ownerStmt->execute();
    $owns = (bool)$ownerStmt->get_result()->fetch_row();
    $ownerStmt->close();
    if (!$owns) {
        return [];
    }

    $stmt = $conn->prepare(
        "SELECT es.enrollment_subject_id, st.student_no, st.first_name, st.last_name
         FROM schedule s
         JOIN enrollment_subject es ON es.subject_id = s.subject_id AND es.status = 'Enrolled'
         JOIN enrollment e ON e.enrollment_id = es.enrollment_id AND e.school_year = s.school_year AND e.semester = s.semester
         JOIN student st ON st.applicant_id = e.applicant_id
         WHERE s.schedule_id = ? AND s.professor_id = ?
           AND (e.section_id = s.section_id OR es.schedule_id = s.schedule_id)
           AND e.status = 'Enrolled'
         ORDER BY st.last_name, st.first_name"
    );
    $stmt->bind_param('ii', $scheduleId, $professorId);
    $stmt->execute();
    $roster = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($roster as &$r) {
        $weighted = compute_weighted_grade($conn, $scheduleId, (int)$r['enrollment_subject_id']);
        $r['overall_percent'] = $weighted['overall_percent'] ?? null;
    }
    unset($r);
    return $roster;
}

/** Resolves enrollment_subject_id for (scheduleId, applicantId), same join pattern used everywhere else. Null if not currently enrolled in that class. */
function resolve_enrollment_subject_id(mysqli $conn, int $scheduleId, int $applicantId): ?int
{
    $stmt = $conn->prepare(
        "SELECT es.enrollment_subject_id
         FROM schedule sc
         JOIN enrollment e ON e.school_year = sc.school_year AND e.semester = sc.semester AND e.applicant_id = ?
         JOIN enrollment_subject es ON es.enrollment_id = e.enrollment_id AND es.subject_id = sc.subject_id AND es.status = 'Enrolled'
         WHERE sc.schedule_id = ? AND e.status = 'Enrolled'
           AND (e.section_id = sc.section_id OR es.schedule_id = sc.schedule_id)
         LIMIT 1"
    );
    $stmt->bind_param('ii', $applicantId, $scheduleId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ? (int)$row['enrollment_subject_id'] : null;
}
