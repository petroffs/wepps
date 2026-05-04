<?php
namespace WeppsExtensions\Template\Filters;

use WeppsCore\Connect;
use WeppsCore\TextTransforms;
use WeppsCore\Utils;

class Filters
{
	private $params = [];
	private $paramsFilters = [];
	function __construct(array $params = [])
	{
		$this->setParams($params);
		$this->setParamsFilters();
	}
	public function getFilters($conditions)
	{
		$fieldIds = "'' ProductsId";
		if (!empty($conditions['params'])) {
			$fieldIds = "group_concat(t.Id) ProductsId";
		}
		$sql = "select concat(p.Id) as PAlias,pv.Name PId,p.Name PName,pv.PValue,pv.Alias,count(*) Co,{$fieldIds}
		from Products as t
		left outer join s_PropertiesValues as pv on pv.TableNameId = t.Id and pv.IsHidden=0
		left outer join s_Properties as p on p.Id = pv.Name and p.IsHidden=0
		where {$conditions['conditions']}
		group by concat(p.Id,'-',pv.Alias)
		order by p.Priority,pv.PValue
		limit 500";
		$res = Connect::$instance->fetch($sql, $conditions['params'], 'group');
		$res = array_values($res);
		return $res;
	}

	/**
	 * Преобразует результат getFilters() в структуру [ProductId => [PropertyId => rows]]
	 * @param array $filterResult - результат от getFilters()
	 * @return array - структура [ProductId => [PropertyId => rows]]
	 */
	public function buildAttributesForProducts(array $filterResult): array
	{
		$grouped = [];
		
		foreach ($filterResult as $filterGroup) {
			if (!is_array($filterGroup) || empty($filterGroup)) {
				continue;
			}
			
			// Каждый элемент filterGroup это массив с данными одного свойства-значения
			foreach ($filterGroup as $row) {
				$propId = (int) ($row['PId'] ?? 0);
				$productsIds = $row['ProductsId'] ?? '';
				
				if (empty($productsIds) || $propId === 0) {
					continue;
				}
				
				// ProductsIds - это comma-separated список: "561,562,563,564,565"
				$productIds = array_filter(array_map('intval', explode(',', $productsIds)));
				
				foreach ($productIds as $productId) {
					if (!isset($grouped[$productId])) {
						$grouped[$productId] = [];
					}
					if (!isset($grouped[$productId][$propId])) {
						$grouped[$productId][$propId] = [];
					}
					unset($row['Co']);
					unset($row['ProductsId']);
					$grouped[$productId][$propId][] = $row;
				}
			}
		}
		
		// Переиндексируем PropertyId для каждого товара с числами начиная с 0
		$result = [];
		foreach ($grouped as $productId => $attrs) {

			$result[$productId] = array_values($attrs);
		}
		
		return $result;
	}
	public function getFiltersCodeJS(array $filtersActive = [], int $count = 0)
	{
		if (empty($filtersActive)) {
			return '';
		}
		$checked = (@$this->params['checked'] === false) ? false : true;
		$last = 1;
		foreach ($this->paramsFilters as $key => $value) {
			if (substr($key, 0, 2) == 'f_') {
				$last = substr($key, 2);
				break;
			}
		}
		$js = "
			var obj = $('div.nav-filters').not('div.nav-filters-{$last}').find('input');
			obj.prop('disabled', true);
			obj.siblings('span').children('span').addClass('w_hide');
			";
		foreach ($filtersActive as $value) {
			foreach ($value as $v) {
				$js .= "
					var obj = $('div.nav-filters-{$v['PName']}').find('input[name=\"{$v['Alias']}\"]');
					obj.prop('disabled', false)
					obj.siblings('span').children().html('{$v['Co']}').removeClass('w_hide');
					";
			}
		}
		$js .= "
			filtersWepps.init();
			var options = $('.options-count').eq(0);
			options.attr('data-last','{$last}');
			options.attr('data-check','{$checked}');
			$('#wepps-options-count').html('{$count} " . TextTransforms::ending2("товар", $count) . "');
			//$('.text-top').addClass('w_hide');
			
			var expand = $('.nav-filters-{$last}').find('li.w_expand').find('a');
			var items = expand.closest('ul').find('li')
			if (items.filter('.w_hide').length!=0) {
				expand.trigger('click');
			}
			";
		foreach ($this->paramsFilters as $key => $value) {
			if (substr($key, 0, 2) == 'f_') {
				foreach (explode('|', $value) as $v) {
					$js .= "$('.nav-filters-" . substr($key, 2) . "').find('input[name=\"{$v}\"]').prop('checked',true);\n";
				}
			}
		}
		#Utils::debug($js,1);
		return $js;
	}
	public function setBrowserStateCodeJS(string $title = '')
	{
		if (!empty($this->params['text'])) {
			$this->paramsFilters['text'] = $this->params['text'];
		}
		if (@$this->params['page'] > 1) {
			$this->paramsFilters['page'] = $this->params['page'];
		}
		$json = json_encode($this->paramsFilters, JSON_UNESCAPED_UNICODE);
		$state = (@$this->params['state'] == 'popstate') ? 'replaceState' : 'pushState';
		$filtersUrl = http_build_query($this->paramsFilters);
		$filtersUrl = (!empty($filtersUrl)) ? "{$this->params['link']}?$filtersUrl" : $this->params['link'];
		$js = "
			window.history.$state($json, '$title', '$filtersUrl');
		";
		return $js;
	}
	private function setParams(array $params)
	{
		$arr = [];
		foreach ($params as $key => $value) {
			$key = preg_replace('~[^-a-z-A-Z\d\-_\.]+~u', '', $key);
			if ($key == 'text') {
				//$value = $value;
			} else {
				$value = preg_replace('~[^\w\d\-_\.\,\/\|]+~u', '', $value);
			}
			$arr[$key] = $value;
		}
		$this->params = &$arr;
	}
	public function getParams(): array
	{
		return $this->params;
	}
	private function setParamsFilters(): array
	{
		if (empty($this->params)) {
			return [];
		}
		$this->paramsFilters = [];
		foreach ($this->params as $key => $value) {
			if (substr($key, 0, 2) == 'f_') {
				$this->paramsFilters[$key] = $value;
			}
		}
		return $this->paramsFilters;
	}
	public function getParamsFilters()
	{
		return $this->paramsFilters;
	}
}