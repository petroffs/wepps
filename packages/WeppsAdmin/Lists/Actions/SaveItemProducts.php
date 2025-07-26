<?php
namespace WeppsAdmin\Lists\Actions;

use WeppsAdmin\ConfigExtensions\Processing\ProcessingProductsWepps;
use WeppsCore\Utils\RequestWepps;

class SaveItemProductsWepps extends RequestWepps {
	public $noclose = 1;
	public $scheme = [];
	public $listSettings = [];
	public $element = [];
	
	public function request($action="") {
	    $this->scheme = $this->get['listScheme'];
	    $this->listSettings = $this->get['listSettings'];
	    $this->element = $this->get['element'];
	    if ($this->listSettings['TableName']=='Products') {
	    	$obj = new ProcessingProductsWepps();
			$obj->setProductsVariations($this->element);
	    }
	}
}