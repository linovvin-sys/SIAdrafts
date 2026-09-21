<?php
/**
 * Internal Dotty — staff-portal version of ask_faq.php. Same shape
 * (server-held API key, client echoes its own conversation history), but
 * authenticated and grounded on live data instead of the fixed public FAQ.
 *
 * Role scoping is enforced entirely in Backend/StaffChat/snapshot.php,
 * BEFORE this ever talks to Gemini — see that file's header comment. This
 * endpoint's own job is just: confirm who's asking, rate-limit them, hand
 * their role's snapshot to the model, and never let the client supply its
 * own "data" to ground answers on.
 */

session_start();
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../roles.php';
require_once __DIR__ . '/../../require_role.php';
require_once __DIR__ . '/../../rate_limit.php';
require_once __DIR__ . '/../../StaffChat/snapshot.php';

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Please log in.']);
    exit;
}
require_role(ROLES_ALL_STAFF, true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

// Keyed per-account (not per-IP) — a shared office IP shouldn't throttle
// everyone in it together the way the public widget's per-IP limit would.
if (!rate_limit_check('staff_chat_' . $_SESSION['user_id'], 30, 3600)) {
    http_response_code(429);
    echo json_encode(['error' => "Whoa, one at a time! Give me a minute to catch up before asking again."]);
    exit;
}

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

$message = trim($data['message'] ?? '');

if ($message === '') {
    http_response_code(422);
    echo json_encode(['error' => "Type something and I'll take a look!"]);
    exit;
}
if (mb_strlen($message) > 500) {
    http_response_code(422);
    echo json_encode(['error' => 'That question is too long — please shorten it.']);
    exit;
}

$rawHistory = is_array($data['history'] ?? null) ? $data['history'] : [];
$rawHistory = array_slice($rawHistory, -10);

$history = [];
foreach ($rawHistory as $turn) {
    $role = ($turn['role'] ?? '') === 'model' ? 'model' : 'user';
    $text = trim((string)($turn['text'] ?? ''));
    if ($text === '') continue;
    $text = mb_substr($text, 0, 500);
    $history[] = ['role' => $role, 'parts' => [['text' => $text]]];
}

$apiKey = config('GEMINI_API_KEY');
if (!$apiKey) {
    http_response_code(500);
    echo json_encode(['error' => 'Chat is not configured yet.']);
    exit;
}

$db   = new Database();
$conn = $db->connect();

$roleName = $_SESSION['role_name'] ?? '';
$fullName = $_SESSION['full_name'] ?? 'there';

// The snapshot is the ONLY data source in this prompt — built strictly
// from $_SESSION['role_name'], never from anything the client sent.
$snapshot = staff_chat_snapshot($conn, $roleName);
$db->close();

// Tuned for a coworker using this many times a day, not a first-time
// visitor: skip the public widget's warmth/personality engineering (varied
// openers, chatty tone) entirely — that costs tokens and reading time
// without adding anything a staff user needs. Direct answer first, no
// preamble. Lower maxOutputTokens below matches: these answers should be
// short by construction, not just instructed to be.
$systemPrompt = <<<TEXT
You are Dotty, the internal data assistant in EduSchool's staff portal, talking to {$fullName} ({$roleName}). Answer directly — no greeting, no preamble, no filler like "Great question." Plain text only — no markdown (no asterisks, no #headers, no numbered-list syntax), since this renders as plain text, not formatted HTML.

Format depends on what's actually being answered:
- A single fact (one number, one name, one date) → one short sentence, inline.
- Multiple items (several names, several rows) → a real list: one item per line, each line starting with "- ", nothing before the list but a short lead-in if needed (e.g. "3 fully paid:"). Use an actual newline between items, not commas crammed into one paragraph — the point is that it's scannable, not that it's short.

The live figures block below is your ONLY data source — it's already scoped to exactly what a {$roleName} account may see. Rules, in priority order:
1. If the block lists specific names/rows relevant to the question, use them directly, one per line as above — quote names, dates, and peso amounts exactly as given, never rounded, never summarized down to just a count when the actual list is available.
2. If the question is covered only as an aggregate (a count/total, no list), give that number exactly, as a single sentence.
3. If it isn't covered at all, say plainly you don't have that — do NOT guess which other office/role would, you have no reliable way to know that and a wrong guess misleads. If it plausibly belongs to the asker's own role but just isn't in the block, say the same thing: not something you can currently see.
4. Never invent, estimate, or guess a number, name, or date under any circumstance.

Casual conversation (greetings, small talk) doesn't need the data block — answer it naturally and briefly.

Live figures for this account ({$roleName}):
{$snapshot}
TEXT;

$payload = [
    'system_instruction' => [
        'parts' => [['text' => $systemPrompt]],
    ],
    'contents' => array_merge(
        $history,
        [['role' => 'user', 'parts' => [['text' => $message]]]]
    ),
    'generationConfig' => [
        // Bumped from 350 — a real 10-item list (name + date + amount per
        // line) needs more headroom than a one-sentence answer, and a cap
        // that's too tight would cut a list off mid-way instead of just
        // being terser.
        'maxOutputTokens' => 600,
        'thinkingConfig'  => ['thinkingLevel' => 'low'],
    ],
];

$ch = curl_init('https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash-lite:generateContent?key=' . urlencode($apiKey));
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_TIMEOUT        => 20,
]);
$response  = curl_exec($ch);
$curlError = curl_error($ch);
$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($curlError || $response === false) {
    error_log('Gemini staff chat: cURL error — ' . $curlError);
    http_response_code(502);
    echo json_encode(['error' => "I'm having a moment — try asking again in a bit?"]);
    exit;
}

$result = json_decode($response, true);

if ($httpCode !== 200 || !isset($result['candidates'][0]['content']['parts'][0]['text'])) {
    error_log('Gemini staff chat: unexpected response (HTTP ' . $httpCode . ') — ' . $response);
    http_response_code(502);
    echo json_encode(['error' => "I'm having a moment — try asking again in a bit?"]);
    exit;
}

$reply = trim($result['candidates'][0]['content']['parts'][0]['text']);

echo json_encode(['reply' => $reply]);
