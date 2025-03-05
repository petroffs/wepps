<?php
namespace WeppsExtensions\Products;

use WeppsCore\Core\NavigatorWepps;
use WeppsCore\Core\SmartyWepps;
use WeppsCore\Core\DataWepps;
use WeppsCore\Core\ExtensionWepps;
use WeppsExtensions\Template\Filters\FiltersWepps;
use WeppsCore\Utils\UtilsWepps;

class ProductsWepps extends ExtensionWepps {
	public function request() {
		$smarty = SmartyWepps::getSmarty ();
		$rand = $this->rand;
		$productsUtils = new ProductsUtilsWepps();
		$productsUtils->setNavigator($this->navigator,'Products');
		$filters = new FiltersWepps($_GET);
		if (NavigatorWepps::$pathItem == '') {
			$this->tpl = 'packages/WeppsExtensions/Products/Products.tpl';
			$this->headers->css("/ext/Products/ProductsItems.{$rand}.css");
			$this->headers->css("/ext/Template/Filters/Filters.{$rand}.css");
			$this->headers->js("/ext/Template/Filters/Filters.{$rand}.js");
			$sorting = $productsUtils->getSorting();
			$conditions = $productsUtils->getConditions();
			$conditionsFilters = $productsUtils->getConditions($filters->getParams());
			$settings = [
					'pages'=>$productsUtils->getPages(),
					'page'=>$this->page,
					'sorting'=>$sorting['conditions'],
					'conditions'=>$conditionsFilters
			];
			$products = $productsUtils->getProducts($settings);
			$smarty->assign('products',$products['rows']);
			$smarty->assign('productsCount',$products['count']);
			$smarty->assign('productsSorting',$sorting['rows']);
			$smarty->assign('productsSortingActive',$sorting['active']);
			$smarty->assign('paginator',$products['paginator']);
			$smarty->assign('paginatorTpl',$smarty->fetch('packages/WeppsExtensions/Template/Paginator/Paginator.tpl'));
			$smarty->assign('productsTpl',$smarty->fetch('packages/WeppsExtensions/Products/ProductsItems.tpl'));
			$smarty->assign('childsNav',$this->navigator->nav['subs'][3]);
			$smarty->assign('filtersNav',$filters->getFilters($conditions));
			$smarty->assign('content',$this->navigator->content);
			if (!empty($filters->getParamsFilters())) {
				$filtersActive = $filters->getFilters($settings['conditions']);
				$filtersJS = $filters->getFiltersCodeJS($filtersActive,$products['count']);
				$smarty->assign('filtersJS',$filtersJS);
			}
			$this->headers->js("/ext/Products/Products.{$rand}.js");
			$this->headers->css("/ext/Template/Paginator/Paginator.{$rand}.css" );
		} else {
			$this->tpl = 'packages/WeppsExtensions/Products/ProductsItem.tpl';
			$this->headers->css("/ext/Products/ProductsItem.{$rand}.css");
			$this->headers->js("/ext/Products/ProductsItem.{$rand}.js");
			$this->headers->css("/packages/vendor/kenwheeler/slick/slick/slick.css");
			$this->headers->css("/packages/vendor/kenwheeler/slick/slick/slick-theme.css");
			$this->headers->js("/packages/vendor/kenwheeler/slick/slick/slick.min.js");
			$this->headers->css("/ext/Template/Carousel/Carousel.{$this->rand}.css");
			$this->navigator->content['Text1'] = '';
			$res = $this->getItem("Products");
			$smarty->assign('element',$res);
			$conditions = "t.DisplayOff=0 and t.Id!='{$res['Id']}'";
			$obj = new DataWepps("Products");
			$obj->setConcat("concat('{$this->navigator->content['Url']}',if(t.Alias!='',t.Alias,t.Id),'.html') as Url");
			$res = $obj->getMax($conditions,3,1,"t.Priority");
			$smarty->assign('elements',$res);
		}
		$smarty->assign('normalView',0);
		$this->headers->css("/ext/Products/Products.{$rand}.css");
		$this->headers->js("/packages/vendor_local/jquery-cookie/jquery.cookie.js" );
		$smarty->assign($this->targetTpl,$smarty->fetch($this->tpl));
		return;
	}
}
?>