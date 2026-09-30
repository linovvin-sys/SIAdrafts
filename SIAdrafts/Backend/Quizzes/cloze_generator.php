<?php
/**
 * Generates multiple-choice cloze (fill-in-the-blank) questions from raw
 * lesson text -- no AI/LLM call, just sentence splitting and term
 * selection. Each result is shaped to drop straight into
 * Backend/Professor/quiz_data.php's existing post_question(), which
 * already validates >=2 choices with exactly one correct -- generation
 * reuses that validation rather than duplicating it.
 */

const CLOZE_STOPWORDS = [
    'the','and','of','to','in','is','are','was','were','that','this','with','for','as','by','an','be',
    'or','it','its','from','at','on','which','who','whom','whose','been','being','has','have','had',
    'not','but','if','then','than','so','such','can','could','would','should','will','shall','may','might',
    'these','those','their','them','they','them','you','your','our','we','he','she','his','her','him',
    'into','onto','about','over','under','between','among','also','both','each','some','any','all','more',
    'most','other','only','own','same','than','too','very','just','because','while','when','where','how',
    'what','there','here','one','two','three','a','i','thank','thanks','please','okay','welcome','hello',
    'goodbye','congratulations',
];

// Whole sentences that are boilerplate/filler rather than actual lesson
// content -- a title slide, a "Thank you"/"Any questions?" closing slide,
// a references list, etc. These pass the word-count filter fine (some
// closing slides are long) but make a nonsense quiz question regardless
// of which word gets blanked, so they're excluded outright before a term
// is ever chosen.
const CLOZE_BOILERPLATE_PATTERNS = [
    '/\bthank(s| you)\b/i',
    '/\bany questions\b/i',
    '/\bthe end\b/i',
    '/\bquestions\?\s*$/i',
    '/\breferences?\s*:?\s*$/i',
    '/\bbibliography\b/i',
    '/\btable of contents\b/i',
    '/\bfor (your )?(attention|listening|watching)\b/i',
    '/\bsee you (next|in)\b/i',
    '/\bhave a (great|good|nice) (day|class|week)\b/i',
    '/\bgood luck\b/i',
    '/\bclick here\b/i',
];

/** @return array<int, array{question_text:string, points:float, choices: array<int, array{text:string, is_correct:bool}>}> */
function generate_cloze_questions(string $text, int $maxQuestions = 100): array
{
    $sentences = cloze_split_sentences($text);
    if (empty($sentences)) {
        return [];
    }

    $globalTerms = cloze_candidate_terms(implode(' ', $sentences));
    if (count($globalTerms) < 2) {
        return [];
    }

    $questions = [];
    $usedTerms = [];

    foreach ($sentences as $sentence) {
        if (count($questions) >= $maxQuestions) {
            break;
        }

        $wordCount = count(preg_split('/\s+/', trim($sentence)));
        if ($wordCount < 6 || $wordCount > 40) {
            continue;
        }

        if (cloze_is_boilerplate($sentence)) {
            continue;
        }

        $sentenceTerms = cloze_candidate_terms($sentence);
        $chosen = cloze_pick_blank_term($sentence, $sentenceTerms, $usedTerms);
        if ($chosen === null) {
            continue;
        }

        $distractors = cloze_pick_distractors($globalTerms, $chosen, 3);
        if (count($distractors) < 1) {
            continue;
        }

        $usedTerms[mb_strtolower($chosen)] = true;

        $questionText = cloze_blank_out($sentence, $chosen);
        $choices = [['text' => $chosen, 'is_correct' => true]];
        foreach ($distractors as $d) {
            $choices[] = ['text' => $d, 'is_correct' => false];
        }
        shuffle($choices);

        $questions[] = [
            'question_text' => $questionText,
            'points' => 1.0,
            'choices' => $choices,
        ];
    }

    return $questions;
}

function cloze_split_sentences(string $text): array
{
    // Newlines are real paragraph/slide/page boundaries (text_extraction.php
    // emits one line per paragraph, blank-line-separated per page/slide) --
    // splitting on them FIRST, before collapsing whitespace within each
    // paragraph, stops a punctuation-less title/heading ("Introduction to
    // Cell Biology") from silently gluing onto the next paragraph's first
    // sentence just because neither ends with a period.
    $paragraphs = preg_split('/\R+/u', $text);

    $sentences = [];
    foreach ($paragraphs as $paragraph) {
        $paragraph = trim(preg_replace('/\s+/', ' ', $paragraph));
        if ($paragraph === '') {
            continue;
        }
        $parts = preg_split('/(?<=[.!?])\s+(?=[A-Z0-9])/', $paragraph);
        foreach ($parts as $p) {
            $p = trim($p);
            if ($p !== '') {
                $sentences[] = $p;
            }
        }
    }
    return $sentences;
}

/** Every non-stopword, alphabetic-only word of >=4 letters, exact casing preserved (first occurrence wins). */
function cloze_candidate_terms(string $text): array
{
    preg_match_all('/[A-Za-z][A-Za-z\'-]{3,}/', $text, $matches);
    $terms = [];
    foreach ($matches[0] as $word) {
        $clean = trim($word, "'-");
        if (mb_strlen($clean) < 4) {
            continue;
        }
        if (in_array(mb_strtolower($clean), CLOZE_STOPWORDS, true)) {
            continue;
        }
        $key = mb_strtolower($clean);
        if (!isset($terms[$key])) {
            $terms[$key] = $clean;
        }
    }
    return array_values($terms);
}

/**
 * Prefers a capitalized term -- a real signal of a proper noun/key term
 * (e.g. "Photosynthesis", "Golgi") -- but the sentence's OWN first word is
 * excluded from that preference, since English capitalizes every
 * sentence-initial word regardless of importance ("Thank you for..."
 * would otherwise blank "Thank" just because it starts the sentence).
 * The first word can still be chosen as an ordinary fallback candidate if
 * nothing better exists. Falls back to the longest remaining candidate.
 * Skips already-used terms.
 */
function cloze_pick_blank_term(string $sentence, array $sentenceTerms, array $usedTerms): ?string
{
    $available = array_values(array_filter($sentenceTerms, fn($t) => !isset($usedTerms[mb_strtolower($t)])));
    if (empty($available)) {
        return null;
    }

    preg_match('/^\s*([A-Za-z][A-Za-z\'-]*)/', $sentence, $m);
    $firstWord = isset($m[1]) ? mb_strtolower($m[1]) : null;

    $capitalized = array_values(array_filter(
        $available,
        fn($t) => ctype_upper(mb_substr($t, 0, 1)) && mb_strtolower($t) !== $firstWord
    ));
    $pool = !empty($capitalized) ? $capitalized : $available;

    usort($pool, fn($a, $b) => mb_strlen($b) - mb_strlen($a));
    return $pool[0];
}

function cloze_is_boilerplate(string $sentence): bool
{
    foreach (CLOZE_BOILERPLATE_PATTERNS as $pattern) {
        if (preg_match($pattern, $sentence)) {
            return true;
        }
    }
    return false;
}

/** Distractors of similar word-length to $correct, drawn from the whole-document term list, excluding $correct itself. */
function cloze_pick_distractors(array $globalTerms, string $correct, int $max): array
{
    $correctLen = mb_strlen($correct);
    $correctKey = mb_strtolower($correct);

    $candidates = array_values(array_filter($globalTerms, fn($t) => mb_strtolower($t) !== $correctKey));
    if (empty($candidates)) {
        return [];
    }

    usort($candidates, function ($a, $b) use ($correctLen) {
        return abs(mb_strlen($a) - $correctLen) <=> abs(mb_strlen($b) - $correctLen);
    });

    // Take a similar-length-biased slice, then shuffle within it so the
    // same document doesn't always produce the same distractor order.
    $pool = array_slice($candidates, 0, max($max * 3, 6));
    shuffle($pool);
    return array_slice($pool, 0, $max);
}

function cloze_blank_out(string $sentence, string $term): string
{
    $pattern = '/\b' . preg_quote($term, '/') . '\b/u';
    return preg_replace($pattern, '_____', $sentence, 1);
}
