<?php
namespace WeppsAdmin\Lists\Actions;

use WeppsCore\Request;
use WeppsCore\Connect;
use WeppsCore\Data;

class RemoveItemConfig extends Request {
	public $noclose = 1;
	public $listSettings = [];
	public $element = [];
	private $id;
	public function request($action="") {
		$this->listSettings = $this->get['listSettings'];
		$this->id = (int) $this->get['id'];
		if ($this->listSettings['TableName']=='s_Config') {
			$sql = "select * from s_Config where Id = '{$this->id}'";
			$res = Connect::$instance->fetch($sql); 
			if (isset($res[0]['Id'])) {
				$this->element = $res[0];
				$tableName = $this->element['TableName'];
				$sql = "delete from s_ConfigFields where TableName='{$tableName}';\n";
				$sql .= "drop table if exists {$tableName};\n";
				Connect::$db->exec($sql);
				// Инвалидировать кэш схемы после удаления таблицы
				Data::invalidateSchemaCacheForTable($tableName);
			}
		}
	}
}