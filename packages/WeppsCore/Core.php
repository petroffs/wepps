<?php
namespace WeppsCore\Core;

use WeppsCore\Utils\UtilsWepps;
use WeppsCore\Utils\RequestWepps;
use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Utils\TemplateHeadersWepps;


/**
 * Класс по работе со списками данных
 * @author petroffs
 *
 */
class DataWepps {
	/**
	 * Таблица БД, с которой производятся операции
	 */
	public $tableName;
	
	/**
	 * Количество строк запроса
	 */
	public $count = 0;
	/**
	 * Пагинация, заполяется при вызове методов get, getMax
	 */
	public $paginator;
	/**
	 * Запрос БД, сформированный в get, getMax
	 */
	public $sql;
	private $sqlCounter;
	/**
	 * Для полей area обрезать возвращаемые значения в getMax()
	 * @var integer
	 */
	public $truncate=0;
	/**
	 * Схема текущей таблицы БД
	 */
	private $scheme;
	
	/**
	 * Перечисление полей таблицы
	 */
	private $fields = '';
	
	/**
	 * Дополнительные поля, уточняющие вывод
	 */
	private $concat = '';
	
	/**
	 * Добавление данных из других таблиц
	 */
	private $join = '';
	
	/**
	 * Добавление данных из других таблиц
	 */
	private $group = '';
	
	/**
	 * Включить having в sql
	 */
	private $having = '';

	private $params = [];
	
	/**
	 * 
	 */
	public $lang;
	
	public function __construct($tableName='') {
		if ($tableName == "")
			exit ();
			return $this->tableName = UtilsWepps::trim ( $tableName );
	}
	/**
	 * Получить набор строк таблицы
	 */
	function get($id = NULL, $onPage = 20, $currentPage = 1, $orderBy = "Priority") {
		if ($id == NULL)
			$id = "Id!=0";
		$fields = $this->fields;
		$fields = ($fields != '') ? $fields : '*';
		$concat = $this->concat;
		if ($concat != '') {
			$concat = "," . $concat;
		}
		$formatted = $this->_getFormatted ( $id, $onPage, $currentPage, $orderBy );
		$this->sql = "select $fields $concat from {$this->tableName} where {$formatted['id']} {$formatted['orderBy']} {$formatted['limit']}";
		$this->sqlCounter = "select count(*) Co from {$this->tableName} where {$formatted['id']}";
		$res = ConnectWepps::$instance->fetch ( $this->sql );
		if ($currentPage > 0) {
			$paginator = $this->_getPaginator($formatted['onPage'], $formatted['currentPage']);
			$this->paginator = $paginator;
		}
		$res = LanguageWepps::getRows($res, $this->scheme, $this->lang);
		if (count($res)==0) {
			return [];
		}
		return $res;
	}
	/**
	 * Получить набор строк таблицы с соединением на основе схемы поля
	 * @param integer $id
	 * @param string $onPage
	 * @param string $currentPage
	 * @param string $orderBy
	 * @return array
	 */
	public function getMax($id = NULL, $onPage = 20, $currentPage = 0, $orderBy = "t.Priority") {
		if ($id == NULL) {
			$id = "t.Id!=0";
		}
		$formatted = $this->_getFormatted($id,$onPage,$currentPage,$orderBy);
		if (substr($formatted['id'], 0,2)=='Id') {
			$formatted['id'] = "t.".$formatted['id'];
		}
		$settings = $this->getScheme ();
		$fields = $joins = "";
		$joinCustom = $this->join;
		$f = 1;
		foreach ($settings as $key=>$value) {
			$ex = explode ("::", $value[0]['Type'],4);
			switch ($ex [0]) {
				case "file":
					$fields .= "'f{$f}' as {$key}_Coordinate,group_concat(distinct f{$f}.FileUrl order by f{$f}.Priority separator ':::') as {$key}_FileUrl,\n";
					$joins .= "left join s_Files as f{$f} on f{$f}.TableNameId = t.Id and f{$f}.TableNameField = '{$key}' and f{$f}.TableName = '{$this->tableName}'\n";
					$f ++;
					break;
				case "select":
				case "remote":
					$str = "";
					foreach ( explode ( ",", $ex [2] ) as $v ) {
						$str .= "s{$f}.{$v} as {$key}_{$v},";
					}
					$str = trim ( $str, "," );
					$fields .= "t.{$key},'s{$f}' as {$key}_Coordinate,$str,\n";
					$joins .= "left join {$ex[1]} as s{$f} on s{$f}.Id = t.{$key}\n";
					$f ++;
					break;
				case "select_multi":
				case "remote_multi":
					$fields .= "t.{$key},'sm{$f}' as {$key}_Coordinate,group_concat(distinct sm{$f}.{$ex[2]} order by sm{$f}.Priority separator ':::') as {$key}_{$ex[2]},\n";
					$joins .= "left join s_SearchKeys as sk{$f} on sk{$f}.Name = t.Id and sk{$f}.DisplayOff=0 and sk{$f}.Field3 = 'List::{$this->tableName}::{$key}' left join {$ex[1]} as sm{$f} on sm{$f}.Id = sk{$f}.Field1\n";
					$formatted['id'] .= "";
					$f ++;
					break;
				case "area":
					if ($this->truncate!=0) {
						$fields .= "substr(t.{$key},1,{$this->truncate}) as {$key},";
					} else {
						$fields .= "t.{$key},";
					}
					break;
				case "blob":
					$fields .= "uncompress (t.{$key}) {$key},";
					break;
				default :
					$fields .= "t.{$key},";
					break;
			}
		}
		$fields = trim ( trim($fields), "," );
		$concat = $this->concat;
		if ($concat != '') {
			$concat = "," . $concat;
		}
		$group = ($this->group=='') ? 't.Id' : $this->group;
		$having = (!empty($this->having)) ? "having {$this->having}" : '';
		$this->sql = "select $fields $concat from {$this->tableName} as t $joins $joinCustom where {$formatted['id']} group by $group $having {$formatted['orderBy']} {$formatted['limit']}";
		$this->sqlCounter = "select count(z.Id) Co from (select t.Id from {$this->tableName} as t $joins $joinCustom where {$formatted['id']} group by $group) z";
		$res = ConnectWepps::$instance->fetch($this->sql,$this->params);
		if ($currentPage > 0) {
			$paginator = $this->_getPaginator($formatted['onPage'],$formatted['currentPage']);
			$this->paginator = $paginator;
		}
		$res = LanguageWepps::getRows($res, $this->scheme, $this->lang);
		return $res;
	}
	/**
	 * Вспомогательная функция для обработки исходных переменных
	 * 
	 * @param integer $id
	 * @param integer $onPage
	 * @param integer $currentPage
	 * @param string $orderBy
	 * @return array
	 */
	private function _getFormatted($id, $onPage, $currentPage, $orderBy) {
		$onPage = UtilsWepps::trim ( $onPage );
		if ($onPage=='') $onPage = 100; 
		$currentPage = UtilsWepps::trim ( $currentPage );
		$orderBy = UtilsWepps::trim ( $orderBy );
		if ($orderBy!='') $orderBy = "order by $orderBy";
		$currentPage = (( int ) $currentPage <= 0) ? 1 : ( int ) $currentPage;
		$limit = ($currentPage - 1) * $onPage;
		$id = (is_numeric ( $id )) ? "Id='{$id}'" : $id;
		return [
				'id' => $id,
				'onPage' => $onPage,
				'currentPage' => $currentPage,
				'orderBy' => $orderBy,
				'limit' => "limit {$limit},{$onPage}" 
		];
	}
	/**
	 * Пагинация для организации постраничного вывода
	 * 
	 * @param integer $onPage
	 * @param integer $currentPage
	 * @return array
	 */
	private function _getPaginator($onPage, $currentPage) {
		$currentPage = ($currentPage <= 0) ? 1 : $currentPage;
		$this->count = 0;
		$dataPages = 1;
		if (!empty($this->sqlCounter)) {
			$res = ConnectWepps::$instance->fetch($this->sqlCounter,$this->params);
		} else {
			return [];
			$res = ConnectWepps::$instance->fetch ( "SELECT FOUND_ROWS() as Co" );
		}
		#UtilsWepps::debug($this->sqlCounter,1);
		if (!empty($res[0]['Co'])) {
			$dataPages = ceil($res[0]['Co'] / $onPage );
			$this->count = $res[0]['Co'];
		}
		$arr = array ();
		$arr['current']=$currentPage;
		for($i=1;$i<=$dataPages;$i++) {
			$arr['pages'][$i]=$i;
		}
		if ($currentPage < $dataPages) {
			$arr['next'] = $currentPage + 1;
		}
		if ($currentPage > 1) {
			$arr['prev'] = $currentPage - 1;
		}
		if (isset($arr['next']) && $dataPages < $arr['next']) {
			unset($arr['next']);
		}
		if (isset($arr['prev']) && $dataPages < $arr['prev']) {
				$arr['prev'] = $dataPages;
		}
		if ($dataPages == 1) {
			return [];
		}
		return $arr;
	}
	/**
	 * Получение схемы полей таблицы
	 * 
	 * @return array
	 */
	public function getScheme($renew=0) {
		if ($this->scheme==null || $renew==1) {
			$fields = $this->fields;
			$orderBy = "t.Priority";
			if (!empty($fields)) {
				$ids = "'".str_replace(",", "','", $fields)."'";
				$fields = " and t.Field in ($ids)";
				$orderBy = "field(t.Field,$ids)";
			}
			$sql = "select
			t.Field,t.Id,t.TableName,t.Name,t.Description,t.Priority,t.Required,t.Type,t.CreateMode,t.ModifyMode,t.DisplayOff,t.FGroup
			from s_ConfigFields as t
			where t.TableName = '{$this->tableName}' $fields order by $orderBy";
			$res = ConnectWepps::$instance->fetch($sql,null,'group');
			if (count($res)==0) {
				http_response_code(404);
				UtilsWepps::debug("Указанной таблицы {$this->tableName} не существует",1);
			}
			$this->scheme = $res;
		}
		return $this->scheme;
	}
	/**
	 * Установка $this->fields
	 * Перечисление полей
	 * @param string $value
	 */
	public function setFields($value) {
		$this->fields = $value;
	}
	
	/**
	 * Установка $this->concat
	 * Перечисление дополнительных полей (с функциями, например)
	 * @param string $value
	 */
	public function setConcat($value) {
		$this->concat = $value;
	}
	
	/**
	 * Установка $this->join
	 * Компоновка left outer join для сложных запросов
	 * @param string $value
	 */
	public function setJoin($value) {
		$this->join = $value;
	}
	
	public function setParams(array $params) : bool {
		$this->params = $params;
		return true;
	}
	
	/**
	 * Установка $this->group
	 * Указание стобца для группировки
	 * @param string $value
	 */
	public function setGroup($value) : bool {
		$this->group = $value;
		return true;
	}
	/**
	 * Обвновить строку
	 * @param integer $id - Id строки
	 * @param array $row - Массив столбцов и новых значений
	 */
	public function set(int $id,array $row,array $settings=[]) {
		$arr = ConnectWepps::$instance->prepare($row,$settings);
		$this->sql = "update {$this->tableName} set {$arr['update']} where Id = '{$id}'";
		return ConnectWepps::$instance->query($this->sql,$arr['row']);
	}
	/**
	 * Добавить строку
	 * @param array $row
	 * @param array $settings
	 * @return number
	 */
	public function add(array $row=[],int $insertOnly=0) : int {
		$scheme = $this->getScheme();
		$insert = [];
		$update = [];
		foreach ($scheme as $key => $value) {
			if ($value[0]['Required']==1) {
				if (empty($row[$key])) {
					throw new \RuntimeException("Field \"$key\" is empty");
					return 0;
				}
				$insert[$key] = $row[$key];
			}
			if (isset($row[$key])) {
				$update[$key] = $row[$key];
			}
		}
		$insert['Priority'] = 0;
		$settings = [
				'Priority' => ['fn'=>"(select round((max(Priority)+5)/5)*5 from {$this->tableName} as tb)"]
				];
		$prepare = ConnectWepps::$instance->prepare($insert,$settings);
		unset($prepare['row']['Priority']);
		$sql = "insert ignore into {$this->tableName} {$prepare['insert']}";
		ConnectWepps::$instance->query($sql,$prepare['row']);
		$id = ConnectWepps::$db->lastInsertId();
		if ($insertOnly == 1) {
			return $id;
		}
		if ((int)$id!=0) {
			unset($update['Id']);
			if (empty($update['Priority']))  {
				unset($update['Priority']);
			}
			$prepare = ConnectWepps::$instance->prepare($update);
			$sql = "update {$this->tableName} set {$prepare['update']} where Id='{$id}'";
			ConnectWepps::$instance->query($sql,$prepare['row']);
		}
		return $id;
	}
	
	/**
	 * Удаление строки
	 * @param integer $id
	 */
	public function remove(int $id) : bool {
		$sql = "delete from {$this->tableName} where Id = '{$id}'";
		ConnectWepps::$instance->query ( $sql );
		$sql = "delete from s_Files where TableName='{$this->tableName}' and TableNameId='{$id}'";
		ConnectWepps::$instance->query ( $sql );
		return true;
	}
}

class NavigatorWepps {
	private $data;
	/**
	 * Раздел сайта
	 * 
	 * @var object
	 */
	public $path;
	/**
	 * Раздел сайта/ITEM.html
	 *
	 * @var string
	 */
	public static $pathItem;
	/**
	 * Навигация верхнего уровня
	 * @var array
	 */
	public $nav = array();
	/**
	 * Содержание раздела
	 * 
	 * @var array
	 */
	public $content ;
	/**
	 * Подразделы родительского уровня
	 *
	 * @var array
	 */
	public $parent;
	/**
	 * Подразделы текущего раздела
	 * 
	 * @var array
	 */
	public $child;
	/**
	 * Путь до текущего раздела (Хлебные крошки)
	 * 
	 * @var array
	 */
	public $way;
	/**
	 * Данные о настройках языковой версии текущего контекста
	 * 
	 * @var array
	 */
	public $lang;
	/**
	 * Перевод шаблонных фраз из таблицы "Перевод"
	 * 
	 * @var array
	 */
	public $multilang;
	/**
	 * Признак текущего расположение (=1 если в админчасти)
	 * 
	 * @var integer
	 */
	public $backOffice = 0;
	/**
	 * Уровень вложенности для групп навигации
	 * @var integer
	 */
	public $navLevel = 2;
	
	/**
	 * Шаблон страницы
	 * @var string
	 */
	public $tpl;
	
	function __construct($url = null,$backOffice = null) {
		if ($url==null) {
			$url = (isset($_GET['ppsUrl'])) ? $_GET['ppsUrl'] : "";
		}
		/*
		 * Для компоновки страницы создания нового элемента
		 */
		if ($backOffice == 1) {
			$url = str_replace("/addNavigator/", "/", $url);
		}
		
		$navigate = $this->getNavigateUrl ( $url );
		$this->path = $navigate ['path'];
		$this->lang = $navigate ['lang'];
		$this->multilang = $navigate ['multilanguage'];
		
		$this->data = new NavigatorDataWepps ( "s_Navigator" );
		$this->data->backOffice = $backOffice;
		$this->data->lang = $this->lang;
		$res = $this->data->getMax ( "binary t.Url='{$this->path}'" );
		
		if (isset($res[0]['Id'])) {
			$this->content = $res[0];
		}
		if (empty($this->content)) {
			ExceptionWepps::error404();
		}
		
		$this->child = $this->data->getChild($this->content['Id']);
		$this->parent = $this->data->getChild($this->content['ParentDir']);
		$this->data->setConcat("if (t.NameMenu!='',t.NameMenu,t.Name) as NameMenu");
		$this->way = $this->data->getWay($this->content['Id']);
		$this->nav = $this->data->getNav($this->navLevel);
		
		foreach ($this->way as $value) {
			if ($value['Template']!=0) {
				$this->tpl = array('tpl'=>$value['Template_FileTemplate']);
			}
		}
		
		return;
	}
	/**
	 * Извлечение переменных (на основе Url) для работы с мультиязычной составляющей
	 * 
	 * @param string $url
	 * @return array
	 */
	private function getNavigateUrl($url) {
		$match = array();
		$m = preg_match ( "/([^\/\?\&\=]+)\.html($|[\?])/", $url, $match );
		if (substr ( $url, - 1 ) != '/' && $m==0 && $url!='' && $_SERVER['REQUEST_URI']!='/') {
			header("HTTP/1.1 301 Moved Permanently");
			header("Location: {$url}/");
			exit();
		} elseif (strstr($_SERVER['REQUEST_URI'],'index.php')) {
 			header("HTTP/1.1 301 Moved Permanently");
 			header("Location: /");
			exit();
		} elseif (substr($_SERVER['REQUEST_URI'], -1)=='/' && substr($_SERVER['REQUEST_URI'],1,1)=='/') {
			$url = "!";
		} elseif (isset($match[1])) {
			self::$pathItem = $match[1];
		}
		$navigateUrl = (empty ( $url )) ? '/' : UtilsWepps::trim($url);
		$navigateUrl = substr ( $navigateUrl, 0, strrpos ( $navigateUrl, "/", - 1 ) + 1 );
		$langLink = '/' . substr ( $navigateUrl, 1, strpos ( $navigateUrl, '/', 1 ) );
		$langData = LanguageWepps::getLanguage ( $langLink );
		if ($langData ['default'] != 1) {
			$navigateUrl = substr ( $navigateUrl, strlen ( $langLink ) - 1 );
		}
		$multilanguage = LanguageWepps::getMultilanguage ( $langData );
		return array (
				'path' => $navigateUrl,
				'lang' => $langData,
				'multilanguage' => $multilanguage 
		);
	}
	
	
}
class NavigatorDataWepps extends DataWepps {
	public $backOffice = 0;
	private $way = array();
	private $nav = array();
	private $navLevel = 0;
	private $rchild = array();
	
	public function getNav($navLevel) {
		$condition = ($this->backOffice==1) ? "t.DisplayOff in (0,1)" : "t.DisplayOff = 0";
		if ($this->navLevel==0) {
			$this->nav[$this->navLevel] = $this->getMax("{$condition} and t.ParentDir in (1,0) and t.NGroup!=0 and t.TableId=0",100,1,"t.NGroup,t.Priority");
			$this->navLevel++;
			return $this->getNav($navLevel);
		} elseif ($navLevel <= $this->navLevel) {
			$arr = array();
			foreach ($this->nav[0] as $value) {
				$arr[$value['NGroup']][] = $value;
			}
			
			unset($this->nav[0]);
			$sub = array();
			foreach ($this->nav as $value) {
				foreach ($value as $v) {
					if (isset($v['ParentDir'])) $sub[$v['ParentDir']][] = $v;
				}
			}
			return array ('groups'=>$arr,'subs'=>$sub);
		} else {
			$res = $this->nav[$this->navLevel-1];
			$res2 = UtilsWepps::array($res);
			unset($res2[1]);
			$res2Keys = implode(",", array_keys($res2));
			if ($res2Keys=="") {
				$this->navLevel++;
				return $this->getNav($navLevel);
			}
			$res = $this->getMax("{$condition} and t.ParentDir in ({$res2Keys}) and t.TableId=0",100,1,"t.Priority");
			$this->nav[$this->navLevel] = $res;
			$this->navLevel++;
			return $this->getNav($navLevel);
		}
		return $res;
	}
	
	/**
	 * Путь до раздела (Хлебные крошки)
	 * 
	 * @param integer $id
	 */
	public function getWay($id) {
		$res = $this->getMax($id);
		array_push($this->way,$res[0]);
		if ($res[0]['ParentDir']==0) return array_reverse($this->way);
		return $this->getWay($res[0]['ParentDir']);
	}
	
	/**
	 * Получение подраздела
	 * 
	 * @param integer $id
	 * @return array
	 */
	public function getChild($id) {
	    $condition = ($this->backOffice==1) ? "" : "and DisplayOff = 0";
	    $this->setConcat("if (NameMenu!='',NameMenu,Name) as NameMenu");
	    $res = $this->get("ParentDir='{$id}' $condition");
	    return $res;
	}
	
	/**
	 * Получение подраздела в рекурсии
	 * @param integer $id
	 * @return array
	 */
	public function getRChild($id) {
		$res = $this->get("ParentDir='{$id}' and DisplayOff=0");
		if (isset($res[0]['Id'])) {
			foreach ($res as $value) {
				$this->rchild[] = $value['Id'];
				$this->getRChild($value['Id']);
			}
		}
		return $this->rchild;
	}
	
	public function getChildTree($res=array(), $parent=1) {
	    if ($parent==1) {
	        $sql = "select if(ParentDir=0,1,ParentDir) as ParentDir,Id,Name,NameMenu,Url,NGroup,DisplayOff 
                    from s_Navigator
                    order by ParentDir,Priority";
	        $res = ConnectWepps::$instance->fetch($sql,array(),"group");
	    }
	    $tree = array();
	    if (isset($res[$parent])) {
	        foreach ($res[$parent] as $value) {
	            if ($value['Id']!=$parent) {
	               $node = array('element'=>$value,'child'=>$this->getChildTree($res,$value['Id']));
	            } else {
	                $node = array('element'=>$value,'child'=>array());
	            }
	            if ($parent == 1 ) {
	                $tree[$value['NGroup']][$value['Id']] = $node;
	            } else {
	                $tree[$value['Id']] = $node;
	            }
	        }
	    }
	    return $tree;
	}
}

/**
 * Расширение для разделов
 * @author Petroffscom
 *
 */
abstract class ExtensionWepps {
	/**
	 * Навигатор
	 * @var NavigatorWepps
	 */
	public $navigator;
	/**
	 * Подключение css, js
	 * @var TemplateHeadersWepps
	 */
	public $headers;
	/**
	 * Входные данные
	 * @var array
	 */
	public $get = array();
	/**
	 * Наименование шаблона
	 * @var string
	 */
	public $tpl = '';
	/**
	 * Содержание шаблона
	 * @var string
	 */
	public $targetTpl = 'extension';
	/**
	 * Текущая страница в постраничном выводе
	 * @var integer
	 */
	public $page = 1;
	
	public $rand;
	
	function __construct(NavigatorWepps $navigator, TemplateHeadersWepps $headers, $get = array()) {
		$this->get = UtilsWepps::trim ( $get );
		$this->navigator = &$navigator;
		$this->headers = &$headers;
		$this->rand = $headers::$rand;
		$this->page = (isset ( $_GET ['page'] )) ? ( int ) $_GET ['page'] : 1;
		$this->request();
		return;
	}

	public function getItem($tableName, $condition='') {
		$id = NavigatorWepps::$pathItem;
		$prefix = ($condition!='') ? ' and ' : '';
		$condition = (strlen((int)$id) == strlen($id)) ? $condition." {$prefix} t.Id = '{$id}'" : $condition." {$prefix} binary t.Alias = '{$id}'";
		$obj = new DataWepps($tableName);
		$res = $obj->getMax($condition)[0];
		if (!isset($res['Id'])) {
			ExceptionWepps::error404();
		}
		$this->extensionData['element'] = 1;
		$this->navigator->content['Name'] = $res['Name'];
		if (!empty($res['MetaTitle'])) {
			$this->navigator->content['MetaTitle'] = $res['MetaTitle'];
		} else {
			$this->navigator->content['MetaTitle'] = $res['Name'];
		}
		if (!empty($res['MetaKeyword'])) {
			$this->navigator->content['MetaKeyword'] = $res['MetaKeyword'];
		}
		if (!empty($res['MetaDescription'])) {
			$this->navigator->content['MetaDescription'] = $res['MetaDescription'];
		}
		return $res;
	}
	
	/**
	 * Реализация логики
	 */
	abstract function request();
}

/**
 * Мультиязычность
 * @author Petroffscom
 */
class LanguageWepps {
	/**
	 * Текущий язык раздела
	 * ($langLink - например /en/)
	 *
	 * @param string $langLink
	 * @return array
	 */
	public static function getLanguage($langLink = null) {
		$langLink = ($langLink != '/') ? "LinkDirectory='" . $langLink . "' or" : "";
		$sql = "select * from s_NGroupsLang where DisplayOff='0' and {$langLink} LinkDirectory='/' order by Priority desc limit 2";
		$langData = ConnectWepps::$instance->fetch ( $sql );
		$url = "";
		if (!empty($_SERVER['REQUEST_URI'])) {
			$url = $_SERVER['REQUEST_URI'];
		}
		if (count ( $langData ) == 1) {
			return array (
					'id' => $langData [0] ['Id'],
					'defaultId' => $langData [0] ['Id'],
					'default' => 1,
					'interface' => "Lang" . substr ( $langData [0] ['Name'], 0, 2 ),
					'interfaceDefault' => "Lang" . substr ( $langData [0] ['Name'], 0, 2 ),
					'link' => "",
					'url'=>$url
			);
		} else {
			return array (
					'id' => $langData [0] ['Id'],
					'defaultId' => $langData [1] ['Id'],
					'default' => 0,
					'interface' => "Lang" . substr ( $langData [0] ['Name'], 0, 2 ),
					'interfaceDefault' => "Lang" . substr ( $langData [1] ['Name'], 0, 2 ),
					'link' => substr ( $langData [0] ['LinkDirectory'], 0, - 1 ),
					'url'=>$url
			);
		}
	}

	/**
	 * Перевод для шаблонов (список "Перевод")
	 *
	 * @param array $langData
	 * @param number $backOffice
	 * @return array
	 */
	public static function getMultilanguage($langData, $backOffice = 0) {
		$ppsInterface = array ();
		$condition = ($backOffice == 0) ? "Category='front'" : "Category='back'";
		$interfaceLangs = ($langData ['default'] == 1) ? $langData ['interface'] : $langData ['interface'] . "," . $langData ['interfaceDefault'];
		foreach ( ConnectWepps::$instance->fetch ( "select Name," . $interfaceLangs . " from s_Lang where $condition order by Name" ) as $v ) {
			$ppsInterface [$v ['Name']] = ($v [$langData ['interface']] != '') ? $v [$langData ['interface']] : $v [$langData ['interfaceDefault']];
		}
		return $ppsInterface;
	}
	/**
	 * Перевод элементов списка данных
	 * 
	 * @param array $data
	 * @param array $scheme
	 * @param array $lang
	 * @return array
	 */
	public static function getRows($data=[], $scheme=[], $lang=[]) {
		if (empty($lang) || @$lang['id'] == 1 || !isset($scheme['TableId']) || !isset($scheme['LanguageId'])) {
			return $data;
		}
		$res = UtilsWepps::array($data);
		$resKeys = implode(",", array_keys($res));
		if ($resKeys=="") {
			return $data;
		}
		$sql = "select * from {$scheme['TableId'][0]['TableName']} where TableId in ({$resKeys}) and LanguageId='".@$lang['id']."' and DisplayOff=0";
		$res2 = ConnectWepps::$instance->fetch($sql);
		if (count($res2)==0) return $data;
		$resParall = UtilsWepps::array($res2,'TableId');
		$resParall2 = array();
		foreach ($res as $key=>$value) {
			if (!empty($resParall[$key]['Id'])) {
				$resParall2[$key] = $resParall[$key];
				foreach ($value as $k => $v) {
					$resParall2[$key][$k] = (!isset($resParall[$key][$k]) || $resParall[$key][$k]=='') ? $v : $resParall[$key][$k];
				}
				$resParall2[$key]['Id']=$value['Id'];
				if (isset($value['Template'])) $resParall2[$key]['Template']=$value['Template'];
				if (isset($value['NGroup'])) $resParall2[$key]['NGroup']=$value['NGroup'];
				if (isset($value['ParentDir'])) $resParall2[$key]['ParentDir']=$value['ParentDir'];
				if (isset($value['Alias'])) $resParall2[$key]['Alias']=$value['Alias'];
				if (isset($value['Url'])) $resParall2[$key]['Url']=$value['Url'];
			} else {
				$resParall2[$key] = $value;
			}
		}
		return array_merge($resParall2);
	}
}

class PermissionsWepps {
	function getRights($userId = NULL) {
	}
}
/**
 * Инициализация шаблонизатора
 * @author Petroffscom
 *
 */
class SmartyWepps {
	private static $instance;
	private function __construct($backOffice = 0) {
		//$root =  $_SERVER['DOCUMENT_ROOT'] . "/";
		$root =  ConnectWepps::$projectDev['root'] . "/";
		$smarty = new \Smarty();
		//$smarty->template_dir = ($backOffice == 0) ? 'tpl/' : 'control/tpl/';
		$smarty->addTemplateDir( $root . 'packages/' );
		$smarty->addPluginsDir( $root . 'packages/vendor_local/smarty_pps/');
		$smarty->compile_dir = $root . 'files/tpl/compile';
		$smarty->cache_dir = $root . 'files/tpl/cache/';
		$smarty->error_reporting = error_reporting() & ~E_NOTICE & ~E_WARNING;
		self::$instance = $smarty;
	}
	public static function getSmarty($backOffice = 0) {
		if (empty ( self::$instance )) {
			new SmartyWepps ( $backOffice );
		}
		return self::$instance;
	}
}