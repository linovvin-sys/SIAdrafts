<?php

require_once __DIR__ . '/db.php';

/**
 * Shared per-request cache for get_setting()/set_setting(), keyed by setting_key.
 * Returns the static cache array by reference so both functions read/write
 * the same store and stay in sync within a request.
 *
 * @return array<string, ?string>
 */
function &_settings_cache(): array
{
    static $cache = [];
    return $cache;
}

function get_setting(string $key): ?string
{
    $cache = &_settings_cache();
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

    $cache = &_settings_cache();
    $cache[$key] = $value;
}
