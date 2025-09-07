<?php
require_once '../../../configloader.php';

use WeppsAdmin\Lists\Lists;
use WeppsCore\Request;
use WeppsCore\Exception;
use WeppsCore\Connect;
use WeppsCore\Validator;
use WeppsCore\Data;
use WeppsAdmin\Admin\Admin;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;

if (!session_id()) {
	@session_start();
}
class RequestLists extends Request {
	public function request($action="") {
		$this->tpl = '';
		if (@Connect::$projectData['user']['ShowAdmin']!=1) {
			Exception::error404();
		}
		switch ($action) {
			case "filter":
				$this->tpl = "RequestListFilter.tpl";
				$obj = new Data($this->get['list']);
				$scheme = $obj->getScheme();
				if (!isset($scheme[$this->get['field']][0])) {
					Exception::error404();
				}
				$type = $scheme[$this->get['field']][0]['Type'];
				if ($type=='area' || $type=='file') {
					Connect::$instance->close();
				} elseif (strstr($type, 'select_multi')) {
					$ex = explode("::", $type);
					$sql = "select distinct t.Id,t.{$ex[2]} from {$ex[1]} as t 
							inner join s_SearchKeys as sk on sk.Field1 = t.Id and sk.Field3='List::{$this->get['list']}::{$this->get['field']}'
							inner join {$this->get['list']} as l on sk.Name=l.Id order by t.{$ex[2]}";
					$res = Connect::$instance->fetch($sql);
					$this->assign('fieldkey', 'Id');
					$this->assign('fieldname', $ex[2]);
					$this->assign('filters', $res);
				} elseif (strstr($type, 'select')) {
					$ex = explode("::", $type);
					$sql = "select distinct t.Id,t.{$ex[2]} from {$ex[1]} as t inner join {$this->get['list']} as l on t.Id=l.{$this->get['field']} order by t.{$ex[2]}";
					$res = Connect::$instance->fetch($sql);
					$this->assign('fieldkey', 'Id');
					$this->assign('fieldname', $ex[2]);
					$this->assign('filters', $res);
				} else {
					$sql = "select distinct {$this->get['field']} from {$this->get['list']} order by {$this->get['field']} limit 300";
					$res = Connect::$instance->fetch($sql);
					$this->assign('filters', $res);
				}
				break;
			case 'upload':
				if (!isset($this->get['filesfield'])) Exception::error404();
				if (!isset($this->get['myform'])) Exception::error404();
				if (!isset($_FILES)) Exception::error404();
				$data = Lists::addUpload($_FILES,$this->get['filesfield'],$this->get['myform']);
				echo $data['js'];
				break;
			case 'uploadRemove':
				if (!isset($this->get['filesfield'])) Exception::error404();
				if (!isset($this->get['filename'])) Exception::error404();
				$t = Lists::removeUpload($this->get['filesfield'],$this->get['filename']);
				echo $t;
				break;
			case 'fileRemove':
				if (!isset($this->get['id']) || (int) $this->get['id']==0) Exception::error404();
				$obj = new Data("s_Files");
				$res = $obj->fetchmini("Id in ({$this->get['id']})");
				if (!isset($res[0]['Id'])) {
					Exception::error404();
				}

				/*
				 * Проверка прав доступа
				 */
				$perm = Admin::getPermissions(Connect::$projectData['user']['UserPermissions'],array('list'=>$res['TableName']));
				if ($perm['status']==0) Exception::error404();
				
				/*
				 * Удалить файл (копии в pic удалим файлклинером)
				 */
				if (is_file(Connect::$projectDev['root'].$res['FileUrl'])) {
					unlink(Connect::$projectDev['root'].$res['FileUrl']);
				}
				
				/*
				 * Удалить файл из базы
				 */
				foreach ($res as $value) {
					/*
					 * Удалить файл (копии в pic удалим файлклинером)
					 */
					if (is_file(Connect::$projectDev['root'].$value['FileUrl'])) {
						unlink(Connect::$projectDev['root'].$value['FileUrl']);
					}
					
					/*
					 * Удалить файл из базы
					 */
					$obj->remove($value['Id']);
				}
				break;
			case 'fileSortable':
				if (empty($this->get['id'])) {
					Exception::error404();
				}
				$tableName = 's_Files';
				$perm = Admin::getPermissions(Connect::$projectData['user']['UserPermissions'],array('list'=>$tableName));
				if ($perm['status']==0) {
					Exception::error404();
				}
				$ex = explode(",", $this->get['id']);
				$i = 1;
				$str = "";
				foreach ($ex as $value) {
					if ((int) $value == 0) {
						Exception::error404();
					}
					$str .= "update s_Files set Priority='$i' where Id='$value';\n";
					$i++;
				}
				//Utils::debug($str);
				Connect::$db->exec($str);
				break;
			case 'fileDescription':
				if (empty($this->get['ids'])) {
					Exception::error404();
				}
				$sql = "update s_Files set FileDescription=? where Id in ({$this->get['ids']})";
				Connect::$instance->query($sql,[$this->get['text']]);
				break;
			case "form":
				echo 1;
				break;
			case "save":
				if (!isset($this->get['pps_tablename_id']) || !isset($this->get['pps_tablename'])) {
					Exception::error404();
				}
				
				$sql = "delete from s_ConfigFields where TableName='' and Field=''";
				Connect::$instance->query($sql);
				
				/*
				 * Проверка введенных данных (обязательные поля) с индикацией ошибки
				 */
				$obj = new Data($this->get['pps_tablename']);
				$listScheme = $obj->getScheme();
				$this->errors = [];
				foreach ($listScheme as $key=>$value) {
					if ($value[0][$this->get['pps_tablename_mode']]!='disabled' && $value[0][$this->get['pps_tablename_mode']]!='hidden') {
						if ($value[0]['Required']==1) {
							$this->errors[$key] = Validator::isNotEmpty(@$this->get[$key], "Не заполнено");
						}
					}
				}
				/*
				 * Специальная обработка 1 с индикацией ошибки
				 * Проверка на наличе заданного поля в схеме
				 */
				if ($this->get['pps_tablename']=='s_ConfigFields' && $this->get['pps_tablename_id']=='add') {
					$sql = "SELECT COLUMN_NAME as Col FROM INFORMATION_SCHEMA.COLUMNS
			                WHERE TABLE_SCHEMA = '".Connect::$projectDB['dbname']."' and TABLE_NAME = '{$this->get['pps_tablename']}'
			                and COLUMN_NAME = '{$this->get['Field']}'
			                ";
					$listSchemeReal = Connect::$instance->fetch($sql);
					if (isset($listSchemeReal[0]['Col']) && $listSchemeReal[0]['Col']==$this->get['Field']) {
						$this->errors['Field'] = "Уже используется";
					}
				}
				/*
				 * Индикация ошибок
				 */
				$outer = Validator::setFormErrorsIndicate($this->errors, $this->get['form']);
				echo $outer['html'];
				if ($outer['count']==0) {
					/*
					 * Сохранение информации
					 */
					if ($this->get['pps_tablename_id']=='add') {
						$obj = new Data($this->get['pps_tablename']);
						$id = $obj->add($this->get,1);
						$this->get['Id'] = $id;
						unset($this->get['Priority']);
						$outer = Lists::setListItem($this->get['pps_tablename'],$id,$this->get);
					} else {
						$outer = Lists::setListItem($this->get['pps_tablename'],$this->get['pps_tablename_id'],$this->get);
					}
					echo $outer['html'];
				}
				break;
			case "remove":
				if (!isset($this->get['id']) || !isset($this->get['list'])) {
					Exception::error404();
				}
				$outer = Lists::removeListItem($this->get['list'],$this->get['id'],$this->get['pps_path']);
				echo $outer['html'];
				break; 
			case "copy":
				if (!isset($this->get['id']) || !isset($this->get['list'])) {
					Exception::error404();
				}
				$outer = Lists::copyListItem($this->get['list'],$this->get['id'],$this->get['pps_path']);
				echo $outer['html'];
				break;
			case "propOptionAdd":
				if (!isset($this->get['id']) || !isset($this->get['value'])) {
					Exception::error404();
				}
				$sql = "select Id,PValues from s_Properties where Id = '{$this->get['id']}'";
				$res = Connect::$instance->fetch($sql);
				if (!isset($res[0]['Id'])) {
					Exception::error404();
				}
				$arr = explode("\r\n", $res[0]['PValues']);
				array_push($arr, $this->get['value']);
				$arr = array_unique($arr);
				$sql = "update s_Properties set PValues = '".implode("\r\n", $arr)."' where Id = '{$this->get['id']}'";
				Connect::$instance->query($sql);
				break;
			case "search":
				if (!isset($this->get['term'])) {
					Connect::$instance->close();
				}
				$id = $this->get['term'];
				$condition = "(t.Name like '%$id%' or t.TableName like '%$id%')";
				
				$obj = new Data("s_Config");
				$obj->setConcat("t.Id as id,t.Name as value,concat('/_wepps/lists/',t.TableName,'/') as Url");
				$res = $obj->fetch($condition);
				if (!isset($res[0]['Id'])) {
					Connect::$instance->close();
				}
				$json = json_encode($res,JSON_UNESCAPED_UNICODE);
				header('Content-type:application/json;charset=utf-8');
				echo $json;
				Connect::$instance->close();
				break;
			case "export":
				if (!isset($this->get['list'])) Exception::error404();
				$tableName = $this->get['list'];
				$obj = new Data($tableName);
				$tableData = $obj->fetchmini(null,5000,1);
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
				
				Connect::$instance->close();
				break;
			default:
				Exception::error404();
				break;
		}
	}
}
$request = new RequestLists($_REQUEST);
$smarty->assign('get',$request->get);
$smarty->display($request->tpl);