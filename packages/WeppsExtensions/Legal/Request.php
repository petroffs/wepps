<?php
require_once '../../../configloader.php';

use WeppsCore\Request;
use WeppsCore\Exception;
use WeppsCore\TemplateHeaders;
use WeppsCore\Utils;
use WeppsExtensions\Legal\LegalUtils;

/**
 * Обработчик AJAX-запросов для юридических соглашений
 *
 * Обрабатывает запросы на согласие с политикой cookies и отображение
 * настроек приватности. Используется для асинхронных запросов.
 *
 * @package WeppsExtensions\Legal
 */
class RequestLegacy extends Request
{
	/**
	 * Обработка AJAX-запроса
	 *
	 * В зависимости от действия ($action) выполняет соответствующие операции:
	 * - 'agree': сохраняет согласия пользователя в cookies
	 * - 'settings': отображает страницу настроек приватности
	 *
	 * @param string $action Действие для выполнения ('agree' или 'settings')
	 * @return void
	 * @throws Exception При неизвестном действии возвращает 404 ошибку
	 */
	public function request($action = "")
	{
		switch ($action) {
			case 'agree':
				$lifetime = 60*60*24*180;
				$default = $this->get['default'] ?? 'false';
				$analytics = $this->get['analytics'] ?? 'false';
				// if ($default === 'false' && $analytics === 'true') {
				//     $default = '';
				// 	$analytics = 'false';
				// }
				// if ($analytics === 'false') {
				//     $analytics = '';
				// }
				if ($default === 'false' && $analytics === 'false') {
				    $default = '';
				    $analytics = '';
				}
				Utils::cookies('wepps_cookies_default',$default,$lifetime);
				Utils::cookies('wepps_cookies_analytics',$analytics,$lifetime);
				if ($default === 'true') {
					echo "<script>$('.legal-modal').remove()</script>";
				}
				break;
			case 'settings':
				$headers = new TemplateHeaders();
				$legalUtils = new LegalUtils($headers);
				$this->assign('privacyPolicyAgreements', $legalUtils->getPrivacyPolicyAgreements());
				$this->tpl = 'RequestSettings.tpl';
				break;
			default:
				Exception::error(404);
				break;
		}
	}
}
$request = new RequestLegacy($_REQUEST);
$smarty->assign('get', $request->get);
$smarty->display($request->tpl);