<?
namespace PPSExtensions\Products;
use PPS\Core\NavigatorPPS;
use PPS\Core\SmartyPPS;
use PPS\Core\DataPPS;
use PPS\Core\ExtensionPPS;
use PPS\Utils\TemplateHeadersPPS;
use PPS\Connect\ConnectPPS;
use PPS\Utils\UtilsPPS;

class ProductsPPS extends ExtensionPPS {
	public function request() {
		$smarty = SmartyPPS::getSmarty ();
		//$headers = &$this->headers;
		$rand = $this->rand;
		if (NavigatorPPS::$pathItem == '') {
			$this->tpl = 'packages/PPSExtensions/Products/ProductsSummary.tpl';
			$extensionConditions = self::setExtensionConditions($this->navigator)['condition'];
			
			/*
			 * Список товаров
			 */
			$obj = new DataPPS("Products");
			$obj->setConcat("concat('{$this->navigator->content['Url']}',if(t.KeyUrl!='',t.KeyUrl,t.Id),'.html') as Url");
			$res = $obj->getMax($extensionConditions,20,$this->page,self::setExtensionOrderBy());
			$smarty->assign('elements',$res);
			$smarty->assign('elementsCount',$obj->count);
			
			/*
			 * Опции сортировки
			 */
			$sql = "select Id,Name from s_Vars where VarsGroup='ПродукцияСортировка' and DisplayOff=0 order by Priority";
			$res = ConnectPPS::$instance->fetch($sql,null,'group');
			$orderBySel = (!isset($_COOKIE['optionsSort']) || !isset($res[$_COOKIE['optionsSort']])) ? 0 : $_COOKIE['optionsSort'];
			$smarty->assign('orderBy', $res);
			$smarty->assign('orderBySel', $orderBySel);
			$this->headers->js( "/plugins/jquery-cookie/jquery.cookie.js" );

			/*
			 * Пагинация
			 */
			$smarty->assign('paginator',$obj->paginator);
			$smarty->assign('paginatorTpl', $smarty->fetch('packages/PPSExtensions/Addons/Paginator/Paginator.tpl'));
			
			/*
			 * Основной шаблон
			 */
			$smarty->assign('elementsTpl', $smarty->fetch('packages/PPSExtensions/Products/ProductsItems.tpl'));
			$smarty->assign('extensionNav',$this->navigator->nav ['subs'][3]);
			$smarty->assign ('filtersNav', self::getProductsItemsProperties($extensionConditions));
			$smarty->assign('normalView',0);
			$smarty->assign('content',$this->navigator->content);
			
		} else {
			$this->tpl = 'packages/PPSExtensions/Products/ProductsItem.tpl';
			$this->headers->css("/ext/Products/ProductsItem.{$rand}.css");
			$this->headers->js("/ext/Products/ProductsItem.{$rand}.js");
			
			$this->headers->css ( "/plugins/slick/slick.css" );
			$this->headers->css ( "/plugins/slick/slick-theme.css" );
			$this->headers->js ( "/plugins/slick/slick.min.js" );
			$this->headers->css ( "/ext/Addons/Carousel/Carousel.{$rand}.css" );
			
			$this->navigator->content['Text1'] = '';
			
			$res = $this->getItem("Products");
			$smarty->assign('element',$res);
			$extensionConditions = "t.DisplayOff=0 and t.Id!='{$res['Id']}'";
			$obj = new DataPPS("Products");
			$obj->setConcat("concat('{$this->navigator->content['Url']}',if(t.KeyUrl!='',t.KeyUrl,t.Id),'.html') as Url");
			$res = $obj->getMax($extensionConditions,3,1,"t.Priority");
			$smarty->assign('elements',$res);
			
			
		}
		/*
		 * Переменные для глобального шаблона
		 */
		$smarty->assign('normalHeader1',0);
		
		
		$this->headers->css("/ext/Products/Products.{$rand}.css");
		$this->headers->js("/ext/Products/Products.{$rand}.js");
		$this->headers->css ( "/ext/Addons/Paginator/Paginator.{$rand}.css" );
		
		$smarty->assign($this->destinationTpl,$this->destinationOuter);
		$smarty->assign('extension',$smarty->fetch($this->tpl));
		
		return;
	}
	
	/**
	 * Получение свойств/значений для генерации фильтров
	 * @param string $extensionConditions
	 * @return array
	 */
	public static function getProductsItemsProperties($extensionConditions) {
		$sql = "select distinct p.Id as PropertyKeyUrl,pv.Name,pv.PValue,pv.KeyUrl,
		p.Name as PropertyName,count(*) as Co
		from Products as t
		left outer join s_PropertiesValues as pv on pv.TableNameId = t.Id
		left outer join s_Properties as p on p.Id = pv.Name
		where $extensionConditions
		group by pv.KeyUrl
		order by p.Priority,pv.PValue
		limit 500
		";
		return $res = ConnectPPS::$instance->fetch( $sql, null, 'group' );
	}
	
	/**
	 * Подготовка SQL-условия для генерации списка элементов расширения
	 * Зависит от рездела
	 *
	 * @param NavigatorPPS $navigator
	 * @return array
	 */
	public static function setExtensionConditions(NavigatorPPS $navigator) {
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