<?php

require_once __DIR__ . '/db.php';

function get_setting(string $key): ?string
{
    static $cache = [];
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    $db   = new Database();
    $conn = $db->connect();
    $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $db->close();

    return $cache[$key] = $row['setting_value'] ?? null;
}

function set_setting(string $key, string $value, int $userId): void
{
    $db   = new Database();
    $conn = $db->connect();
    $stmt = $conn->prepare(
        "INSERT INTO settings (setting_key, setting_value, updated_by)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by)"
    );
    $stmt->bind_param('ssi', $key, $value, $userId);
    $stmt->execute();
    $stmt->close();
    $db->close();
}
