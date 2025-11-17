<?php
require_once '../../../configloader.php';

use WeppsCore\Request;
use WeppsCore\Navigator;
use WeppsExtensions\Cart\CartUtils;
use WeppsExtensions\Products\ProductsUtils;
use WeppsExtensions\Template\Filters\Filters;

class RequestProducts extends Request
{
	private $navigator;
	public function request($action = "")
	{
		switch ($action) {
			case 'suggestions':
				$page = max(1, (int) ($this->get['page'] ?? 1));
				$productsUtils = new ProductsUtils();
				$productsUtils->setNavigator(new Navigator("/catalog/"), 'Products');
				$filters = new Filters($this->get);
				$params = $filters->getParams();
				$sorting = $productsUtils->getSorting();
				$conditions = $productsUtils->getConditions($params, false);
				$conditionsFilters = $productsUtils->getConditions($params, true);
				$settings = [
					'pages' => $productsUtils->getPages(),
					'page' => $page,
					'sorting' => $sorting['conditions'],
					'conditions' => $conditionsFilters,
				];
				$products = $productsUtils->getProducts($settings);
				if (empty($products['rows'])) {
					echo json_encode([
						'hasMore' => false
					]);
					break;
				}
				$html = '';
				foreach ($products['rows'] as $row) {
					$html .= '<div class="w_suggestions-item" data-url="' . $row['Url'] . '"><div><img src="/pic/lists' . $row['Images_FileUrl'] . '"></div><div>' . htmlspecialchars($row['Name']) . '</div><div class="price"><span>' . $row['Price'] . '</span></div></div>';
				}
				echo json_encode([
					'html' => $html,
					'hasMore' => true
				]);
				break;
			case 'filters':
				$this->tpl = "RequestProductsFilters.tpl";
				$weppsurl = (isset($this->get['link'])) ? $this->get['link'] : "/catalog/";
				$this->navigator = new Navigator($weppsurl);
				$productsUtils = new ProductsUtils();
				$productsUtils->setNavigator($this->navigator, 'Products');
				$filters = new Filters($this->get);
				$params = $filters->getParams();
				$sorting = $productsUtils->getSorting();
				$conditions = $productsUtils->getConditions($params, true);
				$settings = [
					'pages' => $productsUtils->getPages(),
					'page' => $this->get['page'],
					'sorting' => $sorting['conditions'],
					'conditions' => $conditions
				];
				$products = $productsUtils->getProducts($settings);
				$filtersActive = $filters->getFilters($settings['conditions']);
				$cartUtils = new CartUtils();
				$cartMetrics = $cartUtils->getCartMetrics();
				$this->assign('cartMetrics', $cartMetrics);
				$this->assign('products', $products['rows']);
				$this->assign('paginator', $products['paginator']);
				$this->fetch('paginatorTpl', '../Template/Paginator/Paginator.tpl');
				$this->fetch('productsTpl', 'ProductsItems.tpl');
				$js = $filters->getFiltersCodeJS($filtersActive, $products['count']);
				$js .= $filters->setBrowserStateCodeJS($this->navigator->content['Name']);
				$js .= "cart.init();productsInit();\n";
				$this->assign('js', $js);
				break;
			default:
				$this->tpl = "RequestProducts.tpl";
				exit();
		}
	}
}
$request = new RequestProducts($_REQUEST);
$smarty->assign('get', $request->get);
$smarty->display($request->tpl);