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
use WeppsAdmin\Admin\Admin;

class SaveItemConfigExtensions extends Request {
	public $noclose = 1;
	public $scheme = [];
	public $listSettings = [];
	public $element = [];
	#[\Override]
	/**
	 * @return void
	 */
	public function request($action="") {
	    $this->scheme = $this->get['listScheme'];
	    $this->listSettings = $this->get['listSettings'];
	    $this->element = $this->get['element'];
	    $root = Connect::$projectDev['root'];
	    if ($this->listSettings['TableName']=='s_ConfigExtensions') {
	    	if ($this->element['CopyFiles'] == '1.0') {
	    		$this->copyExts($this->element['Alias'], ".php", "{$root}/packages/WeppsAdmin/ConfigExtensions",'10');
	    		$this->copyExts($this->element['Alias'], ".tpl", "{$root}/packages/WeppsAdmin/ConfigExtensions",'10');
	    		$this->copyExts($this->element['Alias'], "Test.tpl", "{$root}/packages/WeppsAdmin/ConfigExtensions",'10');
	    		$this->copyExts($this->element['Alias'], ".css", "{$root}/packages/WeppsAdmin/ConfigExtensions",'10');
	    		$this->copyExts($this->element['Alias'], ".js",  "{$root}/packages/WeppsAdmin/ConfigExtensions",'10');
	    		$this->copyExts($this->element['Alias'], "Request.php", "{$root}/packages/WeppsAdmin/ConfigExtensions",'10');
	    	}
	    }
	    $perm = Admin::getPermissions(1,array('list'=>'s_ConfigExtensions'));
	    if ($perm['status']==1) {
	    	$sql = "update s_Permissions set SystemExt = concat(SystemExt,',','{$this->element['Id']}') where Id = 1";
	    	Connect::$instance->query($sql);
	    }
	    
	    /*
	     * При тестировании
	     */
	    #exit();
	}
	private function copyExts($ext, $fileend, $dirstart, $stamp): void {
		$ext = ucfirst($ext);
		$dir = "{$dirstart}/{$ext}";
		if (!is_dir($dir)) {
			mkdir($dir, 0755, true);
		}
		$filename = "{$dir}/{$ext}" . $fileend;
		$filesource = "_Example{$stamp}{$fileend}";
		if ($fileend == "Request.php") {
			$filename = "{$dir}/{$fileend}";
			$filesource = $fileend;
		}
		if (!is_file($filename)) {
			copy("{$dirstart}/_Example{$stamp}/{$filesource}", $filename);
			$filedata = file_get_contents($filename);
			if (strstr($filename, '.tpl') || strstr($filename, '.css')) {
				$ext = strtolower($ext);
			}
			$filedata = str_replace("_Example{$stamp}", $ext, $filedata);
			file_put_contents($filename, $filedata);
		}
	}
}