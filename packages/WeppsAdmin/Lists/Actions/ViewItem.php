<?php
namespace WeppsAdmin\Lists\Actions;

use WeppsCore\Request;
use WeppsCore\Connect;
use WeppsCore\Smarty;

class ViewItem extends Request {
	public $noclose = 1;
	public $scheme = [];
	public $element = [];
	public $settings = [];
	public $headers;
	public function request($action="") {
		//$this->get['element']['Name'] = "Wepps";
		$this->element = &$this->get['element'];
		//unset($this->get['listScheme']['Name']);
		$this->scheme = &$this->get['listScheme'];
		$this->settings = &$this->get['listSettings'];
		//TemplateHeaders - передать тип для переменнной $headers
		$this->headers = &$this->get['headers'];
		$this->headers->js("/packages/WeppsAdmin/Lists/Actions/ViewItem.{$this->headers::$rand}.js");
		
		$smarty = Smarty::getSmarty();
		
		$smarty->assign('itemGroup','Dopoln1');
		$tpl1 = $smarty->fetch( Connect::$projectDev['root'] . '/packages/WeppsAdmin/Lists/Actions/ViewItemDopoln1.tpl');
		$smarty->assign('itemGroup','Dopoln2');
		$tpl2 = $smarty->fetch( Connect::$projectDev['root'] . '/packages/WeppsAdmin/Lists/Actions/ViewItemDopoln2.tpl');
		
		//$this->fetch($key, $value);
		
		/*
		 * Задать навигацию и шаблон для доп. блоков
		 */
		$this->settings['ActionShowIdAddons'] = [
				["title"=>"Дополнение 1","group"=>"Dopoln1","tpl"=>$tpl1],
				["title"=>"Дополнение 2","group"=>"Dopoln2","tpl"=>$tpl2],
		];
	}
}