<?php
namespace WeppsAdmin\Lists;

use WeppsCore\Utils\RequestWepps;
use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Validator\ValidatorWepps;
use WeppsCore\Core\DataWepps;
use WeppsAdmin\Admin\AdminWepps;
use WeppsCore\TextTransforms\TextTransformsWepps;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;

require_once '../../../config.php';
require_once '../../../autoloader.php';
require_once '../../../configloader.php';

if (!isset($_SESSION)) {
	@session_start();
}

/**
 * @var \Smarty $smarty
 */

class RequestListsWepps extends RequestWepps {
	public function request($action="") {
		$this->tpl = '';
		if (@ConnectWepps::$projectData['user']['ShowAdmin']!=1) {
			ExceptionWepps::error404();
		}
		switch ($action) {
			case "filter":
				$this->tpl = "RequestListFilter.tpl";
				$obj = new DataWepps($this->get['list']);
				$scheme = $obj->getScheme();
				if (!isset($scheme[$this->get['field']][0])) {
					ExceptionWepps::error404();
				}
				$type = $scheme[$this->get['field']][0]['Type'];
				if ($type=='area' || $type=='file') {
					ConnectWepps::$instance->close();
				} elseif (strstr($type, 'select_multi')) {
					$ex = explode("::", $type);
					$sql = "select distinct t.Id,t.{$ex[2]} from {$ex[1]} as t 
							inner join s_SearchKeys as sk on sk.Field1 = t.Id and sk.Field3='List::{$this->get['list']}::{$this->get['field']}'
							inner join {$this->get['list']} as l on sk.Name=l.Id order by t.{$ex[2]}";
					//UtilsWepps::debug($sql);
					$res = ConnectWepps::$instance->fetch($sql);
					$this->assign('fieldkey', 'Id');
					$this->assign('fieldname', $ex[2]);
					$this->assign('filters', $res);
				} elseif (strstr($type, 'select')) {
					$ex = explode("::", $type);
					$sql = "select distinct t.Id,t.{$ex[2]} from {$ex[1]} as t inner join {$this->get['list']} as l on t.Id=l.{$this->get['field']} order by t.{$ex[2]}";
					$res = ConnectWepps::$instance->fetch($sql);
					$this->assign('fieldkey', 'Id');
					$this->assign('fieldname', $ex[2]);
					$this->assign('filters', $res);
				} else {
					$sql = "select distinct {$this->get['field']} from {$this->get['list']} order by {$this->get['field']} limit 300";
					$res = ConnectWepps::$instance->fetch($sql);
					$this->assign('filters', $res);
				}
				break;
			case 'upload':
				if (!isset($this->get['filesfield'])) ExceptionWepps::error404();
				if (!isset($this->get['myform'])) ExceptionWepps::error404();
				if (!isset($_FILES)) ExceptionWepps::error404();
				$data = ListsWepps::addUpload($_FILES,$this->get['filesfield'],$this->get['myform']);
				echo $data['js'];
				break;
			case 'uploadRemove':
				if (!isset($this->get['filesfield'])) ExceptionWepps::error404();
				if (!isset($this->get['filename'])) ExceptionWepps::error404();
				$t = ListsWepps::removeUpload($this->get['filesfield'],$this->get['filename']);
				echo $t;
				break;
			case 'fileRemove':
				if (!isset($this->get['id']) || (int) $this->get['id']==0) ExceptionWepps::error404();
				$obj = new DataWepps("s_Files");
				$res = $obj->get("Id in ({$this->get['id']})");
				if (!isset($res[0]['Id'])) {
					ExceptionWepps::error404();
				}

				/*
				 * Проверка прав доступа
				 */
				$perm = AdminWepps::getPermissions(ConnectWepps::$projectData['user']['UserPermissions'],array('list'=>$res['TableName']));
				if ($perm['status']==0) ExceptionWepps::error404();
				
				/*
				 * Удалить файл (копии в pic удалим файлклинером)
				 */
				if (is_file(ConnectWepps::$projectDev['root'].$res['FileUrl'])) {
					unlink(ConnectWepps::$projectDev['root'].$res['FileUrl']);
				}
				
				/*
				 * Удалить файл из базы
				 */
				foreach ($res as $value) {
					/*
					 * Удалить файл (копии в pic удалим файлклинером)
					 */
					if (is_file(ConnectWepps::$projectDev['root'].$value['FileUrl'])) {
						unlink(ConnectWepps::$projectDev['root'].$value['FileUrl']);
					}
					
					/*
					 * Удалить файл из базы
					 */
					$obj->remove($value['Id']);
				}
				break;
			case 'fileSortable':
				if (empty($this->get['id'])) {
					ExceptionWepps::error404();
				}
				$tableName = 's_Files';
				$perm = AdminWepps::getPermissions(ConnectWepps::$projectData['user']['UserPermissions'],array('list'=>$tableName));
				if ($perm['status']==0) {
					ExceptionWepps::error404();
				}
				$ex = explode(",", $this->get['id']);
				$i = 1;
				$str = "";
				foreach ($ex as $value) {
					if ((int) $value == 0) {
						ExceptionWepps::error404();
					}
					$str .= "update s_Files set Priority='$i' where Id='$value';\n";
					$i++;
				}
				//UtilsWepps::debug($str);
				ConnectWepps::$db->exec($str);
				break;
			case 'fileDescription':
				if (empty($this->get['ids'])) {
					ExceptionWepps::error404();
				}
				$sql = "update s_Files set FileDescription=? where Id in ({$this->get['ids']})";
				ConnectWepps::$instance->query($sql,[$this->get['text']]);
				break;
			case "form":
				echo 1;
				break;
			case "save":
				if (!isset($this->get['pps_tablename_id']) || !isset($this->get['pps_tablename'])) {
					ExceptionWepps::error404();
				}
				
				$sql = "delete from s_ConfigFields where TableName='' and Field=''";
				ConnectWepps::$instance->query($sql);
				
				/*
				 * Проверка введенных данных (обязательные поля) с индикацией ошибки
				 */
				$obj = new DataWepps($this->get['pps_tablename']);
				$listScheme = $obj->getScheme();
				$this->errors = [];
				foreach ($listScheme as $key=>$value) {
					if ($value[0][$this->get['pps_tablename_mode']]!='disabled' && $value[0][$this->get['pps_tablename_mode']]!='hidden') {
						if ($value[0]['Required']==1) {
							$this->errors[$key] = ValidatorWepps::isNotEmpty(@$this->get[$key], "Не заполнено");
						}
					}
				}
				/*
				 * Специальная обработка 1 с индикацией ошибки
				 * Проверка на наличе заданного поля в схеме
				 */
				if ($this->get['pps_tablename']=='s_ConfigFields' && $this->get['pps_tablename_id']=='add') {
					$sql = "SELECT COLUMN_NAME as Col FROM INFORMATION_SCHEMA.COLUMNS
			                WHERE TABLE_SCHEMA = '".ConnectWepps::$projectDB['dbname']."' and TABLE_NAME = '{$this->get['pps_tablename']}'
			                and COLUMN_NAME = '{$this->get['Field']}'
			                ";
					$listSchemeReal = ConnectWepps::$instance->fetch($sql);
					if (isset($listSchemeReal[0]['Col']) && $listSchemeReal[0]['Col']==$this->get['Field']) {
						$this->errors['Field'] = "Уже используется";
					}
				}
				/*
				 * Индикация ошибок
				 */
				$outer = ValidatorWepps::setFormErrorsIndicate($this->errors, $this->get['form']);
				echo $outer['Out'];
				if ($outer['Co']==0) {
					/*
					 * Сохранение информации
					 */
					if ($this->get['pps_tablename_id']=='add') {
						$obj = new DataWepps($this->get['pps_tablename']);
						$id = $obj->add($this->get,1);
						$this->get['Id'] = $id;
						unset($this->get['Priority']);
						$outer = ListsWepps::setListItem($this->get['pps_tablename'],$id,$this->get);
					} else {
						$outer = ListsWepps::setListItem($this->get['pps_tablename'],$this->get['pps_tablename_id'],$this->get);
					}
					
					echo $outer['output'];
				}
				break;
			case "remove":
				if (!isset($this->get['id']) || !isset($this->get['list'])) {
					ExceptionWepps::error404();
				}
				$outer = ListsWepps::removeListItem($this->get['list'],$this->get['id'],$this->get['pps_path']);
				echo $outer['output'];
				break; 
			case "copy":
				if (!isset($this->get['id']) || !isset($this->get['list'])) {
					ExceptionWepps::error404();
				}
				
				//UtilsWepps::debug($this->get,1);
				
				$outer = ListsWepps::copyListItem($this->get['list'],$this->get['id'],$this->get['pps_path']);
				echo $outer['output'];
				break;
			case "propOptionAdd":
				if (!isset($this->get['id']) || !isset($this->get['value'])) {
					ExceptionWepps::error404();
				}
				$sql = "select Id,PValues from s_Properties where Id = '{$this->get['id']}'";
				$res = ConnectWepps::$instance->fetch($sql);
				if (!isset($res[0]['Id'])) {
					ExceptionWepps::error404();
				}
				$arr = explode("\r\n", $res[0]['PValues']);
				array_push($arr, $this->get['value']);
				$arr = array_unique($arr);
				$sql = "update s_Properties set PValues = '".implode("\r\n", $arr)."' where Id = '{$this->get['id']}'";
				ConnectWepps::$instance->query($sql);
				break;
			case "search":
				if (!isset($this->get['term'])) {
					ConnectWepps::$instance->close();
				}
				$id = $this->get['term'];
				$condition = "(t.Name like '%$id%' or t.TableName like '%$id%')";
				
				$obj = new DataWepps("s_Config");
				$obj->setConcat("t.Id as id,t.Name as value,concat('/_pps/lists/',t.TableName,'/') as Url");
				$res = $obj->getMax($condition);
				if (!isset($res[0]['Id'])) {
					ConnectWepps::$instance->close();
				}
				$json = json_encode($res,JSON_UNESCAPED_UNICODE);
				header('Content-type:application/json;charset=utf-8');
				echo $json;
				ConnectWepps::$instance->close();
				break;
			case "export":
				if (!isset($this->get['list'])) ExceptionWepps::error404();
				$tableName = $this->get['list'];
				$obj = new DataWepps($tableName);
				$tableData = $obj->get(null,5000,1);
				$filename = "data_$tableName.xlsx";
				
				$spreadsheet = new Spreadsheet();
				$spreadsheet->getProperties()->setCreator('Wepps')
				->setLastModifiedBy('Wepps')
				->setTitle('Office 2007 XLSX Document')
				->setSubject('Office 2007 XLSX Document')
				->setDescription('Test document for Office 2007 XLSX, generated using PHP classes.')
				->setKeywords('office 2007 openxml php')
				->setCategory('Wepps List result file');
				
				$i = 1;
				$j = 1;
				$scheme = $obj->getScheme();
				foreach ($scheme as $key => $value) {
					$str = trim($value[0]['Name']);
					$spreadsheet->setActiveSheetIndex(0)
					->setCellValueByColumnAndRow($j, $i, $str)
					->getColumnDimensionByColumn($j)->setWidth(12);
					$j++;
				}
				
				$i++;
				$j = 1;
				foreach ($scheme as $key => $value) {
					$str = trim($key);
					$spreadsheet->setActiveSheetIndex(0)
					->setCellValueByColumnAndRow($j, $i, $str);
					$j++;
				}
				
				$i++;
				foreach ($tableData as $k => $v) {
					$j = 1;
					foreach ($scheme as $key => $value) {
						$str = trim($v[$key]);
						$spreadsheet->setActiveSheetIndex(0)
						->setCellValueByColumnAndRow($j, $i, $str);
						$j++;
					}
					$i++;
				}
				
				$spreadsheet->getActiveSheet()
				->getStyle('A1:AZ2')
				->getFont()->setBold(2)
				->getColor()
				->setARGB('0080C0');
				
				$spreadsheet->getActiveSheet()
				->getStyle('A1:AZ2')
				->getFill()
				->setFillType('solid')->getStartColor()->setARGB('f1f1f1');
				
				$spreadsheet->getActiveSheet()->setTitle($tableName);
				$spreadsheet->setActiveSheetIndex(0);
				
				// Redirect output to a client’s web browser (Xlsx)
				header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
				header('Content-Disposition: attachment;filename='.$filename);
				header('Cache-Control: max-age=0');
				// If you're serving to IE 9, then the following may be needed
				header('Cache-Control: max-age=1');
				
				// If you're serving to IE over SSL, then the following may be needed
				header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
				header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
				header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
				header('Pragma: public'); // HTTP/1.0
				
				$writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
				$writer->save('php://output');
				
				ConnectWepps::$instance->close();
				break;
			default:
				ExceptionWepps::error404();
				break;
		}
	}
}
$request = new RequestListsWepps($_REQUEST);
$smarty->assign('get',$request->get);
$smarty->display($request->tpl);