<?php
require_once '../../../configloader.php';

use WeppsCore\Request;
use WeppsCore\Exception;

class RequestExample11 extends Request {
	public function request($action="") {
		switch ($action) {
			case 'test':
				$this->assign('test', 'test1');
				$this->tpl = "RequestExample.tpl";
				break;
			default:
				Exception::error(404);
				break;
		}
	}
}
$request = new RequestExample11 ($_REQUEST);
$smarty->assign('get',$request->get);
$smarty->display($request->tpl);