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

namespace WeppsAdmin\Lists\Actions;

use WeppsCore\Request;
use WeppsCore\Connect;
use WeppsCore\Data;

class RemoveItemConfig extends Request {
	public $noclose = 1;
	public $listSettings = [];
	public $element = [];
	private $id;
	#[\Override]
	/**
	 * @return void
	 */
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