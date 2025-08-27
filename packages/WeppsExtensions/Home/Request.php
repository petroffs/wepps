<?php
require_once '../../../configloader.php';

use WeppsCore\Request;

class RequestHome extends Request {
	public function request($get) {
		$action = (isset($get['action'])) ? $get['action'] : '';

		switch ($action) {
			case 'test':
				$this->tpl = "RequestCustom2.tpl";
				break;
			default:
				$this->tpl = "RequestCustom1.tpl";
				break;
		}
	}
}

$request = new RequestHome($_REQUEST);
$smarty->assign('get',$request->get);
$smarty->display($request->tpl);