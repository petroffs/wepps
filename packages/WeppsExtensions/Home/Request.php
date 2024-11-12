<?php
namespace WeppsExtensions\Home;
use WeppsCore\Utils\RequestWepps;

require_once '../../../config.php';
require_once '../../../autoloader.php';
require_once '../../../configloader.php';

class RequestHomeWepps extends RequestWepps {
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

$request = new RequestHomeWepps($_REQUEST);
$smarty->assign('get',$request->get);
$smarty->display($request->tpl);