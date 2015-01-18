<?php
require __DIR__.'/../helpers.php';

$db = require __DIR__.'/../db.php';

$sql = "SELECT page_namespace ns, page_title page, old_text text
	FROM page
	LEFT JOIN revision r ON page_latest = rev_id
	LEFT JOIN text t ON rev_text_id = old_id
	WHERE page_namespace = 0 and page_is_redirect = 0";

$personsWoPicture = [];
$alivePersonsWoPicture = [];
$deadPersonsAfterYear2000WoPicture = [];

foreach ($db->query($sql) as $row) {
	if (!isBulgarian($row['text'])) {
		continue;
	}
	if (hasPhoto($row['text'])) {
		continue;
	}
	$pageName = pageNameFromDb($row['page']);
	$yearOfBirth = getYearOfBirth($row['text']);
	$yearOfDeath = getYearOfDeath($row['text']);
	$personsWoPicture[$pageName] = [
		'birth' => $yearOfBirth,
		'death' => $yearOfDeath,
	];
	if ($yearOfDeath === null && $yearOfBirth > 1900) {
		$alivePersonsWoPicture[$pageName] = [
			'birth' => $yearOfBirth,
			'death' => $yearOfDeath,
		];
	}
	if ($yearOfDeath >= 2000) {
		$deadPersonsAfterYear2000WoPicture[$pageName] = [
			'birth' => $yearOfBirth,
			'death' => $yearOfDeath,
		];
	}
}

$listPrefix = '<div style="-moz-column-count: 2; -webkit-column-count: 2; column-count: 2">';
$listSuffix = '</div>';
$listItemCallback = function($title, $data) {
	$years = rtrim($data['birth'] .'-'. $data['death'], '-');
	$suffix = $years ? " <small>($years)</small>" : '';
	return "# [[$title]]" . $suffix;
};

echo "= ББИ =\n\n";
echo getWikiListWithHeaders($personsWoPicture, $listItemCallback, $listPrefix, $listSuffix);

echo "\n\n";
echo "= ББИ 1 =\n\n";
echo getWikiListWithHeaders($alivePersonsWoPicture, $listItemCallback, $listPrefix, $listSuffix);

echo "\n\n";
echo "= ББИ 2 =\n\n";
echo getWikiListWithHeaders($deadPersonsAfterYear2000WoPicture, $listItemCallback, $listPrefix, $listSuffix);

################################################################################


function isBulgarian($text) {
	$ctext = clearText($text);
	return preg_match('/(роден.?дата=|раждане=)/', $ctext) // is person
		&& preg_match('/Категория:Българи|Категория:Български|(роден|раждане).+България/', $ctext);
}

function hasPhoto($text) {
	return preg_match('/(портрет|снимка)=(\S+)/', clearText($text), $m);
}

function getYearOfBirth($text) {
	if (preg_match('/(роден.?дата|раждане)=.+(\d{4})/', clearText($text), $matches)) {
		return $matches[2];
	}
	return null;
}

function getYearOfDeath($text) {
	if (preg_match('/(починал.?дата|смъртта)=.+(\d{4})/U', clearText($text), $matches)) {
		return $matches[2];
	}
	return null;
}

function clearText($text) {
	$text = strtr($text, array(
		'dot.png' => '',
		' '       => '', // all spaces are stripped
		'[[]]'    => '',
		"\t"      => '',
	));
	return $text;
}
