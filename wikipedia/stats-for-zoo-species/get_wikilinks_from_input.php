<?php
require_once __DIR__.'/../helpers.php';

$file = __DIR__.'/input-enriched.wiki';
$content = file_get_contents($file);
// remove non-existent pages
$content = preg_replace('/.+ ×/', '', $content);
return extractWikiLinksFromContent($content);
