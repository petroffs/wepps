<?php
require_once '../../../../configloader.php';

use WeppsCore\Request;
use WeppsCore\Utils;
use WeppsCore\Exception;
use WeppsCore\Connect;
use WeppsCore\Data;
use WeppsCore\TextTransforms;
use WeppsExtensions\Cart\CartUtils;

class RequestOrders extends Request {
	public function request($action="") {
		$this->tpl = '';
		if (@Connect::$projectData['user']['ShowAdmin']!=1) {
			Exception::error(404);
		}
		Connect::$instance->cached('no');
		switch ($action) {
			case "test":
				Utils::debug('test1',1);
				break;
			case 'viewOrder':
			    $this->tpl = "RequestViewOrder.tpl";
			    if (empty($this->get['id'])) {
			    	Exception::error(400);
			    }
			    $order = $this->getOrder($this->get['id']);
			    break;
			    
			case 'setProducts':
				$this->tpl = "RequestViewOrder.tpl";
				if (empty($this->get['id'])) {
					Exception::error(404);
				}
				$order = $this->getOrder($this->get['id']);
				$jdata = json_decode($order['order']['JPositions'],true);
				if (empty($jdata[$this->get['index']])) {
					Exception::error(400);
				}
				
				$jdata[$this->get['index']]['quantity'] = (int) $this->get['quantity'];
				$jdata[$this->get['index']]['price'] = (float) $this->get['price'];
				$jdata[$this->get['index']]['sum'] = Utils::round($jdata[$this->get['index']]['price'] * $jdata[$this->get['index']]['quantity'],2);
				
				$json = json_encode($jdata,JSON_UNESCAPED_UNICODE);
				$sql = "update Orders set JPositions=? where Id=?";
				Connect::$instance->query($sql,[$json,$order['order']['Id']]);
				$order = $this->getOrder($this->get['id']);
				break;
			case 'addProducts':
				$this->tpl = "RequestViewOrder.tpl";
				if (empty($this->get['id']) || empty($this->get['products']) || empty($this->get['name']) || empty($this->get['quantity']) || empty($this->get['price'])) {
					Exception::error(404);
				}
				$order = $this->getOrder($this->get['id']);
				$jdata = json_decode($order['order']['JPositions'],true);
				$ex = explode('-',$this->get['products']);
				$jdata[] = [
						'id' => (int) $ex[0],
						'idv' => (int) $ex[1],
						'name' => (string) $this->get['name'],
						'quantity' => (int) $this->get['quantity'],
						'price' => (float) $this->get['price'],
						'sum' => Utils::round((float) $this->get['price'] * (int) $this->get['quantity'],2),
				];
				$json = json_encode($jdata,JSON_UNESCAPED_UNICODE);
				$sql = "update Orders set JPositions=? where Id=?";
				Connect::$instance->query($sql,[$json,$order['order']['Id']]);
				$order = $this->getOrder($this->get['id']);
				break;
			case "removeProducts":
				$this->tpl = "RequestViewOrder.tpl";
				if (empty($this->get['id'])) {
					Exception::error(404);
				}
				$order = $this->getOrder($this->get['id']);
				$jdata = json_decode($order['order']['JPositions'],true);
				if (empty($jdata[$this->get['index']])) {
					Exception::error(400);
				}
				unset($jdata[$this->get['index']]);
				$jdata = array_merge([],$jdata);
				$json = json_encode($jdata,JSON_UNESCAPED_UNICODE);
				$sql = "update Orders set JPositions=? where Id=?";
				Connect::$instance->query($sql,[$json,$order['order']['Id']]);
				$order = $this->getOrder($this->get['id']);
				break;
			case "searchProducts":
				#Utils::debug($this->get,31);
				$jdata = self::searchProducts(@$this->get['search'],$this->get['page']);
				$json = json_encode($jdata,JSON_UNESCAPED_UNICODE);
				header ( 'Content-type:application/json;charset=utf-8' );
				echo $json;
				Connect::$instance->close();
				break;
			case "setStatus":
				$this->tpl = "RequestViewOrder.tpl";
				if (empty($this->get['id']) || empty($this->get['status'])) {
					Exception::error(404);
				}
				$sql = "update Orders set OStatus=? where Id=?";
				Connect::$instance->query($sql,[$this->get['status'],$this->get['id']]);
				$order = $this->getOrder($this->get['id']);
				break;
			case "addPayments":
				$this->tpl = "RequestViewOrder.tpl";
				if (empty($this->get['id']) || empty($this->get['payments'])) {
					Exception::error(404);
				}
				$arr = Connect::$instance->prepare([
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
				Connect::$instance->query($sql,$arr['row']);
				$order = $this->getOrder($this->get['id']);
				break;
			case "addMessages":
				$this->tpl = "RequestViewOrder.tpl";
				if (empty($this->get['id']) || empty($this->get['messages'])) {
					Exception::error(404);
				}
				/* $jdata = [
						'date' => date('Y-m-d H:i:s'),
						'text' => $this->get['messages']
				]; */
				$arr = Connect::$instance->prepare([
						'Name' => 'Msg',
						'OrderId' => $this->get['id'],
						'UserId' => Connect::$projectData['user']['Id'],
						'EType' => 'msg',
						'EDate' => date('Y-m-d H:i:s'),
						'EText' => trim(strip_tags($this->get['messages']))
						#'JData' => json_encode($jdata,JSON_UNESCAPED_UNICODE),
				]);
				$sql = "insert into OrdersEvents {$arr['insert']}";
				Connect::$instance->query($sql,$arr['row']);
				$order = $this->getOrder($this->get['id']);
				break;
			case 'setTariff':
				$this->tpl = "RequestViewOrder.tpl";
				if (empty($this->get['tariff']) || empty($this->get['value'])) {
					http_response_code(404);
					exit();
				}
				switch(@$this->get['tariff']) {
					case 'delivery-tariff':
						$field = 'ODeliveryTariff';
						break;
					case 'delivery-discount':
						$field = 'ODeliveryDiscount';
						break;
					case 'payment-tariff':
						$field = 'OPaymentTariff';
						break;
					case 'payment-discount':
						$field = 'OPaymentDiscount';
						break;
					default:
						http_response_code(404);
						exit();
				}
				$sql = "update Orders set $field = ? where Id = ?";
				Connect::$instance->query($sql,[(float) $this->get['value'],(int) $this->get['id']]);
				$order = $this->getOrder($this->get['id']);
				break;
			default:
				Exception::error(404);
				break;
		}
	}
	private function getOrder($id) {
		$cartUtils = new CartUtils();
		$order = $cartUtils->getOrder($id);
		if (empty($order)) {
			Exception::error(404);
		}
		$sql = "select ts.Id,ts.Name from OrdersStatuses ts group by ts.Id order by ts.Priority";
		$statuses = Connect::$instance->fetch($sql);
		$this->assign('statuses',$statuses);
		$this->assign('statusesActive',$order['OStatus']);
		$sql = 'update Orders set OSum = ? where Id = ?';
		Connect::$instance->query($sql,[$order['OSum'],$id]);
		$this->assign('order', $order);
		return ['order'=>$order,'statuses'=>$statuses];
	}
	private function searchProducts($text='',$page=1) {
		if (strlen($text) < 0) {
			$res = [];
		} else {
			$term = $text;
			$limit = 10;
			$offset = ($page - 1) * $limit;
			$sql = "select concat(p.Id,'-',pv.Id) `id`,p.Id `pid`,pv.Id `idv`,
				if(pv.Field1!='',concat(p.Name,' / ',pv.Field1,if(pv.Field2!='',concat(', ',pv.Field2),'')),p.Name) `text`,
				if(pv.Field1!='',concat(p.Name,' / ',pv.Field1,if(pv.Field2!='',concat(', ',pv.Field2),'')),p.Name) `name`,
				p.Price `price` from Products p
				join ProductsVariations pv on pv.ProductsId=p.Id and pv.DisplayOff=0 and pv.Field4>0
				where p.DisplayOff=0 and (p.Name like ? or p.Article like ? or concat(pv.Field1,', ',pv.Field2) like ? or pv.Field3 like ?)
				group by p.Id order by p.Name asc limit $offset,$limit";
			$res = Connect::$instance->fetch($sql,["%{$term}%","%{$term}%","%{$term}%","%{$term}%"]);
		}
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
					".TextTransforms::money($value['Price'])." Р.
				</td>
				<td width=\"10%\" style=\"border-bottom: 1px solid #ddd;\" align=\"center\">
					{$value['ItemQty']}
				</td>
				<td width=\"20%\" style=\"border-bottom: 1px solid #ddd;\" align=\"right\">
					".TextTransforms::money($value['Summ'])." Р.
				</td>
			</tr>
			";
		}
		$text .= "
		<tr>
			<td colspan=\"3\" align=\"right\"><strong>ИТОГО: </strong></td>
			<td align=\"right\">".TextTransforms::money($order['order']['Summ'])." Р.</td>
		</tr>
		";
		$text .= "</table>\n";
		return $text;
	}
}
$request = new RequestOrders ($_REQUEST);
$smarty->assign('get',$request->get);
$smarty->display($request->tpl);