<?php
namespace WeppsCore;
class Navigator
{
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
	public $nav = [];
	/**
	 * Содержание раздела
	 * 
	 * @var array
	 */
	public $content;
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

	function __construct($url = null, $backOffice = null)
	{
		if ($url == null) {
			$url = (isset($_GET['ppsUrl'])) ? $_GET['ppsUrl'] : "";
		}
		/*
		 * Для компоновки страницы создания нового элемента
		 */
		if ($backOffice == 1) {
			$url = str_replace("/addNavigator/", "/", $url);
		}

		$navigate = $this->getNavigateUrl($url);
		$this->path = $navigate['path'];
		$this->lang = $navigate['lang'];
		$this->multilang = $navigate['multilanguage'];

		$this->data = new NavigatorData("s_Navigator");
		$this->data->backOffice = $backOffice;
		$this->data->lang = $this->lang;
		$this->data->setParams([$this->path]);
		$res = $this->data->fetch("binary t.Url=?");
		if (isset($res[0]['Id'])) {
			$this->content = $res[0];
		}
		if (empty($this->content)) {
			Exception::error404();
		}
		$this->child = $this->data->getChild($this->content['Id']);
		$this->parent = $this->data->getChild($this->content['ParentDir']);
		$this->data->setConcat("if (t.NameMenu!='',t.NameMenu,t.Name) as NameMenu");
		$this->way = $this->data->getWay($this->content['Id']);
		$this->nav = $this->data->getNav($this->navLevel);
		foreach ($this->way as $value) {
			if ($value['Template'] != 0) {
				$this->tpl = array('tpl' => $value['Template_FileTemplate']);
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
	private function getNavigateUrl($url)
	{
		$m = preg_match("/([^\/\?\&\=]+)\.([\w\d]{1,7}+)($|[\?])/", $url, $match);
		if (empty(str_ends_with($url,'/')) && $m == 0 && $url != '' && $_SERVER['REQUEST_URI'] != '/') {
			header("HTTP/1.1 301 Moved Permanently");
			header("Location: {$url}/");
			exit();
		} elseif (strstr($_SERVER['REQUEST_URI'], 'index.php')) {
			header("HTTP/1.1 301 Moved Permanently");
			header("Location: /");
			exit();
		} elseif (substr($_SERVER['REQUEST_URI'], -1) == '/' && substr($_SERVER['REQUEST_URI'], 1, 1) == '/') {
			$url = "!";
		} elseif (isset($match[1])) {
			self::$pathItem = $match[1];
		}
		$navigateUrl = (empty($url)) ? '/' : Utils::trim($url);
		$navigateUrl = substr($navigateUrl, 0, strrpos($navigateUrl, "/", -1) + 1);
		$langLink = '/' . substr($navigateUrl, 1, strpos($navigateUrl, '/', 1));
		$langData = Language::getLanguage($langLink);
		if ($langData['default'] != 1) {
			$navigateUrl = substr($navigateUrl, strlen($langLink) - 1);
		}
		$multilanguage = Language::getMultilanguage($langData);
		return array(
			'path' => $navigateUrl,
			'lang' => $langData,
			'multilanguage' => $multilanguage
		);
	}
}