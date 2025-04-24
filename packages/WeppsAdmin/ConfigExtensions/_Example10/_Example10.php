<?php
namespace WeppsAdmin\ConfigExtensions\_Example10;

use WeppsCore\Utils\RequestWepps;
use WeppsCore\Core\SmartyWepps;
use WeppsCore\Utils\TemplateHeadersWepps;
use WeppsCore\Exception\ExceptionWepps;

class _Example10Wepps extends RequestWepps {
	public function request($action="") {
		$smarty = SmartyWepps::getSmarty();
		$this->tpl = '_Example10.tpl';
		$this->title = $this->get['ext']['Name'];
		$this->way = [[	'Url'=>"/_wepps/extensions/{$this->get['ext']['Alias']}/",
						'Name'=>$this->title]];
		$smarty->assign('test1','test1');
		$smarty->assign('url','/packages/WeppsAdmin/ConfigExtensions/_Example10/Request.php');
		$headers = new TemplateHeadersWepps();
		$headers->js ("/packages/WeppsAdmin/ConfigExtensions/_Example10/_Example10.{$headers::$rand}.js");
		$headers->css ("/packages/WeppsAdmin/ConfigExtensions/_Example10/_Example10.{$headers::$rand}.css");
		switch ($action) {
			case 'examplelink':
				$this->title = "Тест";
				$this->tpl = '_Example10Test.tpl';
				break;
			default:
				if ($action!="") {
					ExceptionWepps::error404();
				}
				break;
		}
		$this->headers = &$headers;
	}
}