<?php
namespace WeppsExtensions\Profile;

use WeppsCore\Connect;
use WeppsCore\Extension;
use WeppsCore\Navigator;
use WeppsCore\Smarty;
use WeppsCore\Exception;
use WeppsCore\Utils;
use WeppsExtensions\Addons\Jwt\Jwt;
use WeppsExtensions\Addons\RemoteServices\RecaptchaV2;

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
				$this->profileTpl = 'ProfileOrders.tpl';
				break;
			case 'favorites':
				$this->profileTpl = 'ProfileFavorites.tpl';
				break;
			case 'settings':
				$this->profileTpl = 'ProfileSettings.tpl';
				break;
			default:
				Exception::error404();
				break;
		}
		$smarty->assign('profileTpl', $smarty->fetch(__DIR__ . '/' . $this->profileTpl));
		$smarty->assign($this->targetTpl, $smarty->fetch($this->tpl));
		return;
	}
}