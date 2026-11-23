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
use WeppsAdmin\Lists\Lists;
use WeppsAdmin\Admin\Admin;

class SaveItemConfig extends Request {
	public $noclose = 1;
	public $scheme = [];
	public $listSettings = [];
	public $element = [];
	
	#[\Override]
	public function request($action=""): void {
	    $this->scheme = $this->get['listScheme'];
	    $this->listSettings = $this->get['listSettings'];
	    $this->element = $this->get['element'];
	    if ($this->listSettings['TableName']=='s_Config') {
	        $str = Lists::addList($this->element['TableName']);
	        if ($str!="") {
	            Connect::$db->exec($str);
	            $perm = Admin::getPermissions(1,array('list'=>'s_Config'));
	            if ($perm['status']==1) {
	                $sql = "update s_Permissions set TableName = concat(TableName,',','{$this->element['TableName']}') where Id = 1";
	                Connect::$instance->query($sql);
	            }
	        }
	    }
	}
}