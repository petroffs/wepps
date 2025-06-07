<?php
namespace WeppsAdmin\ConfigExtensions\Orders;

use WeppsCore\Utils\RequestWepps;
use WeppsCore\Utils\UtilsWepps;
use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Validator\ValidatorWepps;
use WeppsCore\Core\DataWepps;
use WeppsCore\TextTransforms\TextTransformsWepps;
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
				UtilsWepps::debug($sql);
				UtilsWepps::debug([$json,$order['order']['Id']]);
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
				#UtilsWepps::debug($this->get,31);
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
				#$order = $this->getOrder($this->get['id']);
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
			$sql .= "\n(select '{$value['id']}' `id`,'{$value['name']}' `name`,'{$value['quantity']}' `quantity`,'{$value['price']}' `price`,'{$value['sum']}' `sum`) union";
		}
		$sql = "(select * from (\n" . trim($sql," union\n").') y)';
		$ids = implode(',', array_column($products, 'id'));
		$sql = "select x.id,x.name name,x.quantity,x.price,x.sum from $sql x left join Products t on x.id=t.Id where x.id in ($ids)";
		#UtilsWepps::debug($sql);
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
		if (strlen($text) < 0) {
			$res = [];
		} else {
			$term = $text;
			$limit = 10;
			$offset = ($page - 1) * $limit;
			$sql = "select t.Id `id`,t.Name `text`,t.Price `price` from Products t
				where t.DisplayOff=0 and (t.Name like '%{$term}%' or t.Article like '%{$term}%')
				group by t.Id order by t.Name asc limit $offset,$limit";
			$res = ConnectWepps::$instance->fetch($sql);
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
					".TextTransformsWepps::money($value['Price'])." Р.
				</td>
				<td width=\"10%\" style=\"border-bottom: 1px solid #ddd;\" align=\"center\">
					{$value['ItemQty']}
				</td>
				<td width=\"20%\" style=\"border-bottom: 1px solid #ddd;\" align=\"right\">
					".TextTransformsWepps::money($value['Summ'])." Р.
				</td>
			</tr>
			";
		}
		$text .= "
		<tr>
			<td colspan=\"3\" align=\"right\"><strong>ИТОГО: </strong></td>
			<td align=\"right\">".TextTransformsWepps::money($order['order']['Summ'])." Р.</td>
		</tr>
		";
		$text .= "</table>\n";
		return $text;
	}
}
$request = new RequestOrdersWepps ($_REQUEST);
$smarty->assign('get',$request->get);
$smarty->display($request->tpl);