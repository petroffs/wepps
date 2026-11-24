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

class UploadsExcelListData
{
	private $settings;
	private $validator;
	private $tableName;

	public function __construct($settings)
    {
		$this->settings = $settings;
		$this->validator = $this->getValidator();
	}

	public function setData()
    {
		if ($this->validator['status'] == 0) {
			/*
			 * Запись/обновление данных
			 */
			$str = "";
			$fields = $this->settings['data'][2];
			unset($this->settings['data'][1]);
			unset($this->settings['data'][2]);
			foreach ($this->settings['data'] as $value) {
				if (!empty($value['A'])) {
					$row = [];
					foreach ($fields as $k => $v) {
						if (!empty($v)) {
							$row[$v] = $value[$k];
						}
					}
					$arr = AdminUtils::query($row);
					$str .= "insert ignore into {$this->tableName} (Id) values ('{$row['Id']}');\n";
					$str .= "update {$this->tableName} set {$arr['update']} where Id='{$row['Id']}';\n";
				}
			}
			if ($str !== "") {
				Connect::$db->exec($str);
				return [
						'status' => 0,
						'message' => "Таблица {$this->tableName} обновлена"
				];
			}
			return [
					'status' => 3,
					'message' => 'Нет обновлений'
			];
		}
        return $this->validator;
	}

	/**
	 * @return string[]
	 *
	 * @psalm-return array{status: '0'|'1', message: string}
	 */
	private function getValidator(): array
    {
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
		if ($error === 0) {
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
