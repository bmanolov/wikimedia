<?php
require_once __DIR__.'/../helpers.php';

$outputFile = __DIR__.'/view_stats.csv';

$inputPages = require __DIR__.'/get_wikilinks_from_input.php';
$dates = require __DIR__.'/dates.php';

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

file_put_contents($outputFile, generateCsv($stats, $dates));
echo "CSV data written to $outputFile.\n";


function generateCsv($stats, $dates) {
	$csv = '';
	$csv .= "Вид;" . implode(";", $dates) . "\n";
	foreach ($stats as $article => $counts) {
		$csv .= "$article;".implode(";", $counts) . "\n";
	}
	return $csv;
}

function getMonthlySumFromJsonData($jsonData) {
	$data = json_decode($jsonData, true);
	return array_sum($data['daily_views']);
}
