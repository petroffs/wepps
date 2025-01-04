<?php
namespace WeppsAdmin\ConfigExtensions\Example;

use WeppsCore\Utils\RequestWepps;
use WeppsCore\Utils\UtilsWepps;
use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\Connect\ConnectWepps;

require_once '../../../../config.php';
require_once '../../../../autoloader.php';
require_once '../../../../configloader.php';

class Request_Example10Wepps extends RequestWepps {
	public function request($action="") {
		$this->tpl = '';
		if (@ConnectWepps::$projectData['user']['ShowAdmin']!=1) {
			ExceptionWepps::error404();
		}
		switch ($action) {
			case "test":
				UtilsWepps::modal('Тест ОК1');
				break;
			default:
				ExceptionWepps::error(404);
				break;
		}
	}
}
$request = new Request_Example10Wepps($_REQUEST);
/** @var \Smarty $smarty */
$smarty->assign('get',$request->get);
$smarty->display($request->tpl);