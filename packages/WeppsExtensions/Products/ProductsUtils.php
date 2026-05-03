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
				$conditions = "t.IsHidden=0 and t.Id in (select pv.TableNameId from s_PropertiesValues pv where pv.Alias=? and pv.IsHidden=0 and pv.Name=1)";
				$prepare[] = Navigator::$pathItem;
			} else {
				$conditions = "t.IsHidden=0 and t.NavigatorId='{$this->navigator->content['Id']}'";
			}
			if (!empty($params['text'])) {
				$conditions = "t.IsHidden=0 and lower(t.Name) like lower(?)";
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
				$conditions .= "\nand t.Id in (select distinct TableNameId from s_PropertiesValues where IsHidden=0 and TableName='{$this->list}' and Name ='" . str_replace('f_', '', $key) . "' and Alias in ($ids))";
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
		$useApiMapping = $settings['useApiMapping'] ?? false;
		$obj = new Data("Products", ['useApiMapping' => $useApiMapping]);
		$obj->setConcat("concat(s1.Url,if(t.Alias!='',t.Alias,t.Id),'.html') as Url,group_concat(distinct concat(pv.Id,':::',pv.Field1,':::',pv.Field2,':::',pv.Field3,':::',pv.Field4) order by pv.Priority separator '\\n') W_Variations,count(pv.Id) W_VariationsCount");
		$obj->setJoin("join ProductsVariations pv on pv.ProductsId=t.Id and pv.IsHidden=0");
		if (!empty($settings['conditions']['params'])) {
			$obj->setParams($settings['conditions']['params']);
		}
		$settings['pages'] = (!empty($settings['pages'])) ? (int) $settings['pages'] : 20;
		$settings['page'] = (!empty($settings['page'])) ? (int) $settings['page'] : 1;
		$settings['sorting'] = (!empty($settings['sorting'])) ? (string) $settings['sorting'] : "t.Priority desc";
		$settings['sorting'] .= ",pv.Priority";
		$res = $obj->fetch($settings['conditions']['conditions'], $settings['pages'], $settings['page'], $settings['sorting']);
		// Преобразуем W_Variations из строки в массив (как в getProductsItem)
		if (!empty($res)) {
			foreach ($res as $k => &$el) {
				if (!empty($el['W_Variations'])) {
					$el['W_Variations'] = self::getVariationsArray($el['W_Variations']);
				}
				unset($res[$k]['Variations']);
			}
			unset($el);
		}
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
		// W_Variations уже преобразован в getProducts(), не нужно вызывать снова
		return $el;
	}

	/**
	 * Проверить наличие товара (с опциональной вариацией)
	 * Параметр может быть:
	 * - "325" — просто товар по ID
	 * - "325-555" — товар с вариацией (ID-variationId)
	 * 
	 * @param string $productId ID товара или "ID-variationId"
	 * @return array ['exists' => bool, 'message' => string, 'product' => array]
	 */
	public function validateProductId(string $productId): array
	{
		if (empty($productId)) {
			return ['exists' => false, 'message' => 'Product ID is empty', 'product' => null];
		}

		// Парсим ID и вариацию
		$parts = explode('-', $productId, 2);
		$id = $parts[0];
		$variationId = $parts[1] ?? null;

		// Проверяем существование товара
		$product = $this->getProductsItem($id);
		if (empty($product)) {
			return ['exists' => false, 'message' => 'Product not found', 'product' => null];
		}

		// Если указана вариация — проверяем её наличие
		if (!empty($variationId)) {
			$variations = $product['W_Variations'] ?? [];
			$variationFound = false;
			
			// Вариации могут быть структурой [группа][вариант]
			foreach ($variations as $group) {
				if (!is_array($group)) {
					continue;
				}
				// Проверяем вложенную структуру
				if (isset($group[0]) && is_array($group[0])) {
					foreach ($group as $item) {
						if (is_array($item) && isset($item['id']) && $item['id'] == $variationId) {
							$variationFound = true;
							break 2;
						}
					}
				}
				// Проверяем плоскую структуру
				else if (isset($group['id']) && $group['id'] == $variationId) {
					$variationFound = true;
					break;
				}
			}

			if (!$variationFound) {
				return ['exists' => false, 'message' => 'Variation not found', 'product' => $product];
			}
		}

		return ['exists' => true, 'message' => 'OK', 'product' => $product];
	}

	/**
	 * Быстрая проверка наличия товара (возвращает только true/false)
	 */
	public function isProductExists(string $productId): bool
	{
		return $this->validateProductId($productId)['exists'];
	}
	public function getVariationsArray(string $string): array
	{
		$arr = Utils::arrayFromString($string,':::',"\n");
		$keys = ['id', 'color', 'size', 'sku', 'stocks'];
		$types = ['id' => 'int', 'color' => 'string', 'size' => 'string', 'sku' => 'string', 'stocks' => 'int'];
		
		$variants = array_map(function($item) use ($keys, $types) {
			$variant = array_combine($keys, $item);
			// Конвертируем типы данных
			foreach ($variant as $key => &$value) {
				if ($types[$key] === 'int') {
					$value = (int)$value;
				} else {
					$value = (string)$value;
				}
			}
			unset($value);
			return $variant;
		}, $arr);
		
		// Группируем по цветам (пустой color → группу без цвета)
		$grouped = [];
		foreach ($variants as $value) {
			$colorKey = empty($value['color']) ? '' : $value['color'];
			if (!isset($grouped[$colorKey])) {
				$grouped[$colorKey] = [];
			}
			$grouped[$colorKey][] = $value;
		}
		
		// Преобразуем в массив массивов (без ключей)
		$result = array_values($grouped);
		return $result;
	}
}