<?php
// Shared validation/storage logic for message attachments.

const MESSAGE_ATTACHMENT_MAX_BYTES = 10 * 1024 * 1024; // 10 MB

const MESSAGE_ATTACHMENT_ALLOWED_TYPES = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
    'image/webp' => 'webp',
    'application/pdf' => 'pdf',
    'application/msword' => 'doc',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
    'application/vnd.ms-excel' => 'xls',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
    'text/plain' => 'txt',
];

function message_attachment_upload_dir(): string {
    return __DIR__ . '/../../uploads/messages/';
}

// Validates and stores an uploaded file from $_FILES['attachment'].
// Returns ['path' => relative path, 'name' => original filename,
// 'type' => mime type, 'size' => bytes] on success, or ['error' => string] on failure.
function store_message_attachment(array $file): array {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return [];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'File upload failed.'];
    }

    if ($file['size'] > MESSAGE_ATTACHMENT_MAX_BYTES) {
        return ['error' => 'File is too large (10 MB max).'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);

    if (!isset(MESSAGE_ATTACHMENT_ALLOWED_TYPES[$mime])) {
        return ['error' => 'That file type is not allowed.'];
    }

    $ext  = MESSAGE_ATTACHMENT_ALLOWED_TYPES[$mime];
    $dir  = message_attachment_upload_dir();
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $storedName = bin2hex(random_bytes(16)) . '.' . $ext;
    $destination = $dir . $storedName;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['error' => 'Could not save the uploaded file.'];
    }

    return [
        'path' => 'messages/' . $storedName,
        'name' => basename($file['name']),
        'type' => $mime,
        'size' => (int)$file['size'],
    ];
}