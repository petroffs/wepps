<?php
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
	
	public function request($action="") {
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