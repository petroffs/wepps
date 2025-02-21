<?php
namespace WeppsExtensions\Products;

use WeppsCore\Utils\RequestWepps;
use WeppsCore\Core\NavigatorWepps;
use WeppsExtensions\Template\Filters\FiltersWepps;

require_once '../../../config.php';
require_once '../../../autoloader.php';
require_once '../../../configloader.php';

/**
 * @var Smarty $smarty
 */

class RequestProductsWepps extends RequestWepps {
	private $navigator;
	public function request($action="") {
		
		switch ($action) {
			case 'test':
				exit();
				break;
			case 'filters':
				$this->tpl = "RequestProductsFilters.tpl";
				$ppsUrl = (isset($this->get['link'])) ? $this->get['link'] : "/catalog/";
				$this->navigator = new NavigatorWepps($ppsUrl);
				$productsUtils = new ProductsUtilsWepps();
				$productsUtils->setNavigator($this->navigator,'Products');
				$filters = new FiltersWepps($this->get);
				$conditions = $productsUtils->getConditions($filters->getParamsFilters());
				$sorting = $productsUtils->getSorting();
				$settings = [
						'pages'=>$productsUtils->getPages(),
						'page'=>$this->get['page'],
						'sorting'=>$sorting['conditions'],
						'conditions'=>$conditions
				];
				$products = $productsUtils->getProducts($settings);
				$filtersActive = $filters->getFilters($settings['conditions']);
				$this->assign('products',$products['rows']);
				$this->assign('paginator',$products['paginator']);
				$this->fetch('paginatorTpl','../Template/Paginator/Paginator.tpl');
				$this->fetch('productsTpl','ProductsItems.tpl');
				$js = $filters->getFiltersCodeJS($filtersActive,$products['count']);
				$js .= $filters->setBrowserStateCodeJS($this->navigator->content['Name']);
				$this->assign('js', $js);
				break;
			default:
				$this->tpl = "RequestProducts.tpl";
				exit();
				break;
		}
	}
}
$request = new RequestProductsWepps($_REQUEST);
$smarty->assign('get',$request->get);
$smarty->display($request->tpl);
?>