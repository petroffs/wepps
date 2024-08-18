<?php
namespace WeppsAdmin\Lists\Actions;
use WeppsCore\Utils\RequestWepps;
use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Utils\UtilsWepps;
use WeppsAdmin\Lists\ListsWepps;
use WeppsAdmin\Admin\AdminWepps;

class SaveItemConfigExtensionsWepps extends RequestWepps {
	public $noclose = 1;
	public $scheme = [];
	public $listSettings = [];
	public $element = [];
	public function request($action="") {
	    $this->scheme = $this->get['listScheme'];
	    $this->listSettings = $this->get['listSettings'];
	    $this->element = $this->get['element'];
	    $root = ConnectWepps::$projectDev['root'];
	    if ($this->listSettings['TableName']=='s_ConfigExtensions') {
    		$this->copyExts($this->element['Alias'], ".php", "{$root}/packages/WeppsAdmin/ConfigExtensions", '1.0');
    		$this->copyExts($this->element['Alias'], ".tpl", "{$root}/packages/WeppsAdmin/ConfigExtensions", '1.0');
    		$this->copyExts($this->element['Alias'], "Test.tpl", "{$root}/packages/WeppsAdmin/ConfigExtensions", '1.0');
    		$this->copyExts($this->element['Alias'], ".css", "{$root}/packages/WeppsAdmin/ConfigExtensions", '1.0');
    		$this->copyExts($this->element['Alias'], ".js",  "{$root}/packages/WeppsAdmin/ConfigExtensions", '1.0');
    		$this->copyExts($this->element['Alias'], "Request.php", "{$root}/packages/WeppsAdmin/ConfigExtensions", '1.0');
	    }
	    $perm = AdminWepps::getPermissions(1,array('list'=>'s_ConfigExtensions'));
	    if ($perm['status']==1) {
	    	$sql = "update s_Permissions set SystemExt = concat(SystemExt,',','{$this->element['Id']}') where Id = 1";
	    	ConnectWepps::$instance->query($sql);
	    }
	    
	    /*
	     * При тестировании
	     */
	    #exit();
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
		if (!is_file($filename)) {
			copy("{$dirstart}/_Example{$stamp}/{$filesource}", $filename);
			$fileData = file_get_contents($filename);
			$fileData = str_replace("Example", $ext, $fileData);
			file_put_contents($filename, $fileData);
		}
	}
}
?>