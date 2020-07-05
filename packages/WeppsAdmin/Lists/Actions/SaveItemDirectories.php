<?
namespace WeppsAdmin\Lists\Actions;
use WeppsCore\Utils\RequestWepps;
use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Utils\UtilsWepps;
use WeppsAdmin\Lists\ListsWepps;
use WeppsAdmin\Admin\AdminWepps;
use WeppsCore\Spell\SpellWepps;

class SaveItemDirectoriesWepps extends RequestWepps {
	public $noclose = 1;
	public $scheme = [];
	public $listSettings = [];
	public $element = [];
	
	
	public function request($action="") {
	    $this->scheme = $this->get['listScheme'];
	    $this->listSettings = $this->get['listSettings'];
	    $this->element = $this->get['element'];
	    $root = ConnectWepps::$projectDev['root'];
	    if ($this->listSettings['TableName']=='s_Directories') {
	    	if ($this->element['Url']=='') {
	    		$url = "/".SpellWepps::getTranslit($this->element['Name'],2)."/";
	    		$sql = "update s_Directories set Url='{$url}' where Id='{$this->element['Id']}'";
	    		ConnectWepps::$instance->query($sql);
	    		$this->element['Url'] = $url;
	    		//UtilsWepps::debug($this->element);
	    	}
	    }
	}
	
	
}
?>