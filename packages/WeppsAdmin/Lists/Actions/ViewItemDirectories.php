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

class ViewItemDirectories extends Request {
	public $noclose = 1;
	public $scheme = [];
	public $element = [];
	public function request($action="") {
		$element = $this->get['element'];
		$this->element = &$this->get['element'];
		$this->scheme = $this->get['listScheme'];
		if (strstr($_GET['weppsurl'], '/addNavigator/')) {
			foreach ($this->scheme as $key=>$value) {
				$this->element[$key] = "";
			}
			$this->element['Id'] = 'add'; 
			$this->element['Name'] = 'Новый раздел'; 
			$this->element['ParentId_SelectChecked'] = $element['Id']; 
			$this->element['Template_SelectChecked'] = ""; 
			$this->element['Extension_SelectChecked'] = "";
		}
	}
}