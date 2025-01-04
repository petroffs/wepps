<?php
namespace WeppsAdmin\Lists\Actions;

use WeppsCore\Utils\RequestWepps;
use WeppsCore\Connect\ConnectWepps;

class SaveItemExtensionsWepps extends RequestWepps {
	public $noclose = 1;
	public $scheme = [];
	public $listSettings = [];
	public $element = [];
	public function request($action="") {
	    $this->scheme = $this->get['listScheme'];
	    $this->listSettings = $this->get['listSettings'];
	    $this->element = $this->get['element'];
	    $root = ConnectWepps::$projectDev['root'];
	    if ($this->listSettings['TableName']=='s_Extensions') {
	    	if ($this->element['CopyFiles'] == '1.0') {
	    		$this->copyExts($this->element['FileExt'], ".php", "{$root}/packages/WeppsExtensions", '10');
	    		$this->copyExts($this->element['FileExt'], ".tpl", "{$root}/packages/WeppsExtensions", '10');
	    		$this->copyExts($this->element['FileExt'], ".css", "{$root}/packages/WeppsExtensions", '10');
	    		$this->copyExts($this->element['FileExt'], ".js",  "{$root}/packages/WeppsExtensions", '10');
	    	} else if ($this->element['CopyFiles'] == '1.1') {
	    		$this->copyExts($this->element['FileExt'], "Request.php", "{$root}/packages/WeppsExtensions", '11');
	    		$this->copyExts($this->element['FileExt'], "RequestExample.tpl", "{$root}/packages/WeppsExtensions", '11');
	    		$this->copyExts($this->element['FileExt'], ".php",        "{$root}/packages/WeppsExtensions", '11');
	    		$this->copyExts($this->element['FileExt'], ".tpl",        "{$root}/packages/WeppsExtensions", '11');
	    		$this->copyExts($this->element['FileExt'], "Item.tpl",	  "{$root}/packages/WeppsExtensions", '11');
	    		$this->copyExts($this->element['FileExt'], ".css",        "{$root}/packages/WeppsExtensions", '11');
	    		$this->copyExts($this->element['FileExt'], ".js",         "{$root}/packages/WeppsExtensions", '11');
	    	}
	    }
	}
	private function copyExts($ext, $fileend, $dirstart, $stamp) {
		$ext = ucfirst($ext);
		$dir = "{$dirstart}/{$ext}";
		if (!is_dir($dir)) {
			mkdir($dir, 0755, true);
		}
		$filename = "{$dir}/{$ext}" . $fileend;
		$filesource = "_Example{$stamp}{$fileend}";
		if ($fileend == "Request.php" || $fileend == "RequestExample.tpl") {
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