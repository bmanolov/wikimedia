<?php
require __DIR__.'/../../vendor/autoload.php';

$outputFile = __DIR__.'/view_stats.csv';

$inputPages = require __DIR__.'/get_wikilinks_from_input.php';
$dates = require __DIR__.'/dates.php';

$redirects = [];
$input = file_get_contents(__DIR__.'/input-enriched.wiki');
if (preg_match_all('/\[\[([^]]+)\]\] ☛ \[\[([^]]+)\]\]/', $input, $matches)) {
	$redirects = array_combine($matches[1], $matches[2]);
}

$stats = [];
foreach ($inputPages as $inputPage) {
	foreach ($dates as $date) {
		$localJsonFile = __DIR__."/data/$inputPage/$date.json";
		if (!file_exists($localJsonFile)) {
			die("File '$localJsonFile' does not exist.\n");
		}
		if (filesize($localJsonFile) == 0) {
			die("File '$localJsonFile' is empty.\n");
		}
		$jsonData = file_get_contents($localJsonFile);
		$stats[$inputPage][$date] = getMonthlySumFromJsonData($jsonData);
	}
}

file_put_contents($outputFile, generateCsv($stats, $dates, $redirects));
echo "CSV data written to $outputFile.\n";


function generateCsv($stats, $dates, $redirects) {
	$csv = '';
	$csv .= generateCsvRow(array_merge(['Страница', 'Цел'], $dates));
	foreach ($stats as $article => $counts) {
		$redirectTarget = isset($redirects[$article]) ? $redirects[$article] : '';
		$csv .= generateCsvRow(array_merge([$article, $redirectTarget], $counts));
	}
	return $csv;
}

function generateCsvRow($data) {
	return implode(",", $data) . "\n";
}

function getMonthlySumFromJsonData($jsonData) {
	$data = json_decode($jsonData, true);
	return array_sum($data['daily_views']);
}
