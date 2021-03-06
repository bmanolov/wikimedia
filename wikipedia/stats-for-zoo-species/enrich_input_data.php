<?php
require __DIR__.'/../../vendor/autoload.php';

$db = Master::createDb();

$inputFile = __DIR__.'/input.wiki';
$outputFile = __DIR__.'/input-enriched.wiki';

$inputContent = file_get_contents($inputFile);
$inputPages = Helper::extractWikiLinksFromContent($inputContent);
if (empty($inputPages)) {
	die("No input pages in file '$inputFile'");
}

$sql = 'SELECT p.page_title, r.rd_title
	FROM page p
	LEFT JOIN redirect r ON p.page_id = r.rd_from
	WHERE p.page_is_redirect = 1 AND p.page_title IN ('.rtrim(str_repeat('?,', count($inputPages)), ',').')';
$st = $db->prepare($sql);
$st->execute(array_map('Db::pageNameForDb', $inputPages));
$redirectsWithTargets = $st->fetchAll(PDO::FETCH_KEY_PAIR);

$enrichedInput = $inputContent;
foreach ($redirectsWithTargets as $redirect => $targetPage) {
	$redirect = Db::pageNameFromDb($redirect);
	$targetPage = Db::pageNameFromDb($targetPage);
	$enrichedInput = str_replace("[[$redirect]]", "[[$redirect]] ☛ [[$targetPage]]", $enrichedInput);
	$enrichedInput = str_replace("* [[$targetPage]]\n", '', $enrichedInput);
}

file_put_contents($outputFile, $enrichedInput);
