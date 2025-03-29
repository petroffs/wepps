<?php
namespace WeppsExtensions\Template\Filters;

use WeppsCore\Connect\ConnectWepps;
use WeppsCore\TextTransforms\TextTransformsWepps;
use WeppsCore\Utils\UtilsWepps;

class FiltersWepps {
	private $params = [];
	private $paramsFilters = [];
	function __construct(array $params=[]) {
		$this->setParams($params);
		$this->setParamsFilters();
	}
	public function getFilters($conditions) {
		$sql = "select distinct p.Id as PropertyAlias,pv.Name,pv.PValue,pv.Alias,
		p.Name as PropertyName,count(*) as Co
		from Products as t
		left outer join s_PropertiesValues as pv on pv.TableNameId = t.Id and pv.DisplayOff=0
		left outer join s_Properties as p on p.Id = pv.Name and p.DisplayOff=0
		where {$conditions['conditions']}
		group by pv.Alias
		order by p.Priority,pv.PValue
		limit 500";
		return ConnectWepps::$instance->fetch($sql,$conditions['params'],'group');
	}
	public function getFiltersCodeJS(array $filtersActive=[],int $count=0) {
		if (empty($filtersActive)) {
			return '';
		}
		$checked = (@$this->params['checked']===false)?false:true;
		$last = 1;
		foreach ($this->paramsFilters as $key => $value) {
			if (substr($key,0,2)=='f_') {
				$last = substr($key, 2);
				break;
			}
		}
		$js = "
			var obj = $('div.nav-filters').not('div.nav-filters-{$last}').find('input');
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
			options.attr('data-last','{$last}');
			options.attr('data-check','{$checked}');
			$('#pps-options-count').html('{$count} товар".TextTransformsWepps::ending2($count)."');
			//$('.text-top').addClass('pps_hide');
			
			var expand = $('.nav-filters-{$last}').find('li.pps_expand').find('a');
			var items = expand.closest('ul').find('li')
			if (items.filter('.pps_hide').length!=0) {
				expand.trigger('click');
			}
			";
		foreach ($this->paramsFilters as $key => $value) {
			if (substr($key,0,2)=='f_') {
				foreach(explode('|',$value) as $v) {
					$js .= "$('.nav-filters-".substr($key, 2)."').find('input[name=\"{$v}\"]').prop('checked',true);\n";
				}
			}
		}
		#UtilsWepps::debug($js,1);
		return $js;
	}
	public function setBrowserStateCodeJS(string $title='') {
		if (!empty($this->params['text'])) {
			$this->paramsFilters['text'] = $this->params['text'];
		}
		if (@$this->params['page']>1) {
			$this->paramsFilters['page'] = $this->params['page'];
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
	private function setParams(array $params) {
		$arr = [];
		foreach ($params as $key=>$value) {
			$key = preg_replace('~[^-a-z-A-Z\d\-_\.]+~u', '', $key);
			if ($key=='text') {
				$value = $value;
			} else {
				$value = preg_replace('~[^\w\d\-_\.\,\/\|]+~u', '', $value);
			}
			$arr[$key] = $value;
		}
		$this->params = &$arr;
	}
	public function getParams() : array {
		return $this->params;
	}
	private function setParamsFilters() : array {
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
	public function getParamsFilters() {
		return $this->paramsFilters;
	}
}