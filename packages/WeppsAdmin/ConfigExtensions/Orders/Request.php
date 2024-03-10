<?php
namespace WeppsAdmin\ConfigExtensions\Orders;

use WeppsCore\Utils\RequestWepps;
use WeppsCore\Utils\UtilsWepps;
use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Validator\ValidatorWepps;
use WeppsCore\Core\DataWepps;
use WeppsCore\Spell\SpellWepps;
use WeppsExtensions\Cart\CartUtilsWepps;
use WeppsExtensions\Addons\Mail\MailWepps;

require_once '../../../../config.php';
require_once '../../../../autoloader.php';
require_once '../../../../configloader.php';

//http://pps.lubluweb.ru/packages/WeppsAdmin/ConfigExtensions/Processing/Request.php?id=5

class RequestOrdersWepps extends RequestWepps {
	public function request($action="") {
		$this->tpl = '';
		if (!isset($_SESSION['user']['ShowAdmin']) || $_SESSION['user']['ShowAdmin']!=1) ExceptionWepps::error404();
		switch ($action) {
			case "test":
				UtilsWepps::debug('test1',1);
				break;
			case "viewOrder":
			    $this->tpl = "RequestViewOrder.tpl";
			    if (empty($this->get['id'])) {
			    	ExceptionWepps::error404();
			    }
			    $id = addslashes($this->get['id']);
			    $order = $this->getOrder($id);
			    break;
			case "setPositionQty":
				if (empty($this->get['order']) || empty($this->get['price']) || empty($this->get['qty']) || empty($this->get['id'])) {
					ExceptionWepps::error404();
				}
				$orderId = addslashes($this->get['order']);
				$qty = addslashes($this->get['qty']);
				$price = addslashes($this->get['price']);
				$positionId = addslashes($this->get['id']);
				$obj = new DataWepps("TradeClientsHistory");
// 				$position = $obj->getMax($positionId)[0];
				$obj->set($positionId, array(
						'ItemQty'=>$qty,
						'Price'=>$price,
						'Summ'=>$price * $qty,
				));

				/*
				 * Обновить стоимость заказа
				 */
				$sql = "update TradeOrders o set o.Summ = (select sum(h.Summ) from TradeClientsHistory h where h.DisplayOff=0 and h.OrderId='{$orderId}') where o.Id='{$orderId}';";
				ConnectWepps::$instance->query($sql);
				
				$order = $this->getOrder($orderId);
				$this->tpl = "RequestViewOrder.tpl";
				
				break;
			case "removePosition":
				if (empty($this->get['order']) || empty($this->get['id'])) {
					ExceptionWepps::error404();
				}
				$orderId = addslashes($this->get['order']);
				$positionId = addslashes($this->get['id']);
				
				$obj = new DataWepps("TradeClientsHistory");
				$obj->remove($positionId);
				
				/*
				 * Обновить стоимость заказа
				 */
				$sql = "update TradeOrders o set o.Summ = (select sum(h.Summ) from TradeClientsHistory h where h.DisplayOff=0 and h.OrderId='{$orderId}') where o.Id='{$orderId}';";
				ConnectWepps::$instance->query($sql);
				
				$order = $this->getOrder($orderId);
				$this->tpl = "RequestViewOrder.tpl";
				break;
			case "searchPosition":
				$term = $this->get['term'];
				$sql = "select t.Name value,t.Id,t.Name,t.Articul,t.Price,pv.PValue OptionsTitle,pv.Id OptionsId 		
                		from Products t
                        left join s_PropertiesValues pv on pv.TableNameId = t.Id and pv.Property = 'size'
                		where t.DisplayOff=0 and (t.Name like '%{$term}%' or t.Articul like '%{$term}%') 
                        group by t.Id order by t.Name asc limit 10";
				$res = ConnectWepps::$instance->fetch($sql);
				$json = SpellWepps::getJsonCyr ( $res );
				header ( 'Content-type:application/json;charset=utf-8' );
				echo $json;
				ConnectWepps::$instance->close ();
				break;
			case "addPosition":
				if (empty($this->get['order']) || empty($this->get['title']) || empty($this->get['price']) || empty($this->get['qty'])) {
					ExceptionWepps::error404();
				}
				$orderId = addslashes($this->get['order']);
				$title = addslashes($this->get['title']);
				$price = addslashes($this->get['price']);
				$qty = addslashes($this->get['qty']);
				
				$option = (!empty($this->get['option'])) ? addslashes($this->get['option']) : '';
				$id =  (!empty($this->get['id'])) ? addslashes($this->get['id']) : '';
				
				/*
				 * Добавить позицию в заказ
				 * Добавить позицию в заказ
				 * Добавить позицию в заказ
				 * Добавить позицию в заказ
				 */
				exit();
				
				
				if ($option!='undefined' && $option!='') {
					$product = CartUtilsWepps::getProduct("Courses",[
							'id'=>$id,
							'option'=>$option,
							'qty'=> $qty
					]);
					$productArr = $product["Product"]["Courses_{$id}_{$option}"]['Data'];
				}

				$obj = new DataWepps("TradeClientsHistory");
				$obj->add(array(
						'Name'=>$title,
						'ItemQty'=>$qty,
						'Price'=>$price,
						'Summ'=>$price * $qty,
						'ClDate'=>date('Y-m-d H:i:s'),
						'TStatus'=>1,
						'OrderId'=>$orderId,
						'Options'=>(!empty($productArr['Options'])) ? json_encode($productArr['Options'],JSON_UNESCAPED_UNICODE) : '',
						'OptionsPrice'=>(!empty($productArr['OptionsPrice'])) ? json_encode($productArr['OptionsPrice'],JSON_UNESCAPED_UNICODE) : '',
				));
				
				/*
				 * Обновить стоимость заказа
				 */
				$sql = "update TradeOrders o set o.Summ = (select sum(h.Summ) from TradeClientsHistory h where h.DisplayOff=0 and h.OrderId='{$orderId}') where o.Id='{$orderId}';";
				ConnectWepps::$instance->query($sql);
				
				$order = $this->getOrder($orderId);
				$this->tpl = "RequestViewOrder.tpl";
				break;
			case "setOrderStatus":
				if (empty($this->get['order']) || empty($this->get['id'])) {
					ExceptionWepps::error404();
				}
				$orderId = addslashes($this->get['order']);
				$id = addslashes($this->get['id']);
				$obj = new DataWepps("TradeOrders");
				$obj->set($this->get['order'],array('TStatus'=>$this->get['id']));
				$order = $this->getOrder($orderId);
				$this->tpl = "RequestViewOrder.tpl";
				break;
			case "setOrderPayment":
				if (empty($this->get['order'])) {
					ExceptionWepps::error404();
				}
				$orderId = addslashes($this->get['order']);
				$paymentValue = addslashes($this->get['value']);
				
				$obj = new DataWepps("TradeOrders");
				$obj->set($this->get['order'],array('OBuySumm'=>$this->get['value'],'OBuyDate'=>date('Y-m-d H:i:s')));
				$order = $this->getOrder($orderId);
				$this->tpl = "RequestViewOrder.tpl";
				break;
			case "addOrderMessage":
				if (empty($this->get['order'])) {
					ExceptionWepps::error404();
				}
				$orderId = addslashes($this->get['order']);
				$message = addslashes($this->get['value']);
				$attach = addslashes($this->get['attach']);
				$payment = addslashes($this->get['payment']);
				
				$errors = array();
				$errors['message'] = ValidatorWepps::isNotEmpty($message, "Не заполнено");
				
				$outer = ValidatorWepps::setFormErrorsIndicate($errors, 'messages');
				echo $outer['Out'];
				if ($outer['Co']==0) {
    				$obj = new DataWepps("TradeMessages");
    				$obj->add(array(
    				    'Name'=>"Заказ",
    				    'MessFrom'=>$_SESSION['user']['Id'],
    				    'MessBody'=>$message,
    				    'MessType'=>1,
    				    'MessDate'=>date('Y-m-d H:i:s'),
    				    'OrderId'=>$orderId,
    				    'OrderInfo'=>($attach=='true')?1:0,
    				    'PaymentAdd'=>($payment=='true')?1:0,
    				));
    			}
				
    			$order = $this->getOrder($orderId);
    			
    			/*
    			 * MailWepps
    			 */
    			$orderPositionsText = $this->getOrderPositionsText($order);
    			
    			$to = $order['order']['Email'];
    			$subject = "Сообщение по заказу";
    			$text = "Уважаемый, {$order['order']['Name']}!<br/><br/>".nl2br($message);
    			
    			if ($attach=='true') {
    			    $text .= $orderPositionsText;
    			}
    			$url = ConnectWepps::$projectDev['protocol'].ConnectWepps::$projectDev['host'];
    			
    			if ($payment=='true') {
    			    $text .= "<br/><br/>
                                <div style=\"text-align:center\"><a href=\"{$url}/ext/MerchantSberbank/Request.php?action=form&id={$order['order']['Id']}\" style=\"display:inline-block;background: #087fc4;
                                color: white;
                                border-radius: 5px;
                                border: 1px solid #087fc4;
                                padding: 10px 20px;
                                font-weight: bold;
                                font-size: 14px;
                                letter-spacing: 0.1px;
                                box-shadow: inset 0 -15px 15px #076ca7;
                                text-decoration: none;
                                text-shadow: 0 1px 1px #054e79;\">Оплата онлайн</a>
                                
                                </div>
                                <br/></br/>
                                ";
    			    $text .= "<div style=\"color:#e5e5e5e;font-size:12px\">
                                Для оплаты (ввода реквизитов Вашей карты) Вы будете перенаправлены на платежный шлюз ПАО СБЕРБАНК. Соединение с платежным шлюзом и передача информации осуществляется в защищенном режиме с использованием протокола шифрования SSL. В случае если Ваш банк поддерживает технологию безопасного проведения интернет-платежей Verified By Visa, MasterCard SecureCode, MIR Accept, J-Secure для проведения платежа также может потребоваться ввод специального пароля.
                                <br/>
                                Настоящий сайт поддерживает 256-битное шифрование. Конфиденциальность сообщаемой персональной информации
                                обеспечивается ПАО СБЕРБАНК. Введенная информация не будет предоставлена третьим лицам за исключением случаев,
                                предусмотренных законодательством РФ. Проведение платежей по банковским картам осуществляется в строгом соответствии с требованиями платежных систем МИР, Visa Int., MasterCard Europe Sprl, JCB
                                <br/>
                                Данная транзакция осуществляется в пользу ООО \"ПСС ГРАЙТЕК\".
                                </div><br/></br/>";
    			}
    			
    			
    			$text .= "<br/>---<br/>С уважением, команда ПСС ГРАЙТЕК";

    			$mail = new MailWepps("html");
				$mail->output = false;
				$mail->mail($to, $subject, $text);
				$this->tpl = "RequestViewOrder.tpl";
				break;
			default:
				ExceptionWepps::error404();
				break;
		}
	}
	
	private function getOrder($id) {
		$obj = new DataWepps("TradeOrders");
		$order = $obj->getMax($id)[0];
		$sql = "select ts.Id,ts.Name
                        from TradeStatus ts
                        group by ts.Id
                        order by ts.Priority";
		$statuses = ConnectWepps::$instance->fetch($sql);
		$obj = new DataWepps("TradeClientsHistory");
		$positions = $obj->getMax("t.DisplayOff=0 and OrderId='{$id}'",2000,1,"t.Priority");
		
		$obj = new DataWepps("TradeMessages");
		$messages = $obj->getMax("t.DisplayOff=0 and OrderId='{$id}'",2000,1,"t.Priority");
		
		$this->assign('order', $order);
		$this->assign('positions', $positions);
		$this->assign('statuses',$statuses);
		$this->assign('statusesActive',$order['TStatus']);
		if (isset($messages[0]['Id'])) {
		    $this->assign('messages',$messages);
		}
		return array('order'=>$order,'positions'=>$positions,'statuses'=>$statuses,'messages'=>$messages);
	}
	
	private function getOrderPositionsText($order) {
	    $text = "";
	    $text.= "<h4>ТОВАРЫ ЗАКАЗА</h4>\n";
	    $text .= "<table border=\"0\" cellspacing=\"0\" cellpadding=\"10\">\n";
	    $text .= "
			<tr style=\"color:gray;font-size:12px;\">
				<td width=\"50%\" style=\"border-bottom: 1px solid #ddd;\">
					Наименование
				</td>
				<td width=\"20%\" style=\"border-bottom: 1px solid #ddd;\" align=\"center\">
					Цена
				</td>
				<td width=\"10%\" style=\"border-bottom: 1px solid #ddd;\" align=\"center\">
					Кол.
				</td>
				<td width=\"20%\" style=\"border-bottom: 1px solid #ddd;\" align=\"center\">
					Сумма
				</td>
			</tr>
			";
	    foreach ($order['positions'] as $key =>$value) {
	        
	        $options = json_decode($value['Options'],true);
	        $optionsText = "";
	        if (is_array($options)) {
	            $optionsText = "<br/>{$options['ProductCity']}<br/>{$options['ProductDateText']}";
	        }
	        $text .= "
			<tr>
				<td width=\"50%\" style=\"border-bottom: 1px solid #ddd;\">
					<strong>{$value['Name']}</strong>{$optionsText}
				</td>
				<td width=\"20%\" style=\"border-bottom: 1px solid #ddd;\" align=\"right\">
					".SpellWepps::money($value['Price'])." Р.
				</td>
				<td width=\"10%\" style=\"border-bottom: 1px solid #ddd;\" align=\"center\">
					{$value['ItemQty']}
				</td>
				<td width=\"20%\" style=\"border-bottom: 1px solid #ddd;\" align=\"right\">
					".SpellWepps::money($value['Summ'])." Р.
				</td>
			</tr>
			";
	    }
	    $text .= "
		<tr>
			<td colspan=\"3\" align=\"right\"><strong>ИТОГО: </strong></td>
			<td align=\"right\">".SpellWepps::money($order['order']['Summ'])." Р.</td>
		</tr>
		";
	    $text .= "</table>\n";
	    return $text;
	}
}
$request = new RequestOrdersWepps ($_REQUEST);
$smarty->assign('get',$request->get);
$smarty->display($request->tpl);
?>