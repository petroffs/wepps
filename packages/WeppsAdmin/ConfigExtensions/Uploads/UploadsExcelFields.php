<?php

namespace WeppsAdmin\ConfigExtensions\Uploads;

use WeppsCore\Utils\UtilsWepps;
use WeppsAdmin\Lists\ListsWepps;
use WeppsCore\Core\DataWepps;
use WeppsCore\Connect\ConnectWepps;

class UploadsExcelFieldsWepps {
	private $settings;
	private $validator;
	private $tableName = 's_ConfigFields';
	
	public function __construct($settings) {
		$this->settings = $settings;
		$this->validator = $this->getValidator();
	}
	
	public function setData() {
		if ($this->validator['status']==0) {
			/*
			 * Запись/обновление данных
			 */
			$obj = new DataWepps($this->tableName);
			$str = "";
			unset($this->settings['data'][1]);
			foreach ($this->settings['data'] as $value) {
				if (!empty($value['A']) && !empty($value['B']) && !empty($value['C']) && !empty($value['D'])) {
					$row1 = array(
							"TableName"=>$value['A'],
							"Name"=>$value['B'],
							"Description"=>$value['F'],
							"Field"=>$value['C'],
							"Type"=>$value['D'],
							"FGroup"=>$value['E'],
					);
					
					$sql = "delete from s_ConfigFields where TableName='' and Field=''";
					ConnectWepps::$instance->query($sql);
					
					$res = $obj->get("TableName = '{$row1['TableName']}' and Field = '{$row1['Field']}'");
					if (!isset($res[0]['Id'])) {
						$id = $obj->add($row1,'ignore');
						if ((int)$id!=0) {
							$str .= ListsWepps::addListField($id,$row1['Type']).";\n";
						}
					}
				}
			}
			if ($str != "") {
				ConnectWepps::$db->exec($str);
				return [
						'status'=>0,
						'message'=>'Новые поля добавлены'
				];
			}
			return [
					'status'=>3,
					'message'=>'Новые поля не добавлены'
			];
		} else {
			return $this->validator;
		}
		
	}
	
	private function getValidator() {
		if (!empty($this->settings['data'][1])) {
			if ($this->settings['data'][1]['A']=='Список' && 
				$this->settings['data'][1]['B']=='Наименование' && 
				$this->settings['data'][1]['C']=='Alias' && 
				$this->settings['data'][1]['D']=='Тип' && 
				$this->settings['data'][1]['E']=='Группа' && 
				$this->settings['data'][1]['F']=='Описание') {
					return ['status' => '0',
							'message' => 'no errors'];
			}
		}
		
		$out = [
				'status' => '1',
				'message' => 'format error'
		];
		return $out;
	}
}
?>