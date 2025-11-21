<?php
namespace WeppsExtensions\Products;

use WeppsCore\Connect;
use WeppsCore\Navigator;
use WeppsCore\Data;
use WeppsCore\Utils;
use WeppsExtensions\Template\Filters\Filters;

class ProductsUtils
{
	private $navigator;
	private $list;
	private $filters;
	public function __construct()
	{

	}
	public function setNavigator(Navigator $navigator, string $list)
	{
		$this->navigator = &$navigator;
		$this->list = $list;
	}
	public function getSorting(): array
	{
		$rows = [
			'priceasc' => 'Сначала дешевле',
			'pricedesc' => 'Сначала дороже',
			'nameasc' => 'Наименование',
			'default' => 'Без сортировки',
		];
		$active = (empty(Utils::cookies('wepps_sort'))) ? 'default' : Utils::cookies('wepps_sort');
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
			default:
				$conditions = "t.Priority desc";
				break;
		}
		return [
			'rows' => $rows,
			'active' => $active,
			'conditions' => $conditions
		];
	}
	public function getConditions(array $params = [], bool $isFilters = false,string $conditions = '',array $prepare = []): array
	{
		if (empty($conditions)) {
			if ($this->navigator->content['Id']==Connect::$projectServices['navigator']['brands']) {
				$conditions = "t.DisplayOff=0 and t.Id in (select pv.TableNameId from s_PropertiesValues pv where pv.Alias=? and pv.DisplayOff=0 and pv.Name=1)";
				$prepare[] = Navigator::$pathItem;
			} else {
				$conditions = "t.DisplayOff=0 and t.NavigatorId='{$this->navigator->content['Id']}'";
			}
			if (!empty($params['text'])) {
				$conditions = "t.DisplayOff=0 and lower(t.Name) like lower(?)";
				$prepare[] = $params['text'] . "%";
			}
		}
		if ($isFilters == false) {
			return [
				'conditions' => $conditions,
				'params' => $prepare
			];
		}
		foreach ($params as $key => $value) {
			if (substr($key, 0, 2) == 'f_') {
				$ex = explode('|', $value);
				$ids = str_repeat('?,', count($ex) - 1) . '?';
				$conditions .= "\nand t.Id in (select distinct TableNameId from s_PropertiesValues where DisplayOff=0 and TableName='{$this->list}' and Name ='" . str_replace('f_', '', $key) . "' and Alias in ($ids))";
				$prepare = array_merge($prepare, $ex);
			}
		}
		return [
			'conditions' => $conditions,
			'params' => $prepare
		];
	}
	public function getPages()
	{
		return 12;
	}
	public function getProducts(array $settings): array
	{
		$obj = new Data("Products");
		$obj->setConcat("concat(s1.Url,if(t.Alias!='',t.Alias,t.Id),'.html') as Url,group_concat(distinct concat(pv.Id,':::',pv.Field1,':::',pv.Field2,':::',pv.Field3,':::',pv.Field4) order by pv.Priority separator '\\n') W_Variations,count(pv.Id) W_VariationsCount");
		$obj->setJoin("join ProductsVariations pv on pv.ProductsId=t.Id and pv.DisplayOff=0");
		if (!empty($settings['conditions']['params'])) {
			$obj->setParams($settings['conditions']['params']);
		}
		$settings['pages'] = (!empty($settings['pages'])) ? (int) $settings['pages'] : 20;
		$settings['page'] = (!empty($settings['page'])) ? (int) $settings['page'] : 1;
		$settings['sorting'] = (!empty($settings['sorting'])) ? (string) $settings['sorting'] : "t.Priority desc";
		$settings['sorting'] .= ",pv.Priority";
		$res = $obj->fetch($settings['conditions']['conditions'], $settings['pages'], $settings['page'], $settings['sorting']);
		return [
			'rows' => $res,
			'count' => $obj->count,
			'paginator' => $obj->paginator,

		];
	}
	public function getProductsItem(string|int $id): array
	{
		$conditions = '';
		$conditions = (strlen((int) $id) == strlen($id)) ? "{$conditions} t.Id = ?" : " {$conditions} binary t.Alias = ?";
		$settings = [
			'pages' => 1,
			'page' => 1,
			'sorting' => '',
			'conditions' => [
				'params' => [$id],
				'conditions' => $conditions,
			]
		];
		$products = $this->getProducts($settings);
		if (empty($el = &$products['rows'][0])) {
			return [];
		}
		$filters = new Filters();
		$el['W_Attributes'] = $filters->getFilters($settings['conditions']);
		if (!empty($el['W_Variations'])) {
			$el['W_Variations'] = self::getVariationsArray($el['W_Variations']);
		}
		return $el;
	}
	public function getVariationsArray(string $string): array
	{
		$arr = Utils::arrayFromString($string,':::',"\n");
		$keys = ['Id', 'Color', 'Size', 'Sku', 'Stocks'];
		$variants = array_map(function($item) use ($keys) {
			return array_combine($keys, $item);
		}, $arr);
		$arr = [];
		foreach ($variants as $value) {
			$color = (empty($value['Color'])) ? 'W_GROUP' : $value['Color'];
			$arr[$color][] = $value;
		}
		#Utils::debug($arr,0);
		return $arr;
	}
}