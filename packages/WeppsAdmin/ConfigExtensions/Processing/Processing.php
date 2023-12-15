<?php
namespace WeppsAdmin\ConfigExtensions\Processing;

use WeppsCore\Utils\RequestWepps;
use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Utils\UtilsWepps;
use WeppsCore\Core\SmartyWepps;
use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\Utils\TemplateHeadersWepps;
use WeppsCore\Core\DataWepps;

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
		if ($action=="") {
			return;
		}
		switch ($action) {
			case 'searchindex':
				$this->title = "Построение поискового индекса";
				$this->tpl = 'ProcessingSearchIndex.tpl';
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
?>