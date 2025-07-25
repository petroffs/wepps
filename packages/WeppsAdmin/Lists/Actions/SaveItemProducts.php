<?php
namespace WeppsAdmin\Lists\Actions;

use WeppsCore\Utils\RequestWepps;
use WeppsCore\Connect\ConnectWepps;

class SaveItemExtensionsWepps extends RequestWepps {
	public $noclose = 1;
	public $scheme = [];
	public $listSettings = [];
	public $element = [];
	
	public function request($action="") {
	    $this->scheme = $this->get['listScheme'];
	    $this->listSettings = $this->get['listSettings'];
	    $this->element = $this->get['element'];
	    $root = ConnectWepps::$projectDev['root'];
	    if ($this->listSettings['TableName']=='Products') {
	    	
	    }
	}
}