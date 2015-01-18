<?php
class Helper {

	public static function extractWikiLinksFromContent($content) {
		if (preg_match_all('/\[\[([^]]+)\]\]/', $content, $matches)) {
			return $matches[1];
		}
		return [];
	}

	public static function getWikiListWithHeaders($pageTitles, $itemCallback = null, $prefix = '', $suffix = '') {
		$list = '';

		if ($itemCallback === null) {
			$itemCallback = function($title) {
				return "* [[$title]]";
			};
		}
		$curFirstChar = '';
		foreach ($pageTitles as $pageTitle => $pageData) {
			if (is_numeric($pageTitle)) {
				$pageTitle = $pageData;
			}
			$firstChar = self::firstChar($pageTitle);
			if ($curFirstChar != $firstChar) {
				$curFirstChar = $firstChar;
				$list .= "$suffix\n\n== $curFirstChar ==\n\n$prefix\n";
			}

			$list .= $itemCallback($pageTitle, $pageData) . "\n";
		}

		$list .= "$suffix\n";

		return $list;
	}

	private static function firstChar($string) {
		$codes = [
			'[\x00-\x7f]',
			'[\xc0-\xdf][\x80-\xbf]',
			'[\xe0-\xef][\x80-\xbf]{2}',
			'[\xf0-\xf7][\x80-\xbf]{3}',
		];
		$re = '/^('.implode('|', $codes).')/';
		if (preg_match($re, $string, $matches)) {
			return $matches[1];
		}
		return '';
	}
}
