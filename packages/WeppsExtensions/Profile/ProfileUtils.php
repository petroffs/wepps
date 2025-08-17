<?php
namespace WeppsExtensions\Profile;

use WeppsCore\Connect\ConnectWepps;
use WeppsExtensions\Addons\Jwt\JwtWepps;
use WeppsCore\Core\NavigatorWepps;
use WeppsExtensions\Products\ProductsWepps;

class ProfileUtilsWepps
{
	private $settings = [];
	private $navigator = [];
	private $user = [];
	public function __construct(array $user)
	{
		$this->user = $user;
	}
	public function setNavigator(NavigatorWepps $navigator)
	{
		$this->navigator = $navigator;
	}
	public function getNav($pathItem = '')
	{
		if (!empty($this->user)) {
			$nav = [
				[
					'title' => 'Профайл',
					'alias' => '',
					'url' => '/profile/',
				],
				[
					'title' => 'Мои заказы',
					'alias' => 'orders',
					'url' => '/profile/orders.html',
				],
				[
					'title' => 'Избранное',
					'alias' => 'favorites',
					'url' => '/profile/favorites.html',
				],
				[
					'title' => 'Настройки',
					'alias' => 'settings',
					'url' => '/profile/settings.html',
				],
				[
					'title' => 'Выход',
					'alias' => 'signout',
					'url' => '/profile/',
					'event' => 'sign-out',
				],
			];
			return $nav;
		}
		$nav = [
			[
				'title' => 'Войти',
				'alias' => '',
				'url' => '/profile/',
				'event' => '',
			],
			[
				'title' => 'Регистрация',
				'alias' => 'reg',
				'url' => '/profile/reg.html',
				'event' => '',
			],
			[
				'title' => 'Восстановить доступ',
				'alias' => 'password',
				'url' => '/profile/password.html',
				'event' => '',
			],
		];
		return $nav;
	}
	public function getFavorites()
	{
		$jdata = json_decode($this->partner['JFav'], true);
		if (!is_array($jdata)) {
			return false;
		}
		$ids = implode(',', array_keys($jdata));
		if (empty($ids)) {
			return false;
		}
		$conditions = "t.Id in ($ids)";
		$settings = [
			'page' => @$_GET['page'],
			'condition' => $conditions,
			'conditionSelf' => $conditions,
			'orderBy' => "FIELD(t.Id,$ids)"
		];
		$products = ProductsWepps::getProducts($settings);
		return $products;
	}

	public function getOrders($id = 0)
	{

	}
}