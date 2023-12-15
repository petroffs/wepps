<?php
namespace WeppsAdmin\Lists\Actions;

use WeppsCore\Utils\RequestWepps;
use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Utils\UtilsWepps;

class ViewListWepps extends RequestWepps {
	public $noclose = 1;
	public $condition = "";
	public $scheme = [];
	public function request($action="") {
		//$this->condition = "t.DisplayOff in (1,0) and t.Id!=5";
		//unset($this->get['listScheme']['Name']);
		$this->scheme = $this->get['listScheme'];
	}
}
?>