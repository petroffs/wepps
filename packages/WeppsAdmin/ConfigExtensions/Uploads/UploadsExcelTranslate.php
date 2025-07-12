<?php

namespace WeppsAdmin\ConfigExtensions\Uploads;

use WeppsAdmin\Admin\AdminUtilsWepps;
use WeppsCore\Utils\UtilsWepps;
use WeppsAdmin\Lists\ListsWepps;
use WeppsCore\Core\DataWepps;
use WeppsCore\Connect\ConnectWepps;

/*
 * Обновление таблицы s_Lang
 */

class UploadsExcelTranslateWepps {
	private $settings;
	private $validator;
	private $tableName = 's_Lang';
	
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
							"Name"=>$value['A'],
							"Category"=>$value['B'],
							"LangRu"=>$value['C'],
							"LangEn"=>$value['D'],
							"Priority"=>1000,
					);
					
					$arr = AdminUtilsWepps::query($row1);
					$str .= "insert ignore into {$this->tableName} (Name) values ('{$row1['Name']}');\n";
					$str .= "update {$this->tableName} set {$arr['update']} where Name='{$row1['Name']}';\n";
				}
			}
			if ($str != "") {
				ConnectWepps::$db->exec($str);
				return [
						'status'=>0,
						'message'=>'Таблица Перевод обновлена'
				];
			}
			return [
					'status'=>3,
					'message'=>'Нет обновлений'
			];
		} else {
			return $this->validator;
		}
		
	}
	
	private function getValidator() {
		if (!empty($this->settings['data'][1])) {
			if ($this->settings['data'][1]['A']=='Name' && 
				$this->settings['data'][1]['B']=='Category' && 
				$this->settings['data'][1]['C']=='LangRu' && 
				$this->settings['data'][1]['D']=='LangEn') {
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