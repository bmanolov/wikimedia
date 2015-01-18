<?php
$dates = [];
foreach (range(2010, 2014) as $year) {
	foreach (range(1, 12) as $month) {
		$dates[] = $year .'-'. str_pad($month, 2, '0', STR_PAD_LEFT);
	}
}
return $dates;
