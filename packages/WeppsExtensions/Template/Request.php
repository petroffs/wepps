<?php

/*
 * This file is part of Wepps Platform.
 *
 * (c) 2026 PETROFFS LLC (ООО «ПЕТРОФФС»)
 * Registered with Rospatent (Russian Federation): certificate No. 2026694013 of 03.11.2026.
 *
 * SPDX-License-Identifier: LicenseRef-Wepps-EULA
 *
 * Licensed under the Wepps Platform EULA: https://github.com/petroffs/wepps/blob/master/LICENSE
 * Free for non-commercial use (Community). Commercial use requires
 * a written license agreement with PETROFFS LLC.
 * This notice must not be removed or altered (EULA, sec. 2.2).
 *
 * @author Aleksei Petrov <mail@petroffs.com>
 */

namespace WeppsExtensions\Template;

require_once '../../../configloader.php';

use WeppsCore\Exception;
use WeppsCore\Request;
use WeppsExtensions\Addons\Files\Files;

class RequestTemplate extends Request {
	#[\Override]
	public function request($action = ''): void {
		switch ($action) {
			case 'files':
				if (!isset($this->get['fileUrl'])) {
					Exception::error(404);
				}
				$files = new Files();
				$files->output($this->get['fileUrl']);
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
				$response = $files->upload($_FILES, htmlspecialchars((string) $this->get['filesfield'], ENT_QUOTES), htmlspecialchars((string) $this->get['filesform'], ENT_QUOTES));
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