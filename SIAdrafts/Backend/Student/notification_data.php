<?php
/**
 * Unified feed for the notification bell. Originally this only surfaced
 * announcements (the bell's endpoint reused get_student_announcements()
 * directly) -- Quizzes, Assignments, and Materials never pushed a
 * notification even though they're the same kind of "your professor
 * posted something new" event. This merges all four into one feed instead
 * of adding three more separate polling endpoints.
 *
 * Each source keeps its own primary key (quiz_id/assignment_id/etc, not a
 * shared id space), so every row is normalized to a common shape with a
 * `type` discriminator + that type's own `item_id` -- the client tracks
 * "seen" per type rather than as one global max id, since an assignment_id
 * and a quiz_id are not comparable to each other.
 */

function get_student_notification_feed(mysqli $conn, int $applicantId, int $limit = 20): array
{
    $items = [];

    $stmt = $conn->prepare(
        "SELECT DISTINCT a.announcement_id AS item_id, a.title, a.body, a.created_at, sub.subject_code
         FROM enrollment e
         JOIN enrollment_subject es ON es.enrollment_id = e.enrollment_id AND es.status = 'Enrolled'
         JOIN schedule sch ON sch.subject_id = es.subject_id
              AND sch.school_year = e.school_year AND sch.semester = e.semester
              AND (e.section_id = sch.section_id OR es.schedule_id = sch.schedule_id)
         JOIN announcement a ON a.schedule_id = sch.schedule_id
         JOIN subject sub    ON sub.subject_id = sch.subject_id
         WHERE e.applicant_id = ? AND e.status = 'Enrolled'
         ORDER BY a.created_at DESC LIMIT ?"
    );
    $stmt->bind_param('ii', $applicantId, $limit);
    $stmt->execute();
    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $r) {
        $items[] = notification_row('announcement', $r, $r['title'], $r['body']);
    }
    $stmt->close();

    $stmt = $conn->prepare(
        "SELECT DISTINCT q.quiz_id AS item_id, q.title, q.instructions AS body, q.created_at, sub.subject_code
         FROM enrollment e
         JOIN enrollment_subject es ON es.enrollment_id = e.enrollment_id AND es.status = 'Enrolled'
         JOIN subject sub ON sub.subject_id = es.subject_id
         JOIN schedule sc ON sc.subject_id = es.subject_id
              AND sc.school_year = e.school_year AND sc.semester = e.semester
              AND (e.section_id = sc.section_id OR es.schedule_id = sc.schedule_id)
         JOIN quiz q ON q.schedule_id = sc.schedule_id
              AND EXISTS (SELECT 1 FROM quiz_question qq WHERE qq.quiz_id = q.quiz_id)
         WHERE e.applicant_id = ? AND e.status = 'Enrolled'
         ORDER BY q.created_at DESC LIMIT ?"
    );
    $stmt->bind_param('ii', $applicantId, $limit);
    $stmt->execute();
    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $r) {
        $items[] = notification_row('quiz', $r, $r['title'], $r['body'] ?: 'A new quiz was posted.');
    }
    $stmt->close();

    $stmt = $conn->prepare(
        "SELECT DISTINCT a.assignment_id AS item_id, a.title, a.instructions AS body, a.created_at, sub.subject_code
         FROM enrollment e
         JOIN enrollment_subject es ON es.enrollment_id = e.enrollment_id AND es.status = 'Enrolled'
         JOIN subject sub ON sub.subject_id = es.subject_id
         JOIN schedule sc ON sc.subject_id = es.subject_id
              AND sc.school_year = e.school_year AND sc.semester = e.semester
              AND (e.section_id = sc.section_id OR es.schedule_id = sc.schedule_id)
         JOIN assignment a ON a.schedule_id = sc.schedule_id
         WHERE e.applicant_id = ? AND e.status = 'Enrolled'
         ORDER BY a.created_at DESC LIMIT ?"
    );
    $stmt->bind_param('ii', $applicantId, $limit);
    $stmt->execute();
    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $r) {
        $items[] = notification_row('assignment', $r, $r['title'], $r['body']);
    }
    $stmt->close();

    // Same visible_from <= CURDATE() gate get_my_materials() uses -- a
    // material scheduled for the future shouldn't notify before students
    // can actually see it on the Materials page.
    $stmt = $conn->prepare(
        "SELECT DISTINCT m.material_id AS item_id, m.title, m.type, m.body, m.created_at, sub.subject_code
         FROM enrollment e
         JOIN enrollment_subject es ON es.enrollment_id = e.enrollment_id AND es.status = 'Enrolled'
         JOIN subject sub ON sub.subject_id = es.subject_id
         JOIN schedule sc ON sc.subject_id = es.subject_id
              AND sc.school_year = e.school_year AND sc.semester = e.semester
              AND (e.section_id = sc.section_id OR es.schedule_id = sc.schedule_id)
         JOIN class_material m ON m.schedule_id = sc.schedule_id
              AND (m.visible_from IS NULL OR m.visible_from <= CURDATE())
         WHERE e.applicant_id = ? AND e.status = 'Enrolled'
         ORDER BY m.created_at DESC LIMIT ?"
    );
    $stmt->bind_param('ii', $applicantId, $limit);
    $stmt->execute();
    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $r) {
        $blurb = $r['body'] ?: ($r['type'] === 'link' ? 'A new link was posted.' : 'A new file was posted.');
        $items[] = notification_row('material', $r, $r['title'], $blurb);
    }
    $stmt->close();

    usort($items, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));
    return array_slice($items, 0, $limit);
}

const NOTIFICATION_LINKS = [
    'announcement' => '/SIAdrafts/Frontend/View/Student/dashboard.php',
    'quiz'          => '/SIAdrafts/Frontend/View/Student/quizzes.php',
    'assignment'    => '/SIAdrafts/Frontend/View/Student/assignments.php',
    'material'      => '/SIAdrafts/Frontend/View/Student/materials.php',
];

function notification_row(string $type, array $row, string $title, ?string $body): array
{
    return [
        'type'         => $type,
        'item_id'      => (int)$row['item_id'],
        'title'        => $title,
        'body'         => $body ?? '',
        'subject_code' => $row['subject_code'],
        'created_at'   => $row['created_at'],
        'link'         => NOTIFICATION_LINKS[$type],
    ];
}
