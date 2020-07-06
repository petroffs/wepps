<?
namespace WeppsExtensions\Cart;
use WeppsCore\Utils\RequestWepps;
use WeppsCore\Core\DataWepps;
use WeppsExtensions\Cart\CartWepps;
//use WeppsExtensions\Cart\CartWepps;
use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\Utils\UtilsWepps;
use WeppsExtensions\Cart\CartUtilsWepps;
use WeppsCore\Spell\SpellWepps;
use WeppsCore\Validator\ValidatorWepps;
use WeppsExtensions\Products\ExtensionProductsWepps;

require_once '../../../config.php';
require_once '../../../autoloader.php';
require_once '../../../configloader.php';

if (!session_start()) session_start();

class RequestCartWepps extends RequestWepps {
	public function request($action="") {
		$priceKey = "Price";
		if (isset($_SESSION['user']) && $_SESSION['user']['Opt']==1) {
			$priceKey = "PriceOpt";
		}
		$js = '';
		switch ($action) {
			case 'test501':
				echo "
						Sed ut perspiciatis, unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam eaque ipsa, quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt, explicabo. Nemo enim ipsam voluptatem, quia voluptas sit, aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos, qui ratione voluptatem sequi nesciunt, neque porro quisquam est, qui dolorem ipsum, quia dolor sit, amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt, ut labore et dolore magnam aliquam quaerat voluptatem. Ut enim ad minima veniam, quis nostrum exercitationem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi consequatur? Quis autem vel eum iure reprehenderit, qui in ea voluptate velit esse, quam nihil molestiae consequatur, vel illum, qui dolorem eum fugiat, quo voluptas nulla pariatur? At vero eos et accusamus et iusto odio dignissimos ducimus, qui blanditiis praesentium voluptatum deleniti atque corrupti, quos dolores et quas molestias excepturi sint, obcaecati cupiditate non provident, similique sunt in culpa, qui officia deserunt mollitia animi, id est laborum et dolorum fuga. Et harum quidem rerum facilis est et expedita distinctio. Nam libero tempore, cum soluta nobis est eligendi optio, cumque nihil impedit, quo minus id, quod maxime placeat, facere possimus, omnis voluptas assumenda est, omnis dolor repellendus. Temporibus autem quibusdam et aut officiis debitis aut rerum necessitatibus saepe eveniet, ut et voluptates repudiandae sint et molestiae non recusandae. Itaque earum rerum hic tenetur a sapiente delectus, ut aut reiciendis voluptatibus maiores alias consequatur aut perferendis doloribus asperiores repellat.
						";
				exit();
				break;
			case 'cartSummary':
				$cartSummary = CartUtilsWepps::cartSummary();
				echo $cartSummary['qty'];
				exit();
			case 'addCart':
				$this->tpl = 'RequestAddCart.tpl';
				if (!isset($this->get['id']) || $this->get['id']=='') exit();
				if (!isset($this->get['color']) || $this->get['color']=='') exit();
				if (!isset($this->get['sizes']) || $this->get['sizes']=='') exit();
				if (!isset($this->get['image']) || $this->get['image']=='') exit();
				if (!isset($this->get['add'])) exit();
				$this->get['qty'] = (!isset($this->get['qty'])) ? 1 : $this->get['qty'];
				$data = new DataWepps("Products");
				 
				/*
				 * Обнуление сессии при тестировании
				 */
				//unset($_SESSION['cart']);
				
				$data->setFields("Id,Name,Price,PriceOpt,PriceOld,PriceOptOld,Brand,Code,Articul,ArticulInner,DirectoryId,ProductType,ProductSex,QtyMin");
				$data->setConcat("concat('/product/',if(t.KeyUrl!='',t.KeyUrl,t.Id),'.html') as Url,s3.NameOsn as ProductType_NameOsn");
				$product = $data->getMax($this->get['id'])[0];
				$productId = $product['Id']."_".SpellWepps::getTranslit($this->get['color'],2).$this->get['add'];
				
				/*
				 * Акция
				 *
				 */
				if (isset($_SESSION['actionFire']) && $_SESSION['actionFire']==1) {
					$actions = ExtensionProductsWepps::getProductsItemActions($product);
					if (isset($actions['Persent'])) {
						$product['PriceOpt'] = $actions['Price'];
						$product['PriceOptAction'] = 1;
					}
				}
				
				/*
				 * Перчатки (QtyMin)
				 */
				$qtyMin = 1;
				if (isset($_SESSION['user']['Opt']) && $_SESSION['user']['Opt']==1 && $product['QtyMin']>0) {
					$qtyMin = $product['QtyMin'];
					$qtyMinDiff = (int)$product['QtyMin'] - count(explode(",", $this->get['sizes']));
					$qtyMin = ($qtyMinDiff <= 0) ? 1 : $qtyMinDiff+1;
				}
				
				$qtySet = array();
				$qtyMax = $qtyMin + 10;
				/*
				 * Расчет кол-ва
				 */
				$depo = ExtensionProductsWepps::getProductsItemDepo($product);
				if (!isset($depo['summary'][$this->get['color']])) exit();
				if (isset($this->get['add']) && $this->get['add']!='') {
					$qtyMax = min($depo['summaryAdd'][$this->get['color']]);
				} else {
					$qtyMax = min($depo['summary'][$this->get['color']]);
				}
				$qtyMax = ($qtyMax>10) ? 10 : $qtyMax;
				for ($i=$qtyMin;$i<=$qtyMax;$i++) {
					$qtySet[$i] = $i;
				}
				
				$this->get['qty'] = ($this->get['qty']<$qtyMin) ? $qtyMin : $this->get['qty'];
				$this->get['qty'] = ($this->get['qty']>$qtyMax) ? $qtyMax : $this->get['qty'];
				$this->assign('qtySet', $qtySet);
				
				$_SESSION['cart'][$productId] = array();
				$_SESSION['cart'][$productId]['Data'] = $product;
				$_SESSION['cart'][$productId]['Data']['PriceAmount'] = $product[$priceKey];
				$_SESSION['cart'][$productId]['Data']['PriceAmountOld'] = $product[$priceKey.'Old'];
				$_SESSION['cart'][$productId]['Data']['OptionColor'] = $this->get['color'];
				$_SESSION['cart'][$productId]['Data']['OptionSize'] = $this->get['sizes'];
				$_SESSION['cart'][$productId]['Data']['OptionQty'] = count(explode(",", $this->get['sizes']));
				$_SESSION['cart'][$productId]['Data']['Image_FileUrl'] = $this->get['image'];
				$_SESSION['cart'][$productId]['Qty'] = $this->get['qty'];
				$_SESSION['cart'][$productId]['QtyMin'] = $qtyMin;
				$_SESSION['cart'][$productId]['QtyMax'] = $qtyMax;
				$_SESSION['cart'][$productId]['PriceAmount'] = $_SESSION['cart'][$productId]['Data']['PriceAmount'] * $_SESSION['cart'][$productId]['Data']['OptionQty'] * $_SESSION['cart'][$productId]['Qty'];
				$cartSummary = CartUtilsWepps::cartSummary();
				$this->assign('cartSummary',$cartSummary);
				$this->assign('product', $_SESSION['cart'][$productId]);
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
				$errors = array ();
				$errors ['address'] = ValidatorWepps::isNotEmpty ( $this->get ['address'], "Не заполнено" );
				$errors ['addressIndex'] = ValidatorWepps::isNotEmpty ( $this->get ['addressIndex'], "Не заполнено" );
				$outer = ValidatorWepps::setFormErrorsIndicate ( $errors, $this->get ['form'] );
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