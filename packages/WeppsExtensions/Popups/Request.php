<?php
require_once '../../../configloader.php';

use WeppsCore\Exception;
use WeppsCore\Request;
use WeppsExtensions\Addons\Bot\BotTest;

class RequestPopups extends Request {
	public function request($action="") {
		switch ($action) {
			case 'test':
				#$obj = new BotTest();
				#$obj->mail();
				sleep(5);
				echo '<h1>Hello, world</h1>';
				break;
			default:
				Exception::error(404);
				break;
		}
	}
}

$request = new RequestPopups($_REQUEST);
$smarty->assign('get',$request->get);
$smarty->display($request->tpl);