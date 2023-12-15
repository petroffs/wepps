<?php
namespace WeppsExtensions\Cart;

use WeppsCore\Core\DataWepps;
use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Spell\SpellWepps;

class CartUtilsWepps {
	public static function cartSummary() {
		if (isset ( $_SESSION ['cart'] )) {
			$price = 0;
			$qty = 0;
			foreach ( $_SESSION ['cart'] as $key => $value ) {
				$qty = $qty + $value['Qty'] * $value['Data']['OptionQty'];
				$price = $price + $value ['PriceAmount'];
			}
			$addCart = array (
					'deliveryPrice',
					'deliveryChecked',
					'paymentPrice',
					'paymentChecked',
					'cityChecked',
					'city',
					'orderId' 
			);
			
			$addCartValues = array();
			foreach ($addCart as $value) {
				$addCartValues[$value] = (isset($_SESSION['cartAdd'][$value])) ? doubleval($_SESSION['cartAdd'][$value]) : 0;
			}
			if (isset($_SESSION['cartAdd']['city'])) {
				$addCartValues['city'] = (string) $_SESSION['cartAdd']['city'];
			}
			$priceTotal = $price + $addCartValues['deliveryPrice'] + $addCartValues['paymentPrice'];
			//UtilsWepps::debug($_SESSION);
			return array (
					'priceAmount' => $price,
					'priceTotal' => $priceTotal,
					'qty' => $qty,
					'cart' => $_SESSION ['cart'],
					'cartAdd' => $addCartValues 
			);
		}
		return array('priceAmount'=>0,'qty'=>0,'cart'=>array());
	}
	public static function addOrder($settings = array(),$userId=null) {
		if (!isset($_SESSION['user']) && $userId==null) return array('error'=>1);
		$cartSummary = self::cartSummary();
		$userId = ($userId==null) ? $_SESSION['user']['Id'] : $userId;
		$obj = new DataWepps("s_Users");
		$user = $obj->get($userId)[0];
		$obj->set($user['Id'], array(
			'CityRegion'=>$cartSummary['cartAdd']['cityChecked'],	
			'City'=>$cartSummary['cartAdd']['city'],	
			'Address'=>$settings['address'],
			'AddressIndex'=>$settings['addressIndex'],	
		));
		
				//exit();
		$row = array();
		$row['Name'] = $user['Name'];
		$row['UserId'] = $user['Id'];
		$row['Email'] = $settings['email'];
		$row['Phone'] = $settings['phone'];
		$row['ODate'] = date('Y-m-d H:i:s');
		$row['Summ'] = $cartSummary['priceTotal'];
		$row['CustomerIP'] = (isset($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : 'localhost';
		$row['TStatus'] = 1;
		$row['ODelivery'] = $cartSummary['cartAdd']['deliveryChecked'];
		$row['OPayment'] = $cartSummary['cartAdd']['paymentChecked'];
		$row['City'] = $cartSummary['cartAdd']['city'];
		$row['Address'] = $settings['address'];
		$row['AddressIndex'] = $settings['addressIndex'];
		$row['OComment'] = $settings['comment'];
		$orderId = ConnectWepps::$instance->insert("TradeOrders", $row);
		$order = 	self::getOrder($orderId);
					self::addOrderPositions($orderId);
		$text = "<h4>ЗАКАЗ №".SpellWepps::getNumberOrder($orderId)." : ".$order['Name']."</h4>\n\n";
		if (strlen($row['OComment'])!=0) $text.= "<h4>ПРИМЕЧАНИЯ К ЗАКАЗУ</h4>\n".$order['OComment']."\n\n";
		$text.= "<h4>ТОВАРЫ ЗАКАЗА</h4>\n";
		
		$text .= "<table cellpadding=\"5\">";
		foreach ($cartSummary['cart'] as $key =>$value) {
		
			$text .= "
			<tr>
			<td>
			<a href=\"http://{$_SERVER['HTTP_HOST']}{$value['Data']['Url']}\"><img src=\"http://{$_SERVER['HTTP_HOST']}{$value['Data']['Image_FileUrl']}\" width=\"50\"/></a>
			</td>
			<td>
			<strong>{$value['Data']['ProductType_NameOsn']} {$value['Data']['Name']}</strong> ({$value['Data']['OptionColor']})<br/>
			Артикул внутренний: {$value['Data']['ArticulInner']}<br/>
		
			Размер: {$value['Data']['OptionSize']}<br/>
		
			{$value['Data']['PriceAmount']} x ".$value['Data']['OptionQty'] * $value['Qty']." = {$value['PriceAmount']} Р.
			</td>
			</tr>
			";
		}
		$text .= "</table>";
		
		$str = ($cartSummary['cartAdd']['deliveryPrice']!=0) ? " (".SpellWepps::money($cartSummary['cartAdd']['deliveryPrice'])." Р.)" : "";
		$text .= "<p><b>ДОСТАВКА</b>: ".$order['ODelivery_Name'].$str."</p>\n";
		$str = ($cartSummary['cartAdd']['paymentPrice']!=0) ? " (".SpellWepps::money($cartSummary['cartAdd']['paymentPrice'])." Р.)" : "";
		$text .= "<p><b>ОПЛАТА:</b> ".$order['OPayment_Name'].$str."</p>\n";
		$text .= "<p><b>ИТОГО К ОПЛАТЕ:</b> ".SpellWepps::money($cartSummary['priceTotal'])." Р.</p>\n\n";
		$text.= "<p><b>ИНФОРМАЦИЯ О КЛИЕНТЕ</b><br/>\n";
		$text.= $user['Name']."<br/>\n";
		$text.= "Адрес доставки: ".$order['AddressIndex'].", ".$order['City']."<br/>\n";
		$text.= $order['Address']."<br/>\n";
		$text.= "Контактный телефон: ".$order['Phone']."<br/>\n";
		$text.= $order['Email'];
		$text.= "</p>";
		
		
// 		UtilsWepps::debug($text,0);
// 		UtilsWepps::debug($cartSummary,1);
		
		//$text = nl2br($text);
		
		ConnectWepps::$instance->query("update TradeOrders set OText='$text' where Id='$orderId'");
		$from = ($order['Email']) ? "=?utf-8?B?" .base64_encode($order['Name']). "?=" . " <".$order['Email'].">" : ConnectWepps::$projectInfo['name']." <".ConnectWepps::$projectInfo['email'].">";
		
		//UtilsWepps::mail(ConnectWepps::$projectInfo['email'], "Новый заказ", $text);
		//exit();
		return $orderId;
	}
	public static function getOrder($id) {
		$obj = new DataWepps("TradeOrders");
		$obj->setJoin('left join GeoCities as c on c.Id = t.City');
		$obj->setConcat('c.Name as CityName');
		$order = $obj->getMax($id)[0];
		return $order;
	}
	public static function addOrderPositions($orderId) {
		$dateCurr = date("Y-m-d H:i:s");
		$cartSummary = self::cartSummary();
		$date = date('Y-m-d H:i:s');
		$obj = new DataWepps("TradeClientsHistory");
		foreach ($cartSummary['cart'] as $key =>$value) {
			$row = array();
			$row['Name'] = "{$value['Data']['ProductType_NameOsn']} {$value['Data']['Name']}";
			$row['ItemQty'] = $value['Data']['OptionQty'] * $value['Qty'];
			$row['Price'] = $value['Data']['PriceAmount'];
			$row['Summ'] = round($row['Price']*$row['ItemQty']);
			$row['ClDate'] = $dateCurr;
			$row['ClientId'] = "";
			$row['ProductId'] = $value['Data']['Id'];
			$row['TStatus'] = 1;
			$row['OrderId'] = $orderId;
			$row['ProductIdLink'] = "http://".$_SERVER['HTTP_HOST'] . $value['Data']['Url']."";
			$row['ArticulInner'] = $value['Data']['ArticulInner'];
			$row['TradeProductType'] = $value['Data']['ProductType_NameOsn'];
			$row['TradeArticul'] = $value['Data']['Name'];
			$row['TradeName'] = $value['Data']['Articul'];
			$row['TradeColor'] = $value['Data']['OptionColor'];
			$str = str_replace(",","-".$value['Qty'].", ",$value['Data']['OptionSize'])."-".$value['Qty'];
			$row['TradeSizes'] = $str;
			$obj->add($row);
		}
		return array('success'=>1);
	}
}

?>