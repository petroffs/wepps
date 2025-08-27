<?php
namespace WeppsAdmin\Lists\Actions;

use WeppsAdmin\ConfigExtensions\Processing\ProcessingProducts;
use WeppsCore\Connect;
use WeppsCore\Request;
use WeppsCore\Utils;

class SaveItemProductsVariations extends Request {
	public $noclose = 1;
	public $scheme = [];
	public $listSettings = [];
	public $element = [];
	
	public function request($action="") {
	    $this->scheme = $this->get['listScheme'];
	    $this->listSettings = $this->get['listSettings'];
	    $this->element = $this->get['element'];
	    if ($this->listSettings['TableName']=='ProductsVariations') {
	    	$obj = new ProcessingProducts();
			$value = [
				$this->element['Field1'],
				$this->element['Field2'],
				$this->element['Field3']
			];
			$alias = $obj->getProductsVariationsHash($this->element['ProductsId'],$value);
			Connect::$instance->query("update ProductsVariations set Alias=? where Id=?",[$alias,$this->element['Id']]);
			echo  "<script>$('input[name=\"Alias\"]').val('{$alias}')</script>";
	    }
	}
}