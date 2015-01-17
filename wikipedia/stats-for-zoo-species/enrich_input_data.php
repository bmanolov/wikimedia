<?php
$db = require __DIR__.'/../db.php';

$inputFile = __DIR__.'/input.wiki';
$outputFile = __DIR__.'/input-enriched.wiki';

$inputContent = file_get_contents($inputFile);
if (!preg_match_all('/\[\[([^]]+)\]\]/', $inputContent, $matches)) {
	die("No input pages in file '$inputFile'");
}
$inputPages = $matches[1];

$sql = 'SELECT p.page_title, r.rd_title
	FROM page p
	LEFT JOIN redirect r ON p.page_id = r.rd_from
	WHERE p.page_is_redirect = 1 AND p.page_title IN ('.rtrim(str_repeat('?,', count($inputPages)), ',').')';
$st = $db->prepare($sql);
$st->execute(array_map('pageNameForDb', $inputPages));
$redirectsWithTargets = $st->fetchAll(PDO::FETCH_KEY_PAIR);

$enrichedInput = $inputContent;
foreach ($redirectsWithTargets as $redirect => $targetPage) {
	$redirect = pageNameFromDb($redirect);
	$targetPage = pageNameFromDb($targetPage);
	$enrichedInput = str_replace("[[$redirect]]", "[[$redirect]] ☛ [[$targetPage]]", $enrichedInput);
	$enrichedInput = str_replace("* [[$targetPage]]\n", '', $enrichedInput);
}

file_put_contents($outputFile, $enrichedInput);
