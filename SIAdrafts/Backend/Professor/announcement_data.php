<?php
/**
 * Query layer for class announcements, professor side. Every write is
 * ownership-checked against the professor's own session id -- a
 * schedule_id/announcement_id must actually belong to the requesting
 * professor before anything is inserted or deleted.
 */

/** All announcements a professor has posted for one specific class. */
function get_class_announcements(mysqli $conn, int $scheduleId, int $professorId): array
{
    $stmt = $conn->prepare(
        "SELECT announcement_id, title, body, created_at
         FROM announcement
         WHERE schedule_id = ? AND professor_id = ?
         ORDER BY created_at DESC"
    );
    $stmt->bind_param('ii', $scheduleId, $professorId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

/**
 * Returns null on success, or an error string if the schedule doesn't
 * belong to this professor or the input is invalid.
 */
function post_announcement(mysqli $conn, int $scheduleId, int $professorId, string $title, string $body): ?string
{
    $title = trim($title);
    $body  = trim($body);
    if ($title === '' || $body === '') {
        return 'Title and message are both required.';
    }
    if (mb_strlen($title) > 150) {
        return 'Title is too long.';
    }

    $stmt = $conn->prepare("SELECT 1 FROM schedule WHERE schedule_id = ? AND professor_id = ?");
    $stmt->bind_param('ii', $scheduleId, $professorId);
    $stmt->execute();
    $owns = (bool)$stmt->get_result()->fetch_row();
    $stmt->close();
    if (!$owns) {
        return 'That class does not belong to you.';
    }

    $stmt = $conn->prepare("INSERT INTO announcement (schedule_id, professor_id, title, body) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('iiss', $scheduleId, $professorId, $title, $body);
    $stmt->execute();
    $stmt->close();
    return null;
}

/** Returns null on success, or an error string if not found/not owned. */
function delete_announcement(mysqli $conn, int $announcementId, int $professorId): ?string
{
    $stmt = $conn->prepare("DELETE FROM announcement WHERE announcement_id = ? AND professor_id = ?");
    $stmt->bind_param('ii', $announcementId, $professorId);
    $stmt->execute();
    $deleted = $stmt->affected_rows > 0;
    $stmt->close();
    return $deleted ? null : 'Announcement not found.';
}
