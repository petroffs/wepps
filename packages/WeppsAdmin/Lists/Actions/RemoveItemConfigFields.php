<?php
namespace WeppsAdmin\Lists\Actions;

use WeppsCore\Utils\RequestWepps;
use WeppsCore\Connect\ConnectWepps;

class RemoveItemConfigFieldsWepps extends RequestWepps {
	public $noclose = 1;
	public $listSettings = [];
	private $id;
	public $element = [];
	public function request($action="") {
		$this->listSettings = $this->get['listSettings'];
		$this->id = (int) $this->get['id'];
		if ($this->listSettings['TableName']=='s_ConfigFields') {
			$sql = "select * from s_ConfigFields where Id = '{$this->id}'";
			$res = ConnectWepps::$instance->fetch($sql); 
			if (isset($res[0]['Id'])) {
				$this->element = $res[0];
				$sql = "alter table {$this->element['TableName']} drop column {$this->element['Field']};\n";
				ConnectWepps::$instance->query($sql);
			}
		}
	}
}