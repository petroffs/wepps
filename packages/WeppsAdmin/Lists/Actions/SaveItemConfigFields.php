<?php
namespace WeppsAdmin\Lists\Actions;
use WeppsCore\Utils\RequestWepps;
use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Utils\UtilsWepps;
use WeppsAdmin\Lists\ListsWepps;

class SaveItemConfigFieldsWepps extends RequestWepps {
	public $noclose = 1;
	public $scheme = [];
	public $listSettings = [];
	public $element = [];
	
	public function request($action="") {
	    $this->scheme = $this->get['listScheme'];
	    $this->listSettings = $this->get['listSettings'];
	    $this->element = $this->get['element'];
	    if ($this->listSettings['TableName']=='s_ConfigFields') {
	        $str = ListsWepps::addListField($this->element['Id'],$this->element['Type']);
	        //UtilsWepps::debugf($str,1);
	        if ($str!="") {
	            ConnectWepps::$instance->query($str);
	        }
	    }
	}
}
?>