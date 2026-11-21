<?php

/*
 * This file is part of Wepps Platform.
 *
 * (c) 2026 PETROFFS LLC (ООО «ПЕТРОФФС»)
 * Registered with Rospatent (Russian Federation): certificate No. 2026694013 of 03.11.2026.
 *
 * SPDX-License-Identifier: LicenseRef-Wepps-EULA
 *
 * Licensed under the Wepps Platform EULA: https://github.com/petroffs/wepps/blob/master/LICENSE
 * Free for non-commercial use (Community). Commercial use requires
 * a written license agreement with PETROFFS LLC.
 * This notice must not be removed or altered (EULA, sec. 2.2).
 *
 * @author Aleksei Petrov <mail@petroffs.com>
 */

namespace WeppsAdmin\ConfigExtensions\Uploads;

use WeppsAdmin\Admin\AdminUtils;
use WeppsCore\Utils;
use WeppsAdmin\Lists\Lists;
use WeppsCore\Data;
use WeppsCore\Connect;

/*
 * Обновление таблицы s_Lang
 */

class UploadsExcelTranslate {
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
			$obj = new Data($this->tableName);
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
					
					$arr = AdminUtils::query($row1);
					$str .= "insert ignore into {$this->tableName} (Name) values ('{$row1['Name']}');\n";
					$str .= "update {$this->tableName} set {$arr['update']} where Name='{$row1['Name']}';\n";
				}
			}
			if ($str != "") {
				Connect::$db->exec($str);
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