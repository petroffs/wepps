<?php

/*
 * This file is part of Wepps Platform.
 *
 * (c) 2026 PETROFFS LLC (ООО «ПЕТРОФФС»)
 * Registered with Rospatent (Russian Federation): certificate No. 2026694013 of 03.11.2026.
 *
 * SPDX-License-Identifier: LicenseRef-Wepps-EULA
 *
 * Licensed under the Wepps Platform EULA: https://github.com/petroffs/wepps/blob/master/LICENSE
 * Free for non-commercial use (Community). Commercial use requires
 * a written license agreement with PETROFFS LLC.
 * This notice must not be removed or altered (EULA, sec. 2.2).
 *
 * @author Aleksei Petrov <mail@petroffs.com>
 */

namespace WeppsExtensions\Products;

use WeppsCore\Connect;
use WeppsCore\Navigator;
use WeppsCore\Smarty;
use WeppsCore\Data;
use WeppsCore\Extension;
use WeppsCore\Utils;
use WeppsExtensions\Brands\Brands;
use WeppsExtensions\Template\Filters\Filters;
use WeppsExtensions\Childs\Childs;
use WeppsCore\Exception;
use WeppsCore\TextTransforms;

class Products extends Extension
{
	private $filters;
	private $productsUtils;
	#[\Override]
	/**
	 * @return Brands|Childs|null
	 */
	public function request(): Brands|Childs|null
	{
		$smarty = Smarty::getSmarty();
		$this->productsUtils = new ProductsUtils();
		$this->productsUtils->setNavigator($this->navigator, 'Products');
		$this->filters = new Filters($this->get);
		$params = $this->filters->getParams();
		$displayForce = 0;
		if ($this->navigator->content['Id'] == Connect::$projectServices['navigator']['catalog'] && empty($params['text'])) {
			return new Childs($this->navigator, $this->headers);
		}
		if ($this->navigator->content['Id'] == Connect::$projectServices['navigator']['brands']) {
			$obj = new Brands($this->navigator, $this->headers);
			$this->extensionData = $obj->extensionData;
			$displayForce = 1;
			if (Navigator::$pathItem == '') {
				return $obj;
			}
		}
		if (Navigator::$pathItem == '' || $displayForce === 1) {
			$this->tpl = 'packages/WeppsExtensions/Products/Products.tpl';
			$this->headers->css("/ext/Products/ProductsItems.{$this->rand}.css");
			$this->headers->css("/ext/Template/Filters/Filters.{$this->rand}.css");
			$this->headers->js("/ext/Template/Filters/Filters.{$this->rand}.js");
			$sorting = $this->productsUtils->getSorting();
			$conditions = $this->productsUtils->getConditions($params, false);
			$conditionsFilters = $this->productsUtils->getConditions($params, true);
			$conditionsFilters['conditions'] .= " and length(t.Variations)>0";
			$settings = [
				'limit' => $this->productsUtils->getLimit(),
				'page' => $this->page,
				'sorting' => $sorting['conditions'],
				'conditions' => $conditionsFilters,
			];
			$products = $this->productsUtils->getProducts($settings);
			$smarty->assign('products', $products['rows']);
			$smarty->assign('productsCount', $products['count'] . ' ' . TextTransforms::ending2("товар", $products['count']));
			$smarty->assign('productsSorting', $sorting['rows']);
			$smarty->assign('productsSortingActive', $sorting['active']);
			$smarty->assign('paginator', $products['paginator']);
			$smarty->assign('paginatorTpl', $smarty->fetch('packages/WeppsExtensions/Template/Paginator/Paginator.tpl'));
			$smarty->assign('productsTpl', $smarty->fetch('packages/WeppsExtensions/Products/ProductsItems.tpl'));
			$smarty->assign('childsNav', $this->navigator->nav['subs'][3]);
			$smarty->assign('filtersNav', $this->filters->getFilters($conditions));
			$smarty->assign('content', $this->navigator->content);
			if (!empty($this->filters->getParamsFilters())) {
				$filtersActive = $this->filters->getFilters($settings['conditions']);
				$filtersJS = $this->filters->getFiltersCodeJS($filtersActive, $products['count']);
				$smarty->assign('filtersJS', $filtersJS);
			}
			$this->headers->js("/ext/Products/Products.{$this->rand}.js");
			$this->headers->css("/ext/Template/Paginator/Paginator.{$this->rand}.css");
		} else {
			$this->tpl = 'packages/WeppsExtensions/Products/ProductsItem.tpl';
			// Подключаем Swiper для галереи товара
			$this->headers->css("/packages/vendor_local/swiper.11.2.10/swiper-bundle.min.css");
			$this->headers->js("/packages/vendor_local/swiper.11.2.10/swiper-bundle.min.js");
			$this->headers->js("/ext/Template/Swiper/SwiperManager.{$this->rand}.js");
			// Стили и скрипты товара
			$this->headers->css("/ext/Products/ProductsItem.{$this->rand}.css");
			$this->headers->js("/ext/Products/ProductsItem.{$this->rand}.js");
			$this->navigator->content['Text1'] = '';
			$res = $this->getItem("Products");
			// Загружаем картинки по цветам для динамической смены галереи
			$imagesByColor = $this->productsUtils->getImagesByColor($res['Id']);
			//$smarty->assign('imagesByColor', json_encode($imagesByColor, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
			$this->headers->meta('<script>var imagesByColor = ' . Utils::json($imagesByColor) . ';</script>');
			$smarty->assign('element', $res);
			$conditions = "t.IsHidden=0 and t.Id!='{$res['Id']}' and length(t.Variations)>0";
			$obj = new Data("Products");
			$obj->setConcat("concat('{$this->navigator->content['Url']}',if(t.Alias!='',t.Alias,t.Id),'.html') as Url");
			$res = $obj->fetch($conditions, 3, 1, "t.Priority");
			$smarty->assign('elements', $res);
		}
		$smarty->assign('normalView', 0);
		$this->headers->js("/ext/Cart/Cart.{$this->rand}.js");
		$this->headers->css("/ext/Products/Products.{$this->rand}.css");
		$this->headers->js("/packages/vendor_local/jquery-cookie/jquery.cookie.js");
		$smarty->assign($this->targetTpl, $smarty->fetch($this->tpl));
		return null;
	}
	#[\Override]
	public function getItem($tableName, $condition = '')
	{
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
		if (!empty($element['MetaKeywords'])) {
			$this->navigator->content['MetaKeywords'] = $element['MetaKeywords'];
		}
		if (!empty($element['MetaDescription'])) {
			$this->navigator->content['MetaDescription'] = $element['MetaDescription'];
		}
		return $element;
	}
}
