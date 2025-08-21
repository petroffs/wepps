<?php
namespace WeppsExtensions\Profile;

use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Core\ExtensionWepps;
use WeppsCore\Core\NavigatorWepps;
use WeppsCore\Core\SmartyWepps;
use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\Utils\UtilsWepps;
use WeppsExtensions\Addons\Jwt\JwtWepps;

class ProfileWepps extends ExtensionWepps {
	private $profileTpl = '';
	private $user;
	public function request()
	{
		$this->tpl = __DIR__ . '/Profile.tpl';
		$this->user = ConnectWepps::$projectData['user']??[];
		if (!empty(NavigatorWepps::$pathItem)) {
			$this->extensionData['element'] = 1;
		}
		$profileUtils = new ProfileUtilsWepps($this->user);
		$profileNav = $profileUtils->getNav();
		#UtilsWepps::debug($profileNav,21);
		$this->headers->js ("/ext/Profile/Profile.{$this->rand}.js");
		$this->headers->css ("/ext/Profile/Profile.{$this->rand}.css");

		$smarty = SmartyWepps::getSmarty();
		$smarty->assign ('normalView',0);
		$smarty->assign ('pathItem',NavigatorWepps::$pathItem);
		$smarty->assign ('get',$this->get);
		$smarty->assign('profileNav',$profileNav);
		
		if (empty($this->user)) {
			switch (NavigatorWepps::$pathItem) {
				case 'reg':
					$this->profileTpl = 'ProfileReg.tpl';
					break;
				case 'password':
					$this->profileTpl = 'ProfilePassword.tpl';
					if (!empty($this->get['token'])) {
						$jwt = new JwtWepps();
						$payload = $jwt->token_decode($this->get['token']);
						if ($payload['payload']['typ']=='pass') {
							$user = @ConnectWepps::$instance->fetch('SELECT * from s_Users where Id=? and DisplayOff=0',[$payload['payload']['id']])[0];
							if (empty($user)) {
								$this->profileTpl = 'ProfilePasswordError.tpl';
								break;
							}
							$this->profileTpl = 'ProfilePasswordConfirm.tpl';
							break;
						}
						$this->profileTpl = 'ProfilePasswordError.tpl';
					}
					break;
				default:
					$this->profileTpl = 'ProfileSignIn.tpl';
					break;
			}
			$smarty->assign('profileTpl',$smarty->fetch(__DIR__ . '/' . $this->profileTpl));
			$smarty->assign($this->targetTpl,$smarty->fetch($this->tpl));
			return;
		}
		switch (NavigatorWepps::$pathItem) {
				case '':
				case 'reg':
				case 'password':
					$this->profileTpl = 'ProfileHome.tpl';
					break;
				case 'orders':
					$this->profileTpl = 'ProfileOrders.tpl';
					break;
				case 'favorites':
					$this->profileTpl = 'ProfileFavorites.tpl';
					break;
				case 'settings':
					$this->profileTpl = 'ProfileSettings.tpl';
					break;
				default:
					ExceptionWepps::error404();
					break;
			}
		$smarty->assign('profileTpl',$smarty->fetch(__DIR__ . '/' . $this->profileTpl));
		$smarty->assign($this->targetTpl,$smarty->fetch($this->tpl));
		return;
	}
}