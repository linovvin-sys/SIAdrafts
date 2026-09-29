<?php
/**
 * Cache-busting: appends a local file's last-modified time as a query
 * string, so the browser fetches a fresh copy the moment that CSS/JS file
 * changes instead of silently serving a stale cached one indefinitely
 * (these files have no version string otherwise, and a normal reload
 * doesn't reliably invalidate a cached stylesheet). Shared by every
 * portal's header -- previously defined only inside the Admin/Registrar/
 * Admission shared header, which meant the Professor and Student portals'
 * local <link> tags had no cache-busting at all.
 */
function asset_url(string $publicPath): string
{
    $fsPath = __DIR__ . '/../' . ltrim(str_replace('/SIAdrafts/', '', $publicPath), '/');
    $mtime = @filemtime($fsPath);
    return $publicPath . ($mtime ? '?v=' . $mtime : '');
}
