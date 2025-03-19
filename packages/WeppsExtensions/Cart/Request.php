<?php
namespace WeppsExtensions\Cart;

use WeppsCore\Utils\RequestWepps;
use WeppsCore\Core\DataWepps;
use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\Spell\SpellWepps;
use WeppsCore\Validator\ValidatorWepps;
use WeppsExtensions\Products\ProductsWepps;

require_once '../../../config.php';
require_once '../../../autoloader.php';
require_once '../../../configloader.php';

class RequestCartWepps extends RequestWepps {
	public function request($action="") {
		switch ($action) {
			case 'cartSummary':
				$cartSummary = CartUtilsWepps::cartSummary();
				echo $cartSummary['qty'];
				exit();
			case 'addCart':
				$this->tpl = 'RequestAddCart.tpl';
				
				break;
			case 'qty':
				if (!isset($this->get['id'])) exit();
				if (!isset($this->get['qty'])) exit();
				if (!isset($_SESSION['cart'][$this->get['id']])) exit();
				$this->tpl = 'RequestTemplate.tpl';
				$productId = $this->get['id'];
				
				//$_SESSION['cart'][$productId]['Qty'] = ($this->get['option']=='add') ? $_SESSION['cart'][$productId]['Qty'] + 1 : $_SESSION['cart'][$productId]['Qty'] - 1;
				$_SESSION['cart'][$productId]['Qty'] = (int) $this->get['qty'];
				$_SESSION['cart'][$productId]['Qty'] = ($_SESSION['cart'][$productId]['Qty']<=$_SESSION['cart'][$productId]['QtyMin']) ? $_SESSION['cart'][$productId]['QtyMin'] : $_SESSION['cart'][$productId]['Qty'];
				$_SESSION['cart'][$productId]['Qty'] = ($_SESSION['cart'][$productId]['Qty']>=$_SESSION['cart'][$productId]['QtyMax']) ? $_SESSION['cart'][$productId]['QtyMax'] : $_SESSION['cart'][$productId]['Qty'];
				$_SESSION['cart'][$productId]['PriceAmount'] = $_SESSION['cart'][$productId]['Qty'] * $_SESSION['cart'][$productId]['Data']['OptionQty'] * $_SESSION['cart'][$productId]['Data']['PriceAmount'];
				$cartSummary = CartUtilsWepps::cartSummary();
				$js .= "
					readyCartInit();
					cartTopUpdate({
						'qtyTop' : '{$cartSummary['qty']}',
						'priceAmountTop' : '".SpellWepps::money($cartSummary['priceAmount'])."'
					});
					
				";
				$this->assign('js', $js);
				$this->assign('cartSummary',$cartSummary);
				//$this->assign('',
				if (isset($_SESSION['user']['ShowAdmin']) && $_SESSION['user']['ShowAdmin']==1) {
					$this->fetch2('profileStaffTpl','../Profile/ProfileStaff.tpl');
				}
				$this->fetch2('cartAboutTpl','CartAbout.tpl');
				$this->fetch('tpl', 'CartSummary.tpl');
				
				break;
			case 'removePromt':
				if (!isset($this->get['id'])) exit();
				if (!isset($_SESSION['cart'][$this->get['id']])) exit();
				$this->tpl = 'RequestRemovePromt.tpl';
				$this->assign('product', $_SESSION['cart'][$this->get['id']]);
				$this->assign('qty', $_SESSION['cart'][$this->get['id']]['Qty']);
				break;
			case 'remove':
				if (!isset($this->get['id'])) exit();
				if (!isset($_SESSION['cart'][$this->get['id']])) exit();
				$id = $this->get['id'];
				$product = $_SESSION['cart'][$this->get['id']];
				$this->tpl = 'RequestTemplate.tpl';
				unset($_SESSION['cart'][$id]);
				$cartSummary = CartUtilsWepps::cartSummary();
				$js .= "
				readyCartInit();
				cartTopUpdate({
					'qtyTop' : '{$cartSummary['qty']}',
					'priceAmountTop' : '". SpellWepps::money($cartSummary['priceAmount'])."'
				});
				";
				
				if ($cartSummary['qty']==0) {
					$js = "
					location.href='{$product['Data']['Url']}';
					";
				}
				$this->assign('js', $js);
				$this->assign('cartSummary',$cartSummary);
				$this->fetch2('cartAboutTpl','CartAbout.tpl');
				$this->fetch('tpl', 'CartSummary.tpl');
				break;
			case 'cities':
				if (!isset($this->get['term'])) exit();
				$obj = new DataWepps("GeoCities");
				$obj->setFields('Name,Id');
				$obj->setConcat('Name as value');
				$res = $obj->get("DisplayOff=0 and (CountryId=3159 or (CountryId=9908 and RegionId=10227)) and Name like '%{$this->get['term']}%'",10,1,"Priority,Name");
				$json = SpellWepps::getJsonCyr($res);
				header('Content-type:application/json;charset=utf-8');
				echo $json;
				exit();
				break;
			case "delivery":
				/**
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
				/**
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
				/**
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
				break;
			case "addOrder" :
				/**
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
					
					
					/**
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
}

$request = new RequestCartWepps($_REQUEST);
$smarty->assign('get',$request->get);
$smarty->display($request->tpl);
?>