<?php
/**
 * Google reCAPTCHA v2 (checkbox) verification for the public online
 * admission form -- the one unauthenticated, internet-facing submission
 * endpoint in this app. Added alongside the existing honeypot field and
 * per-IP rate limit, not instead of them: the honeypot catches naive
 * script spam for free, the rate limit caps damage from anything that
 * gets through, and this is what actually stops a human-operated or
 * CAPTCHA-farm-assisted bot from submitting at all.
 *
 * RECAPTCHA_SITE_KEY is safe to render into HTML -- it's meant to be
 * public, it's what identifies this site to Google's widget. Only
 * RECAPTCHA_SECRET_KEY (used here, server-side only) needs to stay out
 * of the client entirely, same as every other secret in .env.
 */
require_once __DIR__ . '/config.php';

/**
 * Whether a real key pair has been configured yet. Register a site at
 * https://www.google.com/recaptcha/admin and set both RECAPTCHA_SITE_KEY
 * and RECAPTCHA_SECRET_KEY in .env to turn this on -- see .env.example.
 */
function recaptcha_is_configured(): bool
{
    return (bool)config('RECAPTCHA_SECRET_KEY');
}

/**
 * @return bool true if the submitted token is valid for this site
 */
function recaptcha_verify(string $token, string $remoteIp): bool
{
    $secret = config('RECAPTCHA_SECRET_KEY');
    if (!$secret) {
        // Caller is expected to check recaptcha_is_configured() first and
        // skip calling this entirely when it's false -- an admission form
        // deployed before real keys exist should still work on its
        // existing protections (honeypot + rate limit), not go dark.
        // Reaching here anyway (a caller that skipped that check) fails
        // closed rather than silently accepting an unverifiable token.
        error_log('recaptcha_verify: called without RECAPTCHA_SECRET_KEY set -- caller should check recaptcha_is_configured() first');
        return false;
    }
    if ($token === '') {
        return false;
    }

    $ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => [
            'secret'   => $secret,
            'response' => $token,
            'remoteip' => $remoteIp,
        ],
        CURLOPT_TIMEOUT        => 10,
    ]);
    $raw    = curl_exec($ch);
    $errNo  = curl_errno($ch);
    curl_close($ch);

    if ($errNo || $raw === false) {
        // Network failure talking to Google -- fail closed, same reasoning
        // as an unset secret above. A real applicant can just retry.
        error_log('recaptcha_verify: could not reach Google (curl errno ' . $errNo . ')');
        return false;
    }

    $result = json_decode($raw, true);
    return !empty($result['success']);
}
