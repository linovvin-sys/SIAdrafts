<?php
/**
 * Thin PayMongo REST client. One place holds the secret key, the base URL,
 * and the Basic-auth encoding so the two Treasury endpoints
 * (paymongo_create_checkout.php / paymongo_return.php) don't each rebuild
 * the curl call.
 *
 * Test vs live is decided purely by which key is in .env — sk_test_... talks
 * to PayMongo's test environment (fake GCash authorize page, no real money),
 * sk_live_... talks to production.
 */

require_once __DIR__ . '/config.php';

class PayMongoError extends RuntimeException {}

/**
 * @param string     $method  GET | POST
 * @param string     $path    e.g. '/checkout_sessions' or '/checkout_sessions/cs_xxx'
 * @param array|null $body    request body (wrapped as {"data":{"attributes":...}} by the caller)
 * @return array               decoded JSON response body
 * @throws PayMongoError on transport failure or non-2xx response
 */
function paymongo_api(string $method, string $path, ?array $body = null): array
{
    $secret = config('PAYMONGO_SECRET_KEY');
    if (!$secret) {
        throw new PayMongoError('PAYMONGO_SECRET_KEY is not set in .env');
    }

    $ch = curl_init('https://api.paymongo.com/v1' . $path);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_HTTPHEADER     => [
            // PayMongo uses HTTP Basic auth: the secret key as the username,
            // empty password.
            'Authorization: Basic ' . base64_encode($secret . ':'),
            'Content-Type: application/json',
            'Accept: application/json',
        ],
    ];
    if ($body !== null) {
        $opts[CURLOPT_POSTFIELDS] = json_encode($body);
    }
    curl_setopt_array($ch, $opts);

    $raw    = curl_exec($ch);
    $errNo  = curl_errno($ch);
    $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    if ($errNo) {
        throw new PayMongoError('Could not reach PayMongo (network error).');
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        throw new PayMongoError('PayMongo returned an unreadable response.');
    }

    if ($status < 200 || $status >= 300) {
        $detail = $decoded['errors'][0]['detail'] ?? ('HTTP ' . $status);
        throw new PayMongoError('PayMongo: ' . $detail);
    }

    return $decoded;
}

/**
 * Pull the paid/failed verdict out of a retrieved checkout session.
 * Returns 'paid', 'failed', or 'pending'.
 */
function paymongo_checkout_verdict(array $session): string
{
    $attr     = $session['data']['attributes'] ?? [];
    $payments = $attr['payments'] ?? [];

    foreach ($payments as $p) {
        $st = $p['attributes']['status'] ?? '';
        if ($st === 'paid') {
            return 'paid';
        }
    }

    // No paid payment yet — look at the intent to tell "still open" from
    // "definitively failed/expired".
    $intentStatus = $attr['payment_intent']['attributes']['status'] ?? '';
    if (in_array($intentStatus, ['succeeded', 'processing'], true)) {
        return 'paid'; // processing settles to paid for GCash test; treat as done
    }
    if (in_array($intentStatus, ['cancelled', 'expired'], true)) {
        return 'failed';
    }

    return 'pending';
}
