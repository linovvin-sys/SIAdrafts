<?php

// Not used anywhere yet -- there is no XML parsing in this app today.
// This exists so that if one is ever added (a SOAP integration, an
// XML-based import, etc.), it's safe by construction instead of relying
// on whoever writes it to independently know the XXE-safe flags.
//
// PHP 8 already ships with libxml2 >= 2.9, which disables external
// entity substitution by default -- so the classic PHP<5.4-era
// libxml_disable_entity_loader() dance is no longer necessary and that
// function is actually deprecated/removed. The real remaining risk is a
// caller explicitly re-enabling entity/DTD processing (e.g. passing
// LIBXML_NOENT, or LIBXML_DTDLOAD together with LIBXML_DTDATTR) without
// realizing what that reopens. These wrappers just make the safe path
// the path of least resistance.

// Use in place of a bare simplexml_load_string() call.
function safe_load_xml_string(string $xml): SimpleXMLElement|false
{
    return simplexml_load_string(
        $xml,
        'SimpleXMLElement',
        LIBXML_NONET // never fetch anything over the network while parsing
    );
}

// Use in place of manually constructing + loading a DOMDocument.
function safe_load_xml_dom(string $xml): DOMDocument|false
{
    $dom = new DOMDocument();
    $ok  = $dom->loadXML($xml, LIBXML_NONET);

    return $ok ? $dom : false;
}
