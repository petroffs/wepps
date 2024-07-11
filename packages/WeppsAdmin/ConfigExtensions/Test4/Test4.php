<?php
namespace WeppsAdmin\ConfigExtensions\Test4;

use WeppsCore\Utils\RequestWepps;
use WeppsCore\Core\SmartyWepps;
use WeppsCore\Utils\TemplateHeadersWepps;
use WeppsCore\Exception\ExceptionWepps;

class Test4Wepps extends RequestWepps {
	public function request($action="") {
		$smarty = SmartyWepps::getSmarty();
		$this->tpl = 'Test4.tpl';
		$this->title = $this->get['ext']['Name'];
		$this->way = array(0=>array('Url'=>"/_pps/extensions/{$this->get['ext']['Alias']}/",'Name'=>$this->title));
		$headers = new TemplateHeadersWepps();
		$headers->js ("/packages/WeppsAdmin/ConfigExtensions/Test4/Test4.{$headers::$rand}.js");
		$headers->css ("/packages/WeppsAdmin/ConfigExtensions/Test4/Test4.{$headers::$rand}.css");
		switch ($action) {
			case 'test':
				$this->title = "Установка";
				$this->tpl = 'Test4Test.tpl';
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
?>