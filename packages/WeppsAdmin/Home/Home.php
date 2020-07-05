<?
namespace WeppsAdmin\Home;

use WeppsCore\Utils\UtilsWepps;
use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Core\SmartyWepps;
use WeppsCore\Utils\TemplateHeadersWepps;

class HomeWepps {
	function __construct(TemplateHeadersWepps &$headers,$nav) {
		$smarty = SmartyWepps::getSmarty();
		$headers->js ("/packages/WeppsAdmin/Home/Home.{$headers::$rand}.js");
		$headers->css ("/packages/WeppsAdmin/Home/Home.{$headers::$rand}.css");
		$tpl2 = "Home.tpl";
		
		if (!isset($_SESSION['user']) || !isset($_SESSION['user']['ShowAdmin']) || $_SESSION['user']['ShowAdmin'] != 1) {
			$tpl2 = "Welcome.tpl";
			$content = array();
			$content['MetaTitle'] = "Вход в систему — Wepps";
			$content['Name'] = "Введите логин и пароль";
			$content['NameNavItem'] = "Вход в систему Wepps";
		} else {
			
			/*
			 * logic
			 */
			$content = array();
			$content['MetaTitle'] = "Добро пожаловать — Wepps";
			$content['Name'] = "Главная";
			$content['NameNavItem'] = "Wepps";
			
			//UtilsWepps::debug($nav);
			unset($nav['home']);
			$smarty->assign('navhome',$nav);
			
			$sql = "select ";
			//$items = ConnectWepps::$instance->fetch($sql);
			
		}
		
		$smarty->assign('content', $content);
		$smarty->assign('headers', $headers->get());
		$tpl = $smarty->fetch(__DIR__ . '/' . $tpl2);
		$smarty->assign('extension', $tpl);
	}
}
?>