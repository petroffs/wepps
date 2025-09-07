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
				$data = $files->upload($_FILES,$this->get['filesfield'],$this->get['filesform']);
				echo $data['js'];
				Connect::$instance->close();
				break;
			case 'removeUploaded':
				if (!session_id()) {
					session_start();
				}
				if (empty($_SESSION['uploads'][$this->get['filesform']][$this->get['filesfield']][$this->get['key']])) {
					exit;
				}
				unlink($_SESSION['uploads'][$this->get['filesform']][$this->get['filesfield']][$this->get['key']]['filedest']);
				unset($_SESSION['uploads'][$this->get['filesform']][$this->get['filesfield']][$this->get['key']]);
				echo "<script>$('#{$this->get['filesform']}').find('input[name=\"{$this->get['filesfield']}\"]').parent().siblings('.pps_upload_add').children('[data-key=\"{$this->get['key']}\"]').remove();</script>";
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