<?php
require_once '../../../../configloader.php';

use WeppsAdmin\Admin\AdminUtils;
use WeppsCore\Request;
use WeppsCore\Exception;
use WeppsCore\Connect;
use WeppsCore\Data;
use WeppsAdmin\Lists\Lists;
use PhpOffice\PhpSpreadsheet\IOFactory;

class RequestUploads extends Request
{
	public function request($action = "")
	{
		$this->tpl = '';
		if (@Connect::$projectData['user']['ShowAdmin'] != 1) {
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
							$rowFile = array(
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
							);
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
				$source = $obj->fetchmini($id)[0];

				if (!isset($source['Id']) || $id == 0) {
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
				$inputFileName = Connect::$projectDev['root'] . $files[0]['FileUrl'];
				$spreadsheet = IOFactory::load($inputFileName);
				$sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
				$sheetTitle = $spreadsheet->getActiveSheet()->getTitle();

				/*
				 * Загрузка данных по шаблону
				 */
				if (!empty($source['Alias'])) {
					$class = "WeppsAdmin\\ConfigExtensions\\Uploads\\" . $source['Alias'];
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