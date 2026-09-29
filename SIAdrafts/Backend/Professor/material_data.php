<?php
/**
 * Query layer for the professor-side course materials feature. Every
 * write is ownership-checked against the professor's own session id, same
 * pattern as announcement_data.php -- a schedule_id/material_id must
 * actually belong to the requesting professor before anything is written.
 */

require_once __DIR__ . '/../api/Materials/material_attachments.php';

const MATERIAL_TYPES = ['file', 'link', 'text'];

/** All materials posted for one class, most recent first. Professors see everything, including future-dated drip content. */
function get_class_materials(mysqli $conn, int $scheduleId, int $professorId): array
{
    $stmt = $conn->prepare(
        "SELECT material_id, title, type, file_name, url, body, visible_from, created_at
         FROM class_material
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
 * Posts one material item. $file is the raw $_FILES['material_file']
 * entry, only consulted when $type === 'file'. Returns null on success,
 * or an error string if the schedule doesn't belong to this professor or
 * the input is invalid.
 */
function post_material(mysqli $conn, int $scheduleId, int $professorId, string $title, string $type, ?string $url, ?string $body, ?string $visibleFrom, ?array $file): ?string
{
    $title = trim($title);
    if ($title === '') {
        return 'Title is required.';
    }
    if (mb_strlen($title) > 150) {
        return 'Title is too long.';
    }
    if (!in_array($type, MATERIAL_TYPES, true)) {
        return 'Invalid material type.';
    }

    $visibleFromParam = null;
    if ($visibleFrom !== null && trim($visibleFrom) !== '') {
        $d = DateTime::createFromFormat('Y-m-d', $visibleFrom);
        if (!$d || $d->format('Y-m-d') !== $visibleFrom) {
            return 'Invalid visible-from date.';
        }
        $visibleFromParam = $visibleFrom;
    }

    $filePathParam = null;
    $fileNameParam = null;
    $fileTypeParam = null;
    $urlParam       = null;
    $bodyParam      = null;

    if ($type === 'file') {
        $stored = store_material_file($file ?? []);
        if (isset($stored['error'])) {
            return $stored['error'];
        }
        $filePathParam = $stored['path'];
        $fileNameParam = $stored['name'];
        $fileTypeParam = $stored['type'];
    } elseif ($type === 'link') {
        $url = trim((string)$url);
        if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $url)) {
            return 'A valid http(s) link is required.';
        }
        if (mb_strlen($url) > 500) {
            return 'Link is too long.';
        }
        $urlParam = $url;
    } else { // text
        $body = trim((string)$body);
        if ($body === '') {
            return 'Text content is required.';
        }
        $bodyParam = $body;
    }

    $stmt = $conn->prepare("SELECT 1 FROM schedule WHERE schedule_id = ? AND professor_id = ?");
    $stmt->bind_param('ii', $scheduleId, $professorId);
    $stmt->execute();
    $owns = (bool)$stmt->get_result()->fetch_row();
    $stmt->close();
    if (!$owns) {
        if ($filePathParam !== null) {
            delete_material_file($filePathParam);
        }
        return 'That class does not belong to you.';
    }

    try {
        $stmt = $conn->prepare(
            "INSERT INTO class_material (schedule_id, professor_id, title, type, file_path, file_name, file_type, url, body, visible_from)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            'iissssssss',
            $scheduleId, $professorId, $title, $type,
            $filePathParam, $fileNameParam, $fileTypeParam, $urlParam, $bodyParam, $visibleFromParam
        );
        $stmt->execute();
        $stmt->close();
        return null;
    } catch (mysqli_sql_exception $e) {
        // The file was already written to disk above -- if the row never
        // lands, nothing will ever reference it, so clean it up now rather
        // than leaking it.
        if ($filePathParam !== null) {
            delete_material_file($filePathParam);
        }
        error_log('post_material: ' . $e->getMessage());
        return 'A database error occurred. Please try again.';
    }
}

/**
 * Edits a material's title/content/visibility. $type cannot be changed --
 * only the content the original type calls for is read from the args ($file
 * is only consulted, and optional, when the material is a 'file' one; a
 * missing/empty upload there just keeps the existing stored file). Returns
 * null on success, or an error string if not found/owned or input invalid.
 */
function update_material(mysqli $conn, int $materialId, int $professorId, string $title, ?string $url, ?string $body, ?string $visibleFrom, ?array $file): ?string
{
    $title = trim($title);
    if ($title === '') {
        return 'Title is required.';
    }
    if (mb_strlen($title) > 150) {
        return 'Title is too long.';
    }

    $stmt = $conn->prepare("SELECT type, file_path FROM class_material WHERE material_id = ? AND professor_id = ?");
    $stmt->bind_param('ii', $materialId, $professorId);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$existing) {
        return 'Material not found.';
    }
    $type = $existing['type'];

    $visibleFromParam = null;
    if ($visibleFrom !== null && trim($visibleFrom) !== '') {
        $d = DateTime::createFromFormat('Y-m-d', $visibleFrom);
        if (!$d || $d->format('Y-m-d') !== $visibleFrom) {
            return 'Invalid visible-from date.';
        }
        $visibleFromParam = $visibleFrom;
    }

    $newFilePath = null;
    $newFileName = null;
    $newFileType = null;
    $oldFilePathToDelete = null;
    $urlParam  = null;
    $bodyParam = null;

    if ($type === 'file') {
        $hasNewUpload = $file !== null && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
        if ($hasNewUpload) {
            $stored = store_material_file($file);
            if (isset($stored['error'])) {
                return $stored['error'];
            }
            $newFilePath = $stored['path'];
            $newFileName = $stored['name'];
            $newFileType = $stored['type'];
            $oldFilePathToDelete = $existing['file_path'];
        }
    } elseif ($type === 'link') {
        $url = trim((string)$url);
        if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $url)) {
            return 'A valid http(s) link is required.';
        }
        if (mb_strlen($url) > 500) {
            return 'Link is too long.';
        }
        $urlParam = $url;
    } else { // text
        $body = trim((string)$body);
        if ($body === '') {
            return 'Text content is required.';
        }
        $bodyParam = $body;
    }

    if ($type === 'file' && $newFilePath !== null) {
        $stmt = $conn->prepare(
            "UPDATE class_material SET title = ?, file_path = ?, file_name = ?, file_type = ?, visible_from = ?
             WHERE material_id = ? AND professor_id = ?"
        );
        $stmt->bind_param('sssssii', $title, $newFilePath, $newFileName, $newFileType, $visibleFromParam, $materialId, $professorId);
    } elseif ($type === 'file') {
        $stmt = $conn->prepare(
            "UPDATE class_material SET title = ?, visible_from = ? WHERE material_id = ? AND professor_id = ?"
        );
        $stmt->bind_param('ssii', $title, $visibleFromParam, $materialId, $professorId);
    } elseif ($type === 'link') {
        $stmt = $conn->prepare(
            "UPDATE class_material SET title = ?, url = ?, visible_from = ?
             WHERE material_id = ? AND professor_id = ?"
        );
        $stmt->bind_param('sssii', $title, $urlParam, $visibleFromParam, $materialId, $professorId);
    } else { // text
        $stmt = $conn->prepare(
            "UPDATE class_material SET title = ?, body = ?, visible_from = ?
             WHERE material_id = ? AND professor_id = ?"
        );
        $stmt->bind_param('sssii', $title, $bodyParam, $visibleFromParam, $materialId, $professorId);
    }
    try {
        $stmt->execute();
        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        // The new file (if any) was already written to disk above -- if
        // the update never lands, the existing row still points at the
        // OLD file, so clean up the orphaned new one instead of leaking
        // it. Never touch $oldFilePathToDelete here -- it's still the
        // live, referenced file.
        if ($newFilePath !== null) {
            delete_material_file($newFilePath);
        }
        error_log('update_material: ' . $e->getMessage());
        return 'A database error occurred. Please try again.';
    }

    if ($oldFilePathToDelete !== null) {
        delete_material_file($oldFilePathToDelete);
    }

    return null;
}

/**
 * Deletes a material. Returns the deleted row's stored file path (or null
 * string field if it wasn't a file) so the caller can remove it from
 * disk, or false if the material wasn't found/owned.
 */
function delete_material(mysqli $conn, int $materialId, int $professorId)
{
    $stmt = $conn->prepare("SELECT file_path FROM class_material WHERE material_id = ? AND professor_id = ?");
    $stmt->bind_param('ii', $materialId, $professorId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) {
        return false;
    }

    $stmt = $conn->prepare("DELETE FROM class_material WHERE material_id = ? AND professor_id = ?");
    $stmt->bind_param('ii', $materialId, $professorId);
    $stmt->execute();
    $stmt->close();

    return $row['file_path'];
}
