<?php
namespace WeppsExtensions\Products;

use WeppsCore\Utils\RequestWepps;
use WeppsCore\Core\NavigatorWepps;
use WeppsExtensions\Template\Filters\FiltersWepps;
use WeppsCore\Utils\UtilsWepps;
use WeppsCore\Connect\ConnectWepps;

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
			case 'search':
				if (empty($this->get['search'])) {
					exit();
				}
				$sql = "select Id id,Name text from Products where Name regexp ? order by Name limit 5";
				$res = ConnectWepps::$instance->fetch($sql,[@$this->get['search']]);
				
				$res = array_merge(
					[
						[
								'id'=>$this->get['search'],'text'=>$this->get['search']
								
						]
					],	
					$res
				);
				$pagination = false;
				$output = [
					'results'=>$res,
					'pagination' => [
						'more'=> $pagination
					]
				];
				echo json_encode($output,JSON_UNESCAPED_UNICODE);
				break;
			case 'search2':
				$page = max(1, (int)($_POST['page'] ?? 1));
				$limit = (int)($_POST['limit'] ?? 15);
				$offset = (int) ($page - 1) * $limit;
				
				$sql = "select Id id,Name text from Products where Name like ? order by Name limit $limit ,$offset";
				$sql = "select Id id,Name text from Products where Name regexp ? order by Name limit $offset,$limit";
				$res = ConnectWepps::$instance->fetch($sql,[$this->get['query']]);
				
				$hasMore = count($res) === $limit;
				
				$html = '';
				foreach($res as $row) {
					$html .= '<div class="suggestion-item">'.htmlspecialchars($row['text']).'</div>';
				}
				
				echo json_encode([
						'html' => $html,
						'hasMore' => $hasMore
				]);
				break;
			case 'filters':
				$this->tpl = "RequestProductsFilters.tpl";
				$ppsUrl = (isset($this->get['link'])) ? $this->get['link'] : "/catalog/";
				$this->navigator = new NavigatorWepps($ppsUrl);
				$productsUtils = new ProductsUtilsWepps();
				$productsUtils->setNavigator($this->navigator,'Products');
				$filters = new FiltersWepps($this->get);
				$params = $filters->getParams();
				$sorting = $productsUtils->getSorting();
				$conditions = $productsUtils->getConditions($params,true);
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