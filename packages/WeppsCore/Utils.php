<?php
namespace WeppsCore\Utils;

use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Core\SmartyWepps;
use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\Spell\SpellWepps;
use WeppsCore\Validator\ValidatorWepps;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use WeppsExtensions\Addons\Jwt\JwtWepps;

/**
 * Утилиты
 * @author Petroffscom
 *
 */
class UtilsWepps {
	/**
	 * Debug
	 */
	public static function debug($var,int $rule=0,string $filename='') {
		$filename = (empty($filename)) ? __DIR__ . "/../../debug.conf":$filename;
		$separator = "\n===================\n";
		$header = "";
		if (ConnectWepps::$projectDev['debug']==1) {
			$backtrace = debug_backtrace();
			$header = "".date('Y-m-d H:i:s')."\n";
			$header .= "{$backtrace[0]['file']}:{$backtrace[0]['line']}\n";
			if (!empty($backtrace[1]['file'])) {
				$header .= "{$backtrace[1]['file']}:{$backtrace[1]['line']}\n";
			}
			if (!empty($backtrace[2]['file'])) {
				$header .= "{$backtrace[2]['file']}:{$backtrace[2]['line']}\n";
			}
			$header = trim($header).$separator;
		}
		$val = print_r($var,true);
		switch ($rule) {
			case 2:
				$output = $header.$val;
				file_put_contents ($filename,$output);
				break;
			case 21:
				$output = $header.$val;
				file_put_contents($filename,$output);
				ConnectWepps::$instance->close();
				break;
			case 22:
				$output = $header.$val."\n\n";
				$fp = fopen($filename, 'a+');
				fwrite($fp, $output);
				fclose($fp);
				break;
			case 3:
				$output = $header.$val;
				echo $output;
				break;
			case 31:
				$output = $header.$val;
				echo $output;
				ConnectWepps::$instance->close();
				break;
			case 1:
				$val = htmlspecialchars($val);
				$output = "\n<pre style='font:14px sans-serif;text-align:left;color:black;background: #80FF80;border:1px solid gray;box-sizing:border-box;margin:0;padding: 12px;width:100%;max-width:100%;height:400px;overflow:auto;position:relative;z-index:999;'>\n";
				$output .= $header.$val;
				$output .= "\n</pre>\n";
				echo $output;
				ConnectWepps::$instance->close();
				break;
			default:
				$val = htmlspecialchars($val);
				$output = "\n<pre style='font:14px sans-serif;text-align:left;color:black;background: #80FF80;border:1px solid gray;box-sizing:border-box;margin:0;padding: 12px;width:100%;max-width:100%;height:400px;overflow:auto;position:relative;z-index:999;'>\n";
				$output .= $header.$val;
				$output .= "\n</pre>\n";
				echo $output;
				break;
		}
		return true;
	}
	
	/**
	 * Форматирование входной строки
	 * @param (string|array) $value
	 * @return string
	 */
	public static function trim($value,string $chr='') {
		if (is_array($value)) {
			foreach ($value as $k => $v) {
				if (is_array($v)) {
					foreach ($v as $k1=>$v1) {
						$value[$k][$k1] = self::trim($v1,$chr);
					}
				} else {
					$value[$k] = self::trim($v,$chr);
				}
			}
			return $value;
		}
		if (is_string($value)) {
			$value = trim($value,$chr);
		}
		#$value = htmlspecialchars ( $value );
		return $value;
	}
	
	/**
	 * Форматирование телефонного номера
	 * @param string|array $value
	 * @return array
	 */
	public static function phone(string $string='') : array {
		$num = preg_replace("/[^0-9]/","",$string);
		$num = preg_replace("/^8(.+)/","7$1",$num);
		if (strlen($num)==11 && substr($num,0,2)=='79') {
			#РФ
			$part1 = substr($num,0,4);
			$part2 = substr($num,4,3);
			$part3 = substr($num,7,2);
			$part4 = substr($num,9,2);
			return [
					'num'=>(int)$num,
					'view'=>"+".$part1." ".$part2."-".$part3."-".$part4
			];
		} elseif (strlen($num)==12 && substr($num,0,3)=='375') {
			#РБ
			$part1 = substr($num,0,5);
			$part2 = substr($num,5,3);
			$part3 = substr($num,8,2);
			$part4 = substr($num,10,2);
			return [
					'num'=>(int)$num,
					'view'=>"+".$part1." ".$part2."-".$part3."-".$part4
			];
		}
		return [];
	}
	
	/**
	 * Компоновка SQL запроса на основе входного массива array('Row1'=>'ROW1','Row2'=>'ROW2');
	 * @param array $row
	 * @return array
	 */
	public static function query(array $row) : array {
		$strCond = $strUpdate = $strInsert1 = $strInsert2 = $strSelect = "";
		if (empty($row)) {
			return [];
		}
		foreach ($row as $key => $value ) {
			$value = self::trim(str_replace(["&gt;","&lt;","&quot;"],[">","<","\""],$value));
			$value1 = (empty($value)) ? "null" : "'{$value}'";
			if (strstr($key,'@')) {
				$key = str_replace('@', '', $key);
				$value1 = $value;
				$strUpdate .= "{$key}={$value}, ";
				$strInsert2 .= "{$value},";
			} elseif ($value1=="null" && $key=="GUID") {
				$value1 = "uuid()";
				$strUpdate .= "{$key}=uuid(), ";
				$strInsert2 .= "uuid(),";
			} else {
				$strUpdate .= "{$key}=".ConnectWepps::$db->quote($value).", ";
				$strInsert2 .= "".ConnectWepps::$db->quote($value).",";
			}
			$strInsert1 .= "$key,";
			$strCond .= "{$key}={$value1} and ";
			$strSelect .= "{$value1} {$key}, ";
		}
		$strCond = ($strCond != "")?trim($strCond," and "):"";
		$strCond = str_replace("\n","\\n",$strCond);
		$strUpdate = ($strCond != "")?trim($strUpdate, ", "):"";
		$strUpdate = str_replace ("\r\n", "\\n",$strUpdate);
		$strInsert = ($strCond != "")?"(".trim($strInsert1,",").") values (".trim($strInsert2,",").")":"";
		$strInsert = str_replace("\n","\\n",$strInsert).";";
		$strSelect= ($strCond != "")?trim($strSelect,", "):"";
		$strSelect = str_replace("\r\n","\\n",$strSelect);
		$outer = ($strCond != "") ? [
				"insert" => $strInsert,
				"update" => $strUpdate,
				"condition" => $strCond,
				"select" => $strSelect
		] : [];
		return $outer;
	}

	/**
	 * Установка массиву ключей по произвольному полю
	 * @param array $value
	 * @param string $index
	 * @return array
	 */
	public static function array(array $value,string $index='Id',string $output='') : array {
		$arr = [];
		if (!is_array($value)) return array();
		
		if ($output=='') {
			foreach ($value as $v) {
				$arr[$v[$index]] = $v;
			}
		} else {
			foreach ($value as $v) {
				$arr[$v[$index]] = $v[$output];
			}
		}
		return $arr;
	}
	
	/**
	 * Получить массив из строки
	 */
	public static function arrayFromString(string $string,string $columns="\t",string $rows="\n") : array {
		$output = [];
		$string = str_replace("\r", "", $string);
		$string = trim($string);
		
		$ex = explode($rows,$string);
		foreach ($ex as $i=>$value) {
			$tabs = explode($columns, trim($value));
			foreach ($tabs as $j=>$v) {
				$output[$i][$j] = $v;
			}
		}
		return $output;
	}
	
	public static function modal(string $message='') {
		$js = "
			<script>
				let dialogWidth = (window.screen.width<400) ? '90%' : 400;
				$('#dialog').html('<p>{$message}</p>').dialog({
					title:'Сообщение',
					modal: true,
					resizable: false,
      				width: dialogWidth,
   					buttons : [
						{
							text : 'ОК',
							icon : 'ui-icon-check',
							click : function() {
								$(this).dialog('close');
							}
						},{
							text : 'Обновить',
							icon : 'ui-icon-refresh',
							click : function() {
								location.reload();
							}
						}]
				});
			</script>
		";
		echo $js;
		ConnectWepps::$instance->close();
	}
	public static function guid(string $string='') : string {
		$charid = ($string=='') ? strtolower(md5(uniqid(rand(), true))) : strtolower(md5($string));
		$guid = substr($charid,  0, 8) . '-' .
				substr($charid,  8, 4) . '-' .
				substr($charid, 12, 4) . '-' .
				substr($charid, 16, 4) . '-' .
				substr($charid, 20, 12);
				return $guid;
	}
	public static function setExcel(array $data,string $filename='') {
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
		$fields = $data[0];
		foreach ($fields as $key=>$value) {
			$str = trim($key);
			$spreadsheet->setActiveSheetIndex(0)
			->setCellValueExplicit([$j, $i], $str,'s')
			->getColumnDimensionByColumn($j)->setWidth(12);
			$j++;
		}
		
		$i++;
		foreach ($data as $v) {
			$j = 1;
			foreach ($fields as $key => $value) {
				$str = trim($v[$key]);
				$spreadsheet->setActiveSheetIndex(0)
				->setCellValueExplicit([$j, $i], $str,'s');
				$j++;
			}
			$i++;
		}
		
		$spreadsheet->getActiveSheet()
		->getStyle('A1:AZ1')
		->getFont()->setBold(2)
		->getColor()
		->setARGB('0080C0');
		
		$spreadsheet->getActiveSheet()
		->getStyle('A1:AZ1')
		->getFill()
		->setFillType('solid')->getStartColor()->setARGB('f1f1f1');
		
		$spreadsheet->getActiveSheet()->setTitle("Data");
		$spreadsheet->setActiveSheetIndex(0);
		$writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
		if (!empty($filename)) {
			/*
			 * Записать в файл для проверки
			 */
			$writer->save($filename);
			return;
		}
		ob_start();
		$writer->save('php://output');
		$content = ob_get_clean();
		return $content;
	}
	public static function getExcel(string $filename) {
		
	}
	public static function getAllHeaders() : array {
		$headers = getallheaders();
		#array_map('strtolower', $haystack);
		foreach ($headers as $key=>$value) {
			unset($headers[$key]);
			$headers[strtolower($key)] = $value;
		}
		return $headers;
	}
}

/**
 * Запросы AJAX
 */
abstract class RequestWepps {
	/**
	 * Массив с входными переменными
	 * @var array
	 */
	public $get = [];
	
	/**
	 * Переменные для шаблонов
	 * @var array
	 */
	private $assign = [];
	
	/**
	 * Шаблон вывода
	 * @var string
	 */
	public $tpl;
	
	/**
	 * Инициализация $smarty
	 * @var \Smarty
	 */
	#private $smarty;
	
	/**
	 * Подключение шаблона, который передается в общий шаблон self::$tpl
	 * @var array
	 */
	private $fetch = [];
	
	/**
	 * Закрытие соединения с БД
	 */
	public $noclose = 0;
	
	/**
	 * Ошибки входных данных
	 */
	public $errors = [];
	
	public function __construct(array $settings=[]) {
		$this->get = UtilsWepps::trim ($settings);
		$action = (isset($this->get['action'])) ? $this->get['action'] : '';
		$this->request($action);
		if ($this->noclose==0) {
			if ($this->tpl == '') {
				ConnectWepps::$instance->close();
			}
			$this->cssjs();
			ConnectWepps::$instance->close(0);
		}
		return;
	}
	
	/**
	 * Обработка запроса (Реализация логики)
	 */
	abstract function request();
	
	/**
	 * Подключение стилей и js-сценариев
	 * Подключаются автоматически, при наличии файла:
	 * Для шаблона $this->tpl = "RequestExample.tpl" требуется
	 * файл RequestExample.tpl.css или RequestExample.tpl.js
	 * Шаблон следует определять в self::request()
	 */
	private function cssjs() {
		if ($this->tpl == '') return;
		$smarty = SmartyWepps::getSmarty();
		$css = (is_file($this->tpl.'.css')) ? 1 : 0;
		$js = (is_file($this->tpl.'.js')) ? 1 : 0;
		foreach ($this->assign as $key=>$value) {
			$smarty->assign($key,$value);
		}
		if ($css==1 || $js==1) {
			$this->get['cssjs'] = '';
			if ($css == 1) $this->get['cssjs']  = "<style>{$smarty->fetch($this->tpl.'.css')}</style>";
			if ($js == 1)  $this->get['cssjs'] .= "<script type=\"text/javascript\">{$smarty->fetch($this->tpl.'.js')}</script>";
		}
	}
	public function assign($key,$value) {
		$this->assign[$key] = $value;
	}
	public function fetch($key,$value) {
		$smarty = SmartyWepps::getSmarty();
		foreach ($this->assign as $k=>$v) {
			$smarty->assign($k,$v);
		}
		$smarty->assign($key,$smarty->fetch($value));
	}
}

/**
 * Генерация html-кода ссылок на css-таблицы и js-библиотеки для применения в шаблоне сайта
 * Генерация html-кода meta-тегов
 */
class TemplateHeadersWepps {
	public static $rand;
	private $output = array (
			'meta' => '',
			'cssjs' => '' 
	);
	private $cssjs = array(
			'js' => array(),
			'css' => array()
	);
	
	public function js(string $filename) {
		return $this->cssjs['js'][] = "\n".'<script type="text/javascript" src="'.$filename.'"></script>';
	}
	public function css(string $filename) {
		return $this->cssjs['css'][] .= "\n".'<link rel="stylesheet" type="text/css" href="'.$filename.'"/>';
	}
	public function meta(string $meta) {
		return $this->output['meta'] .= "\n".$meta;
	}
	private function join_old(string $str) {
		$ex = explode("\n",$str);
		if ($ex[0]=='') unset($ex[0]);
		$match = [];
		foreach ($ex as $value) {
			if (strstr($value, "text/javascript")) {
				preg_match('/src="(.+)"></', $value,$match);
				$this->js($match[1]);
			} else {
				preg_match('/href="(.+)"\/>/', $value,$match);
				$this->css($match[1]);
			}
		}
	}
	public function join(TemplateHeadersWepps $headers) {
		$this->cssjs['js'] = array_merge($this->cssjs['js'],$headers->cssjs['js']);
		$this->cssjs['css'] = array_merge($this->cssjs['css'],$headers->cssjs['css']);
	}
	/**
	 * Установка $this->output - содержит html-код
	 * @return string[]
	 */
	public function get() {
		$this->cssjs['css'] = array_unique($this->cssjs['css']);
		$this->cssjs['js'] = array_unique($this->cssjs['js']);
		$this->output['cssjs'] = implode("", $this->cssjs['css']) . implode("", $this->cssjs['js']);
		return $this->output;
	}
}

/**
 * Работа с файлами
 */
class FilesWepps {
	/**
	 * Вывод указанного файла в браузер на сохранение или открытие на стороне клиента
	 * @param string $file
	 */
	public static function output(string $filename) {
		$filenameFull = ConnectWepps::$projectDev['root'] . $filename;
		if (!is_file($filenameFull)) {
			http_response_code(404);
			ConnectWepps::$instance->close();
		}
		$sql = "select * from s_Files where FileUrl='$filename' limit 1";
		$res = ConnectWepps::$instance->fetch($sql);
		if (count($res) == 0) ExceptionWepps::error404();
		$row = $res[0];
		$filetitle = $row['Name'];

		header("Content-type: application/octet-stream");
		header("Content-Disposition: attachment; filename=\"$filetitle\"");
		header("Content-Length: ".filesize($filenameFull));
		header("Last-Modified: " . gmdate("D, d M Y H:i:s",mktime (0,0,0,1,1,2000)) . " GMT"); // Дата в прошлом
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		readfile($filenameFull);
		exit();
	}

	/**
	 * Очистка файловой ситсемы от файлов, не указанных в s_Files
	 */
	public static function clear() {

	}

	/**
	 * Запись файлов в s_Files с физическим копировнием
	 */
	public static function save($file) {

	}
	
	/**
	 * Загрузка файлов из формы, расчитано что за один раз грузится 1 файл,
	 * в дальнешем проработать возможность мультизагрузки (или вызывать этот
	 * метод необходимое кол-во раз при таком случае.
	 * 
	 * @param array $myFiles Массив с загруженными файлами ($_FILES)
	 * @param string $filesfield Наименование html-элемента input[type="file"]
	 * @param string $myform Идентификатор формы
	 * @return array
	 */
	public static function upload(array $myFiles,string $filesfield,string $myform) : array {
		$root = ConnectWepps::$projectDev['root'];
		$errors = [];
		/*
		 * Не все изображения имеют эту метку, возможны ошибки
		 * Переработать таким образом, чтобы была входная информация
		 * о типе загруженного файла и в зависимости от этого делать
		 * валидацию
		 */
		if (!strstr($myFiles[0]['type'],"image/")) {
			$errors[$filesfield] = "Неверный тип файла";
			$outer = ValidatorWepps::setFormErrorsIndicate($errors,$myform);
			return ['error'=>$errors[$filesfield],'js'=>$outer['Out']];
		}
		if ((int)$myFiles[0]['size']>3000000) {
			#1 мегабайт = 1 000 000 байт
			$errors[$filesfield] = "Слишком большой файл";
			$outer = ValidatorWepps::setFormErrorsIndicate($errors,$myform);
			return ['error'=>$errors[$filesfield],'js'=>$outer['Out']];
		}
		$filepathinfo = pathinfo($myFiles[0]['name']);
		$filepathinfo['filename'] = strtolower(SpellWepps::getTranslit($filepathinfo['filename'],2));
		$filedest = "{$root}/packages/WeppsExtensions/Addons/Forms/uploads/{$filepathinfo['filename']}-".date("U").".{$filepathinfo['extension']}";
		move_uploaded_file($myFiles[0]['tmp_name'],$filedest);
		if (!isset($_SESSION['uploads'][$myform][$filesfield])) {
			$_SESSION['uploads'][$myform][$filesfield] = array();
		}
		array_push($_SESSION['uploads'][$myform][$filesfield], $filedest);
		$_SESSION['uploads'][$myform][$filesfield] = array_unique($_SESSION['uploads'][$myform][$filesfield]);
		$js = "	<script>
		$('.fileadd').remove();
		$('input[name=\"{$filesfield}\"]').parent().append($('<p class=\"pps_fileadd\">Загружен файл &laquo;{$myFiles[0]['name']}&raquo;</p>'));
		$('label.{$filesfield}').siblings('.pps_error').trigger('click');
		</script>";
		$data = ['success' => 'Form was submitted','js'=>$js];
		return $data;
	}
}
/**
 * Командная строка
 */
class CliWepps {
	private $display = 0;
	public function __construct() {
		$this->display();
	}
	public function display(bool $display = true) {
		$this->display = $display;
	}
	public function error(string $text='') {
		return self::outer(self::color("[error] $text",'e'));
	}
	public function success(string $text='') {
		return self::outer(self::color("[success] $text",'s'));
	}
	public function warning(string $text='') {
		return self::outer(self::color("[warning] $text",'w'));
	}
	public function info(string $text='') {
		return self::outer(self::color("[info] $text",'i'));
	}
	public function text(string $text='') {
		return self::outer(self::color($text));
	}
	public function br() {
		return self::outer("\n");
	}
	public function progress($done, $total) {
		$perc = floor(($done / $total) * 100);
		$left = 100 - $perc;
		$rate = 0.5;
		$perc2 = floor($perc * $rate);
		$left2 = ceil($left * $rate);
		$write = sprintf("\033[0G\033[2K[%'#{$perc2}s#%-{$left2}s] $done/$total [$perc%%]", "", "");
		echo $write;
	}
	public function copy(string $source,string $destination,bool $overwrite=true) : bool {
		if ($overwrite===false && file_exists($destination)) {
			return false;
		}
		$path = pathinfo($destination);
		if (!file_exists($path['dirname'])) {
			mkdir($path['dirname'], 0755, true);
		}
		if (!copy($source, $destination)) {
			return false;
		}
		return true;
	}
	public function move(string $source,string $destination,bool $overwrite=true) {
		if ($overwrite===false && file_exists($destination)) {
			return false;
		} elseif (!file_exists($source)) {
			return false;
		}
		$path = pathinfo($destination);
		if (!file_exists($path['dirname'])) {
			mkdir($path['dirname'], 0755, true);
		}
		if (!rename($source, $destination)) {
			return false;
		}
		return true;
	}
	public function put($content,$destination) {
		$path = pathinfo($destination);
		if (!file_exists($path['dirname'])) {
			mkdir($path['dirname'], 0755, true);
		}
		if (!file_put_contents($destination,$content)) {
			return false;
		}
		return true;
	}
	public function mkdir(string $dir) : bool {
		$dir = str_replace('\\', '/', $dir);
		if (!stristr($dir, ConnectWepps::$projectDev['root'])) {
			$this->warning('no project path');
			return false;
		}
		if (!is_dir($dir)) {
			mkdir($dir,0755,true);
		}
		return true;
	}
	public function rmdir(string $dir) {
		$dir = str_replace('\\', '/', $dir);
		if (!is_dir($dir)) {
			$this->warning('no dir');
			return false;
		} elseif (!stristr($dir, ConnectWepps::$projectDev['root'])) {
			$this->warning('no project path');
			return false;
		}
		exec("rm $dir -rf");
		return true;
	}
	public function rmfile(string $file) {
		if (!file_exists($file)) {
			return false;
		}
		unlink($file);
		return true;
	}
	public function cmd(string $cmd, bool $silent = false) : array {
		if (empty($cmd)) {
			$this->warning("cmd is empty");
		}
		$o = [];
		$v = 100;
		exec("$cmd 2>&1", $o ,$v);
		if ($v!=0) {
			if (!empty($o[0])) {
				$this->error($o[0]);
			} else {
				$this->error('cmd');
			}
			exit();
		}
		if ($silent == false && !empty($o)) {
			$this->info(implode("\n",$o));
		}
		return $o;
	}
	private function color(string $str='', string $type='') : string {
		$output = '';
		switch ($type) {
			case 'e': //error
				$output = "\033[0;31;47m$str\033[0m\n";
				break;
			case 's': //success
				$output = "\033[32m$str\033[0m\n";
				break;
			case 'w': //warning
				$output = "\033[33m$str\033[0m\n";
				break;
			case 'i': //info
				$output = "\033[36m$str\033[0m\n";
				break;
			default:
				$output = "$str\n";
				break;
		}
		return $output;
	}
	private function outer(string $text='') : string {
		if ($this->display == true) {
			echo $text;
		}
		return $text;
	}
}

class UsersWepps {
	private $token = '';
	private $get = [];
	private $errors = [];
	public function __construct(array $settings = []) {
		$this->get = $settings;
	}
	public function signIn() : bool {
		$sql = "select * from s_Users where Login=? and ShowAdmin=1 and DisplayOff=0";
		$res = ConnectWepps::$instance->fetch($sql,[$this->get['login']]);
		$this->errors = [];
		if (empty($res[0]['Id'])) {
			$this->errors['login'] = 'Неверный логин';
		} elseif (!password_verify($this->get['password'],$res[0]['Password'])) {
			$this->errors['password'] = 'Неверный пароль';
		}
		if (!empty($this->errors)) {
			return false;
		}
		$lifetime = 3600*24*180;
		$jwt = new JwtWepps();
		$token = $jwt->token_encode([
				'typ'=>'auth',
				'id'=>$res[0]['Id']
		],$lifetime);
		#setcookie('wepps_token', $token, time() + $lifetime,'/',ConnectWepps::$projectDev['host'],true,true);
		setcookie('wepps_token', $token, [
				'expires' => time() + $lifetime,
				'path' => '/',
				'domain' => ConnectWepps::$projectDev['host'],
				'secure' => true,
				'httponly' => true,
				'samesite' => 'Strict',
		]);
		ConnectWepps::$instance->query("update s_Users set AuthDate=?,AuthIP=?,Password=? where Id=?",[date("Y-m-d H:i:s"),$_SERVER['REMOTE_ADDR'],password_hash($this->get['password'],PASSWORD_BCRYPT),$res[0]['Id']]);
		return true;
	}
	public function errors() {
		return $this->errors;
	}
	public function getAuth() : bool {
		$allheaders = UtilsWepps::getAllheaders();
		$token = '';
		if (!empty($allheaders['authorization'])) {
			$token = str_replace('Bearer ', '', $allheaders['authorization']);
		}
		$token = (empty($token) && !empty($_COOKIE['wepps_token'])) ? @$_COOKIE['wepps_token'] : $token;
		if (empty($token)) {
			return false;
		}
		$jwt = new JwtWepps();
		$data = $jwt->token_decode($token);
		if (@$data['payload']['typ']!='auth' || empty($data['payload']['id'])) {
			setcookie('wepps_token','',0,'/',ConnectWepps::$projectDev['host'],true,true);
			return false;
		}
		$sql = "select * from s_Users where Id=? and DisplayOff=0";
		$res = ConnectWepps::$instance->fetch($sql,[$data['payload']['id']]);
		ConnectWepps::$projectData['user'] = $res[0];
		return true;
	}
	public function removeAuth() : bool {
		if (empty(ConnectWepps::$projectData['user'])) {
			return false;
		}
		setcookie('wepps_token','',0,'/',ConnectWepps::$projectDev['host'],true,true);
		return true;
	}
	public function password() : string {
		$letters = ['a','o','u','i','e','y','A','U','I','E','Y','w','r','t','k','m','n','b','h','d','s','W','R','T','K','M','N','B','H','D','S'];
		$symbols = ['.','$','-','!'];
		$arr = [];
		for ($i=1;$i<=8;$i++) {
			$arr[] = $letters[rand(0,count($letters)-1)];
		}
		$arr[] = rand(1,9);
		$arr[] = rand(1,9);
		$arr[] = $symbols[rand(0,count($symbols)-1)];
		shuffle($arr);
		return implode('', $arr);
	}
}

if (!function_exists('getallheaders')) {
	function getallheaders() {
		$headers = [];
		foreach ($_SERVER as $name => $value) {
			if (substr($name, 0, 5) == 'HTTP_') {
				$headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
			}
		}
		return $headers;
	}
}