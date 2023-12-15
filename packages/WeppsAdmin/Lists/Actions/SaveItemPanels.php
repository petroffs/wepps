<?php
namespace WeppsAdmin\Lists\Actions;
use WeppsCore\Utils\RequestWepps;
use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Utils\UtilsWepps;
use WeppsAdmin\Lists\ListsWepps;
use WeppsAdmin\Admin\AdminWepps;

class SaveItemExtensionsWepps extends RequestWepps {
	public $noclose = 1;
	public $scheme = [];
	public $listSettings = [];
	public $element = [];
	
	
	public function request($action="") {
		return;
	    $this->scheme = $this->get['listScheme'];
	    $this->listSettings = $this->get['listSettings'];
	    $this->element = $this->get['element'];
	    $root = ConnectWepps::$projectDev['root'];
	    if ($this->listSettings['TableName']=='s_Extensions') {
	    	if ($this->element['CopyFiles'] == '1.0') {
	    		$this->copyExts($this->element['FileExt'], ".php", "{$root}/packages/WeppsExtensions", '1.0');
	    		$this->copyExts($this->element['FileExt'], ".tpl", "{$root}/packages/WeppsExtensions", '1.0');
	    		$this->copyExts($this->element['FileExt'], ".css", "{$root}/packages/WeppsExtensions", '1.0');
	    		$this->copyExts($this->element['FileExt'], ".js",  "{$root}/packages/WeppsExtensions", '1.0');
	    	} else if ($this->element['CopyFiles'] == '1.1') {
	    		$this->copyExts($this->element['FileExt'], "Request.php", "{$root}/packages/WeppsExtensions", '1.1');
	    		$this->copyExts($this->element['FileExt'], ".php",        "{$root}/packages/WeppsExtensions", '1.1');
	    		$this->copyExts($this->element['FileExt'], ".tpl",        "{$root}/packages/WeppsExtensions", '1.1');
	    		$this->copyExts($this->element['FileExt'], "Item.tpl",	  "{$root}/packages/WeppsExtensions", '1.1');
	    		$this->copyExts($this->element['FileExt'], ".css",        "{$root}/packages/WeppsExtensions", '1.1');
	    		$this->copyExts($this->element['FileExt'], ".js",         "{$root}/packages/WeppsExtensions", '1.1');
	    	}
	    }
	}
	
	private function copyExts($ext, $fileend, $dirstart, $stamp) {
		$ext = ucfirst($ext);
		$dir = "{$dirstart}/{$ext}";
		if (! is_dir($dir)) {
			mkdir($dir, 0755, true);
		}
		$filename = "{$dir}/{$ext}" . $fileend;
		$filesource = "Example{$fileend}";
		if ($fileend == "Request.php") {
			$filename = "{$dir}/{$fileend}";
			$filesource = $fileend;
		}
		if (! is_file($filename)) {
			// echo "{$dirstart}/_Example{$stamp}/{$filesource} //// $filename<br>";
			copy("{$dirstart}/_Example{$stamp}/{$filesource}", $filename);
			$fileData = file_get_contents($filename);
			$fileData = str_replace("Example", $ext, $fileData);
			file_put_contents($filename, $fileData);
		}
	}
}
?>