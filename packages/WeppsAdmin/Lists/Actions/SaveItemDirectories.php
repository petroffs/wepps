<?php
namespace WeppsAdmin\Lists\Actions;

use WeppsCore\Request;
use WeppsCore\Connect;
use WeppsCore\TextTransforms;

class SaveItemDirectories extends Request {
	public $noclose = 1;
	public $scheme = [];
	public $listSettings = [];
	public $element = [];
	
	
	public function request($action="") {
	    $this->scheme = $this->get['listScheme'];
	    $this->listSettings = $this->get['listSettings'];
	    $this->element = $this->get['element'];
	    $root = Connect::$projectDev['root'];
	    if ($this->listSettings['TableName']=='s_Navigator') {
	    	if ($this->element['Url']=='') {
	    		$url = "/".TextTransforms::translit($this->element['Name'],2)."/";
	    		$sql = "update s_Navigator set Url='{$url}' where Id='{$this->element['Id']}'";
	    		Connect::$instance->query($sql);
	    		$this->element['Url'] = $url;
	    		//Utils::debug($this->element);
	    	}
	    }
	}	
}