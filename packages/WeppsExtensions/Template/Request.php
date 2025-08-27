<?php
require_once '../../../configloader.php';

use WeppsCore\Exception;
use WeppsCore\Request;
use WeppsCore\Connect;
use WeppsExtensions\Addons\Files\Files;

class RequestAddons extends Request {
	public function request($action="") {
		switch ($action) {
			case 'files':
				if (!isset($this->get['fileUrl'])) {
					Exception::error404();
				}
				Files::output($this->get['fileUrl']);
				break;
			case 'upload':
				if (!isset($this->get['filesfield'])) {
					Exception::error404();
				}
				if (!isset($this->get['myform'])) {
					Exception::error404();
				}
				if (!isset($_FILES)) {
					Exception::error404();
				}
				$data = Files::upload($_FILES,$this->get['filesfield'],$this->get['myform']);
				echo $data['js'];
				Connect::$instance->close();
				break;
			default:
				$this->tpl = "RequestCustom1.tpl";
				break;
		}
	}
}

$request = new RequestAddons ($_REQUEST);
$smarty->assign('get',$request->get);
$smarty->display($request->tpl);