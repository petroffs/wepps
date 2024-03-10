<?php
namespace WeppsAdmin\ConfigExtensions\Uploads;

use WeppsCore\Utils\RequestWepps;
use WeppsCore\Utils\UtilsWepps;
use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Core\DataWepps;
use WeppsAdmin\Lists\ListsWepps;
use PhpOffice\PhpSpreadsheet\IOFactory;

require_once '../../../../config.php';
require_once '../../../../autoloader.php';
require_once '../../../../configloader.php';

//http://host/packages/WeppsAdmin/ConfigExtensions/Processing/Request.php?id=5

class RequestUploadsWepps extends RequestWepps {
	public function request($action="") {
		$this->tpl = '';
		if (!isset($_SESSION['user']['ShowAdmin']) || $_SESSION['user']['ShowAdmin']!=1) {
			ExceptionWepps::error404();
		}
		switch ($action) {
			case "excel":
				$list = "s_UploadsSource";
				$id = (int) $this->get['source'];
				$objFile = new DataWepps("s_Files");
				if (isset($_SESSION['uploads']['list-data-form'])) {
					foreach ($_SESSION['uploads']['list-data-form'] as $key=>$value) {
						foreach ($value as $k=>$v) {
							$file = ListsWepps::getUploadFileName($v, $list, "Files", $id);
							$rowFile = array(
									'Name'=>$file['title'],
									'TableNameId'=>$id,
									'InnerName'=>$file['inner'],
									'TableName'=>$file['list'],
									'FileDate'=>date('Y-m-d H:i:s'),
									'FileSize'=>$file['size'],
									'FileExt'=>$file['ext'],
									'FileType'=>$file['type'],
									'TableNameField'=>$file['field'],
									'FileUrl'=>$file['url'],
									'FileDescription'=>json_encode(['source'=>$id],JSON_UNESCAPED_UNICODE),
							);
							$objFile->add($rowFile,'ignore');
							ListsWepps::removeUpload("upload",$v['url']);
							break;
						}
					}
				}
				
				/*
				 * Выбор последнего файла и его обработка PHPEXCEL
				 *  and Name like '%addfields%'
				 */
				$obj = new DataWepps("s_UploadsSource");
				$source = $obj->get($id)[0];
				
				if (!isset($source['Id']) || $id == 0) {
					UtilsWepps::getModal('Ошибка : Укажите источник');
				}
				
				$obj = new DataWepps("s_Files");
				$files = $obj->getMax("TableName='{$list}' and t.FileDescription!='' and JSON_EXTRACT(t.FileDescription, '$.source') = {$id}",1,1,"t.Id desc");
				if (!isset($files[0]['Id'])) {
					UtilsWepps::getModal('Ошибка : Файл не найден');
				}
				
				/*
				 * Получить содержимое файла для дальнейшей обработки
				 */
				$inputFileName = ConnectWepps::$projectDev['root'] . $files[0]['FileUrl'];
				$spreadsheet = IOFactory::load($inputFileName);
				$sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
				$sheetTitle = $spreadsheet->getActiveSheet()->getTitle();
				
				/*
				 * Загрузка данных по шаблону
				 */				
				if (!empty($source['Alias'])) {
					$class = "WeppsAdmin\\ConfigExtensions\\Uploads\\".$source['Alias'];
					$uploadObj = new $class(['title'=>$sheetTitle,'data'=>$sheetData]);
					$response = $uploadObj->setData();
				} else {
					$response = [
							'status'=>2,
							'message'=>'Шаблон для источника не задан'
					];
				}
				UtilsWepps::getModal($response['message']);
				break;
			default:
				ExceptionWepps::error404();
				break;
		}
	}
}
$request = new RequestUploadsWepps ($_REQUEST);
$smarty->assign('get',$request->get);
$smarty->display($request->tpl);
?>