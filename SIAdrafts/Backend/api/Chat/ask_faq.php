<?php
/**
 * Public FAQ chatbot for the landing page (index.php). Deliberately has no
 * session/auth check — same public-facing shape as online_admission_process.php.
 * Never calls Gemini directly from the browser: the API key stays server-side.
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../rate_limit.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

// Public, unauthenticated endpoint that spends real (if free-tier) API
// quota per request — cap it per IP so it can't be used to drain the quota
// or run up a bill if the free tier ever runs out.
if (!rate_limit_check('faq_chat', 30, 3600)) {
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

// Conversation history — the client echoes back its own prior turns (this
// endpoint keeps no server-side session), so a follow-up like "for BSIT?"
// can be understood against the earlier "how much is tuition?" turn. Never
// trust it blindly: cap how many turns and how long each one can be, so a
// crafted request can't inflate a single call into a huge token spend.
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
    echo json_encode(['error' => 'Chat is not configured yet. Please contact Admissions directly.']);
    exit;
}

// Grounding content — the same four Q&As already published on index.php's
// own FAQ section, kept here as the model's only source of truth. Add to
// this list (and to index.php's FAQ section) as new questions come up;
// the two are not auto-synced, so keep them consistent by hand.
const FAQ_CONTENT = <<<'TEXT'
Q: Do I need to bring documents to apply online?
A: No — the online form only needs your details. You can upload requirements now or mark them "submit at campus" and bring them in person.

Q: How long does the whole process take?
A: The online form takes about ten minutes. The on-campus steps (document verification, enlistment, and payment) depend on how busy the line is that day.

Q: I lost my reference number — what do I do?
A: Visit the Admissions counter with a valid ID and the staff can look up your application by name and birth date.

Q: Can I change my program after applying?
A: There's no self-service way to do this yet, even at the Admissions counter — it currently needs a manual correction on our end. Contact the Admissions office and they'll get it sorted.

Q: What programs do you offer?
A: See the Programs section on this page for the current list open for enrollment — it's kept up to date there directly.

Q: How much is the tuition?
A: Tuition varies by program and year level. The exact fee breakdown is generated once enrollment is confirmed — Treasury or Admissions can also walk applicants through it beforehand.

Q: Can I apply in person instead of online?
A: Yes — visit the Admissions counter and staff can take the application and documents in person, no online form required.

Q: How do I check my application status?
A: Visit or contact the Admissions office with the reference number. Status updates to "verified" once document verification is complete.
TEXT;

// FAQ_CONTENT is fixed server-side, never taken from the request — a client
// supplying its own "grounding" content would defeat the whole point of it.
$faqContent = FAQ_CONTENT;
$systemPrompt = <<<TEXT
You are Dotty, a friendly assistant on EduSchool's public admissions website. Keep every answer short — two or three sentences at most, plain text, no markdown formatting.

Write like a warm, quick-witted front-desk person, not a form letter. Vary how you open — a genuine "Sure thing!", "Good question —", "Hi there!", or just diving straight into the answer are all fine; never open the same way twice in a row if you can help it. Match the energy of what's asked: brief and light for a greeting, straightforward and clear for a real question. This applies to tone only — it never loosens how strictly you stick to the facts in rule 2 below.

Two different kinds of questions get two different treatments:

1. Casual conversation — greetings, small talk, questions about yourself, or anything with no real/wrong answer at stake (e.g. "hi", "how are you", "do you know someone named Ian", "what's your favorite color"). Answer these naturally and briefly, like a friendly assistant would. It's fine to say you don't know something like a person's identity — just say so plainly, don't deflect to Admissions for things that have nothing to do with admissions.

2. Questions about EduSchool admissions, enrollment, programs, fees, or the application process — these are the only questions where being wrong actually matters. Answer these ONLY using the reference information below. If the specific thing asked isn't covered there, say you're not sure and direct them to contact the Admissions office, rather than guessing. Never invent a policy, requirement, deadline, or number that isn't stated below.

Reference information (admissions questions only):
{$faqContent}
TEXT;

$payload = [
    'system_instruction' => [
        'parts' => [['text' => $systemPrompt]],
    ],
    // Prior turns first, then the new message — gives Gemini the running
    // conversation so a follow-up ("for BSIT?") resolves against what was
    // asked before ("how much is tuition?"), not in isolation.
    'contents' => array_merge(
        $history,
        [['role' => 'user', 'parts' => [['text' => $message]]]]
    ),
    'generationConfig' => [
        'maxOutputTokens' => 500,
        'thinkingConfig'  => ['thinkingLevel' => 'low'],
    ],
];

// gemini-3.5-flash-lite, not gemini-3.6-flash: the free tier for 3.6-flash
// is capped at 20 requests PER DAY (locks the whole widget out for hours on
// real traffic); 3.5-flash-lite's free tier is 15 requests PER MINUTE,
// which resets on its own and actually holds up under live use.
$ch = curl_init('https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash-lite:generateContent?key=' . urlencode($apiKey));
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_TIMEOUT        => 20,
]);
$response = curl_exec($ch);
$curlError = curl_error($ch);
$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($curlError || $response === false) {
    error_log('Gemini FAQ chat: cURL error — ' . $curlError);
    http_response_code(502);
    echo json_encode(['error' => "I'm having a moment — try asking again in a bit?"]);
    exit;
}

$result = json_decode($response, true);

if ($httpCode !== 200 || !isset($result['candidates'][0]['content']['parts'][0]['text'])) {
    error_log('Gemini FAQ chat: unexpected response (HTTP ' . $httpCode . ') — ' . $response);
    http_response_code(502);
    echo json_encode(['error' => "I'm having a moment — try asking again in a bit?"]);
    exit;
}

$reply = trim($result['candidates'][0]['content']['parts'][0]['text']);

echo json_encode(['reply' => $reply]);
