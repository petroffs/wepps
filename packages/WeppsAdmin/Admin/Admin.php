<?php
namespace WeppsAdmin\Admin;

use WeppsCore\Core\SmartyWepps;
use WeppsCore\Utils\UtilsWepps;
use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\Core\DataWepps;
use WeppsCore\Connect\ConnectWepps;

class AdminWepps {
	private $headers;
	private $nav;
	private $page;
	public static $pathItem;
	public static $path;
	function __construct($ppsUrl,&$headers) {
		/*
		 * Если не залогинен - вывести форму авторизации WELCOME
		 */
		//if (!isset($_SESSION['user']['Id'])) exit();
		self::getNavigateUrl($ppsUrl);
		$this->page = (isset ( $_GET ['page'] )) ? ( int ) $_GET ['page'] : 1;
		$this->headers = &$headers;
		$this->nav = array (
				'home' => array (
						'Alias' => 'home',
						'Name' => "Wepps",
						'Extension' => 'Home'
				),
				'navigator' => array (
						'Alias' => 'navigator',
						'Name' => "Навигатор",
						'Extension' => 'NavigatorAd'
				),
				'lists' => array (
						'Alias' => 'lists',
						'Name' => "Списки данных",
						'Extension' => 'Lists'
				),
				'extensions' => array (
						'Alias' => 'extensions',
						'Name' => "Системные расширения",
						'Extension' => 'ConfigExtensions'
				)
		);
		$this->request();
		$smarty = SmartyWepps::getSmarty();
		$smarty->display( __DIR__ . '/Admin.tpl');
		ConnectWepps::$instance->close();
	}
	public function request () {
		
		if (! isset($_SESSION['user']['Id']) && isset($_COOKIE['authEmail']) && isset($_COOKIE['authKey'])) {
			$user = ConnectWepps::$instance->fetch("
					select * from s_Users 
					where Login='" . addslashes($_COOKIE['authEmail']) . "'
					and AuthKey regexp '" . ConnectWepps::$instance->selectRegx(addslashes($_COOKIE['authKey'])) . "' 
					and AuthKey!=0 and UserBlock!=1");
			if (isset($user[0]['Id'])) {
				$_SESSION['user'] = $user[0];
				ConnectWepps::$instance->query("
					update s_Users set AuthDate = '" . date("Y-m-d H:i:s") . "',
					MyIP = '{$_SERVER['REMOTE_ADDR']}' where Id = {$user[0]['Id']}");
			}
		}
		
		$headers = &$this->headers;
		$path = (self::$path[0]=='') ? 'home' : self::$path[0];
		if (!isset($_SESSION['user']) || !isset($_SESSION['user']['ShowAdmin']) || $_SESSION['user']['ShowAdmin'] != 1) {
        	$path = 'home';
		}
		
		if (!isset($this->nav[$path])) {
			ExceptionWepps::error404();
		}
		$navItem = $this->nav[$path];
		$this->headers->js ( "/packages/vendor/components/jquery/jquery.min.js" );
		$this->headers->js ( "/packages/vendor/components/jqueryui/jquery-ui.min.js" );
		$this->headers->css ( "/packages/vendor/components/jqueryui/themes/base/jquery-ui.min.css" );
		$this->headers->css ( "/packages/vendor/fortawesome/font-awesome/css/font-awesome.min.css" );
		$this->headers->js  ("/packages/vendor/select2/select2/dist/js/select2.min.js");
		$this->headers->js  ("/packages/vendor/select2/select2/dist/js/i18n/ru.js");
		$this->headers->css ("/packages/vendor/select2/select2/dist/css/select2.min.css");
		$this->headers->js ( "/packages/WeppsAdmin/Admin/Layout/Layout.{$headers::$rand}.js" );
		$this->headers->css ( "/packages/WeppsAdmin/Admin/Layout/Layout.{$headers::$rand}.css" );
		$this->headers->css ( "/packages/WeppsAdmin/Admin/Layout/Win.{$headers::$rand}.css" );
		$this->headers->js ("/packages/WeppsAdmin/Admin/Forms/Forms.{$headers::$rand}.js");
		$this->headers->css ("/packages/WeppsAdmin/Admin/Forms/Forms.{$headers::$rand}.css");
		$this->headers->js ("/packages/WeppsAdmin/Admin/AdminWepps.{$headers::$rand}.js");
		$this->headers->css ("/packages/WeppsAdmin/Admin/AdminWepps.{$headers::$rand}.css");
		$smarty = SmartyWepps::getSmarty();
		$smarty->assign('navtop',$this->nav);
		$smarty->assign('contenttop',$navItem);
		//$smarty->assign('navTpl',$smarty->fetch( __DIR__ . '/AdminNav.tpl'));
		
		if (ConnectWepps::$projectDev['multilang']==1) {
			$sql = "select * from s_NGroupsLang where DisplayOff=0 order by Priority";
			$language = ConnectWepps::$instance->fetch($sql);
			$smarty->assign('language',$language);
			$sql = "select * from s_Lang where Category='back' order by Priority";
			$multilang = ConnectWepps::$instance->fetch($sql);
			$smarty->assign('multilang',$multilang);
		}
		$smarty->assign('headers',$this->headers->get());
		$className = "WeppsAdmin\\{$navItem['Extension']}\\{$navItem['Extension']}Wepps";
		if(class_exists($className)) {
			return new $className($this->headers,$this->nav);
		} else {
			echo "Класс $className не найден.";
			exit();
		}
	}
	function __destruct() {
		
	}
	private function getNavigateUrl($url) {
		//UtilsWepps::debug($url,1);
		$match = array();
		$m = preg_match ( "/([^\/\?\&\=]+)\.html($|[\?])/", $url, $match );
		if (strstr($url, "home/")) {
			header("HTTP/1.1 301 Moved Permanently");
			header("Location: /_pps/");
			exit();
		}
		elseif (substr ( $url, - 1 ) != '/' && $m==0 && $url!='' && $_SERVER['REQUEST_URI']!='/') {
			header("HTTP/1.1 301 Moved Permanently");
			header("Location: /_pps/{$url}/");
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
		$url = (empty ( $url )) ? '/' : UtilsWepps::getStringFormatted ( $url );
		$url = substr ( $url, 0, strrpos ( $url, "/", - 1 ) + 1 );
		
		if (preg_match("/(\/{2,10})/",  $_SERVER['REQUEST_URI'])) {
			$url = preg_replace("/(\/{2,10})/", "/", $_SERVER['REQUEST_URI']);
			header("HTTP/1.1 301 Moved Permanently");
			header("Location: $url");
			exit();
		}
		self::$path = explode("/", substr($url, 0,-1));
	}
	
	public static function userPerm($permId=0,$check=[]) {
		if ($permId==0 || (int) $permId==0) return array('status'=>0);
		$obj = new DataWepps("s_Permissions");
		$res = $obj->getMax($permId)[0];
		if (!isset($res['Id'])) return array('status'=>0);

		$lists = explode(",", $res['TableName']);
		if (isset($check['list'])) {
			$permLists = array_flip($lists);
			if (isset($permLists[$check['list']])) {
				return array('status'=>1);
			} else {
				return array('status'=>0);
			}
		}
		
		$extensions = explode(",", $res['SystemExt']);
		if (isset($check['extension'])) {
			$permExts = array_flip($extensions);
			if (isset($permExts[$check['extension']])) {
				return array('status'=>1);
			} else {
				return array('status'=>0);
			}
		}
		return array('status'=>1,'lists'=>$lists,'extensions'=>$extensions);
	}
	
	public static function getTranslate() {
		$sql = "select Name,LangRu,LangEn from s_Lang where Category='back'";
		$res = ConnectWepps::$instance->fetch($sql,null,'group');
		$translate = array();
		foreach ($res as $key=>$value) {
			$translate[$key] = $value[0]['LangRu'];
		}
		return $translate;
	}
}
?>