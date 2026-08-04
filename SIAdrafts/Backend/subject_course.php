<?php

// subject_course is the authoritative many-to-many mapping when a subject
// has any rows there at all — this correctly handles subjects shared
// across multiple courses (e.g. Gen-Ed subjects cross-listed to both
// BSPSYCH and BSCRIM). subject.course_id defaults to 1 (BSIT) for nearly
// every subject regardless of which courses actually offer it, so it's
// only trustworthy as a fallback for subjects with zero subject_course
// rows (course-specific subjects that were never cross-listed).
function subject_belongs_to_course(mysqli $conn, int $subject_id, int $course_id): bool
{
    $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM subject_course WHERE subject_id = ?");
    $stmt->bind_param('i', $subject_id);
    $stmt->execute();
    $total = (int)($stmt->get_result()->fetch_assoc()['cnt'] ?? 0);
    $stmt->close();

    if ($total > 0) {
        $stmt = $conn->prepare("SELECT 1 FROM subject_course WHERE subject_id = ? AND course_id = ? LIMIT 1");
        $stmt->bind_param('ii', $subject_id, $course_id);
        $stmt->execute();
        $match = (bool)$stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $match;
    }

    $stmt = $conn->prepare("SELECT course_id FROM subject WHERE subject_id = ? LIMIT 1");
    $stmt->bind_param('i', $subject_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row && (int)$row['course_id'] === $course_id;
}
