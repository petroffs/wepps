<?php
require_once __DIR__ . '/../../../../configloader.php';

use WeppsCore\Exception;
use WeppsCore\Connect;
use WeppsCore\Request;

class RequestBlocks extends Request {
	public function request($action="") {
		switch ($action) {
			case 'sortable':
				if (@Connect::$projectData['user']['ShowAdmin']==1 && !empty($this->get['items'])) {
					$ex = explode(",", $this->get['items']);
					$co = 50;
					$str = "";
					foreach ($ex as $value) {
						$str .= "update s_Blocks set Priority='{$co}' where Id='{$value}';\n";
						$co += 5;
					}
					Connect::$db->exec($str);
				} else {
					exit();
				}
				break;
			default:
				Exception::error404();
				break;
		}
	}
}
$request = new RequestBlocks ($_REQUEST);
$smarty->assign('get',$request->get);
$smarty->display($request->tpl);