<?
namespace WeppsCore\Utils;

use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Core\SmartyWepps;
use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\Spell\SpellWepps;
use WeppsCore\Validator\ValidatorWepps;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Утилиты
 * @author Petroffscom
 *
 */
class UtilsWepps {
	/**
	 * Вывод информации о переменной в браузер или в переменную
	 *
	 * @param number|array|string $var - переменная, которую нужно вывести
	 * @param number $exit        	
	 * @param bool $print
	 * @return string
	 */
	public static function debug($var, $exit = 0, $print = true) {
		$backtrace = debug_backtrace();
		$debuginfo = date('Y-m-d H:i:s') . "\n";
		$debuginfo.= "{$backtrace[0]['file']}:{$backtrace[0]['line']}\n";
		$debuginfo.= "===================\n";
		if (ConnectWepps::$projectDev['debug']==0) {
			$debuginfo = "";
		}
		if (empty ( $var ) && $var != 0) {
			$var = "[" . date ( "d/m/Y" ) . "] Variable is empty.";
		}
			
		$tmp = "\n<pre style='text-align:left;color:black;font:11px trebuchet ms;border:0px;background: #80FF80;'>\n";
		$tmp .= $debuginfo . htmlspecialchars ( print_r ( $var, true ) ) . "\n";
		$tmp .= "</pre>";
		$val = "";
		foreach ( explode ( "\n", $tmp ) as $value ) {
			$val .= "{$value}\n";
		}
		$tmp = "<div style='overflow:scroll;width:98%;height:200px;border:1px solid gray;background: #80FF80;position:relative;left:1%;z-index:999;'>{$val}</div>\n";
		if ($print == true) {
			echo $tmp;
			$tmp = 1;
		}
		if ($exit == 1) {
			ConnectWepps::$instance->close();
		}
		return $tmp;
	}
	
	/**
	 * 
	 * @param $var - Переменная для вывода
	 * @param number $exit
	 * @param number $trace
	 * @param string $filename
	 */
	public static function debugf($var, $exit = 0, $trace=0, $filename='debug.conf') {
		$t = print_r($var,true);
		$filename = dirname(__FILE__)."/../../{$filename}";
		if (!is_file($filename)) {
			echo "\n$filename\n";
			echo dirname(__FILE__)."\n";
			return ;
		}
		$backtrace = debug_backtrace();
		$backtrace2 = print_r($backtrace,true);
		
		$debuginfo = date('Y-m-d H:i:s') . "\n";
		$debuginfo.= "{$backtrace[0]['file']}:{$backtrace[0]['line']}\n";
		$debuginfo.= "===================\n";
		if ($trace==1) {
			$debuginfo.= $backtrace2."\n===================\n";
		}
		$debuginfo.= $t;
		file_put_contents ($filename,$debuginfo);
		if ($exit == 1) {
			exit ();
		}
	}
	
	/**
	 * Форматирование входной строки
	 * @param (string|array) $value
	 * @return string
	 */
	public static function getStringFormatted($value) {
		if (is_array($value)) {
			//UtilsWepps::debug($value,1);
			foreach ( $value as $k => $v ) {
				if (is_array($v)) {
					foreach ($v as $k1=>$v1) {
						$value[$k][$k1] = UtilsWepps::getStringFormatted ( $v1 );
					}
				} else {
					//$v = trim ( $v );
					//$v = htmlspecialchars ( $v );
					$value[$k] = UtilsWepps::getStringFormatted ( $v );
				}
			}
			return $value;
		}
		if (is_string($value)) {
			$value = trim ( $value );
		}
		
		//$value = htmlspecialchars ( $value );
		return $value;
	}
	
	/**
	 * Форматирование телефонного номера
	 * @param string|array $value
	 * @return array
	 */
	public static function getPhoneFormatted($string='') {
		if ($string=='') return array();
		$tmp = $string;
		$t = preg_replace("/[^0-9]/","",$tmp);
		$t = preg_replace("/^8(.+)/","7$1",$t);
	
		if (strlen($t)==11) {
			//РФ
			$part1 = substr($t,0,4);
			$part2 = substr($t,4,3);
			$part3 = substr($t,7,2);
			$part4 = substr($t,9,2);
			return array('num'=>$t,'view'=>"+".$part1." ".$part2."-".$part3."-".$part4,'viewpref'=>substr($part1,1),'viewnum'=>$part2."-".$part3."-".$part4,'view2'=>$part1.$part2.$part3.$part4);
		} elseif (strlen($t)==12) {
			//Белоруссия
			if (substr($t,0,3)!="375") return array();
			$part1 = substr($t,0,5);
			$part2 = substr($t,5,3);
			$part3 = substr($t,8,2);
			$part4 = substr($t,10,2);
			return array('num'=>$t,'view'=>"+".$part1." ".$part2."-".$part3."-".$part4,'viewpref'=>substr($part1,1),'viewnum'=>$part2."-".$part3."-".$part4,'view2'=>$part1.$part2.$part3.$part4);
		}
		return array();
	}
	
	/**
	 * Компоновка SQL запроса на основе входного массива array('Row1'=>'ROW1','Row2'=>'ROW2');
	 * @param array $row
	 * @return array
	 */
	public static function getQuery($row) {
		$strCond = $strUpdate = $strInsert1 = $strInsert2 = $strSelect = "";
		if (count ( $row ) == 0) {
			return array ();
		}
			
		foreach ( $row as $key => $value ) {
			$value = trim ( self::_getQueryFormatted ( $value ) );
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
				$strUpdate .= "{$key}=". ConnectWepps::$db->quote ( $value ) .", ";
				$strInsert2 .= "" . ConnectWepps::$db->quote ( $value ) . ",";
			}
			$strInsert1 .= "$key,";
			$strCond .= "{$key}={$value1} and ";
			$strSelect .= "{$value1} {$key}, ";
		}
		$strCond = ($strCond != "") ? trim ( $strCond, " and " ) : "";
		$strCond = str_replace ( "\n", "\\n", $strCond );
		$strUpdate = ($strCond != "") ? trim ( $strUpdate, ", " ) : "";
		$strUpdate = str_replace ( "\r\n", "\\n", $strUpdate );
		$strInsert = ($strCond != "") ? "(" . trim ( $strInsert1, "," ) . ") values (" . trim ( $strInsert2, "," ) . ")" : "";
		$strInsert = str_replace ( "\n", "\\n", $strInsert ) . ";";
		$strSelect= ($strCond != "") ? trim ( $strSelect, ", " ) : "";
		$strSelect = str_replace ( "\r\n", "\\n", $strSelect );
		$outer = ($strCond != "") ? array (
				"insert" => $strInsert,
				"update" => $strUpdate,
				"condition" => $strCond,
				"select" => $strSelect
		) : array ();
		return $outer;
	}
	
	/**
	 * Форматирование значения, используется в self::getQuery()
	 * @param array $value
	 * @return mixed
	 */
	private static function _getQueryFormatted($value) {
		return str_replace ( array (
				"&gt;",
				"&lt;",
				"&quot;",
				//"'"
		), array (
				">",
				"<",
				"\"",
				//"\'"
		), $value );
	}
	
	/**
	 * Установка массиву ключей по произвольному полю
	 * @param array $value
	 * @param string $index
	 * @return array
	 */
	public static function getArrayId($value,$index='Id',$output=null) {
		$arr = array();
		if (!is_array($value)) return array();
		
		if ($output==null) {
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
	public static function getArrayFromStringTabs($string) {
		$output = [];
		$string = trim($string);
		$ex = explode("\r\n",$string);
		foreach ($ex as $i=>$value) {
			$tabs = explode("\t", trim($value));
			foreach ($tabs as $j=>$v) {
				$output[$i][$j] = $v;
			}
		}
		return $output;
	}
	
	public static function getModal($message) {
		$js = "
                    <script>
                    $('#dialog').html('<p>{$message}</p>').dialog({
        				'title':'Сообщение',
        				'modal': true,
        				'buttons' : [
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
	public static function getGUID($string='') {
		$charid = ($string=='') ? strtolower(md5(uniqid(rand(), true))) : strtolower(md5($string));
		$guid = substr($charid,  0, 8) . '-' .
				substr($charid,  8, 4) . '-' .
				substr($charid, 12, 4) . '-' .
				substr($charid, 16, 4) . '-' .
				substr($charid, 20, 12);
				return $guid;
	}
	public static function setExcel($data,$test=0) {
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
			->setCellValueExplicitByColumnAndRow($j, $i, $str,'s')
			->getColumnDimensionByColumn($j)->setWidth(12);
			$j++;
		}
		
		$i++;
		foreach ($data as $v) {
			$j = 1;
			foreach ($fields as $key => $value) {
				$str = trim($v[$key]);
				$spreadsheet->setActiveSheetIndex(0)
				->setCellValueExplicitByColumnAndRow($j, $i, $str,'s');
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
		if ($test==1) {
			/*
			 * Записать в файл для проверки
			 */
			$writer->save(__DIR__ . '/../WeppsAdmin/Bot/files/file.xlsx');
			return;
		}
		ob_start();
		$writer->save('php://output');
		$content = ob_get_clean();
		return $content;
	}
}

/**
 * Запросы AJAX
 * @author Petroffscom
 *
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
	private $smarty;
	/**
	 * Подключение шаблона, который передается в общий шаблон self::$tpl
	 * @var array
	 */
	private $fetch = array();
	
	/**
	 * Закрытие соединения с БД
	 */
	public $noclose = 0;
	
	
	public function __construct($myPost) {
		$this->get = UtilsWepps::getStringFormatted ( $myPost );
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
		if (count($this->fetch)!=0) {
			foreach ($this->fetch as $key=>$value) {
				$smarty->assign($key,$smarty->fetch($value));
			}
		}
	}
	public function assign($key,$value) {
		$this->assign[$key] = $value;
	}
	
	public function fetch($key,$value) {
		$this->fetch[$key] = $value;
	}
}

/**
 * Генерация html-кода ссылок на css-таблицы и js-библиотеки для применения в шаблоне сайта
 * Генерация html-кода meta-тегов
 * @author Petroffscom
 *
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
	
	public function js($filename) {
		return $this->cssjs['js'][] = "\n".'<script type="text/javascript" src="'.$filename.'"></script>';
	}
	public function css($filename) {
		return $this->cssjs['css'][] .= "\n".'<link rel="stylesheet" type="text/css" href="'.$filename.'"/>';
	}
	public function meta($meta) {
		return $this->output['meta'] .= "\n".$meta;
	}
	private function join_old($str) {
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
 * @author Petroffscom
 *
 */

class FilesWepps {

	/**
	 * Вывод указанного файла в браузер на сохранение или открытие на стороне клиента
	 * @param string $file
	 */
	public static function output($file) {
		$root = $_SERVER['DOCUMENT_ROOT'];
		$filename = $root . $file;
		//UtilsWepps::debug($filename,1);
		if (!is_file($filename)) ExceptionWepps::error404();
		$sql = "select * from s_Files where FileUrl='$file' limit 1";
		$res = ConnectWepps::$instance->fetch($sql);
		if (count($res) == 0) ExceptionWepps::error404();
		$row = $res[0];
		$filetitle = $row['Name'];

		header("Content-type: application/octet-stream");
		//header("Content-Disposition: attachment; filename=\"".iconv('utf-8','windows-1251',$filetitle)."\"");
		header("Content-Disposition: attachment; filename=\"$filetitle\"");
		header("Content-Length: ".filesize($filename));
		header("Last-Modified: " . gmdate("D, d M Y H:i:s",mktime (0,0,0,1,1,2000)) . " GMT"); // Дата в прошлом
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		readfile($filename);
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
	 * @param array $myFiles - $_FILES
	 * @param string $filesfield - Наименование поля type="file"
	 * @param string $myform - Идентификатор формы
	 * @return array
	 */
	public static function upload($myFiles,$filesfield,$myform) {
		$root = $_SERVER['DOCUMENT_ROOT'];
		$errors = array();
		/**
		 * Не все изображения имеют эту метку, возможны ошибки
		 * Переработать таким образом, чтобы была входная информация
		 * о типе загруженного файла и в зависимости от этого делать
		 * валидацию
		 */
		if (!strstr($myFiles[0]['type'],"image/")) {
			$errors[$filesfield] = "Неверный тип файла";
			$outer = ValidatorWepps::setFormErrorsIndicate($errors,$myform);
			return array('error'=>$errors[$filesfield],'js'=>$outer['Out']);
		}
		if ((int)$myFiles[0]['size']>3000000) {
			/**
			 * 1 мегабайт = 1 000 000 байт
			 */
			$errors[$filesfield] = "Слишком большой файл";
			$outer = ValidatorWepps::setFormErrorsIndicate($errors,$myform);
			return array('error'=>$errors[$filesfield],'js'=>$outer['Out']);
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
		$('input[name=\"{$filesfield}\"]').parent().append($('<p class=\"fileadd\">Загружен файл &laquo;{$myFiles[0]['name']}&raquo;</p>'));
		$('label.{$filesfield}').siblings('.controlserrormess').trigger('click');
		</script>";
		$data = array('success' => 'Form was submitted','js'=>$js);
		return $data;
	}
}

?>