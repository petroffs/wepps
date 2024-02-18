<?php
namespace WeppsAdmin\ConfigExtensions\Example;

use WeppsCore\Utils\RequestWepps;
use WeppsCore\Core\SmartyWepps;
use WeppsCore\Utils\TemplateHeadersWepps;
use WeppsCore\Exception\ExceptionWepps;

class RequestExampleWepps extends RequestWepps {
	public function request($action="") {
		$smarty = SmartyWepps::getSmarty();
		$tpl = 'Example.tpl';
		$this->title = $this->get['ext']['Name'];
		$this->way = array(0=>array('Url'=>"/_pps/extensions/{$this->get['ext']['Alias']}/",'Name'=>$this->title));
		$headers = new TemplateHeadersWepps();
		$headers->js ("/packages/WeppsAdmin/ConfigExtensions/Example/Example.{$headers::$rand}.js");
		$headers->css ("/packages/WeppsAdmin/ConfigExtensions/Example/Example.{$headers::$rand}.css");
		switch ($action) {
			case 'tets':
				$this->title = "Установка";
				$tpl = 'ExampleTest.tpl';
				break;
			default:
				if ($action!="") {
					ExceptionWepps::error404();
				}
				break;
		}
		$this->headers = &$headers;
		$this->tpl = $smarty->fetch( __DIR__ . '/' . $tpl);
	}
}
?>