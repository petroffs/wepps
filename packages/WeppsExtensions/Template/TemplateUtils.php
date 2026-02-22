<?php
namespace WeppsExtensions\Template;

use WeppsCore\Connect;
use WeppsCore\Navigator;
use WeppsCore\TemplateHeaders;
use WeppsCore\Utils;

class TemplateUtils extends Utils {
	public static function test() {
		self::debug(1,21);
	}

	public static function getMeta(Navigator &$navigator, TemplateHeaders &$headers)
	{
		$host = Connect::$projectDev['host'];
		if (isset($_GET['weppsurl'])) {
			$navigator->content['Canonical'] = "https://" . $host . "{$_GET['weppsurl']}";
		} else {
			$navigator->content['Canonical'] = "https://" . $host;
		}
		$metaimage = [
			"src" => "https://" . $host . "/ext/Template/files/cover.png",
			"width" => '640',
			"height" => '480',
		];
		if (!empty($navigator->content['Images_FileUrl'])) {
			$metaimage = [
				"src" => "https://" . $host . '/pic/mediumsq' . $navigator->content['Images_FileUrl'],
				"width" => '600',
				"height" => '600',
			];
		}
		$headers->meta("<link rel=\"canonical\" href=\"{$navigator->content['Canonical']}\">");
		$headers->meta("<meta property=\"og:type\" content=\"website\">");
		$headers->meta("<meta property=\"og:site_name\" content=\"" . Connect::$projectInfo['name'] . "\">");
		$headers->meta("<meta property=\"og:title\" content=\"{$navigator->content['MetaTitle']}\">");
		$headers->meta("<meta property=\"og:description\" content=\"" . htmlspecialchars(strip_tags($navigator->content['MetaDescription'])) . "\">");
		$headers->meta("<meta property=\"og:url\" content=\"https://" . $host . ($_SERVER['REQUEST_URI'] ?? '') . "\">");
		$headers->meta("<meta property=\"og:locale\" content=\"ru_RU\">");
		$headers->meta("<meta property=\"og:image\" content=\"" . $metaimage['src'] . "\">");
		$headers->meta("<meta property=\"og:image:width\" content=\"" . $metaimage['width'] . "\">");
		$headers->meta("<meta property=\"og:image:height\" content=\"" . $metaimage['height'] . "\">");
		$headers->meta("<meta name=\"twitter:card\" content=\"summary_large_image\">");
		$headers->meta("<meta name=\"twitter:title\" content=\"{$navigator->content['MetaTitle']}\">");
		$headers->meta("<meta name=\"twitter:description\" content=\"" . htmlspecialchars(strip_tags($navigator->content['MetaDescription'])) . "\">");
		$headers->meta("<meta name=\"twitter:image:src\" content=\"" . $metaimage['src'] . "\">");
		$headers->meta("<meta name=\"twitter:url\" content=\"https://" . $host . ($_SERVER['REQUEST_URI'] ?? '') . "\">");
		$headers->meta("<meta name=\"twitter:domain\" content=\"" . $host . "\">");
		$headers->meta("<link itemprop=\"thumbnailUrl\" href=\"" . $metaimage['src'] . "\">");
	}
}