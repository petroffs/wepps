<?php
require_once '../../../configloader.php';

use WeppsCore\Request;

class RequestServices extends Request {
	public function request($action="") {
		switch ($action) {
			case 'test':
				exit();
				break;
			default:
				$this->tpl = "RequestServices.tpl";
				break;
		}
	}
}
$request = new RequestServices($_REQUEST);
$smarty->assign('get',$request->get);
$smarty->display($request->tpl);