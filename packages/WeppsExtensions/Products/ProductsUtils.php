<?php
namespace WeppsExtensions\Products;

use WeppsCore\Core\NavigatorWepps;
use WeppsCore\Core\DataWepps;
use WeppsCore\Utils\UtilsWepps;

class ProductsUtilsWepps {
	private $navigator;
	private $list;
	private $filters;
	public function __construct() {
		
	}
	public function setNavigator(NavigatorWepps $navigator,string $list) {
		$this->navigator = &$navigator;
		$this->list = $list;
	}
	public function getSorting() : array {
		$rows = [
				'priceasc' => 'Сначала дешевле',
				'pricedesc' => 'Сначала дороже',
				'nameasc' => 'Наименование',
				'default' => 'Без сортировки',
		];
		$active = (!isset($_COOKIE['wepps_sort'])) ? 'default' : $_COOKIE['wepps_sort'];
		switch ($active) {
			case 'priceasc':
				$conditions = "t.Price asc";
				break;
			case 'pricedesc':
				$conditions = "t.Price desc";
				break;
			case 'nameasc':
				$conditions = "t.Name asc";
				break;
			default :
				$conditions = "t.Priority desc";
				break;
		}
		return [
				'rows'=>$rows,
				'active'=>$active,
				'conditions'=>$conditions
		];
	}
	public function getConditions(array $params=[],bool $isFilters=false) : array {
		$conditions = "t.DisplayOff=0 and t.NavigatorId='{$this->navigator->content['Id']}'";
		$prepare = [];
		if (!empty($params['text'])) {
			$conditions = "t.DisplayOff=0 and lower(t.Name) like lower(?)";
			$prepare[] = $params['text']."%";
		}
		if ($isFilters==false) {
			return [
					'conditions' => $conditions,
					'params' => $prepare
			];
		}
		foreach ($params as $key => $value) {
			if (substr($key,0,2)=='f_') {
				$ex = explode('|', $value);
				$ids = str_repeat('?,', count($ex)-1) . '?';
				$conditions .= "\nand t.Id in (select distinct TableNameId from s_PropertiesValues where DisplayOff=0 and TableName='{$this->list}' and Name ='".str_replace('f_','',$key)."' and Alias in ($ids))";
				$prepare = array_merge($prepare,$ex);
			}
		}
		return [
				'conditions' => $conditions,
				'params' => $prepare
		];
	}
	public function getPages() {
		return 12;
	}
	public function getProducts(array $settings) : array {
		$obj = new DataWepps("Products");
		$obj->setConcat("concat(s1.Url,if(t.Alias!='',t.Alias,t.Id),'.html') as Url");
		if (!empty($settings['conditions']['params'])) {
			$obj->setParams($settings['conditions']['params']);
		}
		$settings['pages'] = (!empty($settings['pages'])) ? (int) $settings['pages'] : 20;
		$settings['page'] = (!empty($settings['page'])) ? (int) $settings['page'] : 1;
		$settings['sorting'] = (!empty($settings['sorting'])) ? (string) $settings['sorting'] : "t.Priority desc";
		$res = $obj->fetch($settings['conditions']['conditions'],$settings['pages'],$settings['page'],$settings['sorting']);
		return [
				'rows'=>$res,
				'count'=>$obj->count,
				'paginator'=>$obj->paginator,
				
		];
	}
}