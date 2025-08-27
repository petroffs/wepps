<?php

namespace WeppsAdmin\ConfigExtensions\Uploads;

use WeppsAdmin\Admin\AdminUtils;
use WeppsCore\Utils;
use WeppsAdmin\Lists\Lists;
use WeppsCore\Data;
use WeppsCore\Connect;

/*
 * Обновление таблицы s_Lang
 */

class UploadsExcelListData {
	private $settings;
	private $validator;
	private $tableName;
	
	public function __construct($settings) {
		$this->settings = $settings;
		$this->validator = $this->getValidator();
	}
	
	public function setData() {
		if ($this->validator['status']==0) {
			/*
			 * Запись/обновление данных
			 */
			$obj = new Data($this->tableName);
			$str = "";
			$fields = $this->settings['data'][2];
			unset($this->settings['data'][1]);
			unset($this->settings['data'][2]);
			foreach ($this->settings['data'] as $key => $value) {
				if (!empty($value['A'])) {
					$row = [];
					foreach ($fields as $k=>$v) {
						if (!empty($v)) {
							$row[$v] = $value[$k];
						}
						
					}
					$arr = AdminUtils::query($row);
					$str .= "insert ignore into {$this->tableName} (Id) values ('{$row['Id']}');\n";
					$str .= "update {$this->tableName} set {$arr['update']} where Id='{$row['Id']}';\n";
				}
			}
			if ($str != "") {
				Connect::$db->exec($str);
				return [
						'status'=>0,
						'message'=>"Таблица {$this->tableName} обновлена"
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
		$obj = new Data($this->settings['title']);
		$scheme = $obj->getScheme();
		$error = 0;
		$message = "";
		foreach ($this->settings['data'][2] as $value) {
			if (!empty($value) && !isset($scheme[$value])) {
				$error = 1;
				$message = $value;
				break;
			}
		}
		if ($error==0) {
			$out = ['status' => '0',
					'message' => 'no errors'];
			$this->tableName = $this->settings['title'];
		} else {
			$out = [
					'status' => '1',
					'message' => "Поле $message не создано"
			];
		}
		return $out;
	}
}
?>