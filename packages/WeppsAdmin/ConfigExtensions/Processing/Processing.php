<?php
namespace WeppsAdmin\ConfigExtensions\Processing;

use WeppsCore\Utils\RequestWepps;
use WeppsCore\Core\SmartyWepps;
use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\Utils\TemplateHeadersWepps;

class ProcessingWepps extends RequestWepps {
	public function request($action="") {
		$this->tpl = 'Processing.tpl';
		$this->title = $this->get['ext']['Name'];
		$this->way = [];
		array_push($this->way, [
				'Url'=>"/_pps/extensions/{$this->get['ext']['Alias']}/",
				'Name'=>$this->title
		]);
		$this->headers = new TemplateHeadersWepps();
		$smarty = SmartyWepps::getSmarty();
		$smarty->assign('url','/packages/WeppsAdmin/ConfigExtensions/Processing/Request.php');
		if ($action=="") {
			return;
		}
		switch ($action) {
			case 'tasks':
				$this->title = "Задачи";
				$this->tpl = 'ProcessingTasks.tpl';
				break;
			case 'products':
				$this->title = "Товары";
				$this->tpl = 'ProcessingProducts.tpl';
				break;
			default:
				if ($action!="") {
					ExceptionWepps::error404();
				}
				break;
		}
		array_push($this->way, [
				'Url'=>"/_pps/extensions/{$this->get['ext']['Alias']}/{$action}.html",
				'Name'=>$this->title
		]);
	}
}