<?php
namespace WeppsAdmin\Lists\Actions;
use WeppsCore\Utils\RequestWepps;
use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Utils\UtilsWepps;
use WeppsAdmin\Lists\ListsWepps;
use WeppsCore\Core\NavigatorDataWepps;

class RemoveItemDirectoriesWepps extends RequestWepps {
	public $noclose = 1;
	public $listSettings = [];
	public $element = [];
	public function request($action="") {
		$this->listSettings = $this->get['listSettings'];
		$this->id = (int) $this->get['id'];
		if ($this->listSettings['TableName']=='s_Directories') {
			if ($this->id==1) {
				ConnectWepps::$instance->close();
			}
			$nav2 = new NavigatorDataWepps("s_Directories");
			$child = $nav2->getRChild($this->id);
			if (count($child)!=0) {
				$str = "0,".implode(",", $child);
				$sql = "delete from s_Directories where Id in ($str)";
				ConnectWepps::$db->query($sql);
			}
			return;
		}
	}
}
?>