<?php
namespace WeppsExtensions\Cart;

use WeppsCore\Utils\RequestWepps;
use WeppsCore\Core\DataWepps;
use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\TextTransforms\TextTransformsWepps;
use WeppsCore\Validator\ValidatorWepps;
use WeppsCore\Utils\UtilsWepps;
use WeppsCore\Connect\ConnectWepps;
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
			case 'addOrder':
				break;
			case 'copyOrder':
				break;
			case 'cities':
				$deliveryUtils = new DeliveryUtilsWepps();
				$res = $deliveryUtils->getCitiesByQuery($this->get['text']);
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
				$this->tpl = 'RequestDelivery.tpl';
				$deliveryUtils = new DeliveryUtilsWepps();
				$delivery = $deliveryUtils->getDeliveryTariffsByCitiesId($this->get['citiesId']);
				if (!empty($delivery)) {
					$this->assign('delivery', $delivery);
					$cartUtils->setCartCitiesId($this->get['citiesId']);
				}
			break;
			case "payments":
				if (empty($this->get["deliveryId"])) {
					http_response_code(404);
					exit();
				}
				$this->tpl = 'RequestPayments.tpl';
				$paymentsUtils = new PaymentsUtilsWepps();
				$payments = $paymentsUtils->getPaymentsByDeliveryId($this->get['deliveryId']);
				if (!empty($payments)) {
					$this->assign('payments', $payments);
					$cartUtils->setCartDelivery($this->get['deliveryId']);
				}
			break;
			case "shipping":
				if (empty($this->get["paymentsId"])) {
					http_response_code(404);
					exit();
				}
				$cartUtils->setCartPayments($this->get['paymentsId']);
				break;
			
			/**
			 * ! to remove
			 */
				case "addOrder--" :
				/*
				 * Проверка данных, индикация ошибок
				 */
				$this->errors = array ();
				$this->errors ['address'] = ValidatorWepps::isNotEmpty ( $this->get ['address'], "Не заполнено" );
				$this->errors ['addressIndex'] = ValidatorWepps::isNotEmpty ( $this->get ['addressIndex'], "Не заполнено" );
				$outer = ValidatorWepps::setFormErrorsIndicate ( $this->errors, $this->get ['form'] );
				echo $outer ['Out'];
				if ($outer ['Co'] == 0) {
					/**
					 * Регистрация заказа
					 */
					$settings = array (
							'phone' => $this->get ['phone'],
							'email' => $this->get ['email'],
							'address' => $this->get ['address'],
							'addressIndex' => $this->get ['addressIndex'],
							'comment' => $this->get ['comment'],
					);
					$orderId = CartUtilsWepps::addOrder($settings);
					$_SESSION['cartAdd']['orderId'] = $orderId;
					
					
					//UtilsWepps::debug($order,1);
					
					
					/*
					 * Отправка на страницу Финиша и подключение
					 * финального скрипта для оплаты (при наличии)
					 * Сохранить флаг в сессию, чтобы вызвать заказ на финише
					 */
					$js = "
							<script>
							location.href='/cart/finish.html';
							</script>
							";
					echo $js;
				}
				exit();
			default:
				ExceptionWepps::error404();
				break;
		}
	}
	private function displayCart(CartUtilsWepps $cartUtils) {
		$this->tpl = 'RequestEditCart.tpl';
		$cartSummary = $cartUtils->getCartSummary();
		if (empty($cartSummary['items'])) {
			$this->fetch('cartCheckoutTpl','CartEmpty.tpl');
			return;
		}
		$this->assign('cartSummary',$cartSummary);
		$this->assign('cartText',[
				'goodsCount' => TextTransformsWepps::ending2("товар",$cartSummary['quantityActive'])
		]);
		$arr = [];
		if (!empty($cartSummary['favorites']['items'])) {
			$arr = array_column($cartSummary['favorites']['items'],'id');
		}
		$this->assign('cartFavorites',$arr);
		$this->fetch('cartCheckoutTpl','CartCheckout.tpl');
		return;
	}
}

$request = new RequestCartWepps($_REQUEST);
$smarty->assign('get',$request->get);
$smarty->display($request->tpl);
?>