<?php

// Simple file-based sliding-window rate limiter, keyed by client IP + a
// caller-chosen bucket name. No Redis/Memcached available on this MAMP
// setup, so attempts are tracked in small per-key JSON files instead.
function rate_limit_check(string $bucket, int $maxAttempts, int $windowSeconds): bool
{
    $dir = __DIR__ . '/../storage/rate_limit';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $ip  = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $bucket . '_' . $ip);
    $file = $dir . '/' . $key . '.json';

    $fp = fopen($file, 'c+');
    if (!$fp) {
        return true; // fail open rather than lock out real users on a disk error
    }
    flock($fp, LOCK_EX);

    $raw = stream_get_contents($fp);
    $attempts = $raw ? (json_decode($raw, true) ?: []) : [];

    $now = time();
    $attempts = array_values(array_filter($attempts, fn($t) => $t > $now - $windowSeconds));

    $allowed = count($attempts) < $maxAttempts;
    if ($allowed) {
        $attempts[] = $now;
        rewind($fp);
        ftruncate($fp, 0);
        fwrite($fp, json_encode($attempts));
    }

    flock($fp, LOCK_UN);
    fclose($fp);

    return $allowed;
}
