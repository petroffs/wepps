<?php
namespace WeppsAdmin\Home;

use WeppsCore\Connect;
use WeppsCore\Smarty;
use WeppsCore\TemplateHeaders;

class Home
{
	public function __construct(TemplateHeaders &$headers, $nav)
	{
		$smarty = Smarty::getSmarty();
		$headers->js("/packages/WeppsAdmin/Home/Home.{$headers::$rand}.js");
		$headers->css("/packages/WeppsAdmin/Home/Home.{$headers::$rand}.css");
		$tpl = "Home.tpl";
		if (!empty(Connect::$projectData['user']) && Connect::$projectData['user']['ShowAdmin'] == 1) {
			$content = [
				'MetaTitle' => 'Wepps',
				'Name' => 'Главная',
				'NameNavItem' => 'Wepps',
			];
			unset($nav['home']);
			$smarty->assign('navhome', $nav);
		} else {
			$tpl = "SignIn.tpl";
			$content = [
				'MetaTitle' => 'Вход в систему — Wepps',
				'Name' => 'Введите логин и пароль',
				'NameNavItem' => 'Вход в систему Wepps',
			];
		}
		$smarty->assign('content', $content);
		$tpl = $smarty->fetch(__DIR__ . '/' . $tpl);
		$smarty->assign('extension', $tpl);
	}
}