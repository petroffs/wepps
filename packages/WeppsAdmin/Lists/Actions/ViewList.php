<?php
namespace WeppsAdmin\Lists\Actions;

use WeppsCore\Request;
use WeppsCore\Connect;
use WeppsCore\Utils;

class ViewList extends Request {
	public $noclose = 1;
	public $condition = "";
	public $scheme = [];
	public function request($action="") {
		//$this->condition = "t.IsHidden in (1,0) and t.Id!=5";
		//unset($this->get['listScheme']['Name']);
		$this->scheme = $this->get['listScheme'];
	}
}