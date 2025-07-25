<?php
namespace WeppsAdmin\Lists\Actions;

use WeppsCore\Utils\RequestWepps;
use WeppsCore\Connect\ConnectWepps;
use WeppsAdmin\Lists\ListsWepps;
use WeppsAdmin\Admin\AdminWepps;

class SaveItemConfigWepps extends RequestWepps {
	public $noclose = 1;
	public $scheme = [];
	public $listSettings = [];
	public $element = [];
	
	public function request($action="") {
	    $this->scheme = $this->get['listScheme'];
	    $this->listSettings = $this->get['listSettings'];
	    $this->element = $this->get['element'];
	    if ($this->listSettings['TableName']=='s_Config') {
	        $str = ListsWepps::addList($this->element['TableName']);
	        if ($str!="") {
	            ConnectWepps::$db->exec($str);
	            $perm = AdminWepps::getPermissions(1,array('list'=>'s_Config'));
	            if ($perm['status']==1) {
	                $sql = "update s_Permissions set TableName = concat(TableName,',','{$this->element['TableName']}') where Id = 1";
	                ConnectWepps::$instance->query($sql);
	            }
	        }
	    }
	}
}