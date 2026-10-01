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

require_once __DIR__ . '/../../../Backend/session_bootstrap.php';
app_session_start();
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

THE BLOCK BELOW IS GROUND TRUTH, NOT A PERMISSION GATE. Server-side code already filtered it to exactly what a {$roleName} account is cleared to see before this prompt was ever built — that decision is already made and done. If a number, name, or row is sitting in the block, {$fullName} is fully authorized to hear it. Your only job is reading the block correctly, not re-deciding what they're allowed to know. Never hedge with "I believe," "it looks like," or "I think" about something that's stated plainly in the block, and never frame an answer as a permission or access issue — the block IS the access grant.

Before answering, check in this exact order:
1. Does the block state this number, name, or date directly, anywhere in it (read the whole block, not just the first lines)? → State it immediately, exactly as written — never round a peso amount, never shorten an available list down to just a count.
2. Can it be computed from numbers already in the block with simple arithmetic (a difference, a sum, a percentage of two figures that are both present)? → Do the math and give the resulting number, not just a description of how to get it.
3. Only if neither #1 nor #2 applies — the block truly has nothing related to the question, even indirectly — say so in one plain sentence using language like "That's not something captured in what I can see right now," never "you don't have access" or "that's above your role." The real reason is almost always that this particular figure isn't tracked in this snapshot, not a permissions problem, and implying otherwise is inaccurate and unhelpful.
4. Never guess which other office or system might have it instead. You have no reliable way to know that, and a wrong guess sends someone chasing the wrong desk.
5. Never invent, estimate, or guess a number, name, or date that isn't in the block or isn't trivially derivable from it per #2.

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
        // 1500, not 600 -- thinkingConfig's reasoning tokens are drawn from
        // this SAME budget, not a separate pool, for this model. 600 was
        // sized back when thinkingLevel was 'low' and left barely any of
        // that shared budget for the actual reply once 'medium' thinking
        // started eating into it first, silently truncating real answers
        // mid-list (e.g. a 10-row applicant list cutting off after row 2).
        // Sized generously here so a full 10-item list (name + date +
        // amount per line) always has room left after thinking, regardless
        // of how much of the budget reasoning consumes first.
        'maxOutputTokens' => 1500,
        // premature "I don't have that" refusals are more likely when the
        // model doesn't budget enough reasoning to actually scan the whole
        // block (see the numbered check in the system prompt above) before
        // giving up. 'medium' costs a bit more latency/tokens but directly
        // trades for the accuracy this prompt is tuned for -- maxOutputTokens
        // above was raised specifically to afford this.
        'thinkingConfig'  => ['thinkingLevel' => 'medium'],
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
