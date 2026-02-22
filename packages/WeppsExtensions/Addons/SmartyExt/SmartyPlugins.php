<?php
namespace WeppsExtensions\Addons\SmartyExt;

use Smarty\Smarty;
use WeppsCore\Connect;

class SmartyPlugins
{
	public function __construct(Smarty $smarty)
	{
		$smarty->registerPlugin('modifier', 'split', function ($string, $delimiter = ',') {
			return explode($delimiter, $string);
		});
		$smarty->registerPlugin('modifier', 'json_decode', function ($jsonString, $assoc = true) {
			return json_decode($jsonString, $assoc);
		});
		$smarty->registerPlugin('modifier', 'isset', function ($var) {
			return (isset($var)) ? 1 : 0;
		});
		$smarty->registerPlugin('modifier', 'strstr', function ($haystack = '', $needle = '') {
			return strstr($haystack, $needle);
		});
		$smarty->registerPlugin('modifier', 'stristr', function ($haystack = '', $needle = '') {
			return stristr($haystack, $needle);
		});
		$smarty->registerPlugin('modifier', 'array_slice', function (array $array, int $offset) {
			return array_slice($array, $offset);
		});
		$smarty->registerPlugin('modifier', 'strarr', function (?string $string, $delimiter = ':::') {
			if ($string === null) {
				return [];
			}
			return explode($delimiter, $string);
		});
		$smarty->registerPlugin('modifier', 'format', function ($string, $br = '') {
			$string = preg_replace('/((http|ftp|telnet|gopher):\/\/[^ \n$]+)(|.html)/iu', '<A href="\\1\\3" target="_blank">\\1\\3</A>', $string);
			$search = ["' - '", "' с '", "' в '", "' и '", "' а '", "' не '", "' для '", "' на '", "' без '", "' к '", "' о '", "' об '", "' от '", "' по '", "' при '", "' про '", "' у '", "' из '", "' за '"];
			$replace = ["&nbsp;&mdash; ", " с&nbsp;", " в&nbsp;", " и&nbsp;", " а&nbsp;", " не&nbsp;", " для&nbsp;", " на&nbsp;", " без&nbsp;", " к&nbsp;", " о&nbsp;", " об&nbsp;", " от&nbsp;", " по&nbsp;", " при&nbsp;", " про&nbsp;", " у&nbsp;", " из&nbsp;", " за&nbsp;"];
			if ($br == 'br') {
				$search[] = "'\n|\r\n'";
				$replace[] = "<br/>";
			}
			$string = preg_replace($search, $replace, $string);
			return $string;
		});
		$smarty->registerPlugin('modifier', 'money', function ($string, $float = 0) {
			if (!is_numeric($string))
				return 0;
			$tmp = "";
			if (strstr($string, "от ") !== false) {
				$tmp = "от ";
				$string = str_replace("от ", "", $string);
			}
			return $tmp . number_format($string, $float, ".", " ");
		});
		$smarty->registerPlugin('modifier', 'number', function ($string) {
			return sprintf("%04d", $string);
		});
		$smarty->registerPlugin('modifier', 'wepps', function ($id, string $tablename, string $panel = '') {
			$str = '';
			$user = @Connect::$projectData['user']['ShowAdmin'];
			if ($user != 1) {
				return $str;
			}
			switch ($tablename) {
				case "navigator":
					$str = (string) '<div class="navigator w_admin_navigator"><a href="/_wepps/navigator' . $id . '" target="_blank"></a></div>';
					break;
				case "panels":
					$str = (string) '<div class="w_admin_list w_admin_panels">
                            <a href="/_wepps/lists/s_Panels/' . $panel . '/" target="_blank" title="Редактировать панель"></a>
                            <a href="/_wepps/lists/s_Panels/add/?NavigatorId=' . $id . '" target="_blank" title="Добавить панель"></a>
                            <a href="/_wepps/lists/s_Blocks/add/?PanelId=' . $panel . '" target="_blank" title="Добавить блок"></a>
                        </div>';
					break;
				default:
					$str = (string) '<div class="default w_admin_list"><a href="/_wepps/lists/' . $tablename . '/' . $id . '/" target="_blank"></a></div>';
					break;
			}
			return $str;
		});
	}
}