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

namespace WeppsAdmin\ConfigExtensions\Uploads;

require_once __DIR__ . '/../../../../configloader.php';

use WeppsAdmin\Admin\AdminUtils;
use WeppsCore\Request;
use WeppsCore\Exception;
use WeppsCore\Connect;
use WeppsCore\Data;
use WeppsAdmin\Lists\Lists;
use PhpOffice\PhpSpreadsheet\IOFactory;
use WeppsCore\Utils;

class RequestUploads extends Request
{
	#[\Override]
	public function request($action = ""): void
	{
		$this->tpl = '';
		if ((Connect::$projectData['user']['ShowAdmin'] ?? 0) != 1) {
			Exception::error404();
		}

		switch ($action) {
			case "excel":
				if (!isset($_SESSION)) {
					@session_start();
				}
				$list = "s_UploadsSource";
				$id = (int) $this->get['source'];
				$objFile = new Data("s_Files");
				if (isset($_SESSION['uploads']['list-data-form'])) {
					foreach ($_SESSION['uploads']['list-data-form'] as $value) {
						foreach ($value as $v) {
							$file = Lists::getUploadFileName($v, $list, "Files", $id);
							$rowFile = [
								'Name' => $file['title'],
								'TableNameId' => $id,
								'InnerName' => $file['inner'],
								'TableName' => $file['list'],
								'FileDate' => date('Y-m-d H:i:s'),
								'FileSize' => $file['size'],
								'FileExt' => $file['ext'],
								'FileType' => $file['type'],
								'TableNameField' => $file['field'],
								'FileUrl' => $file['url'],
								'FileDescription' => json_encode(['source' => $id], JSON_UNESCAPED_UNICODE),
								'Guid' => $file['guid'],
							];
							$objFile->add($rowFile);
							Lists::removeUpload("upload", $v['url']);
							break;
						}
					}
				}

				/*
				 * Выбор последнего файла и его обработка PHPEXCEL
				 *  and Name like '%addfields%'
				 */
				$obj = new Data("s_UploadsSource");
				$source = $obj->fetchmini((string) $id)[0];

				if (!isset($source['Id']) || $id === 0) {
					AdminUtils::modal('Ошибка : Укажите источник');
				}

				$obj = new Data("s_Files");
				$files = $obj->fetch("TableName='{$list}' and t.FileDescription!='' and JSON_EXTRACT(t.FileDescription, '$.source') = {$id}", 1, 1, "t.Id desc");
				if (!isset($files[0]['Id'])) {
					AdminUtils::modal('Ошибка : Файл не найден');
				}

				/*
				 * Получить содержимое файла для дальнейшей обработки
				 */
				// Файл из БД: относительный путь без .. — чтение только внутри корня
				$relFile = Utils::safeRelPath(ltrim((string) $files[0]['FileUrl'], '/')) ?? '';
				if ($relFile === '') {
					AdminUtils::modal('Ошибка : Файл не найден');
				}
				$inputFileName = Connect::$projectDev['root'] . $relFile;
				$spreadsheet = IOFactory::load($inputFileName);
				$sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
				$sheetTitle = $spreadsheet->getActiveSheet()->getTitle();

				/*
				 * Загрузка данных по шаблону
				 */
				if (!empty($source['Alias'])) {
					$class = "WeppsAdmin\\ConfigExtensions\\Uploads\\" . $source['Alias'];
					// Alias — из внутренней конфигурации загрузок
					/** @psalm-suppress TaintedInput */
					$uploadObj = new $class(['title' => $sheetTitle, 'data' => $sheetData]);
					$response = $uploadObj->setData();
				} else {
					$response = [
						'status' => 2,
						'message' => 'Шаблон для источника не задан'
					];
				}
				AdminUtils::modal($response['message']);
				break;
			default:
				Exception::error404();
				break;
		}
	}
}
$request = new RequestUploads($_REQUEST);
$smarty->assign('get', $request->get);
$smarty->display($request->tpl);
