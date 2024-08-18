<?php
namespace WeppsAdmin\ConfigExtensions\Test4;

use WeppsCore\Utils\RequestWepps;
use WeppsCore\Utils\UtilsWepps;
use WeppsCore\Exception\ExceptionWepps;

require_once '../../../../config.php';
require_once '../../../../autoloader.php';
require_once '../../../../configloader.php';

//http://host/packages/WeppsAdmin/ConfigExtensions/Processing/Request.php?id=5

class RequestTest4Wepps extends RequestWepps {
	public function request($action="") {
		$this->tpl = '';
		if (@ConnectWepps::$projectData['user']['ShowAdmin']!=1) ExceptionWepps::error404();
		switch ($action) {
			case "test":
				UtilsWepps::debug('test1',1);
				break;
			default:
				ExceptionWepps::error404();
				break;
		}
	}
}
$request = new RequestTest4Wepps ($_REQUEST);
$smarty->assign('get',$request->get);
$smarty->display($request->tpl);