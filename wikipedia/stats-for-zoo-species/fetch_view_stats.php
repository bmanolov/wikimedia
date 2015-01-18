<?php
require __DIR__.'/../helpers.php';

$statsUrl = 'http://stats.grok.se/json/bg/{DATE}/{ARTICLE}';

$inputFile = __DIR__.'/input-enriched.wiki';
$inputPages = extractWikiLinksFromContent(file_get_contents($inputFile));

$dates = require __DIR__.'/dates.php';

$throttleSecs = 3;
foreach ($inputPages as $inputPage) {
	foreach ($dates as $date) {
		$localJsonFile = __DIR__."/data/$inputPage/$date.json";
		if (file_exists($localJsonFile) && filesize($localJsonFile) > 0) {
			continue;
		}
		if (!file_exists($localJsonDir = dirname($localJsonFile))) {
			mkdir($localJsonDir, 0755, true);
		}
		$url = strtr($statsUrl, [
			'{DATE}' => str_replace('-', '', $date),
			'{ARTICLE}' => urlencode(str_replace(' ', '_', $inputPage)),
		]);
		echo "fetching $inputPage for $date\n";
		$jsonData = file_get_contents($url);
		if ($jsonData) {
			file_put_contents($localJsonFile, $jsonData);
		}

		sleep($throttleSecs);
	}
}

echo "Done.\n";
