<?php
namespace WeppsExtensions\Products;

use WeppsCore\Navigator;
use WeppsCore\Smarty;
use WeppsCore\Data;
use WeppsCore\Extension;
use WeppsExtensions\Template\Filters\Filters;
use WeppsExtensions\Childs\Childs;
use WeppsCore\Exception;
use WeppsCore\TextTransforms;

class Products extends Extension {
	private $filters;
	private $productsUtils;
	public function request() {
		$smarty = Smarty::getSmarty ();
		$this->productsUtils = new ProductsUtils();
		$this->productsUtils->setNavigator($this->navigator,'Products');
		$this->filters = new Filters($this->get);
		$params = $this->filters->getParams();
		if ($this->navigator->content['Id']==3 && empty($params['text'])) {
			return new Childs($this->navigator, $this->headers);
		}
		if (Navigator::$pathItem == '') {
			$this->tpl = 'packages/WeppsExtensions/Products/Products.tpl';
			$this->headers->css("/ext/Products/ProductsItems.{$this->rand}.css");
			$this->headers->css("/ext/Template/Filters/Filters.{$this->rand}.css");
			$this->headers->js("/ext/Template/Filters/Filters.{$this->rand}.js");
			$sorting = $this->productsUtils->getSorting();
			$conditions = $this->productsUtils->getConditions($params,false);
			$conditionsFilters = $this->productsUtils->getConditions($params,true);
			$settings = [
					'pages'=>$this->productsUtils->getPages(),
					'page'=>$this->page,
					'sorting'=>$sorting['conditions'],
					'conditions'=>$conditionsFilters,
			];
			$products = $this->productsUtils->getProducts($settings);
			#Utils::debug($products,21);
			$smarty->assign('products',$products['rows']);
			$smarty->assign('productsCount', $products['count'] . ' ' . TextTransforms::ending2("товар",$products['count']));
			$smarty->assign('productsSorting',$sorting['rows']);
			$smarty->assign('productsSortingActive',$sorting['active']);
			$smarty->assign('paginator',$products['paginator']);
			$smarty->assign('paginatorTpl',$smarty->fetch('packages/WeppsExtensions/Template/Paginator/Paginator.tpl'));
			$smarty->assign('productsTpl',$smarty->fetch('packages/WeppsExtensions/Products/ProductsItems.tpl'));
			$smarty->assign('childsNav',$this->navigator->nav['subs'][3]);
			$smarty->assign('filtersNav',$this->filters->getFilters($conditions));
			$smarty->assign('content',$this->navigator->content);
			if (!empty($this->filters->getParamsFilters())) {
				$filtersActive = $this->filters->getFilters($settings['conditions']);
				$filtersJS = $this->filters->getFiltersCodeJS($filtersActive,$products['count']);
				$smarty->assign('filtersJS',$filtersJS);
			}
			$this->headers->js("/ext/Products/Products.{$this->rand}.js");
			$this->headers->css("/ext/Template/Paginator/Paginator.{$this->rand}.css" );
		} else {
			$this->tpl = 'packages/WeppsExtensions/Products/ProductsItem.tpl';
			$this->headers->css("/ext/Products/ProductsItem.{$this->rand}.css");
			$this->headers->js("/ext/Products/ProductsItem.{$this->rand}.js");
			$this->navigator->content['Text1'] = '';
			$res = $this->getItem("Products");
			$smarty->assign('element',$res);
			$conditions = "t.DisplayOff=0 and t.Id!='{$res['Id']}'";
			$obj = new Data("Products");
			$obj->setConcat("concat('{$this->navigator->content['Url']}',if(t.Alias!='',t.Alias,t.Id),'.html') as Url");
			$res = $obj->fetch($conditions,3,1,"t.Priority");
			$smarty->assign('elements',$res);
		}
		$smarty->assign('normalView',0);
		$this->headers->js("/ext/Cart/Cart.{$this->rand}.js");
		$this->headers->css("/ext/Products/Products.{$this->rand}.css");
		$this->headers->js("/packages/vendor_local/jquery-cookie/jquery.cookie.js" );
		$smarty->assign($this->targetTpl,$smarty->fetch($this->tpl));
		return;
	}
	public function getItem($tableName, $condition='') {
		$id = Navigator::$pathItem;
		if (empty($element = $this->productsUtils->getProductsItem(Navigator::$pathItem))) {
			Exception::error404();
		}
		$this->extensionData['element'] = 1;
		$this->navigator->content['Name'] = $element['Name'];
		if (!empty($element['MetaTitle'])) {
			$this->navigator->content['MetaTitle'] = $element['MetaTitle'];
		} else {
			$this->navigator->content['MetaTitle'] = $element['Name'];
		}
		if (!empty($element['MetaKeyword'])) {
			$this->navigator->content['MetaKeyword'] = $element['MetaKeyword'];
		}
		if (!empty($element['MetaDescription'])) {
			$this->navigator->content['MetaDescription'] = $element['MetaDescription'];
		}
		return $element;
	}
}