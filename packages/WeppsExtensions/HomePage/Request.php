<?php
namespace WeppsExtensions\FirstPage;
use WeppsCore\Utils\RequestWepps;

require_once '../../../config.php';
require_once '../../../autoloader.php';
require_once '../../../configloader.php';

class RequestCustomWepps extends RequestWepps {
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

$request = new RequestCustomWepps($_REQUEST);
$smarty->assign('get',$request->get);
$smarty->display($request->tpl);
?>