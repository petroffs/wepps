<?php
namespace WeppsAdmin\Lists\Actions;
use WeppsCore\Utils\RequestWepps;
use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Utils\UtilsWepps;
use WeppsAdmin\Lists\ListsWepps;

class RemoveItemConfigWepps extends RequestWepps {
	public $noclose = 1;
	public $listSettings = [];
	public $element = [];
	public function request($action="") {
		$this->listSettings = $this->get['listSettings'];
		$this->id = (int) $this->get['id'];
		if ($this->listSettings['TableName']=='s_Config') {
			$sql = "select * from s_Config where Id = '{$this->id}'";
			$res = ConnectWepps::$instance->fetch($sql); 
			if (isset($res[0]['Id'])) {
				$this->element = $res[0];
				$sql = "delete from s_ConfigFields where TableName='{$this->element['TableName']}';\n";
				$sql .= "drop table if exists {$this->element['TableName']};\n";
				ConnectWepps::$db->exec($sql);
			}
		}
	}
}
?>