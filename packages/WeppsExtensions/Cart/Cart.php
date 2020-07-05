<?
namespace PPSExtensions\Cart;
use PPS\Core\NavigatorPPS;
use PPS\Core\SmartyPPS;
use PPS\Core\DataPPS;
use PPS\Core\ExtensionPPS;
use PPS\Exception\ExceptionPPS;
use PPS\Utils\TemplateHeadersPPS;
use PPS\Utils\UtilsPPS;
use PPS\Spell\SpellPPS;

class CartPPS extends ExtensionPPS {
	public function request() {
		$headers = $this->headers;
		$smarty = SmartyPPS::getSmarty();
		$cartSummary = CartUtilsPPS::cartSummary();
		
// 		UtilsPPS::debug($cartSummary['cart']);
// 		CartUtilsPPS::addOrderPositions(0);
// 		exit();
		
		switch (NavigatorPPS::$pathItem) {
			case 'order':
				if (!isset($_SESSION['user']['Id'])) ExceptionPPS::error404 ();
				$this->extensionData ['element'] = 1;
				
				if ($cartSummary ['qty'] == 0) {
					$this->tpl = 'packages/PPSExtensions/Cart/CartEmpty.tpl';
					$this->navigator->content ['Name'] = "Ваша корзина пуста";
				} else {
					$this->tpl = 'packages/PPSExtensions/Cart/CartOrder.tpl';
					$this->navigator->content ['Name'] = "Оформление заказа";
					$this->headers->js ( "/ext/Cart/CartOrder.js" );
					
					$obj = new DataPPS ( "s_Users" );
					$user = $obj->getMax ( $_SESSION ['user'] ['Id'] ) [0];
					$smarty->assign ( 'user', $user );
					if ($_SESSION['user']['ShowAdmin']==1) {
						$this->headers->js("/ext/Profile/Profile.{$headers::$rand}.js");
						$smarty->assign( 'profileStaffTpl' , $smarty->fetch('packages/PPSExtensions/Profile/ProfileStaff.tpl'));
					}
					$smarty->assign ( 'cartAboutTpl', $smarty->fetch ( 'packages/PPSExtensions/Cart/CartAbout.tpl' ) );
					$obj = new DataPPS ( "GeoCities" );
					$res = $obj->get ( "DisplayOff=0 and Name = '{$user['City']}'", 1, 1, "Priority,Name" );
					// UtilsPPS::debug($user,1);
					if (isset ( $res [0] ['Id'] )) {
						$js = "
					<script>
					setTimeout(function() {
						layoutPPS.request('action=delivery&city={$res[0]['Name']}&cityId={$res[0]['Id']}', '/ext/Cart/Request.php',$('#delivery'));
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
				//UtilsPPS::debug($text,0);
				//UtilsPPS::debug($_SESSION,1);
				$this->extensionData['element'] = 1;
				
				
				if ($cartSummary['qty']==0) {
					$this->navigator->content['Name'] = "Ваша корзина пуста";
					$this->tpl = 'packages/PPSExtensions/Cart/CartEmpty.tpl';
				} else {
					/*
					 * Создать заказ, смотрим структуру текущиего сайта (HISTORY ORDERS STATUSES)
					 */
					$this->tpl = 'packages/PPSExtensions/Cart/CartFinish.tpl';
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
					
					$orderId = SpellPPS::getNumberOrder($_SESSION['cartAdd']['orderId']);
					
					$this->navigator->content['Name'] = "Заказ № {$orderId} успешно отправлен";
					
					
					/*
					 * Дополнение к финальному сообщению
					 */
					$obj = new DataPPS("TradePaymentVars");
					$payment = $obj->getMax($cartSummary['cartAdd']['paymentChecked'])[0];
					$paymentFinishDir = 'packages/PPSExtensions/Cart/'.$payment['PaymentExtFinish'];
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
				$this->tpl = 'packages/PPSExtensions/Cart/CartSummary.tpl';
				if (isset($_SESSION['user']['ShowAdmin']) && $_SESSION['user']['ShowAdmin']==1) {
					$this->headers->js("/ext/Profile/Profile.{$headers::$rand}.js");
					$smarty->assign( 'profileStaffTpl' , $smarty->fetch('packages/PPSExtensions/Profile/ProfileStaff.tpl'));
				}
				$smarty->assign('cartSummary',$cartSummary);
				
				$smarty->assign('cartAboutTpl',$smarty->fetch('packages/PPSExtensions/Cart/CartAbout.tpl'));
// 				$obj = new DataPPS("Cart");
// 				$res = $obj->getMax("t.DisplayOff=0");
// 				$smarty->assign('elements',$res);

				if ($cartSummary['qty']==0) {
					$this->navigator->content['Name'] = "Ваша корзина пуста";
					$this->tpl = 'packages/PPSExtensions/Cart/CartEmpty.tpl';
				}
				break;
			default:
				ExceptionPPS::error404();
				break;
		}
		
		
		
		/**
		 * Переменные для глобального шаблона
		 */
		$this->headers->css("/ext/Cart/Cart.{$headers::$rand}.css");
		$this->headers->js("/ext/Cart/Cart.{$headers::$rand}.js");
		$this->headers->css("/ext/Products/Products.{$headers::$rand}.css");
		//UtilsPPS::debug($this->destinationOuter);
		//$smarty->assign($this->destinationTpl,$this->destinationOuter);
		$smarty->assign('tpl',$smarty->fetch($this->tpl));
		$smarty->assign('extension',$smarty->fetch('packages/PPSExtensions/Cart/Cart.tpl'));
		//UtilsPPS::debug(1,1);
		return;
	}
}
?>