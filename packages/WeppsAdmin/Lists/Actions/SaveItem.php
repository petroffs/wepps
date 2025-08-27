<?php
namespace WeppsAdmin\Lists\Actions;

use WeppsCore\Request;

class SaveItem extends Request {
	public $noclose = 1;
	public $scheme = [];
	public $listSettings = [];
	public $element = [];
	
	public function request($action="") {
	    $this->scheme = $this->get['listScheme'];
	    $this->listSettings = $this->get['listSettings'];
	    $this->element = $this->get['element'];
	    #echo $this->listSettings['TableName'] . " - вывод тестовый - " . __FILE__;
	}
}