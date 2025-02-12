<?php
namespace WeppsExtensions\Products;

use WeppsCore\Core\NavigatorWepps;
use WeppsCore\Core\SmartyWepps;
use WeppsCore\Core\DataWepps;
use WeppsCore\Core\ExtensionWepps;
use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Utils\UtilsWepps;

class ProductsWepps extends ExtensionWepps {
	public function request() {
		$smarty = SmartyWepps::getSmarty ();
		$rand = $this->rand;
		$productsUtils = new ProductsUtilsWepps();
		$productsUtils->setNavigator($this->navigator);
		
		if (NavigatorWepps::$pathItem == '') {
			$this->tpl = 'packages/WeppsExtensions/Products/Products.tpl';
			$this->headers->css("/ext/Products/ProductsItems.{$rand}.css");
			#$this->headers->js("/ext/Products/ProductsItems.{$rand}.js");
			#$conditions = self::setExtensionConditions($this->navigator)['condition'];
			$conditions = $productsUtils->getConditions();
			$sorting = $productsUtils->getSorting();
			
			$settings = [
					'pages'=>12,
					'page'=>$this->page,
					'sorting'=>$sorting['conditions'],
					'conditions'=>$conditions
			];
			$products = $productsUtils->getProducts($settings);
			$smarty->assign('products',$products['rows']);
			$smarty->assign('productsCount',$products['count']);
			$smarty->assign('productsSorting', $sorting['rows']);
			$smarty->assign('productsSortingActive', $sorting['active']);
			$smarty->assign('paginator',$products['paginator']);
			$smarty->assign('paginatorTpl', $smarty->fetch('packages/WeppsExtensions/Template/Paginator/Paginator.tpl'));
			$smarty->assign('productsTpl', $smarty->fetch('packages/WeppsExtensions/Products/ProductsItems.tpl'));
			$smarty->assign('extensionNav',$this->navigator->nav ['subs'][3]);
			$smarty->assign ('filtersNav', self::getProductsItemsProperties($conditions));
			$smarty->assign('normalView',0);
			$smarty->assign('content',$this->navigator->content);
			
		} else {
			$this->tpl = 'packages/WeppsExtensions/Products/ProductsItem.tpl';
			$this->headers->css("/ext/Products/ProductsItem.{$rand}.css");
			$this->headers->js("/ext/Products/ProductsItem.{$rand}.js");
			
			$this->headers->css ( "/packages/vendor/kenwheeler/slick/slick.css" );
			$this->headers->css ( "/packages/vendor/kenwheeler/slick/slick-theme.css" );
			$this->headers->js ( "/packages/vendor/kenwheeler/slick/slick.min.js" );
			$this->headers->css ( "/ext/Addons/Carousel/Carousel.{$rand}.css" );
			
			$this->navigator->content['Text1'] = '';
			
			$res = $this->getItem("Products");
			$smarty->assign('element',$res);
			$conditions = "t.DisplayOff=0 and t.Id!='{$res['Id']}'";
			$obj = new DataWepps("Products");
			$obj->setConcat("concat('{$this->navigator->content['Url']}',if(t.Alias!='',t.Alias,t.Id),'.html') as Url");
			$res = $obj->getMax($conditions,3,1,"t.Priority");
			$smarty->assign('elements',$res);
			
			
		}

		$smarty->assign('normalHeader1',0);
		$this->headers->css("/ext/Products/Products.{$rand}.css");
		$this->headers->js("/ext/Products/Products.{$rand}.js");
		$this->headers->css("/ext/Template/Paginator/Paginator.{$rand}.css" );
		$this->headers->js("/packages/vendor_local/jquery-cookie/jquery.cookie.js" );
		$smarty->assign($this->targetTpl,$smarty->fetch($this->tpl));
		return;
	}
	
	/**
	 * Получение свойств/значений для генерации фильтров
	 * @param string $conditions
	 * @return array
	 */
	public static function getProductsItemsProperties($conditions) {
		$sql = "select distinct p.Id as PropertyAlias,pv.Name,pv.PValue,pv.Alias,
		p.Name as PropertyName,count(*) as Co
		from Products as t
		left outer join s_PropertiesValues as pv on pv.TableNameId = t.Id
		left outer join s_Properties as p on p.Id = pv.Name
		where $conditions
		group by pv.Alias
		order by p.Priority,pv.PValue
		limit 500
		";
		return ConnectWepps::$instance->fetch( $sql, null, 'group' );
	}
	
	/**
	 * Подготовка SQL-условия для генерации списка элементов расширения
	 * Зависит от рездела
	 *
	 * @param NavigatorWepps $navigator
	 * @return array
	 */
	public static function setExtensionConditions(NavigatorWepps $navigator) {
		$conditions = "t.DisplayOff=0 and t.NavigatorId='{$navigator->content['Id']}'";
		return array('condition'=>$conditions);
	}
	
	/**
	 * Установка сортировки для элементов
	 * Зависит от выбранной опции сортировки
	 *
	 * @return string
	 */
	public static function setExtensionOrderBy() {
		$orderBySel = (!isset($_COOKIE['optionsSort'])) ? 0 : $_COOKIE['optionsSort'];
		$orderBy = "t.Priority desc";
		switch ($orderBySel) {
			case "6" : //Увеличению цены
				$orderBy = "t.Price asc";
				break;
			case "7" : //Уменьшению цены
				$orderBy = "t.Price desc";
				break;
			case "8" : //Названию
				$orderBy = "t.Name asc";
				break;
			case "9" : //Популярности
				//join в запросе Rating
				//здесь сортировка по значинию в этом join
				//Необходимо вычилить
				break;
			default :
				break;
		}
	
	
		return $orderBy;
	}
}
?>