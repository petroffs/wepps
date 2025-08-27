<?php
namespace WeppsAdmin\ConfigExtensions;

use WeppsCore\Smarty;
use WeppsCore\TemplateHeaders;
use WeppsCore\Utils;
use WeppsCore\Connect;
use WeppsCore\Data;
use WeppsCore\Exception;
use WeppsAdmin\Admin\Admin;

class ConfigExtensions
{
	private $extensions;
	public function __construct(TemplateHeaders &$headers)
	{
		$smarty = Smarty::getSmarty();
		$headers->js("/packages/WeppsAdmin/ConfigExtensions/ConfigExtensions.{$headers::$rand}.js");
		$headers->css("/packages/WeppsAdmin/ConfigExtensions/ConfigExtensions.{$headers::$rand}.css");
		$tpl2 = "ConfigExtensions.tpl";
		$this->getExtensionsEnv();
		$ppsUrl = "/" . $_GET['ppsUrl'];
		$ppsUrlEx = explode("/", trim($ppsUrl, '/'));
		if (!isset($ppsUrlEx[1])) {
			$content = [
				'MetaTitle' => "Системные расширения",
				'Name' => "Все системные расширения",
				'NameNavItem' => "Системные расширения"
			];
		} elseif (isset($ppsUrlEx[1]) && isset($this->extensions[$ppsUrlEx[1]])) {
			$ext = $this->extensions[$ppsUrlEx[1]];
			$content = [
				'MetaTitle' => "{$ext['Name']} — Системные расширения",
				'Name' => $ext['Name'],
				'NameNavItem' => "Системные расширения"
			];

			/*
			 * Включение расширения
			 */
			$action = "";
			if (strstr($_GET['ppsUrl'], ".html")) {
				$action = substr($_GET['ppsUrl'], strrpos($_GET['ppsUrl'], "/", 0) + 1);
				$action = substr($action, 0, -5);
				$smarty->assign('extsActive', $action);
			}
			if ($action == "" && $_GET['ppsUrl'] != "extensions/{$ext['Alias']}/") {
				Exception::error404();
			}
			$request = ['action' => $action];
			$request = array_merge($request, $_REQUEST, array('ext' => $ext));
			$extClass = "\WeppsAdmin\\ConfigExtensions\\{$ext['Alias']}\\{$ext['Alias']}";
			$extResult = new $extClass($request);
			$smarty->assign('ext', $ext);
			$smarty->assign('extNavSubTpl', $smarty->fetch(__DIR__ . '/' . 'ConfigExtensionsNavSub.tpl'));
			$smarty->assign('extTpl', $smarty->fetch(__DIR__ . '/' . $ext['Alias'] . '/' . $extResult->tpl));
			$smarty->assign('way', $extResult->way);
			$content['Name'] = $extResult->title;
			if (!empty($extResult->headers)) {
				$headers->join($extResult->headers);
			}
		} elseif (isset($ppsUrlEx[1])) {
			Exception::error404();
		}
		$smarty->assign('exts', $this->extensions);
		$smarty->assign('extsNavTpl', $smarty->fetch(Connect::$projectDev['root'] . '/packages/WeppsAdmin/ConfigExtensions/ConfigExtensionsNav.tpl'));
		$smarty->assign('content', $content);
		$tpl = $smarty->fetch(__DIR__ . '/' . $tpl2);
		$smarty->assign('extension', $tpl);
	}
	private function getExtensionsEnv()
	{
		$perm = Admin::getPermissions(Connect::$projectData['user']['UserPermissions']);
		$fcond = "'" . implode("','", $perm['extensions']) . "'";
		$objExt = new Data("s_ConfigExtensions");
		$extensions = $objExt->fetch("t.DisplayOff=0 and t.Id in ($fcond)", 2000);
		$this->extensions = [];
		foreach ($extensions as $value) {
			$value['ENavArr'] = Utils::arrayFromString($value['ENav'], ":::");
			$this->extensions[$value['Alias']] = $value;
		}
	}
}