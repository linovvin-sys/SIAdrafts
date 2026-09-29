<?php
/**
 * Query layer for randomized class grouping. A schedule's groups are
 * regenerated wholesale each time (delete + recreate) rather than
 * incrementally adjusted -- simpler and matches the actual use case
 * ("shuffle the class into new groups for today's activity") better than
 * a stateful diff.
 */

/** All currently-enrolled applicant_ids + names for one class, same enrollment join used everywhere else (announcements, messaging). */
function get_class_roster_for_grouping(mysqli $conn, int $scheduleId, int $professorId): array
{
    $stmt = $conn->prepare(
        "SELECT DISTINCT e.applicant_id, a.first_name, a.last_name
         FROM schedule sch
         JOIN subject sub ON sub.subject_id = sch.subject_id
         JOIN enrollment e ON e.school_year = sch.school_year AND e.semester = sch.semester
         JOIN enrollment_subject es ON es.enrollment_id = e.enrollment_id AND es.status = 'Enrolled'
              AND es.subject_id = sch.subject_id
              AND (e.section_id = sch.section_id OR es.schedule_id = sch.schedule_id)
         JOIN applicants a ON a.applicant_id = e.applicant_id
         WHERE sch.schedule_id = ? AND sch.professor_id = ?
         ORDER BY a.last_name, a.first_name"
    );
    $stmt->bind_param('ii', $scheduleId, $professorId);
    $stmt->execute();
    $roster = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $roster;
}

/**
 * Deletes any existing groups for this schedule and creates a fresh
 * random set. $groupCount must be >= 1 and <= the roster size. Returns
 * null on success, or an error string.
 */
function generate_random_groups(mysqli $conn, int $scheduleId, int $professorId, int $groupCount): ?string
{
    $ownerStmt = $conn->prepare("SELECT 1 FROM schedule WHERE schedule_id = ? AND professor_id = ?");
    $ownerStmt->bind_param('ii', $scheduleId, $professorId);
    $ownerStmt->execute();
    $owns = (bool)$ownerStmt->get_result()->fetch_row();
    $ownerStmt->close();
    if (!$owns) {
        return 'That class does not belong to you.';
    }

    $roster = get_class_roster_for_grouping($conn, $scheduleId, $professorId);
    if (empty($roster)) {
        return 'No enrolled students to group yet.';
    }
    if ($groupCount < 1) {
        return 'Number of groups must be at least 1.';
    }
    if ($groupCount > count($roster)) {
        return 'Cannot make more groups than there are students (' . count($roster) . ').';
    }

    shuffle($roster);

    try {
        $conn->begin_transaction();

        $delMembers = $conn->prepare(
            "DELETE gm FROM class_group_member gm
             JOIN class_group g ON g.group_id = gm.group_id
             WHERE g.schedule_id = ?"
        );
        $delMembers->bind_param('i', $scheduleId);
        $delMembers->execute();
        $delMembers->close();

        $delGroups = $conn->prepare("DELETE FROM class_group WHERE schedule_id = ?");
        $delGroups->bind_param('i', $scheduleId);
        $delGroups->execute();
        $delGroups->close();

        $insGroup = $conn->prepare("INSERT INTO class_group (schedule_id, group_name) VALUES (?, ?)");
        $insMember = $conn->prepare("INSERT INTO class_group_member (group_id, applicant_id) VALUES (?, ?)");

        $groupIds = [];
        for ($i = 1; $i <= $groupCount; $i++) {
            $name = 'Group ' . $i;
            $insGroup->bind_param('is', $scheduleId, $name);
            $insGroup->execute();
            $groupIds[] = $insGroup->insert_id;
        }
        $insGroup->close();

        // Round-robin distribution keeps group sizes within 1 of each other
        // regardless of how evenly $groupCount divides the roster.
        foreach ($roster as $i => $student) {
            $groupId = $groupIds[$i % $groupCount];
            $applicantId = (int)$student['applicant_id'];
            $insMember->bind_param('ii', $groupId, $applicantId);
            $insMember->execute();
        }
        $insMember->close();

        $conn->commit();
        return null;
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        error_log('generate_random_groups: ' . $e->getMessage());
        return 'A database error occurred. Please try again.';
    }
}

/** Current groups + members for one class, for the professor's own view. */
function get_class_groups(mysqli $conn, int $scheduleId, int $professorId): array
{
    $ownerStmt = $conn->prepare("SELECT 1 FROM schedule WHERE schedule_id = ? AND professor_id = ?");
    $ownerStmt->bind_param('ii', $scheduleId, $professorId);
    $ownerStmt->execute();
    $owns = (bool)$ownerStmt->get_result()->fetch_row();
    $ownerStmt->close();
    if (!$owns) {
        return [];
    }

    return fetch_groups_with_members($conn, $scheduleId);
}

function fetch_groups_with_members(mysqli $conn, int $scheduleId): array
{
    $stmt = $conn->prepare(
        "SELECT g.group_id, g.group_name, a.applicant_id, a.first_name, a.last_name
         FROM class_group g
         LEFT JOIN class_group_member gm ON gm.group_id = g.group_id
         LEFT JOIN applicants a ON a.applicant_id = gm.applicant_id
         WHERE g.schedule_id = ?
         ORDER BY g.group_id, a.last_name, a.first_name"
    );
    $stmt->bind_param('i', $scheduleId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $groups = [];
    foreach ($rows as $row) {
        $gid = $row['group_id'];
        if (!isset($groups[$gid])) {
            $groups[$gid] = ['group_id' => $gid, 'group_name' => $row['group_name'], 'members' => []];
        }
        if ($row['applicant_id'] !== null) {
            $groups[$gid]['members'][] = [
                'applicant_id' => (int)$row['applicant_id'],
                'name' => trim($row['first_name'] . ' ' . $row['last_name']),
            ];
        }
    }
    return array_values($groups);
}
