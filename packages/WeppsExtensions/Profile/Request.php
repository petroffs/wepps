<?php
require_once '../../../configloader.php';

use WeppsCore\Connect;
use WeppsCore\Request;
use WeppsCore\Exception;
use WeppsCore\TemplateHeaders;
use WeppsCore\Users;
use WeppsCore\Utils;
use WeppsCore\Validator;
use WeppsExtensions\Addons\RemoteServices\RecaptchaV2;
use WeppsExtensions\Addons\RemoteServices\SmartCaptcha;
use WeppsExtensions\Cart\CartUtils;
use WeppsExtensions\Profile\ProfileActions;

/**
 * Класс для обработки запросов профиля пользователя.
 */
class RequestProfile extends Request
{
	/**
	 * Обрабатывает запросы профиля пользователя.
	 *
	 * @param string $action Действие, которое необходимо выполнить.
	 */
	public function request($action = "")
	{
		$profileActions = new ProfileActions(true);
		switch ($action) {
			case "sign-in":
				$result = $profileActions->signIn($this->get['login'] ?? '', $this->get['password'] ?? '');
				$this->errors = $profileActions->errors;
				if ($result['status'] === 200) {
					// Склеить корзину неавторизованного с текущим
					if (!empty(Utils::cookies('wepps_cart')) && !empty(Utils::cookies('wepps_cart_guid'))) {
						$cartUtils = new CartUtils();
						$cart = $cartUtils->getCart();
						$cartUser = json_decode($result['data']['user']['JCart'], true);
						if (!empty($cart['items']) && !empty($cartUser['items'])) {
							foreach ($cartUser['items'] as $item) {
								$cartUtils->add($item['id'], $item['qu']);
							}
							$cart = $cartUtils->getCart();
							$json = json_encode($cart, JSON_UNESCAPED_UNICODE);
							Connect::$instance->query('update s_Users set JCart=? where Id=?', [$json, $result['data']['user']['Id']]);
							Utils::cookies('wepps_cart', '');
							Utils::cookies('wepps_cart_guid', '');
						}
					}
				}
				$outer = Validator::setFormErrorsIndicate($this->errors, $this->get['form']);
				echo $outer['html'];
				if ($outer['count'] == 0) {
					$js = "
						<script>
						location.reload();
						</script>
					";
					echo $js;
				}
				break;
			case "sign-in-popup":
				$this->tpl = 'ProfilePopupSignIn.tpl';
				break;
			case "sign-out":
				$users = new Users();
				$users->removeAuth();
				$js = "
						<script>
						//location.reload();
						location.href='/profile/';
						</script>
					";
				echo $js;
				break;
			case 'password':
				$smartcaptcha = new SmartCaptcha();
				$check = $smartcaptcha->check($this->get['smartcaptcha-response'] ?? '');
				$message = '';
				if ($check['response']['status'] != 'ok') {
					$this->errors[$smartcaptcha->captchadub()] = 'Проверка не пройдена';
					if ($smartcaptcha->getSitekey()) {
						echo $smartcaptcha->reset();
					}
				} else {
					$result = $profileActions->requestPasswordReset($this->get['login'] ?? '');
					$this->errors = $profileActions->errors;
					$message = $result['status'] === 200 ? $result['message'] : '';
				}
				$this->outer($message);
				break;
			case 'password-confirm':
				$result = $profileActions->confirmPassword(
					$this->get['token'] ?? '',
					$this->get['password'] ?? '',
					$this->get['password2'] ?? ''
				);
				$this->errors = $profileActions->errors;
				$this->outer($result['status'] === 200 ? $result['message'] : '');
				break;
			case 'reg':
				$result = $profileActions->register(
					strtolower(trim($this->get['login'] ?? '')),
					Utils::phone($this->get['phone'] ?? '')['num'] ?? '',
					$this->get['nameSurname'] ?? '',
					$this->get['nameFirst'] ?? '',
					$this->get['namePatronymic'] ?? ''
				);
				$this->errors = $profileActions->errors;
				$this->outer($result['status'] === 200 ? $result['message'] : '');
				break;
			case 'reg-confirm':
				$result = $profileActions->confirmReg(
					$this->get['token'] ?? '',
					$this->get['password'] ?? '',
					$this->get['password2'] ?? ''
				);
				$this->errors = $profileActions->errors;
				$this->outer($result['status'] === 200 ? $result['message'] : '');
				break;
			case 'addOrdersMessage':
				$result = $profileActions->addOrdersMessage(
					Connect::$projectData['user']['Id'],
					(int) ($this->get['id'] ?? 0),
					$this->get['message'] ?? ''
				);
				if ($result['status'] === 200 && !empty($result['data']['order'])) {
					$this->assign('order', $result['data']['order']);
					$this->tpl = 'ProfileOrdersItem.tpl';
				}
				break;
			case 'change-name':
				$result = $profileActions->changeName(
					Connect::$projectData['user']['Id'],
					$this->get['nameSurname'] ?? '',
					$this->get['nameFirst'] ?? '',
					$this->get['namePatronymic'] ?? ''
				);
				$this->errors = $profileActions->errors;
				$this->outer($result['status'] === 200 ? $result['message'] : '');
				break;
			case 'change-email':
				$result = $profileActions->changeEmail(
					Connect::$projectData['user']['Id'],
					$this->get['login'] ?? '',
					$this->get['code'] ?? ''
				);
				$this->errors = $profileActions->errors;
				if ($result['status'] === 200) {
					$this->outer($result['message']);
					exit();
				}
				if ($result['status'] === 202) {
					echo "<script>$('.change-email-code').removeClass('w_hide');$('.change-email-code').find('input').prop('disabled',false);</script>";
				}
				$this->outer("", true, false);
				break;
			case 'change-phone':
				$result = $profileActions->changePhone(
					Connect::$projectData['user']['Id'],
					Connect::$projectData['user']['Email'],
					$this->get['phone'] ?? '',
					$this->get['code'] ?? ''
				);
				$this->errors = $profileActions->errors;
				if ($result['status'] === 200) {
					$this->outer($result['message']);
					exit();
				}
				if ($result['status'] === 202) {
					echo "<script>$('.change-phone-code').removeClass('w_hide');$('.change-phone-code').find('input').prop('disabled',false);</script>";
				}
				$this->outer("", true, false);
				break;
			case 'change-password':
				$result = $profileActions->changePassword(
					Connect::$projectData['user']['Id'],
					$this->get['password'] ?? '',
					$this->get['password2'] ?? ''
				);
				$this->errors = $profileActions->errors;
				$this->outer($result['status'] === 200 ? $result['message'] : '');
				break;
			case 'remove':
				$result = $profileActions->remove(
					Connect::$projectData['user']['Id'],
					Connect::$projectData['user']['Login'],
					Connect::$projectData['user']['Email'],
					$this->get['word'] ?? '',
					$this->get['code'] ?? ''
				);
				$this->errors = $profileActions->errors;
				if ($result['status'] === 200) {
					$this->outer($result['message']);
					$users = new Users();
					$users->removeAuth();
					echo "<script>setTimeout(function() { location.href = '/profile/'; }, 2500);</script>";
					exit();
				}
				if ($result['status'] === 202) {
					echo "<script>$('.remove-code').removeClass('w_hide');$('.remove-code').find('input').prop('disabled',false);</script>";
				}
				$this->outer("", true, false);
				break;
			default:
				Exception::error(404);
				break;
		}
	}
}
$request = new RequestProfile($_REQUEST);
$smarty->assign('get', $request->get);
$smarty->display($request->tpl);