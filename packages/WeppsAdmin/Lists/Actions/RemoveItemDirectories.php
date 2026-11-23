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
use WeppsCore\NavigatorData;

class RemoveItemDirectories extends Request {
	public $noclose = 1;
	public $listSettings = [];
	private $id;
	public $element = [];
	#[\Override]
	public function request($action=""): void {
		$this->listSettings = $this->get['listSettings'];
		$this->id = (int) $this->get['id'];
		if ($this->listSettings['TableName']=='s_Navigator') {
			if ($this->id==1) {
				Connect::$instance->close();
			}
			$nav2 = new NavigatorData("s_Navigator");
			$child = $nav2->getRChild($this->id);
			if (count($child)!=0) {
				$str = "0,".implode(",", $child);
				$sql = "delete from s_Navigator where Id in ($str)";
				Connect::$db->query($sql);
			}
			return;
		}
	}
}