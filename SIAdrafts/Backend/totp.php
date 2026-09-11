<?php

// Minimal RFC 6238 TOTP (the standard behind Google Authenticator, Authy,
// 1Password, etc.) implemented directly rather than pulling in a Composer
// package -- the whole algorithm is HMAC-SHA1 over a time counter, small
// enough to read and verify by eye, which matters for something this
// security-sensitive.

function totp_generate_secret(int $bytes = 20): string
{
    return totp_base32_encode(random_bytes($bytes));
}

function totp_current_code(string $base32Secret, int $timestamp = null): string
{
    $timestamp ??= time();
    $counter = intdiv($timestamp, 30);
    return totp_hotp(totp_base32_decode($base32Secret), $counter);
}

// Accepts a code from either of the two 30-second windows adjacent to now,
// to tolerate normal clock drift between the server and the user's phone.
function totp_verify_code(string $base32Secret, string $code, int $timestamp = null): bool
{
    $timestamp ??= time();
    $code = preg_replace('/\s+/', '', $code);

    if (!preg_match('/^\d{6}$/', $code)) {
        return false;
    }

    $secretBytes = totp_base32_decode($base32Secret);
    $counter = intdiv($timestamp, 30);

    for ($offset = -1; $offset <= 1; $offset++) {
        if (hash_equals(totp_hotp($secretBytes, $counter + $offset), $code)) {
            return true;
        }
    }

    return false;
}

function totp_qr_uri(string $base32Secret, string $accountLabel, string $issuer = 'EduSchool'): string
{
    return sprintf(
        'otpauth://totp/%s:%s?secret=%s&issuer=%s&algorithm=SHA1&digits=6&period=30',
        rawurlencode($issuer),
        rawurlencode($accountLabel),
        $base32Secret,
        rawurlencode($issuer)
    );
}

/** @return string[] Plain-text codes -- caller must hash before storing and show these to the user exactly once. */
function totp_generate_recovery_codes(int $count = 8): array
{
    $codes = [];
    for ($i = 0; $i < $count; $i++) {
        // XXXX-XXXX, base32 alphabet so there's no ambiguous 0/O or 1/I.
        $raw = totp_base32_encode(random_bytes(5));
        $codes[] = substr($raw, 0, 4) . '-' . substr($raw, 4, 4);
    }
    return $codes;
}

function totp_hotp(string $secretBytes, int $counter): string
{
    $counterBytes = pack('N*', 0, $counter); // 8-byte big-endian counter
    $hash = hash_hmac('sha1', $counterBytes, $secretBytes, true);

    $offset = ord($hash[19]) & 0x0F;
    $value = (
        ((ord($hash[$offset]) & 0x7F) << 24) |
        ((ord($hash[$offset + 1]) & 0xFF) << 16) |
        ((ord($hash[$offset + 2]) & 0xFF) << 8) |
        (ord($hash[$offset + 3]) & 0xFF)
    );

    return str_pad((string)($value % 1000000), 6, '0', STR_PAD_LEFT);
}

function totp_base32_encode(string $bytes): string
{
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $binary = '';
    foreach (str_split($bytes) as $byte) {
        $binary .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
    }

    $output = '';
    foreach (str_split($binary, 5) as $chunk) {
        if (strlen($chunk) < 5) {
            $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
        }
        $output .= $alphabet[bindec($chunk)];
    }

    return $output;
}

function totp_base32_decode(string $base32): string
{
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $base32 = strtoupper(preg_replace('/[^A-Z2-7]/i', '', $base32));

    $binary = '';
    foreach (str_split($base32) as $char) {
        $pos = strpos($alphabet, $char);
        if ($pos === false) {
            continue;
        }
        $binary .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
    }

    $bytes = '';
    foreach (str_split($binary, 8) as $chunk) {
        if (strlen($chunk) < 8) {
            continue; // trailing padding bits, not a full byte
        }
        $bytes .= chr(bindec($chunk));
    }

    return $bytes;
}
