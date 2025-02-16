<?php
namespace WeppsExtensions\Template\Filters;

use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Spell\SpellWepps;
use WeppsCore\Utils\UtilsWepps;

class FiltersWepps {
	private $params = [];
	private $paramsFilters = [];
	function __construct(array $params=[]) {
		$this->url = @$params['link'];
		$this->state = @$params['state'];
		$this->setParams($params);
	}
	public function getFilters($conditions) {
		$sql = "select distinct p.Id as PropertyAlias,pv.Name,pv.PValue,pv.Alias,
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
	public function getFiltersCodeJS(array $filtersActive=[],int $count=0) {
		if (empty($filtersActive)) {
			return '';
		}
		$js = "
			var obj = $('div.nav-filters').not('div.nav-filters-{$this->params['last']}').find('input');
			obj.prop('disabled', true);
			obj.siblings('span').children('span').addClass('pps_hide');
			";
		foreach ($filtersActive as $value) {
			foreach ($value as $v) {
				$js .= "
					var obj = $('div.nav-filters-{$v['Name']}').find('input[name=\"{$v['Alias']}\"]');
					obj.prop('disabled', false)
					obj.siblings('span').children().html('{$v['Co']}').removeClass('pps_hide');
					";
			}
		}
		
		$js .= "
			filtersWepps.init();
			var options = $('.options-count').eq(0);
			options.attr('data-last','{$this->params['last']}');
			options.attr('data-check','{$this->params['checked']}');
			$('#pps-options-count').html('{$count} товар".SpellWepps::russ2($count)."');
			$('.text-top').addClass('pps_hide')
			";
		return $js;
	}
	public function setBrowserState(string $title='') {
		if (!empty($this->params['text'])) {
			$this->paramsFilters['text'] = $this->params['text'];
		}
		if (@$this->params['page']>1) {
			$this->paramsFilters['page'] = $this->get['page'];
		}
		$json = json_encode($this->paramsFilters,JSON_UNESCAPED_UNICODE);
		$state = (@$this->params['state']=='popstate')?'replaceState':'pushState';
		$filtersUrl = http_build_query($this->paramsFilters);
		$filtersUrl = (!empty($filtersUrl))?"{$this->params['link']}?$filtersUrl":$this->params['link'];
		$js = "
			window.history.$state($json, '$title', '$filtersUrl');
		";
		return $js;
	}
	public function setParams(array $params) {
		$arr = [];
		foreach ($params as $key=>$value) {
			$key = preg_replace('~[^-a-z-A-Z0-9\-_\.]+~u', '', $key);
			$value = preg_replace('~[^-a-z-A-Z0-9\-_\.\,\/]+~u', '', $value);
			$arr[$key] = $value;
		}
		$this->params = &$arr;
	}
	public function getParams() : array {
		return $this->params;
	}
	public function getParamsFilters() : array {
		if (empty($this->params)) {
			return [];
		}
		$this->paramsFilters = [];
		foreach ($this->params as $key => $value) {
			if (substr($key,0,2)=='f_') {
				$this->paramsFilters[$key] = $value;
			}
		}
		return $this->paramsFilters;
	}
}