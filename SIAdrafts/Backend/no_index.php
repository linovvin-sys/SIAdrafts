<?php
/**
 * What a bare folder request (…/Admission/, …/Backend/api/Treasury/, etc.)
 * is internally rewritten to by .htaccess. A 204 No Content, empty body —
 * per the HTML spec, a top-level navigation that gets a 204 response is
 * simply discarded: the browser does not navigate away, the page already
 * on screen stays exactly as it was. No listing, no error page, no visible
 * change at all — which is the actual behaviour asked for here, stronger
 * than a 403 (a 403 at least confirms "a real folder exists here"; this
 * doesn't even do that).
 */
http_response_code(204);
exit;
