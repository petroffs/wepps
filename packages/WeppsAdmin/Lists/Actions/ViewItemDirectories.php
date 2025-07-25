<?php
namespace WeppsAdmin\Lists\Actions;

use WeppsCore\Utils\RequestWepps;

class ViewItemDirectoriesWepps extends RequestWepps {
	public $noclose = 1;
	public $scheme = [];
	public $element = [];
	public function request($action="") {
		$element = $this->get['element'];
		$this->element = &$this->get['element'];
		$this->scheme = $this->get['listScheme'];
		if (strstr($_GET['ppsUrl'], '/addNavigator/')) {
			foreach ($this->scheme as $key=>$value) {
				$this->element[$key] = "";
			}
			$this->element['Id'] = 'add'; 
			$this->element['Name'] = 'Новый раздел'; 
			$this->element['ParentDir_SelectChecked'] = $element['Id']; 
			$this->element['Template_SelectChecked'] = ""; 
			$this->element['Extension_SelectChecked'] = "";
		}
	}
}