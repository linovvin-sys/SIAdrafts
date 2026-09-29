<?php
// Shared validation/storage logic for assignment submission files.
// Mirrors Backend/api/Messaging/message_attachments.php's pattern exactly:
// finfo over the file's actual bytes (never the client-supplied name or
// Content-Type), a random stored filename, and a directory Apache denies
// direct access to (see uploads/submissions/.htaccess).

const SUBMISSION_MAX_BYTES = 15 * 1024 * 1024; // 15 MB

const SUBMISSION_ALLOWED_TYPES = [
    'application/pdf' => 'pdf',
    'application/msword' => 'doc',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
    'application/vnd.ms-powerpoint' => 'ppt',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
    'application/zip' => 'zip',
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'text/plain' => 'txt',
];

function submission_upload_dir(): string {
    return __DIR__ . '/../../uploads/submissions/';
}

/**
 * Validates and stores an uploaded file from $_FILES['submission_file'].
 * Returns ['path' => relative path, 'name' => original filename,
 * 'type' => mime type] on success, or ['error' => string] on failure.
 */
function store_submission_file(array $file): array {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['error' => 'Please choose a file to submit.'];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'File upload failed.'];
    }
    if ($file['size'] > SUBMISSION_MAX_BYTES) {
        return ['error' => 'File is too large (15 MB max).'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);

    if (!isset(SUBMISSION_ALLOWED_TYPES[$mime])) {
        return ['error' => 'That file type is not allowed. Use PDF, Word, PowerPoint, ZIP, JPG, PNG, or TXT.'];
    }

    $ext = SUBMISSION_ALLOWED_TYPES[$mime];
    $dir = submission_upload_dir();
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $storedName  = bin2hex(random_bytes(16)) . '.' . $ext;
    $destination = $dir . $storedName;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['error' => 'Could not save the uploaded file.'];
    }

    return [
        'path' => $storedName,
        'name' => basename($file['name']),
        'type' => $mime,
    ];
}

/** Deletes a previously stored submission file by its stored (basename) path. Safe to call on a missing file. */
function delete_submission_file(string $storedName): void {
    $path = submission_upload_dir() . basename($storedName);
    if (is_file($path)) {
        unlink($path);
    }
}
