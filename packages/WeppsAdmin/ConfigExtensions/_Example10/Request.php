<?php
require_once '../../../../configloader.php';

use WeppsAdmin\Admin\AdminUtils;
use WeppsCore\Request;
use WeppsCore\Exception;
use WeppsCore\Connect;

class Request_Example10 extends Request
{
	public function request($action = "")
	{
		$this->tpl = '';
		if (@Connect::$projectData['user']['ShowAdmin'] != 1) {
			Exception::error404();
		}
		switch ($action) {
			case "test":
				AdminUtils::modal('Тест ОК1');
				break;
			default:
				Exception::error(404);
				break;
		}
	}
}
$request = new Request_Example10($_REQUEST);
$smarty->assign('get', $request->get);
$smarty->display($request->tpl);