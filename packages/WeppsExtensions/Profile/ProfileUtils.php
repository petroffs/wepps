<?php
namespace WeppsExtensions\Profile;

use WeppsCore\Connect\ConnectWepps;
use WeppsExtensions\Addons\Jwt\JwtWepps;
use WeppsCore\Core\NavigatorWepps;
use WeppsExtensions\Products\ProductsWepps;

class ProfileUtilsWepps {
	private $settings = [];
	private $navigator = [];
	private $user = [];
	public function __construct() {
		
	}
	public function setNavigator(NavigatorWepps $navigator) {
		$this->navigator = $navigator;
	}
	public function getNav($pathItem='') {
		if (!empty($this->partner)) {
			$nav = [
					[
							'title'=>'Профайл',
							'alias'=>'',
							'url'=>'/profile/',
					],
					[
							'title'=>'Мои бонусы',
							'alias'=>'bonuses',
							'url'=>'/profile/bonuses.html',
					],
					[
							'title'=>'Мои заказы',
							'alias'=>'orders',
							'url'=>'/profile/orders.html',
					],
					[
							'title'=>'Избранное',
							'alias'=>'favorites',
							'url'=>'/profile/favorites.html',
					],
					[
							'title'=>'Настройки',
							'alias'=>'settings',
							'url'=>'/profile/settings.html',
					],
					[
							'title'=>'Выход',
							'alias'=>'signout',
							'url'=>'/profile/',
							'event'=>'signOut',
					],
			];
		} else {
			$nav = [
					[
							'title'=>'Войти',
							'alias'=>'signin',
							'url'=>'/profile/',
							'event'=>'win:popup:signIn',
					],
					[
							'title'=>'Регистрация',
							'alias'=>'reg',
							'url'=>'/profile/',
							'event'=>'win:popup:registration',
					],
					[
							'title'=>'Восстановить доступ',
							'alias'=>'password',
							'url'=>'/profile/',
							'event'=>'win:popup:passwordReset',
					],
			];
		}
		return [
				'nav'=>$nav,
		];
	}
	
	public function getAuthByToken(string $token='') {
		$token = (empty($token) && !empty($_COOKIE['wepps_token'])) ? @$_COOKIE['wepps_token'] : $token;
		if (!empty($token)) {
			$jwt = new JwtWepps();
			$data = $jwt->token_decode($token);
			if (@$data['payload']['typ']=='auth' && !empty($data['payload']['id'])) {
				$sql = "select * from s_Users where Id=?";
				$res = ConnectWepps::$instance->fetch($sql,[$data['payload']['id']]);
				if (!empty($res[0]['JData'])) {
					$partner = json_decode($res[0]['JData'],true);
					ConnectWepps::$projectData['partnerPrev'] = $partner;
					foreach ($partner['customers'] as $key=>$value) {
						if (empty($value['cards'])) {
							unset($partner['customers'][$key]);
						}
					}
					$partner['customers'] = array_merge($partner['customers'],[]);
					$partner['JCart'] = $res[0]['JCart'];
					$partner['JFav'] = $res[0]['JFav'];
					$partner['JAddress'] = $res[0]['JAddress'];
					ConnectWepps::$projectData['partner'] = $this->partner =  $partner;
					return true;
				}
			}
		}
		setcookie('access_token','',0,'/',ConnectWepps::$projectDev['host'],true,true);
		return false;
	}
	public function removeAuth() {
		
	}
	public function getFavorites() {
		$jdata = json_decode($this->partner['JFav'],true);
		if (!is_array($jdata)) {
			return false;
		}
		$ids = implode(',', array_keys($jdata));
		if (empty($ids)) {
			return false;
		}
		$conditions = "t.Id in ($ids)";
		$settings = [
				'page'=>@$_GET['page'],
				'condition'=>$conditions,
				'conditionSelf'=>$conditions,
				'orderBy'=>"FIELD(t.Id,$ids)"
		];
		$products = ProductsWepps::getProducts($settings);
		return $products;
	}
	
	public function getOrders($id=0) {
		
	}
}
?>