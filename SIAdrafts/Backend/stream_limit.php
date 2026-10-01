<?php
/**
 * Caps how many long-lived SSE connections (message_stream.php, both
 * Messaging and ClassMessaging variants) one account can have open at
 * once. Each individual stream already bounds itself (5-minute max
 * runtime, connection_aborted() check every cycle) -- this closes the gap
 * one level up: nothing previously stopped one authenticated account
 * from opening many of those simultaneously, each holding a PHP-FPM
 * worker and a DB connection for up to 5 minutes, which a compromised or
 * malicious session could use to exhaust the worker pool.
 *
 * File-based, same reasoning as rate_limit.php (no Redis/Memcached on
 * this MAMP setup): one small marker file per open connection, named by a
 * random token so concurrent acquires never collide. The count of files
 * in a bucket's directory IS the count of that account's open streams.
 */
function stream_slot_acquire(string $bucket, int $maxConcurrent): bool
{
    $dir = __DIR__ . '/../storage/stream_locks/' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $bucket);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    // A slot file only disappears via its own shutdown function, which
    // doesn't run if the process was killed outright (OOM, `kill -9`,
    // a server restart mid-stream) -- so anything older than a stream's
    // own max runtime is definitely dead, not just a long-lived one, and
    // gets swept here rather than permanently eating a slot.
    $staleCutoff = time() - 320;
    foreach (glob($dir . '/*.lock') ?: [] as $f) {
        if (@filemtime($f) < $staleCutoff) {
            @unlink($f);
        }
    }

    $files = glob($dir . '/*.lock') ?: [];
    if (count($files) >= $maxConcurrent) {
        return false;
    }

    $slotFile = $dir . '/' . bin2hex(random_bytes(8)) . '.lock';
    file_put_contents($slotFile, (string)getmypid());

    // Covers the normal `break` exit AND an abnormal one (timeout, fatal
    // error, the worker being killed) -- a shutdown function still runs
    // in every one of those cases except a hard kill, which the stale
    // sweep above catches on the next acquire instead.
    register_shutdown_function(function () use ($slotFile) {
        @unlink($slotFile);
    });

    return true;
}
