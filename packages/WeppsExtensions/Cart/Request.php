<?php
namespace WeppsExtensions\Cart;

use WeppsCore\Core\SmartyWepps;
use WeppsCore\Utils\RequestWepps;
use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\Utils\TemplateHeadersWepps;
use WeppsCore\Utils\UtilsWepps;
use WeppsExtensions\Cart\Delivery\DeliveryUtilsWepps;
use WeppsExtensions\Cart\Payments\PaymentsUtilsWepps;

require_once '../../../config.php';
require_once '../../../autoloader.php';
require_once '../../../configloader.php';

class RequestCartWepps extends RequestWepps {
	public function request($action="") {
		$cartUtils = new CartUtilsWepps();
		switch ($action) {
			case 'add':
				if (empty($this->get['id'])) {
					ExceptionWepps::error(400);
				}
				$this->tpl = 'RequestAddCart.tpl';
				$cartUtils->add($this->get['id']);
				break;
			case 'edit':
				if (empty($this->get['id'])) {
					ExceptionWepps::error(400);
				}
				if (empty($this->get['quantity']) || !is_numeric($this->get['quantity'])) {
					$this->get['quantity'] = 1;
				}
				$cartUtils->edit($this->get['id'],$this->get['quantity']);
				self::displayCart($cartUtils);
				break;
			case 'check':
				$cartUtils->check(@$this->get['id']);
				self::displayCart($cartUtils);
				break;
			case 'remove':
				if (empty($this->get['id'])) {
					ExceptionWepps::error(400);
				}
				$cartUtils->remove((int)$this->get['id']);
				self::displayCart($cartUtils);
				break;
			case 'removeCart':
				break;
			case 'favorites':
				if (empty($this->get['id'])) {
					ExceptionWepps::error(400);
				}
				$cartUtils->setFavorites($this->get['id']);
				break;
			case 'cities':
				$deliveryUtils = new DeliveryUtilsWepps();
				$res = $deliveryUtils->getCitiesByQuery($this->get['text'],(int) $this->get['page']??1);
				if (empty($res)) {
					echo json_encode([
							'hasMore' => false
					]);
					break;
				}
				$html = '';
				foreach($res as $row) {
					$html .= '<div class="w_suggestions-item" data-id="'.$row['Id'].'"><div>'.htmlspecialchars($row['Title']).'</div></div>';
				}
				echo json_encode([
						'html' => $html,
						'hasMore' => true
				]);
				break;
			case "delivery":
				if (empty($this->get["citiesId"])) {
					http_response_code(404);
					exit();
				}
				$deliveryUtils = new DeliveryUtilsWepps();
				$cartUtils->setCartSummary();
				$delivery = $deliveryUtils->getTariffsByCitiesId($this->get['citiesId'],$cartUtils);
				if (!empty($delivery)) {
					$cartUtils->setCartCitiesId($this->get['citiesId']);
					self::displayCheckoutCart($cartUtils);
				}
			break;
			case 'deliveryOperations':
				$deliveryUtils = new DeliveryUtilsWepps();
				$cartUtils->setCartSummary();
				$deliveryUtils->setOperations($this->get,$cartUtils);
				break;
			case "payments":
				if (empty($this->get["deliveryId"])) {
					http_response_code(404);
					exit();
				}
				$paymentsUtils = new PaymentsUtilsWepps();
				$payments = $paymentsUtils->getByDeliveryId($this->get['deliveryId'],$cartUtils);
				if (!empty($payments)) {
					$cartUtils->setCartDelivery($this->get['deliveryId']);
				}
				self::displayCheckoutCart($cartUtils);
			break;
			case "shipping":
				if (empty($this->get["paymentsId"])) {
					http_response_code(404);
					exit();
				}
				$cartUtils->setCartPayments($this->get['paymentsId']);
				self::displayCheckoutCart($cartUtils);
				break;
			case 'addOrder':
				/**
				 * По доставке/оплате получить контекст и вызывать необходимые проверки
				 * Если ошибки - выводим
				 * Если нет ошибок - вызываем необходимые сценарии 
				 * 	доставка - извлекаем нужные поля
				 * 	оплата - формируем ссылки и др. на оплату если требуется
				 * Оформляем заказ
				 * Жетально через транзакции
				 */
				UtilsWepps::debug($this->get,21);
				break;
			case 'copyOrder':
				break;
			default:
				ExceptionWepps::error404();
				break;
		}
	}
	private function displayCart(CartUtilsWepps $cartUtils) {
		$this->tpl = 'RequestDefault.tpl';
		$smarty = SmartyWepps::getSmarty();
		$template = new CartTemplatesWepps($smarty,$cartUtils);
		$template->default();
		return;
	}
	private function displayCheckoutCart(CartUtilsWepps $cartUtils) {
		$this->tpl = 'RequestCheckout.tpl';
		$smarty = SmartyWepps::getSmarty();
		$template = new CartTemplatesWepps($smarty,$cartUtils);
		$template->checkout();
		return;
	}
}

$request = new RequestCartWepps($_REQUEST);
$smarty->assign('get',$request->get);
$smarty->display($request->tpl);