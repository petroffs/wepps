<?
namespace WeppsAdmin\ConfigExtensions;

use WeppsCore\Core\SmartyWepps;
use WeppsCore\Utils\TemplateHeadersWepps;
use WeppsCore\Utils\UtilsWepps;
use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Core\DataWepps;
use WeppsCore\Exception\ExceptionWepps;
use WeppsAdmin\Admin\AdminWepps;

class ConfigExtensionsWepps {
	function __construct(TemplateHeadersWepps &$headers) {
		$smarty = SmartyWepps::getSmarty();
		$headers->js ("/packages/WeppsAdmin/ConfigExtensions/ConfigExtensions.{$headers::$rand}.js");
		$headers->css ("/packages/WeppsAdmin/ConfigExtensions/ConfigExtensions.{$headers::$rand}.css");
		$tpl2 = "ConfigExtensions.tpl";
		$ppsUrl = "/".$_GET['ppsUrl'];
		$ppsUrlEx = explode("/", trim($ppsUrl,'/'));
		$perm = AdminWepps::userPerm($_SESSION['user']['UserPermissions']);
		$fcond = "'".implode("','", $perm['extensions'])."'";
		$objExt = new DataWepps("s_ConfigExtensions");
		$extensions = $objExt->getMax("t.DisplayOff=0 and t.Id in ($fcond)",2000);
		
		if (!isset($ppsUrlEx[1])) {
			$content = array();
			$content['MetaTitle'] = "Системные расширения";
			$content['Name'] = "Все системные расширения";
			$content['NameNavItem'] = "Системные расширения";
		} elseif (isset($ppsUrlEx[1])) {
			$res = $objExt->getMax("t.KeyUrl='{$ppsUrlEx[1]}'");
			
			if (!isset($res[0]['Id'])) {
				ExceptionWepps::error404();
			}
			$ext = $res[0];
			
			$content = array();
			$content['MetaTitle'] = "{$ext['Name']} — Системные расширения";
			$content['Name'] = $ext['Name'];
			$content['NameNavItem'] = "Системные расширения";
			
			/*
			 * Включение расширения
			 */
			$action = "";
			if (strstr($_GET['ppsUrl'], ".html")) {
				$action = substr($_GET['ppsUrl'],strrpos($_GET['ppsUrl'],"/",0)+1);
				$action = substr($action, 0, -5);
				$smarty->assign('extsActive',$action);
			}
			if ($action=="" && $_GET['ppsUrl']!="extensions/{$ext['KeyUrl']}/") {
				ExceptionWepps::error404();
			}
			$request = array('action'=>$action);
			$request = array_merge($request,$_REQUEST,array('ext'=>$ext));
			$extClass = "\WeppsAdmin\\ConfigExtensions\\{$ext['KeyUrl']}\\{$ext['KeyUrl']}Wepps";
			$extResult = new $extClass ($request);
			$smarty->assign('ext',$smarty->fetch( __DIR__ . '/' . $ext['KeyUrl'] . '/' . $extResult->tpl));
			$smarty->assign('way',$extResult->way);
			$content['Name'] = $extResult->title;
			if (isset($extResult->headers) && !empty($extResult->headers)) {
				$headers->join($extResult->headers);
			}
		}
		$smarty->assign('exts',$extensions);
		$smarty->assign('extsNavTpl', $smarty->fetch( ConnectWepps::$projectDev['root'] . '/packages/WeppsAdmin/ConfigExtensions/ConfigExtensionsNav.tpl'));
		
		$smarty->assign('content', $content);
		$smarty->assign('headers', $headers->get());
		$tpl = $smarty->fetch(__DIR__ . '/' . $tpl2);
		$smarty->assign('extension', $tpl);
	}
}
?>