<?php
/**
 * Extracts plain text from an uploaded lesson file so cloze_generator.php
 * can turn it into quiz questions. Same validation shape as
 * Backend/api/Assignments/submission_attachments.php -- finfo-sniffed
 * real bytes, never the client-supplied name/Content-Type -- but this
 * file is processed in-memory from tmp_name and never permanently
 * stored; generation is a one-shot action, not a stored asset.
 */

const LESSON_UPLOAD_MAX_BYTES = 20 * 1024 * 1024; // 20 MB

const LESSON_UPLOAD_ALLOWED_TYPES = [
    'application/pdf' => 'pdf',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
];

// getElementsByTagName('w:p')/('a:t') unreliably matches 0 elements even
// though nodeName is literally "w:p" -- libxml's non-namespace-aware
// lookup doesn't resolve a prefix consistently. getElementsByTagNameNS
// with the real OOXML namespace URI (not just relying on a "w:"/"a:"
// prefix, which a file could alias differently) is the reliable lookup.
const WORDML_NS = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
const DRAWINGML_NS = 'http://schemas.openxmlformats.org/drawingml/2006/main';

/** Returns ['text' => string] on success, or ['error' => string] on failure. */
function extract_text_from_upload(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['error' => 'Please choose a lesson file.'];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'File upload failed.'];
    }
    if ($file['size'] > LESSON_UPLOAD_MAX_BYTES) {
        return ['error' => 'File is too large (20 MB max).'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);

    if (!isset(LESSON_UPLOAD_ALLOWED_TYPES[$mime])) {
        return ['error' => 'Upload a PDF, DOCX, or PPTX file.'];
    }

    switch (LESSON_UPLOAD_ALLOWED_TYPES[$mime]) {
        case 'pdf':
            return extract_pdf_text($file['tmp_name']);
        case 'docx':
            return extract_docx_text($file['tmp_name']);
        case 'pptx':
            return extract_pptx_text($file['tmp_name']);
        default:
            return ['error' => 'Upload a PDF, DOCX, or PPTX file.'];
    }
}

/**
 * A letterhead/watermark line (school name, address, "Page X of Y") that
 * a PDF or slide deck repeats on nearly every page/slide isn't lesson
 * content -- fed to the cloze generator like any other sentence, it
 * produces a nonsense "fill in the blank" question about the school's own
 * name. Any line seen on at least half of a document's pages/slides (and
 * there are enough pages/slides for "repeated" to be a meaningful signal,
 * not just 1-2 sentences that happen to coincide) is dropped everywhere
 * it appears, not just the first occurrence -- the masthead is the
 * problem regardless of which page shows it.
 */
function remove_repeated_boilerplate_lines(array $perPageLines): array
{
    $pageCount = count($perPageLines);
    if ($pageCount < 3) {
        return $perPageLines;
    }

    $counts = [];
    foreach ($perPageLines as $lines) {
        $seenOnThisPage = [];
        foreach ($lines as $line) {
            $key = mb_strtolower(trim($line));
            if ($key === '' || isset($seenOnThisPage[$key])) {
                continue;
            }
            $seenOnThisPage[$key] = true;
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }
    }

    $threshold = max(3, (int)ceil($pageCount / 2));
    $boilerplate = [];
    foreach ($counts as $key => $count) {
        if ($count >= $threshold) {
            $boilerplate[$key] = true;
        }
    }

    if (empty($boilerplate)) {
        return $perPageLines;
    }

    return array_map(
        fn($lines) => array_values(array_filter($lines, fn($line) => !isset($boilerplate[mb_strtolower(trim($line))]))),
        $perPageLines
    );
}

function extract_pdf_text(string $tmpPath): array
{
    $autoload = __DIR__ . '/../../vendor/autoload.php';
    if (is_file($autoload)) {
        require_once $autoload;
    }
    if (!class_exists('\Smalot\PdfParser\Parser')) {
        return ['error' => "PDF support isn't installed on this server yet. Run: composer require smalot/pdfparser (DOCX/PPTX work without this)."];
    }
    try {
        $parser = new \Smalot\PdfParser\Parser();
        $pdf    = $parser->parseFile($tmpPath);

        $pageLines = [];
        foreach ($pdf->getPages() as $page) {
            $lines = preg_split('/\r\n|\r|\n/', $page->getText());
            $lines = array_values(array_filter(array_map('trim', $lines), fn($l) => $l !== ''));
            $pageLines[] = $lines;
        }

        $pageLines = remove_repeated_boilerplate_lines($pageLines);
        $pageTexts = array_filter(array_map(fn($lines) => implode("\n", $lines), $pageLines), fn($t) => $t !== '');

        return ['text' => implode("\n\n", $pageTexts)];
    } catch (\Throwable $e) {
        error_log('extract_pdf_text: ' . $e->getMessage());
        return ['error' => 'Could not read that PDF. It may be scanned/image-only, encrypted, or corrupted.'];
    }
}

/**
 * A .docx is a ZIP of XML parts. word/document.xml holds the body text,
 * with each paragraph as a <w:p> element containing one or more <w:t>
 * text-run nodes. Concatenating raw stripped tags (instead of walking
 * <w:p> boundaries) would lose paragraph/sentence breaks the cloze
 * generator relies on to split sentences correctly.
 */
function extract_docx_text(string $tmpPath): array
{
    $zip = new ZipArchive();
    if ($zip->open($tmpPath) !== true) {
        return ['error' => 'Could not open that DOCX file.'];
    }
    $xml = $zip->getFromName('word/document.xml');
    $zip->close();
    if ($xml === false) {
        return ['error' => 'That DOCX file has no readable document content.'];
    }

    $dom = new DOMDocument();
    $prevErrors = libxml_use_internal_errors(true);
    $dom->loadXML($xml);
    libxml_use_internal_errors($prevErrors);

    $paragraphs = [];
    foreach ($dom->getElementsByTagNameNS(WORDML_NS, 'p') as $p) {
        $text = '';
        foreach ($p->getElementsByTagNameNS(WORDML_NS, 't') as $t) {
            $text .= $t->textContent;
        }
        $text = trim($text);
        if ($text !== '') {
            $paragraphs[] = $text;
        }
    }

    return ['text' => implode("\n", $paragraphs)];
}

/**
 * A .pptx is a ZIP with one XML part per slide under ppt/slides/. Text
 * runs live in <a:t> (drawingml) elements. Slide filenames must be
 * sorted numerically, not as strings -- "slide10.xml" sorts before
 * "slide2.xml" alphabetically, which would scramble document order.
 */
function extract_pptx_text(string $tmpPath): array
{
    $zip = new ZipArchive();
    if ($zip->open($tmpPath) !== true) {
        return ['error' => 'Could not open that PPTX file.'];
    }

    $slideNumbers = [];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        if (preg_match('#^ppt/slides/slide(\d+)\.xml$#', $name, $m)) {
            $slideNumbers[(int)$m[1]] = $name;
        }
    }
    ksort($slideNumbers, SORT_NUMERIC);

    // Segmented by <a:p> paragraph (not flattened across the whole slide)
    // so a repeated masthead text box -- its own paragraph, separate from
    // the actual slide content -- can be detected and dropped as a single
    // line, rather than being merged into one run-on string per slide.
    $slideLines = [];
    foreach ($slideNumbers as $name) {
        $xml = $zip->getFromName($name);
        if ($xml === false) {
            continue;
        }
        $dom = new DOMDocument();
        $prevErrors = libxml_use_internal_errors(true);
        $dom->loadXML($xml);
        libxml_use_internal_errors($prevErrors);

        $lines = [];
        foreach ($dom->getElementsByTagNameNS(DRAWINGML_NS, 'p') as $para) {
            $runs = [];
            foreach ($para->getElementsByTagNameNS(DRAWINGML_NS, 't') as $t) {
                $text = trim($t->textContent);
                if ($text !== '') {
                    $runs[] = $text;
                }
            }
            if (!empty($runs)) {
                $lines[] = implode(' ', $runs);
            }
        }
        $slideLines[] = $lines;
    }
    $zip->close();

    $slideLines = remove_repeated_boilerplate_lines($slideLines);
    $slideTexts = array_filter(array_map(fn($lines) => implode("\n", $lines), $slideLines), fn($t) => $t !== '');

    return ['text' => implode("\n\n", $slideTexts)];
}
