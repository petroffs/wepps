<?php
namespace WeppsAdmin\Lists\Actions;

use WeppsAdmin\ConfigExtensions\Processing\ProcessingProducts;
use WeppsCore\Request;
use WeppsCore\Utils;

class SaveItemProducts extends Request {
	public $noclose = 1;
	public $scheme = [];
	public $listSettings = [];
	public $element = [];
	
	public function request($action="") {
	    $this->scheme = $this->get['listScheme'];
	    $this->listSettings = $this->get['listSettings'];
	    $this->element = $this->get['element'];
	    if ($this->listSettings['TableName']=='Products') {
	    	$obj = new ProcessingProducts();
			$obj->setProductsVariations($this->element);
	    }
	}
}