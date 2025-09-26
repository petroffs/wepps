<?php
namespace WeppsExtensions\Profile;

use WeppsCore\Connect;
use WeppsCore\Data;
use WeppsCore\Extension;
use WeppsCore\Navigator;
use WeppsCore\Smarty;
use WeppsCore\Exception;
use WeppsCore\TextTransforms;
use WeppsCore\Utils;
use WeppsExtensions\Addons\Jwt\Jwt;
use WeppsExtensions\Addons\RemoteServices\RecaptchaV2;
use WeppsExtensions\Cart\CartUtils;
use WeppsExtensions\Products\ProductsUtils;
use WeppsExtensions\Template\Filters\Filters;

class Profile extends Extension
{
	private $profileTpl = '';
	private $user;
	public function request()
	{
		$this->tpl = __DIR__ . '/Profile.tpl';
		$this->user = Connect::$projectData['user'] ?? [];
		if (!empty(Navigator::$pathItem)) {
			$this->extensionData['element'] = 1;
		}
		$profileUtils = new ProfileUtils($this->user);
		$profileNav = $profileUtils->getNav();
		$smarty = Smarty::getSmarty();
		$smarty->assign('pathItem', Navigator::$pathItem);
		$smarty->assign('get', $this->get);
		$smarty->assign('profileNav', $profileNav);
		$this->headers->js("/ext/Profile/Profile.{$this->rand}.js");
		$this->headers->css("/ext/Profile/Profile.{$this->rand}.css");
		$this->headers->js("/packages/vendor/robinherbots/jquery.inputmask/dist/jquery.inputmask.min.js");
		if (!empty($this->get['token'])) {
			Utils::cookies('wepps_token');
			$jwt = new Jwt();
			$payload = $jwt->token_decode($this->get['token']);
			if ($payload['payload']['status'] = 200) {
				switch ($payload['payload']['typ']) {
					case 'pass':
						$user = @Connect::$instance->fetch('SELECT * from s_Users where Id=? and DisplayOff=0', [$payload['payload']['id']])[0];
						if (empty($user)) {
							$this->profileTpl = 'ProfilePasswordError.tpl';
							break;
						}
						$this->profileTpl = 'ProfilePasswordConfirm.tpl';
						break;
					case 'reg':
						$this->profileTpl = 'ProfileRegConfirm.tpl';
						break;
				}
			} else {
				$this->profileTpl = 'ProfilePasswordError.tpl';
			}
			$smarty->assign('profileTpl', $smarty->fetch(__DIR__ . '/' . $this->profileTpl));
			$smarty->assign($this->targetTpl, $smarty->fetch($this->tpl));
			return;
		}
		if (empty($this->user)) {
			switch (Navigator::$pathItem) {
				case 'reg':
					$this->profileTpl = 'ProfileReg.tpl';
					break;
				case 'password':
					$this->profileTpl = 'ProfilePassword.tpl';
					$recaptcha = new RecaptchaV2($this->headers);
					$response = $recaptcha->render();
					$smarty->assign('recaptcha', $response);
					break;
				default:
					$this->profileTpl = 'ProfileSignIn.tpl';
					break;
			}
			$smarty->assign('profileTpl', $smarty->fetch(__DIR__ . '/' . $this->profileTpl));
			$smarty->assign($this->targetTpl, $smarty->fetch($this->tpl));
			return;
		}
		switch (Navigator::$pathItem) {
			case '':
			case 'reg':
			case 'password':
				$this->profileTpl = 'ProfileHome.tpl';
				break;
			case 'orders':
				if (!empty($this->get['id'])) {
					$this->profileTpl = 'ProfileOrdersItem.tpl';
					$this->getOrder($this->get['id'],$smarty);
					break;
				}
				$this->profileTpl = 'ProfileOrders.tpl';
				$obj = new Data('Orders');
				$obj->setParams([$this->user['Id']]);
				$orders = $obj->fetch('t.DisplayOff=0 and t.UserId=?', 10,(int) ($this->get['page'] ?? 1),'Id desc');
				$smarty->assign('paginator',$obj->paginator);
				$smarty->assign('paginatorTpl', $smarty->fetch('packages/WeppsExtensions/Template/Paginator/Paginator.tpl'));
				$this->headers->css("/ext/Template/Paginator/Paginator.{$this->rand}.css");
				if (empty($orders)) {
					$this->profileTpl = 'ProfileOrdersEmpty.tpl';
				}
				$smarty->assign('orders', $orders);
				break;
			case 'favorites':
				$this->profileTpl = '';
				$this->tpl = __DIR__ . '/ProfileFavorites.tpl';
				$this->favorites($smarty,$profileNav);
				break;
			case 'settings':
				$this->profileTpl = 'ProfileSettings.tpl';
				break;
			default:
				Exception::error404();
				break;
		}
		if (!empty($this->profileTpl)) {
			$smarty->assign('profileTpl', $smarty->fetch(__DIR__ . '/' . $this->profileTpl));
		}
		$smarty->assign($this->targetTpl, $smarty->fetch($this->tpl));
		return;
	}
	private function getOrder($id,\Smarty\Smarty $smarty): array {
		$cartUtils = new CartUtils();
		if (empty($order = $cartUtils->getOrder($id,$this->user['Id']))) {
			Exception::error(404);
		}
		$smarty->assign('order', $order);
		return $order;
	}
	private function favorites(\Smarty\Smarty $smarty,array $profileNav)
	{
		$this->navigator->content['Name'] = 'Избранное';
		$productsUtils = new ProductsUtils();
		$productsUtils->setNavigator($this->navigator, 'Products');
		$filters = new Filters();
		$this->headers->js("/ext/Cart/Cart.{$this->rand}.js");
		$this->headers->css("/ext/Products/Products.{$this->rand}.css");
		$this->headers->css("/ext/Products/ProductsItems.{$this->rand}.css");
		$this->headers->css("/ext/Template/Filters/Filters.{$this->rand}.css");
		$this->headers->js("/ext/Template/Filters/Filters.{$this->rand}.js");
		$this->headers->js("/packages/vendor_local/jquery-cookie/jquery.cookie.js" );
		$this->headers->js("/ext/Products/Products.{$this->rand}.js");
		$this->headers->css("/ext/Template/Paginator/Paginator.{$this->rand}.css" );
		$params = $filters->getParams();
		$sorting = $productsUtils->getSorting();
		if (empty($jfav = @json_decode($this->user['JFav'],true)['items'])) {
			$this->profileTpl = 'ProfileFavoritesEmpty.tpl';
			$this->tpl = __DIR__ . '/Profile.tpl';
			return;

		}
		$ids = array_column($jfav,'id');
		$in = Connect::$instance->in($ids);
		$conditionsPrepared = "t.DisplayOff=0 and t.Id in ($in)";
		$conditions = $productsUtils->getConditions($params, false,$conditionsPrepared,$ids);
		$conditionsFilters = $productsUtils->getConditions($params, true,$conditionsPrepared,$ids);
		$settings = [
			'pages' => $productsUtils->getPages(),
			'page' => $this->page,
			'sorting' => $sorting['conditions'],
			'conditions' => $conditionsFilters,
		];
		$subs = [];
		foreach ($profileNav as $key => $item) {
			if (!empty($item['event'])) {
				continue;
			}
			$subs[] = [
				'Id' => ($item['url']=='/profile/favorites.html')?Connect::$projectServices['navigator']['profileId']:0,
				'Name' => $item['title'],
				'Url' => $item['url'],
			]; 
		};
		$products = $productsUtils->getProducts($settings);
		$smarty->assign('normalView',0);
		$smarty->assign('products', $products['rows']);
		$smarty->assign('productsCount', $products['count'] . ' ' . TextTransforms::ending2("товар", $products['count']));
		$smarty->assign('productsSorting', $sorting['rows']);
		$smarty->assign('productsSortingActive', $sorting['active']);
		$smarty->assign('paginator', $products['paginator']);
		$smarty->assign('paginatorTpl', $smarty->fetch('packages/WeppsExtensions/Template/Paginator/Paginator.tpl'));
		$smarty->assign('productsTpl', $smarty->fetch('packages/WeppsExtensions/Products/ProductsItems.tpl'));
		$smarty->assign('childsNav', $subs);
		$smarty->assign('filtersNav', $filters->getFilters($conditions));
		$smarty->assign('content', $this->navigator->content);
		$smarty->assign('productsPageTpl', $smarty->fetch('packages/WeppsExtensions/Products/Products.tpl'));
	}
}