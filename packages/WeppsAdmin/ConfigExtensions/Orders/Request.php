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
		if (@ConnectWepps::$projectData['user']['ShowAdmin']!=1) {
			ExceptionWepps::error(404);
		}
		switch ($action) {
			case "test":
				UtilsWepps::debug('test1',1);
				break;
			case 'viewOrder':
			    $this->tpl = "RequestViewOrder.tpl";
			    if (empty($this->get['id'])) {
			    	ExceptionWepps::error(400);
			    }
			    $order = $this->getOrder($this->get['id']);
			    break;
			    
			case 'setProducts':
				$this->tpl = "RequestViewOrder.tpl";
				if (empty($this->get['id'])) {
					ExceptionWepps::error(404);
				}
				$order = $this->getOrder($this->get['id']);
				$jdata = json_decode($order['order']['JPositions'],true);
				if (empty($jdata[$this->get['index']])) {
					ExceptionWepps::error(400);
				}
				
				$jdata[$this->get['index']]['quantity'] = (int) $this->get['quantity'];
				$jdata[$this->get['index']]['price'] = (float) $this->get['price'];
				$jdata[$this->get['index']]['sum'] = UtilsWepps::round($jdata[$this->get['index']]['price'] * $jdata[$this->get['index']]['quantity'],2);
				
				$json = json_encode($jdata,JSON_UNESCAPED_UNICODE);
				$sql = "update Orders set JPositions=? where Id=?";
				ConnectWepps::$instance->query($sql,[$json,$order['order']['Id']]);
				$order = $this->getOrder($this->get['id']);
				break;
			case 'addProducts':
				$this->tpl = "RequestViewOrder.tpl";
				if (empty($this->get['id']) || empty($this->get['products']) || empty($this->get['quantity']) || empty($this->get['price'])) {
					ExceptionWepps::error(404);
				}
				$order = $this->getOrder($this->get['id']);
				$jdata = json_decode($order['order']['JPositions'],true);
				
				/* $jdata[$this->get['index']]['quantity'] = (int) $this->get['quantity'];
				$jdata[$this->get['index']]['price'] = (float) $this->get['price'];
				$jdata[$this->get['index']]['sum'] = UtilsWepps::round($jdata[$this->get['index']]['price'] * $jdata[$this->get['index']]['quantity'],2); */
				
				$jdata[] = [
						'id' => (int) $this->get['products'],
						'quantity' => (int) $this->get['quantity'],
						'price' => (float) $this->get['price'],
						'sum' => UtilsWepps::round((float) $this->get['price'] * (int) $this->get['quantity'],2),
				];
				
				$json = json_encode($jdata,JSON_UNESCAPED_UNICODE);
				$sql = "update Orders set JPositions=? where Id=?";
				ConnectWepps::$instance->query($sql,[$json,$order['order']['Id']]);
				$order = $this->getOrder($this->get['id']);
				break;
			case "removeProducts":
				$this->tpl = "RequestViewOrder.tpl";
				if (empty($this->get['id'])) {
					ExceptionWepps::error(404);
				}
				$order = $this->getOrder($this->get['id']);
				$jdata = json_decode($order['order']['JPositions'],true);
				if (empty($jdata[$this->get['index']])) {
					ExceptionWepps::error(400);
				}
				unset($jdata[$this->get['index']]);
				$json = json_encode($jdata,JSON_UNESCAPED_UNICODE);
				$sql = "update Orders set JPositions=? where Id=?";
				ConnectWepps::$instance->query($sql,[$json,$order['order']['Id']]);
				$order = $this->getOrder($this->get['id']);
				break;
			case "searchProducts":
				$jdata = self::searchProducts(@$this->get['search'],$this->get['page']);
				$json = json_encode($jdata,JSON_UNESCAPED_UNICODE);
				header ( 'Content-type:application/json;charset=utf-8' );
				echo $json;
				ConnectWepps::$instance->close();
				break;
			case "setStatus":
				$this->tpl = "RequestViewOrder.tpl";
				if (empty($this->get['id']) || empty($this->get['status'])) {
					ExceptionWepps::error(404);
				}
				$sql = "update Orders set OStatus=? where Id=?";
				ConnectWepps::$instance->query($sql,[$this->get['status'],$this->get['id']]);
				$order = $this->getOrder($this->get['id']);
				break;
			case "addPayments":
				$this->tpl = "RequestViewOrder.tpl";
				if (empty($this->get['id']) || empty($this->get['payments'])) {
					ExceptionWepps::error(404);
				}
				$arr = ConnectWepps::$instance->prepare([
						'Name' => 'Оплата Сайт',
						'PriceTotal' => (float) $this->get['payments'],
						'IsPaid' => 1,
						'IsProcessed' => 1,
						'TableName' => 'Orders',
						'TableNameId' => $this->get['id'],
						'MerchantDate' => date('Y-m-d H:i:s'),
						'Priority' => 0
				]);
				$sql = "insert into Payments {$arr['insert']}";
				ConnectWepps::$instance->query($sql,$arr['row']);
				$order = $this->getOrder($this->get['id']);
				break;
			case "addMessages":
				$this->tpl = "RequestViewOrder.tpl";
				if (empty($this->get['id']) || empty($this->get['messages'])) {
					ExceptionWepps::error(404);
				}
				$jdata = [
						'date' => date('Y-m-d H:i:s'),
						'text' => $this->get['messages']
						
				];
				$arr = ConnectWepps::$instance->prepare([
						'Name' => 'Message',
						'OrderId' => $this->get['id'],
						'UserId' => ConnectWepps::$projectData['user']['Id'],
						'EType' => 'messages',
						'JData' => json_encode($jdata,JSON_UNESCAPED_UNICODE),
				]);
				$sql = "insert into OrdersEvents {$arr['insert']}";
				ConnectWepps::$instance->query($sql,$arr['row']);
				
				/*
				 * Уведомление клиенту
				 */
				
				
				
				$order = $this->getOrder($this->get['id']);
				break;
    			
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
				ExceptionWepps::error(404);
				break;
		}
	}
	
	private function getOrder($id) {
		$obj = new DataWepps("Orders");
		$obj->setJoin('left join Payments p on p.TableNameId=t.Id and p.TableName=\'Orders\' and p.IsPaid=1 and p.IsProcessed=1 and p.DisplayOff=0');
		$obj->setConcat('if(sum(p.PriceTotal)>0,sum(p.PriceTotal),0) PricePaid,if(sum(p.PriceTotal)>0,(t.OSum-sum(p.PriceTotal)),t.OSum) OSumPay,group_concat(p.Id,\':::\',p.Name,\':::\',p.PriceTotal,\':::\',p.MerchantDate,\':::\' separator \';;;\') Payments');
		$obj->setParams([$id]);
		$order = @$obj->getMax("t.Id=?")[0];
		#UtilsWepps::debug($obj->sql,2);
		if (empty($order)) {
			ExceptionWepps::error(404);
		}
		$sql = "select ts.Id,ts.Name from OrdersStatuses ts group by ts.Id order by ts.Priority";
		$statuses = ConnectWepps::$instance->fetch($sql);
		$this->assign('statuses',$statuses);
		$this->assign('statusesActive',$order['OStatus']);
		$products = json_decode($order['JPositions'],true);
		$sql = '';
		$sum = 0;
		foreach ($products as $value) {
			$sum += $value['sum'];
			$sql .= "\n(select '{$value['id']}' `id`,'{$value['quantity']}' `quantity`,'{$value['price']}' `price`,'{$value['sum']}' `sum`) union";
		}
		$sql = "(select * from (\n" . trim($sql," union\n").') y)';
		$ids = implode(',', array_column($products, 'id'));
		$sql = "select x.id,t.Name name,x.quantity,x.price,x.sum from Products t inner join $sql x on x.id=t.Id where t.Id in ($ids)";
		$products = ConnectWepps::$instance->fetch($sql);
		$this->assign('products', $products);
		$order['OSum'] = $sum;
		#$order['OSumPay'] = $sum - $order['PricePaid'];
		$sql = "update Orders set OSum=? where Id=?";
		ConnectWepps::$instance->query($sql,[$sum,$id]);
		$obj = new DataWepps("OrdersEvents");
		$obj->setParams([$id]);
		$obj->setJoin("join s_Users u on u.Id=t.UserId");
		$obj->setConcat("u.Name UsersName");
		$res = $obj->getMax("t.DisplayOff=0 and t.OrderId=?",2000,1,"t.Priority");
		if (!empty($res)) {
			$order['Messages'] = [];
			foreach ($res as $value) {
				$jdata = json_decode($value['JData'],true);
				$jdata['user'] = $value['UsersName'];
				array_push($order['Messages'], $jdata);
			}
		}
		$this->assign('order', $order);
		return ['order'=>$order,'products'=>$products,'statuses'=>$statuses];
	}
	private function searchProducts($text='',$page=1) {
		$term = $text;
		$limit = 10;
		$offset = ($page-1)*$limit;
		$sql = "select t.Id `id`,t.Name `text`,t.Price `price` from Products t
                		where t.DisplayOff=0 and (t.Name like '%{$term}%' or t.Articul like '%{$term}%')
                        group by t.Id order by t.Name asc limit $offset,$limit";
		$res = ConnectWepps::$instance->fetch($sql);
		$pagination = false;
		if (!empty($res)) {
			$pagination = true;
		}
		$output = [
				'results'=>$res,
				'pagination' => [
						'more'=> $pagination
				]
		];
		return $output;
	}

	/**
	 * @deprecated
	 */
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
		foreach ($order['positions'] as $value) {
			
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
/** @var \Smarty $smarty */
$smarty->assign('get',$request->get);
$smarty->display($request->tpl);