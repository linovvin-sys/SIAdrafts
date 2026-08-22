<?php

// PHP's ini defaults to UTC (date.timezone=UTC in MAMP's php.ini), but
// MySQL's TIMESTAMP columns here are stored/returned in the server's local
// time (time_zone=SYSTEM, which is Asia/Manila, UTC+8 -- matches this
// school's actual locale). Without this, every time()/date()/
// DateTimeImmutable('now') call in PHP silently disagrees with the
// database by 8 hours: "is this class happening right now" checks,
// countdown timers, today's-weekday highlighting, and relative-time
// displays ("3h ago") were all computing against the wrong "now".
// Setting this once, centrally, fixes all of them instead of patching
// each date calculation individually.
date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

function config(string $key): ?string
{
    $value = $_ENV[$key] ?? null;
    return $value === null ? null : (string)$value;
}
