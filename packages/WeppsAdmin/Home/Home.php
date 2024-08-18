<?php
namespace WeppsAdmin\Home;

use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Core\SmartyWepps;
use WeppsCore\Utils\TemplateHeadersWepps;

class HomeWepps {
	function __construct(TemplateHeadersWepps &$headers,$nav) {
		$smarty = SmartyWepps::getSmarty();
		$headers->js ("/packages/WeppsAdmin/Home/Home.{$headers::$rand}.js");
		$headers->css ("/packages/WeppsAdmin/Home/Home.{$headers::$rand}.css");
		$tpl = "Home.tpl";
		if (!empty(ConnectWepps::$projectData['user']) && ConnectWepps::$projectData['user']['ShowAdmin']==1) {
			$content = [
					'MetaTitle' => 'Wepps',
					'Name' => 'Главная',
					'NameNavItem' => 'Wepps',
			];
			unset($nav['home']);
			$smarty->assign('navhome',$nav);
		} else {
			$tpl = "SignIn.tpl";
			$content = [
					'MetaTitle' => 'Вход в систему — Wepps',
					'Name' => 'Введите логин и пароль',
					'NameNavItem' => 'Вход в систему Wepps',
			];
		}
		$smarty->assign('content', $content);
		$smarty->assign('headers', $headers->get());
		$tpl = $smarty->fetch(__DIR__ . '/' . $tpl);
		$smarty->assign('extension', $tpl);
	}
}