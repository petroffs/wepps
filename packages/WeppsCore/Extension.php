<?php
namespace WeppsCore;
abstract class Extension
{
	/**
	 * Навигатор
	 * @var Navigator
	 */
	public $navigator;
	/**
	 * Подключение css, js
	 * @var TemplateHeaders
	 */
	public $headers;
	/**
	 * Входные данные
	 * @var array
	 */
	public $get = [];
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

	/**
	 * Указатель на элемент в шаблоне
	 */
	public $extensionData = [];

	public function __construct(Navigator $navigator, TemplateHeaders $headers, $get = [])
	{
		$this->get = Utils::trim($get);
		$this->navigator = &$navigator;
		$this->headers = &$headers;
		$this->rand = $headers::$rand;
		$this->page = (isset($_GET['page'])) ? (int) $_GET['page'] : 1;
		$this->request();
		return;
	}

	public function getItem($tableName, $condition = '')
	{
		$id = Navigator::$pathItem;
		$prefix = ($condition != '') ? ' and ' : '';
		$condition = (strlen((int) $id) == strlen($id)) ? $condition . " {$prefix} t.Id = ?" : $condition . " {$prefix} binary t.Alias = ?";
		$obj = new Data($tableName);
		$obj->setParams([$id]);
		$res = $obj->fetch($condition)[0];
		if (!isset($res['Id'])) {
			Exception::error404();
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
	abstract public function request();
}