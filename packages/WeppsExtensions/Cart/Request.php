<?php
namespace WeppsExtensions\Cart;

use WeppsCore\Utils\RequestWepps;
use WeppsCore\Core\DataWepps;
use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\TextTransforms\TextTransformsWepps;
use WeppsCore\Validator\ValidatorWepps;
use WeppsCore\Utils\UtilsWepps;
use WeppsCore\Connect\ConnectWepps;

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
				$onpage=12;
				$page = max(1, (int)($this->get['page'] ?? 1));
				$limit = ($page - 1) * $onpage;
				/* $sql = "select c.Id,r.Id RegionsId,c.Name,r.Name,if (c.Name=r.Name,c.Name,concat(c.Name,', ',r.Name)) Title from CitiesCdek c
						join RegionsCdek r on r.Id = c.RegionsId where c.Name like \"{$this->get['text']}%\" limit $limit,$onpage";
				UtilsWepps::debug($sql,21) */
				$sql = "select c.Id,r.Id RegionsId,c.Name,r.Name,if (c.Name=r.Name,c.Name,concat(c.Name,', ',r.Name)) Title from CitiesCdek c
						join RegionsCdek r on r.Id = c.RegionsId where c.Name like ? limit $limit,$onpage";
				$res = ConnectWepps::$instance->fetch($sql,["{$this->get['text']}%"]);
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
				
		
			/*
			 * to remove
			 */		
			case 'cities':
				if (!isset($this->get['term'])) exit();
				$obj = new DataWepps("GeoCities");
				$obj->setFields('Name,Id');
				$obj->setConcat('Name as value');
				$res = $obj->get("DisplayOff=0 and (CountryId=3159 or (CountryId=9908 and RegionId=10227)) and Name like '%{$this->get['term']}%'",10,1,"Priority,Name");
				$json = json_encode($res,JSON_UNESCAPED_UNICODE);
				header('Content-type:application/json;charset=utf-8');
				echo $json;
				exit();
				break;
			case "delivery":
				/*
				 * Способы доставки текущего city
				 * Вычислить и передать в шаблон
				 */
				$cond = " and IsRetail=1";
				if ($_SESSION['user']['Opt']==1) {
					$cond = " and IsRetail=0";
				}
				
				
				
				$obj = new DataWepps("TradeDeliveryVars");
				$res = $obj->getMax("t.DisplayOff=0 and (Region = '' or Region like '%{$this->get['city']}%') and RegionExcl not like ('%{$this->get['city']}%') $cond",30,1,'t.Priority,t.Name');
				$this->assign('delivery', $res);
				$this->assign('city', $this->get['city']);
				$this->tpl = 'RequestOrderDelivery.tpl';
				
				$_SESSION['cartAdd']['cityChecked'] = $this->get['cityId']; 
				$_SESSION['cartAdd']['city'] = $this->get['city'];
				
				
			break;
			case "payment":
				/*
				 * Способы оплаты текущего delivery
				 * Вычислить и передать в шаблон
				 */
				$obj = new DataWepps("TradePaymentVars");
				$res = $obj->getMax("t.DisplayOff=0 and sk1.Field1 = '{$this->get['delivery']}'",30,1,'t.Priority,t.Name');
				
				
				//
				
				$this->assign('payment', $res);
				$this->assign('city', $this->get['city']);
				$obj = new DataWepps("TradeDeliveryVars");
				$res = $obj->getMax($this->get['delivery']);
				$cartSummary = CartUtilsWepps::cartSummary();
				$priceAdd = ($res[0]['PriceDelivMorePers']==1) ? round($cartSummary['priceAmount'] * $res[0]['PriceDelivMore'] / 100,0) : $res[0]['PriceDelivMore'];
				if ($res[0]['DeliveryExt']!='' && is_file($res[0]['DeliveryExt'])) require_once $res[0]['DeliveryExt'];
				$_SESSION['cartAdd']['deliveryChecked'] = $this->get['delivery'];
				$_SESSION['cartAdd']['deliveryPrice'] = $priceAdd;
				$cartSummary = CartUtilsWepps::cartSummary();
				$js = "
						$('input[name=\"delivery\"]').attr('data-price','0');
						$('input[name=\"delivery\"]:checked').attr('data-price','{$priceAdd}');
						cartPriceAdd('{$cartSummary['priceTotal']}');
						
						$('.cart-other').css('opacity',0.5);
						$('#submitOrder').prop('disabled',true);
					";
				$this->assign('jscode', $js);
				$this->assign('deliveryChecked', $this->get['delivery']);
				$this->tpl = 'RequestOrderPayment.tpl';
				
				break;
			case "shipping":
				/*
				 * Вычисление стоимости доставки
				 * на основе данных в списке TradeDeliveryVars,TradePaymentVars
				 */
				$obj = new DataWepps("TradePaymentVars");
				$res = $obj->getMax($this->get['payment']);
				$cartSummary = CartUtilsWepps::cartSummary();
				$priceAdd = ($res[0]['PriceMorePers']==1) ? round($cartSummary['priceAmount'] * $res[0]['PriceMore'] / 100,0) : $res[0]['PriceMore'];
				if ($res[0]['PaymentExt']!='' && is_file($res[0]['PaymentExt'])) require_once $res[0]['PaymentExt'];
				$_SESSION['cartAdd']['paymentChecked'] = $this->get['payment'];
				$_SESSION['cartAdd']['paymentPrice'] = $priceAdd;
				$cartSummary = CartUtilsWepps::cartSummary();
				$js = "
						<script>
						$('input[name=\"payment\"]').attr('data-price','0');
						$('input[name=\"payment\"]:checked').attr('data-price','{$priceAdd}');
						cartPriceAdd('{$cartSummary['priceTotal']}');
						
						$('.cart-other').css('opacity',1);
						$('#submitOrder').prop('disabled',false);
						</script>
					";
				echo $js;
				//UtilsWepps::debug($_SESSION['cartAdd']);
				exit ();
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
			break;
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