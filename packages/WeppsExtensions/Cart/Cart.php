<?php
namespace WeppsExtensions\Cart;

use WeppsCore\Core\NavigatorWepps;
use WeppsCore\Core\SmartyWepps;
use WeppsCore\Core\DataWepps;
use WeppsCore\Core\ExtensionWepps;
use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\Spell\SpellWepps;

class CartWepps extends ExtensionWepps {
	public function request() {
		$headers = $this->headers;
		$smarty = SmartyWepps::getSmarty();
		$cartSummary = CartUtilsWepps::cartSummary();
		switch (NavigatorWepps::$pathItem) {
			case 'order':
				if (!isset($_SESSION['user']['Id'])) ExceptionWepps::error404 ();
				$this->extensionData ['element'] = 1;
				if ($cartSummary ['qty'] == 0) {
					$this->tpl = 'packages/WeppsExtensions/Cart/CartEmpty.tpl';
					$this->navigator->content ['Name'] = "Ваша корзина пуста";
				} else {
					$this->tpl = 'packages/WeppsExtensions/Cart/CartOrder.tpl';
					$this->navigator->content ['Name'] = "Оформление заказа";
					$this->headers->js ( "/ext/Cart/CartOrder.js" );
					
					$obj = new DataWepps ( "s_Users" );
					$user = $obj->getMax ( $_SESSION ['user'] ['Id'] ) [0];
					$smarty->assign ( 'user', $user );
					if ($_SESSION['user']['ShowAdmin']==1) {
						$this->headers->js("/ext/Profile/Profile.{$headers::$rand}.js");
						$smarty->assign( 'profileStaffTpl' , $smarty->fetch('packages/WeppsExtensions/Profile/ProfileStaff.tpl'));
					}
					$smarty->assign ( 'cartAboutTpl', $smarty->fetch ( 'packages/WeppsExtensions/Cart/CartAbout.tpl' ) );
					$obj = new DataWepps ( "GeoCities" );
					$res = $obj->get ( "DisplayOff=0 and Name = '{$user['City']}'", 1, 1, "Priority,Name" );
					// UtilsWepps::debug($user,1);
					if (isset ( $res [0] ['Id'] )) {
						$js = "
					<script>
					setTimeout(function() {
						layoutWepps.request('action=delivery&city={$res[0]['Name']}&cityId={$res[0]['Id']}', '/ext/Cart/Request.php',$('#delivery'));
					},500);
					</script>
					";
						$smarty->assign ( 'cityChecked', $res [0] ['Name'] );
						$smarty->assign ( 'js', $js );
					}
				}
				break;
			case 'finish':
				//echo $text;
				//UtilsWepps::debug($text,0);
				//UtilsWepps::debug($_SESSION,1);
				$this->extensionData['element'] = 1;
				
				
				if ($cartSummary['qty']==0) {
					$this->navigator->content['Name'] = "Ваша корзина пуста";
					$this->tpl = 'packages/WeppsExtensions/Cart/CartEmpty.tpl';
				} else {
					/*
					 * Создать заказ, смотрим структуру текущиего сайта (HISTORY ORDERS STATUSES)
					 */
					$this->tpl = 'packages/WeppsExtensions/Cart/CartFinish.tpl';
					//$this->pathItem = $ppsUrl;
					
					
					/*
					 * Этот блок переработать
					 * Этот блок переработать
					 * Этот блок переработать
					 */
					if (!isset($cartSummary['cartAdd']['orderId']) || $cartSummary['cartAdd']['orderId']==0) {
						//$this->get['title'] = 'Ваша корзина пуста';
						return;
					}
					
					$orderId = SpellWepps::getNumberOrder($_SESSION['cartAdd']['orderId']);
					
					$this->navigator->content['Name'] = "Заказ № {$orderId} успешно отправлен";
					
					
					/*
					 * Дополнение к финальному сообщению
					 */
					$obj = new DataWepps("TradePaymentVars");
					$payment = $obj->getMax($cartSummary['cartAdd']['paymentChecked'])[0];
					$paymentFinishDir = 'packages/WeppsExtensions/Cart/'.$payment['PaymentExtFinish'];
					if (is_file($paymentFinishDir)) require_once $paymentFinishDir;
					$smarty->assign('messageFinal',$payment['DescrFinish']);
					
					/***
					 * Временно скрываем
					 */
					//unset($_SESSION['cart']);
					//unset($_SESSION['cartAdd']);
					
					
				}
				break;
			case '':
				$this->tpl = 'packages/WeppsExtensions/Cart/CartSummary.tpl';
				if (isset($_SESSION['user']['ShowAdmin']) && $_SESSION['user']['ShowAdmin']==1) {
					$this->headers->js("/ext/Profile/Profile.{$headers::$rand}.js");
					$smarty->assign( 'profileStaffTpl' , $smarty->fetch('packages/WeppsExtensions/Profile/ProfileStaff.tpl'));
				}
				$smarty->assign('cartSummary',$cartSummary);
				$smarty->assign('cartAboutTpl',$smarty->fetch('packages/WeppsExtensions/Cart/CartAbout.tpl'));
				if ($cartSummary['qty']==0) {
					$this->navigator->content['Name'] = "Ваша корзина пуста";
					$this->tpl = 'packages/WeppsExtensions/Cart/CartEmpty.tpl';
				}
				break;
			default:
				ExceptionWepps::error404();
				break;
		}
		
		/*
		 * Переменные для глобального шаблона
		 */
		$this->headers->css("/ext/Cart/Cart.{$headers::$rand}.css");
		$this->headers->js("/ext/Cart/Cart.{$headers::$rand}.js");
		$this->headers->css("/ext/Products/Products.{$headers::$rand}.css");
		$smarty->assign('tpl',$smarty->fetch($this->tpl));
		$smarty->assign($this->targetTpl,$smarty->fetch('packages/WeppsExtensions/Cart/Cart.tpl'));
		//UtilsWepps::debug(1,1);
		return;
	}
}
?>