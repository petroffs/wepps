<?php
namespace WeppsExtensions\Addons;

use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\Utils\RequestWepps;
use WeppsCore\Connect\ConnectWepps;
use WeppsExtensions\Addons\Files\FilesWepps;

require_once '../../../config.php';
require_once '../../../autoloader.php';
require_once '../../../configloader.php';

class RequestAddonsWepps extends RequestWepps {
	public function request($action="") {
		switch ($action) {
			case 'files':
				if (!isset($this->get['fileUrl'])) {
					ExceptionWepps::error404();
				}
				FilesWepps::output($this->get['fileUrl']);
				break;
			case 'upload':
				if (!isset($this->get['filesfield'])) {
					ExceptionWepps::error404();
				}
				if (!isset($this->get['myform'])) {
					ExceptionWepps::error404();
				}
				if (!isset($_FILES)) {
					ExceptionWepps::error404();
				}
				$data = FilesWepps::upload($_FILES,$this->get['filesfield'],$this->get['myform']);
				echo $data['js'];
				ConnectWepps::$instance->close();
				break;
			default:
				$this->tpl = "RequestCustom1.tpl";
				break;
		}
	}
}

$request = new RequestAddonsWepps ($_REQUEST);
$smarty->assign('get',$request->get);
$smarty->display($request->tpl);
?>