<?php
require_once '../../../configloader.php';

use WeppsCore\Request;
use WeppsCore\Exception;

class RequestNews extends Request {
	public function request($action="") {
		switch ($action) {
			case 'test':
				exit();
				break;
			default:
				Exception::error(404);
				exit();
				break;
		}
	}
}
$request = new RequestNews($_REQUEST);
$smarty->assign('get',$request->get);
$smarty->display($request->tpl);