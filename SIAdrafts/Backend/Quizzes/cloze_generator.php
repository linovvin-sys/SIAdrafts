<?php
/**
 * Generates multiple-choice questions from raw lesson text -- no AI/LLM
 * call, just sentence splitting and term selection. Each result is shaped
 * to drop straight into Backend/Professor/quiz_data.php's existing
 * post_question(), which already validates >=2 choices with exactly one
 * correct -- generation reuses that validation rather than duplicating it.
 *
 * Two question styles only (no fill-in-the-blank with an embedded blank --
 * confirmed by the professor as reading poorly, and worse, slide bullet
 * lists/headings were getting blanked into nonsense):
 *  - True/False: the sentence (or a term-swapped false version of it)
 *    presented as a statement to verify.
 *  - Identification: the sentence turned into a standalone description
 *    ("This is the powerhouse of the cell. What is being described?"),
 *    answer = the term, like a real identification-type test item.
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
// of which word gets chosen, so they're excluded outright.
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

// A small set of common verbs/auxiliaries -- real prose sentences almost
// always contain one. A PPTX slide's bullet row ("Button States & Variants
// | Card Layouts") has none: it's a list of noun phrases, not a sentence,
// even though it passes the word-count filter and contains no boilerplate
// phrase. Checking for a verb is the cheapest reliable "is this actually a
// sentence" signal without real POS tagging.
const CLOZE_COMMON_VERBS = [
    'is','are','was','were','be','been','being','has','have','had','can','could','will','would','shall',
    'should','may','might','must','do','does','did','allows','allow','means','mean','refers','refer',
    'includes','include','provides','provide','creates','create','forms','form','enables','enable',
    'uses','use','helps','help','makes','make','shows','show','ensures','ensure','requires','require',
    'consists','consist','involves','involve','describes','describe','represents','represent','acts',
    'act','serves','serve','contains','contain','produces','produce','occurs','occur','functions',
    'function','needs','need','becomes','become','gives','give','takes','take','works','work','helps',
    'supports','support','builds','build','focuses','focus','starts','start','begins','begin','leads',
    'lead','relies','rely','depends','depend','exists','exist','apply','applies','defines','define',
];

/**
 * $excludedTerms is typically the quiz's own subject name/code (e.g.
 * "Cell Biology", "BIO101") -- without this, the subject name tends to be
 * the single most-repeated capitalized phrase in a lesson file, so the
 * generator kept picking it as "the important term," producing nonsense
 * questions that ask the student to fill in the name of the class they're
 * already sitting in.
 *
 * @return array<int, array{question_text:string, points:float, choices: array<int, array{text:string, is_correct:bool}>}>
 */
function generate_cloze_questions(string $text, int $maxQuestions = 100, array $excludedTerms = []): array
{
    $sentences = cloze_split_sentences($text);
    if (empty($sentences)) {
        return [];
    }

    $excludedKeys = [];
    foreach ($excludedTerms as $term) {
        foreach (preg_split('/\s+/', trim((string)$term)) as $word) {
            $word = trim($word, "'-.,");
            if ($word !== '') {
                $excludedKeys[mb_strtolower($word)] = true;
            }
        }
    }

    $globalTerms = cloze_candidate_terms(implode(' ', $sentences), $excludedKeys);
    if (count($globalTerms) < 2) {
        return [];
    }

    // Splitting the term pool into "noun-like" (capitalized or
    // article-led somewhere in the document -- "the mitochondria",
    // "Photosynthesis") vs "other" (adjectives/verbs picked only as a
    // fallback, e.g. "responsible") matters for True/False: its False
    // variant splices a distractor directly into the sentence in the
    // term's place, so swapping across buckets produces broken grammar
    // ("a solid converted" from swapping a noun for a verb). Swapping
    // within the same bucket is far more likely to preserve the sentence's
    // part of speech. Identification never splices a distractor into
    // prose (it's just an answer choice), so it can safely use the full pool.
    // A second split within "other": a verb-shaped word ("converted",
    // "producing") swapped into a noun/adjective slot ("a solid converted")
    // is just as broken as a cross-bucket noun/verb swap was, so verb-like
    // and non-verb "other" words get kept apart too.
    $nounKeys = [];
    foreach ($sentences as $s) {
        foreach (cloze_candidate_terms($s, $excludedKeys) as $t) {
            if (cloze_term_is_identifiable($s, $t)) {
                $nounKeys[mb_strtolower($t)] = true;
            }
        }
    }
    $otherTerms = array_values(array_filter($globalTerms, fn($t) => !isset($nounKeys[mb_strtolower($t)])));
    $verbTerms = array_values(array_filter($otherTerms, 'cloze_looks_like_verb'));
    $nonVerbOtherTerms = array_values(array_filter($otherTerms, fn($t) => !cloze_looks_like_verb($t)));

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

        if (cloze_is_boilerplate($sentence) || !cloze_looks_like_sentence($sentence)) {
            continue;
        }

        $sentenceTerms = cloze_candidate_terms($sentence, $excludedKeys);
        $chosen = cloze_pick_blank_term($sentence, $sentenceTerms, $usedTerms);
        if ($chosen === null) {
            continue;
        }

        // Identification only reads naturally when the term is swappable
        // for "this"/"This" as a noun phrase. Anything else (an adjective
        // like "responsible" picked as a fallback candidate) would turn
        // into a broken clue ("Ribosomes are this for..."), so those
        // sentences become True/False instead.
        $canIdentify = cloze_term_is_identifiable($sentence, $chosen);
        $usedTerms[mb_strtolower($chosen)] = true;

        if ($canIdentify) {
            $distractors = cloze_pick_distractors($globalTerms, $chosen, 3);
            if (count($distractors) < 1) {
                continue;
            }
            $question = cloze_build_identification($sentence, $chosen, $distractors);
        } else {
            $sameClassPool = cloze_looks_like_verb($chosen) ? $verbTerms : $nonVerbOtherTerms;
            $swapPool = count($sameClassPool) >= 2 ? $sameClassPool : (count($otherTerms) >= 2 ? $otherTerms : $globalTerms);
            $distractor = cloze_pick_distractors($swapPool, $chosen, 1);
            if (empty($distractor)) {
                continue;
            }
            $question = cloze_build_true_false($sentence, $chosen, $distractor[0]);
        }

        $questions[] = $question;
    }

    return $questions;
}

/**
 * Rejects slide bullet-list rows and section headings that pass the
 * word-count filter but aren't real prose: no verb at all ("Button States
 * & Variants | Card Layouts"), a literal "|" column separator from a
 * two-column slide layout, or mostly Title Case words (a heading, not a
 * sentence -- real prose capitalizes only proper nouns and sentence
 * starts).
 */
function cloze_looks_like_sentence(string $sentence): bool
{
    if (str_contains($sentence, '|')) {
        return false;
    }

    $words = preg_split('/\s+/', trim($sentence));
    $words = array_values(array_filter($words, fn($w) => $w !== ''));
    if (empty($words)) {
        return false;
    }

    $capitalizedCount = 0;
    foreach ($words as $i => $w) {
        $letters = preg_replace('/[^A-Za-z]/', '', $w);
        if ($letters === '') {
            continue;
        }
        if ($i > 0 && ctype_upper($letters[0])) {
            $capitalizedCount++;
        }
    }
    if ($capitalizedCount / count($words) > 0.35) {
        return false;
    }

    $hasVerb = false;
    foreach ($words as $w) {
        $clean = mb_strtolower(trim($w, ".,!?;:'\""));
        if (in_array($clean, CLOZE_COMMON_VERBS, true)) {
            $hasVerb = true;
            break;
        }
    }

    return $hasVerb;
}

/** True if $term can be swapped for "this"/"This" and still read as a grammatical noun phrase. */
function cloze_term_is_identifiable(string $sentence, string $term): bool
{
    if (ctype_upper(mb_substr($term, 0, 1))) {
        return true;
    }
    return (bool)preg_match('/\b(the|a|an)\s+' . preg_quote($term, '/') . '\b/iu', $sentence);
}

/** Cheap shape-based verb heuristic (no POS tagging available): -ing/-ed participles, or a known common verb. */
function cloze_looks_like_verb(string $term): bool
{
    $lower = mb_strtolower($term);
    if (in_array($lower, CLOZE_COMMON_VERBS, true)) {
        return true;
    }
    return (bool)preg_match('/(ing|ed)$/u', $lower);
}

/**
 * Turns the sentence into a standalone description with the term itself
 * replaced by "This"/"this" (swallowing a leading article as a unit, so
 * "The mitochondria is..." becomes "This is...", not the ungrammatical
 * "The this is..."), then appends a fixed identification prompt. Answer
 * choices are the term vs distractors, like a real identification-type
 * test item.
 */
function cloze_build_identification(string $sentence, string $term, array $distractors): array
{
    $withArticle = '/\b(the|a|an)\s+' . preg_quote($term, '/') . '\b/iu';
    $bareTerm = '/\b' . preg_quote($term, '/') . '\b/u';

    $clue = $sentence;
    if (preg_match($withArticle, $sentence, $m, PREG_OFFSET_CAPTURE)) {
        $replacement = $m[0][1] === 0 ? 'This' : 'this';
        $clue = preg_replace($withArticle, $replacement, $sentence, 1);
    } elseif (preg_match($bareTerm, $sentence, $m, PREG_OFFSET_CAPTURE)) {
        $replacement = $m[0][1] === 0 ? 'This' : 'this';
        $clue = preg_replace($bareTerm, $replacement, $sentence, 1);
    }

    $clue = rtrim(trim($clue), ". \t\n");
    $questionText = $clue . '. What is being described?';

    $choices = [['text' => $term, 'is_correct' => true]];
    foreach ($distractors as $d) {
        $choices[] = ['text' => $d, 'is_correct' => false];
    }
    shuffle($choices);

    return ['question_text' => $questionText, 'points' => 1.0, 'choices' => $choices];
}

/**
 * Presents the sentence as a statement and asks True or False. Half the
 * time it's shown unmodified (True); the other half, its key term is
 * swapped for a plausible wrong one (False) -- so the correct answer
 * isn't predictably "always True", which would let a student game the
 * question type itself instead of the content.
 */
function cloze_build_true_false(string $sentence, string $term, string $distractor): array
{
    $isTrue = (bool)random_int(0, 1);
    $statement = $isTrue ? $sentence : cloze_substitute_term($sentence, $term, $distractor);

    $choices = [
        ['text' => 'True', 'is_correct' => $isTrue],
        ['text' => 'False', 'is_correct' => !$isTrue],
    ];

    return [
        'question_text' => 'True or False: ' . $statement,
        'points' => 1.0,
        'choices' => $choices,
    ];
}

function cloze_substitute_term(string $sentence, string $term, string $replacement): string
{
    $pattern = '/\b' . preg_quote($term, '/') . '\b/u';
    return preg_replace($pattern, $replacement, $sentence, 1);
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

/** Every non-stopword, non-excluded, alphabetic-only word of >=4 letters, exact casing preserved (first occurrence wins). */
function cloze_candidate_terms(string $text, array $excludedKeys = []): array
{
    preg_match_all('/[A-Za-z][A-Za-z\'-]{3,}/', $text, $matches);
    $terms = [];
    foreach ($matches[0] as $word) {
        $clean = trim($word, "'-");
        if (mb_strlen($clean) < 4) {
            continue;
        }
        $key = mb_strtolower($clean);
        if (in_array($key, CLOZE_STOPWORDS, true) || isset($excludedKeys[$key])) {
            continue;
        }
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
 * would otherwise pick "Thank" just because it starts the sentence).
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
