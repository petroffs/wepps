<?php
namespace WeppsExtensions\Products;

use WeppsCore\Core\NavigatorWepps;
use WeppsCore\Core\SmartyWepps;
use WeppsCore\Core\DataWepps;
use WeppsCore\Core\ExtensionWepps;
use WeppsCore\Connect\ConnectWepps;

class ProductsWepps extends ExtensionWepps {
	public function request() {
		$smarty = SmartyWepps::getSmarty ();
		$rand = $this->rand;
		if (NavigatorWepps::$pathItem == '') {
			$this->tpl = 'packages/WeppsExtensions/Products/ProductsSummary.tpl';
			$extensionConditions = self::setExtensionConditions($this->navigator)['condition'];
			
			/*
			 * Список товаров
			 */
			$obj = new DataWepps("Products");
			$obj->setConcat("concat('{$this->navigator->content['Url']}',if(t.Alias!='',t.Alias,t.Id),'.html') as Url");
			$res = $obj->getMax($extensionConditions,20,$this->page,self::setExtensionOrderBy());
			$smarty->assign('elements',$res);
			$smarty->assign('elementsCount',$obj->count);
			
			/*
			 * Опции сортировки
			 */
			$sql = "select Id,Name from s_Vars where VarsGroup='ПродукцияСортировка' and DisplayOff=0 order by Priority";
			$res = ConnectWepps::$instance->fetch($sql,null,'group');
			$orderBySel = (!isset($_COOKIE['optionsSort']) || !isset($res[$_COOKIE['optionsSort']])) ? 0 : $_COOKIE['optionsSort'];
			$smarty->assign('orderBy', $res);
			$smarty->assign('orderBySel', $orderBySel);
			$this->headers->js( "/packages/vendor_local/jquery-cookie/jquery.cookie.js" );

			/*
			 * Пагинация
			 */
			$smarty->assign('paginator',$obj->paginator);
			$smarty->assign('paginatorTpl', $smarty->fetch('packages/WeppsExtensions/Template/Paginator/Paginator.tpl'));
			
			/*
			 * Основной шаблон
			 */
			$smarty->assign('elementsTpl', $smarty->fetch('packages/WeppsExtensions/Products/ProductsItems.tpl'));
			$smarty->assign('extensionNav',$this->navigator->nav ['subs'][3]);
			$smarty->assign ('filtersNav', self::getProductsItemsProperties($extensionConditions));
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
			$extensionConditions = "t.DisplayOff=0 and t.Id!='{$res['Id']}'";
			$obj = new DataWepps("Products");
			$obj->setConcat("concat('{$this->navigator->content['Url']}',if(t.Alias!='',t.Alias,t.Id),'.html') as Url");
			$res = $obj->getMax($extensionConditions,3,1,"t.Priority");
			$smarty->assign('elements',$res);
			
			
		}

		/*
		 * Переменные для глобального шаблона
		 */
		$smarty->assign('normalHeader1',0);
		$this->headers->css("/ext/Products/Products.{$rand}.css");
		$this->headers->js("/ext/Products/Products.{$rand}.js");
		$this->headers->css ( "/ext/Template/Paginator/Paginator.{$rand}.css" );
		$smarty->assign($this->targetTpl,$smarty->fetch($this->tpl));
		return;
	}
	
	/**
	 * Получение свойств/значений для генерации фильтров
	 * @param string $extensionConditions
	 * @return array
	 */
	public static function getProductsItemsProperties($extensionConditions) {
		$sql = "select distinct p.Id as PropertyAlias,pv.Name,pv.PValue,pv.Alias,
		p.Name as PropertyName,count(*) as Co
		from Products as t
		left outer join s_PropertiesValues as pv on pv.TableNameId = t.Id
		left outer join s_Properties as p on p.Id = pv.Name
		where $extensionConditions
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
		$extensionConditions = "t.DisplayOff=0 and t.DirectoryId='{$navigator->content['Id']}'";
		return array('condition'=>$extensionConditions);
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