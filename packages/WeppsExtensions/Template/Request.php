<?php
require_once '../../../configloader.php';

use WeppsCore\Exception;
use WeppsCore\Request;
use WeppsCore\Connect;
use WeppsCore\Utils;
use WeppsExtensions\Addons\Files\Files;

class RequestTemplate extends Request {
	public function request($action = '') {
		switch ($action) {
			case 'files':
				if (!isset($this->get['fileUrl'])) {
					Exception::error(404);
				}
				$files = new Files();
				$files->output($this->get['fileUrl']);
				break;
			case 'upload':
				if (!isset($this->get['filesfield'])) {
					Exception::error(404);
				}
				if (!isset($this->get['filesform'])) {
					Exception::error(404);
				}
				if (!isset($_FILES)) {
					Exception::error(404);
				}
				$files = new Files();
				/**
				 * Настройка правил
				 * $files->setUploadSettings(1024 * 1024, 'image/jpeg'); // JPG до 1 МБ
				 * $files->setUploadSettings(5 * 1024 * 1024, 'application/pdf'); // PDF до 5 МБ
				 * Вызов метода
				 * $result = $files->upload($_FILES['images'], 'images', 'product_form');
				 */
				switch ($this->get['filesform']) {
					case 'feedback-form':
						$files->setUploadSettings(1024 * 1024 * 5, 'image/'); // Изображение до 5 МБ
						$files->setUploadSettings(1024 * 1024 * 5, 'application/pdf'); // Pdf до 5 МБ
						break;
					default:
						$files->setUploadSettings(1024 * 1024 * 1, 'image/'); // Изображение до 5 МБ
						break;
				}
				$response = $files->upload($_FILES,$this->get['filesfield'],$this->get['filesform']);
				echo $response['html'];
				break;
			case 'removeUploaded':
				if (!session_id()) {
					session_start();
				}
				$files = new Files();
				$response = $files->removeUploaded($this->get['filesform']??'',$this->get['filesfield']??'',$this->get['key']??'0');
				echo $response['html'];
				break;
			default:
				$this->tpl = "RequestCustom1.tpl";
				break;
		}
	}
}
$request = new RequestTemplate ($_REQUEST);
$smarty->assign('get',$request->get);
$smarty->display($request->tpl);