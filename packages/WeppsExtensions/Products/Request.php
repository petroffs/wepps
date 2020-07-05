<?
namespace PPSExtensions\Products;

use PPS\Utils\RequestPPS;
use PPS\Core\NavigatorPPS;
//use PPSExtensions\Products\ProductsPPS;
use PPS\Utils\UtilsPPS;
use PPS\Core\DataPPS;
use PPS\Connect\ConnectPPS;

require_once '../../../config.php';
require_once '../../../autoloader.php';
require_once '../../../configloader.php';

class RequestProductsPPS extends RequestPPS {
	public function request($action="") {
		switch ($action) {
			case 'test':
				exit();
				break;
			case 'filters':
			
				/*
				 * Формируем навигацию в фильтрах
				 */
				$ppsUrl = (isset($this->get['url'])) ? $this->get['url'] : "/catalog/";
				$navigatorAjax = new NavigatorPPS($ppsUrl);
				
				//UtilsPPS::debug($navigatorAjax,1);
				
				$extensionConditions = ProductsPPS::setExtensionConditions ( $navigatorAjax )['condition'];
				foreach ($this->get as $key=>$value) {
					if (strstr($key, 'filter_')) {
						$extensionConditions .= "and t.Id in (select distinct TableNameId from s_PropertiesValues where Name ='" . str_replace('filter_', '', $key) . "' and KeyUrl in ('" .str_replace(",","','", $value)."'))\n";
					}
				}
				$filters = ProductsPPS::getProductsItemsProperties($extensionConditions);
			
				/**
				 * Для корректного UI
				 * if $this->get['checked']=='false'
				 * Получаем все значения текущего блока - чтобы пользователь смог выбрать еще
				 * Сделать расчет текущего блока свойств без учета текущего свойства
				 */
				if ($this->get ['checked'] == 'false') {
					$extensionConditionsActive = $extensionConditions . "\n";
					foreach ( $this->get as $key => $value ) {
						if (strstr ( $key, 'filter_' ) && $key != 'filter_' . $this->get ['last']) {
							$extensionConditionsActive .= "and t.Id in (select distinct TableNameId from s_PropertiesValues where Name ='" . str_replace ( 'filter_', '', $key ) . "' and KeyUrl in ('" . str_replace ( ",", "','", $value ) . "'))\n";
						}
					}
					$filtersActive = ProductsPPS::getProductsItemsProperties ( $extensionConditionsActive );
					$filters[$this->get['last']] = $filtersActive[$this->get['last']];
				}
			
				$js = "
						var obj = $('div.extFilters').find('input');
						obj.prop('disabled', true);
						obj.siblings('span').children('span').addClass('pps_hide');
						";
				foreach ($filters as $key=>$value) {
					$js .= "
						var obj = $('div.extFilters[data-id=\"{$key}\"]').find('input');
					";
					if ($key!=$this->get['last'] || $this->get['checked']=='false') {
						$js .= "
						obj.prop('disabled', true);
						obj.siblings('span').children().addClass('pps_hide');
						";
						foreach ( $value as $v ) {
							$js .= "
							var obj = $('div.extFilters').find('input[name=\"{$v['KeyUrl']}\"]');
							obj.prop('disabled', false)
							obj.siblings('span').children().html('{$v['Co']}').removeClass('pps_hide');
							";
						}
					} elseif ($key==$this->get['last'] && $this->get['checked']=='true') {
						$js .= "
						obj.prop('disabled', false);
						obj.siblings('span').children().removeClass('pps_hide');
						";
					}
				}
			
				/*
				 * Вывод товаров с примененным фильтром
				 */
				$extensionSettings = array (
						'tpl' => 'Products',
						'tableName' => 'Products',
						'condition' => $extensionConditions,
						'onPage' => '20',
						'page' => (isset ( $this->get ['page'] )) ? ( int ) $this->get ['page'] : 1,
						'orderBy' => ProductsPPS::setExtensionOrderBy()
				);
			
				$data = new DataPPS($extensionSettings['tableName']);
				$data->setConcat("concat('',if(t.KeyUrl!='',t.KeyUrl,t.Id),'.html') as Url");
				$res = $data->getMax($extensionConditions,$extensionSettings['onPage'],$extensionSettings['page'],$extensionSettings['orderBy']);
				$this->assign('elements', $res);
				$this->assign('paginator', $data->paginator);
				$this->assign('elementsCount', $data->count);
			
				/**
				 * Опции сортировки
				 */
				$sql = "select Id,Name from s_Vars where VarsGroup='ПродукцияСортировка' and DisplayOff=0 order by Priority";
				$res = ConnectPPS::$instance->fetch($sql,null,'group');
				$orderBySel = (!isset($_COOKIE['optionsSort']) || !isset($res[$_COOKIE['optionsSort']])) ? 0 : $_COOKIE['optionsSort'];
			
				$this->assign('orderBy', $res);
				$this->assign('orderBySel', $orderBySel);
				$this->fetch2('paginatorTpl', "../packages/PPSExtensions/Addons/Paginator/Paginator.tpl");
				$this->fetch ('productsItemsTpl', '../packages/PPSExtensions/Products/ProductsItems.tpl');
			
				$js .= "
				var obj = $('div.extProductsItems').find('div.extProductsItems2').eq(0);
				obj.html($('#products-container-id').html());
				obj.fadeIn();
				$('.uk-icon-refresh').remove();
			
				//readyCartInit(jQuery);
				readyProductsInit(jQuery);
			
				var optionCount = $('.optionsCount').eq(0);
				optionCount.attr('data-last','{$this->get['last']}');
				optionCount.attr('data-check','{$this->get['checked']}');
				";
			
				/**
				 * Вывод в шаблон
				 */
				$this->assign('js', $js);
				$this->tpl = "RequestProductsFilters.tpl";
				break;
			default:
				$this->tpl = "RequestProducts.tpl";
				exit();
				break;
		}
	}
}
$request = new RequestProductsPPS ($_REQUEST);
$smarty->assign('get',$request->get);
$smarty->display($request->tpl);
?>