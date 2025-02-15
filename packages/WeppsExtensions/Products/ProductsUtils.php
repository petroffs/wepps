<?php
namespace WeppsExtensions\Products;

use WeppsCore\Core\NavigatorWepps;
use WeppsCore\Core\DataWepps;
use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Utils\UtilsWepps;

class ProductsUtilsWepps {
	private $navigator;
	private $params;
	private $filters;
	public function __construct() {
		
	}
	public function setNavigator(NavigatorWepps $navigator) {
		$this->navigator = &$navigator;
	}
	public function setParams(array $params) {
		$this->params = &$params;
	}
	public function getSorting() : array {
		$rows = [
				'priceasc' => 'Сначала дешевле',
				'pricedesc' => 'Сначала дороже',
				'nameasc' => 'Наименование',
				'default' => 'Без сортировки',
		];
		$active = (!isset($_COOKIE['pps_sort'])) ? 'default' : $_COOKIE['pps_sort'];
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
	public function getConditions() : string {
		$conditions = "t.DisplayOff=0 and t.NavigatorId='{$this->navigator->content['Id']}'";
		if (empty($this->params)) {
			return $conditions;
		}
		foreach ($this->params as $key => $value) {
			if (substr($key,0,2)=='f_') {
				$conditions .= "\nand t.Id in (select distinct TableNameId from s_PropertiesValues where DisplayOff=0 and TableName='Goods' and Name ='" . str_replace ( 'f_', '', $key ) . "' and Alias in ('" . str_replace ( ",", "','", $value ) . "'))";
			}
		}
		UtilsWepps::debug($conditions);
		return $conditions;
		
		
		
	}
	public function getPages() {
		return 12;
	}
	public function getProducts(array $settings) : array {
		$obj = new DataWepps("Products");
		$obj->setConcat("concat('{$this->navigator->content['Url']}',if(t.Alias!='',t.Alias,t.Id),'.html') as Url");
		if (!empty($settings['params'])) {
			$obj->setParams($settings['params']);
		}
		$settings['pages'] = (!empty($settings['pages'])) ? (int) $settings['pages'] : 20;
		$settings['page'] = (!empty($settings['page'])) ? (int) $settings['page'] : 1;
		$settings['sorting'] = (!empty($settings['sorting'])) ? (string) $settings['sorting'] : "t.Priority desc";
		$res = $obj->getMax($settings['conditions'],$settings['pages'],$settings['page'],$settings['sorting']);
		return [
				'rows'=>$res,
				'count'=>$obj->count,
				'paginator'=>$obj->paginator,
				
		];
	}
	public function getFilters($conditions) {
		$sql = "select distinct p.Alias as PropertyAlias,pv.Name,pv.PValue,pv.Alias,
		p.Name as PropertyName,count(*) as Co
		from Products as t
		left outer join s_PropertiesValues as pv on pv.TableNameId = t.Id
		left outer join s_Properties as p on p.Id = pv.Name
		where $conditions
		group by pv.Alias
		order by p.Priority,pv.PValue
		limit 500";
		return ConnectWepps::$instance->fetch($sql,[],'group');
	}
}