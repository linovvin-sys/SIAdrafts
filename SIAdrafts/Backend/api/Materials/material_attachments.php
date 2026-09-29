<?php
// Shared validation/storage logic for course-material files. Mirrors
// Backend/api/Assignments/submission_attachments.php's pattern exactly,
// with a broader allowed-type list since slides/spreadsheets are common
// course material but not something a student would submit back.

const MATERIAL_MAX_BYTES = 25 * 1024 * 1024; // 25 MB

const MATERIAL_ALLOWED_TYPES = [
    'application/pdf' => 'pdf',
    'application/msword' => 'doc',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
    'application/vnd.ms-powerpoint' => 'ppt',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
    'application/vnd.ms-excel' => 'xls',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
    'application/zip' => 'zip',
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'text/plain' => 'txt',
];

function material_upload_dir(): string {
    return __DIR__ . '/../../uploads/materials/';
}

/**
 * Validates and stores an uploaded file from $_FILES['material_file'].
 * Returns ['path' => stored filename, 'name' => original filename,
 * 'type' => mime type] on success, or ['error' => string] on failure.
 */
function store_material_file(array $file): array {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['error' => 'Please choose a file to upload.'];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'File upload failed.'];
    }
    if ($file['size'] > MATERIAL_MAX_BYTES) {
        return ['error' => 'File is too large (25 MB max).'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);

    if (!isset(MATERIAL_ALLOWED_TYPES[$mime])) {
        return ['error' => 'That file type is not allowed. Use PDF, Word, PowerPoint, Excel, ZIP, JPG, PNG, or TXT.'];
    }

    $ext = MATERIAL_ALLOWED_TYPES[$mime];
    $dir = material_upload_dir();
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

/** Deletes a previously stored material file by its stored (basename) path. Safe to call on a missing file. */
function delete_material_file(string $storedName): void {
    $path = material_upload_dir() . basename($storedName);
    if (is_file($path)) {
        unlink($path);
    }
}
