<?php
namespace WeppsExtensions\Cart;

use WeppsCore\Core\DataWepps;
use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Spell\SpellWepps;
use WeppsCore\Utils\UtilsWepps;

class CartUtilsWepps {
	private $user = [];
	private $cart = [];
	private $favorites = [];
	public function __construct() {
		if (empty(ConnectWepps::$projectData['user'])) {
			$this->user['JCart'] = $this->_getCartFromCookies();
		} else {
			$cart = $this->_getCartFromCookies(false);
			$jdata = json_decode($cart,true);
			if (!empty($jdata['items'])) {
				$jdata2 = json_decode(ConnectWepps::$projectData['user']['JCart'],true);
				$jdata2['items'] = $jdata2['items']+$jdata['items'];
				UtilsWepps::cookies('wepps_cart');
				UtilsWepps::cookies('wepps_cart_guid');
				$this->setCart();
			}
			$this->user = $this->getUser(ConnectWepps::$projectData['user']);
			$this->favorites = json_decode($this->user['JFav'],true);
		}
		$this->cart = json_decode($this->user['JCart']??'',true)??[];
	}
	
	public function getUser(array $user) : array {
		return $this->user = $user;
	}
	
	public function setFavorites(int $id) {
		$this->favorites['items']??[];
		$keys = array_column($this->favorites['items'],'id');
		if (!in_array($id,$keys)) {
			array_push($this->favorites['items'], [
					'id' => $id,
			]);
		} else {
			$index = array_search($id,$keys);
			unset($this->favorites['items'][$index]);
			$this->favorites['items'] = array_merge($this->favorites['items'],[]);
		}
		$this->favorites['date'] = date('Y-m-d H:i:s');
		$json = json_encode($this->favorites);
		ConnectWepps::$instance->query("update s_Users set JFav=? where Id=?",[$json,@$this->user['Id']]);
		return $this->favorites;
	}
	
	public function getFavorites() : array {
		return $this->favorites??[];
	}
	public function getCart(string $cart='') : array {
		return $this->cart;
	}

	public function setCart() {
		$this->cart['date'] = date('Y-m-d H:i:s');
		$json = json_encode($this->cart);
		if (empty(ConnectWepps::$projectData['user'])) {
			UtilsWepps::cookies('wepps_cart',$json);
			UtilsWepps::cookies('wepps_cart_guid',UtilsWepps::guid($json.ConnectWepps::$projectServices['jwt']['secret']));
			return $this->cart;
		}
		ConnectWepps::$instance->query("update s_Users set JCart=? where Id=?",[$json,@$this->user['Id']]);
		return $this->cart;
	}
	public function add(int $id,int $quantity=1) {
		if (empty($this->cart['items'])) {
			$this->cart['items'] = [];
		}
		$keys = array_column($this->cart['items'],'id');
		if (!in_array($id,$keys)) {
			array_push($this->cart['items'], [
					'id' => $id,
					'ac' => 1,
					'qu' => $quantity
			]);
		} else {
			$index = array_search($id,$keys);
			if (intval($index)>=0) {
				$this->cart['items'][$index]['qu'] += $quantity;
			}
		}
		return $this->setCart();
	}
	public function edit(int $id,int $quantity=1) {
		$keys = array_column($this->cart['items'],'id');
		if (!in_array($id,$keys)) {
			array_push($this->cart['items'], [
					'id' => $id,
					'ac' => 1,
					'qu' => $quantity
			]);
		} else {
			$index = array_search($id,$keys);
			if (intval($index)>=0) {
				$this->cart['items'][$index]['qu'] = $quantity;
				$this->cart['items'][$index]['ac'] = 1;
			}
		}
		return $this->setCart();
	}
	public function check(string $ids = '') {
		$ex = explode(',', $ids);
		foreach ($this->cart['items'] as $key => $value) {
			$this->cart['items'][$key]['ac'] = (in_array($value['id'],$ex))?1:0;
		}
		return $this->setCart();
	}
	public function remove(int $id) {
		$keys = array_column($this->cart['items'],'id');
		$index = array_search($id,$keys);
		if (intval($index)>=0) {
			unset($this->cart['items'][$index]);
			$this->cart['items'] = array_merge($this->cart['items'],[]);
			#UtilsWepps::debug($this->cart['items'],2);
		}
		return $this->setCart();
	}
	public function getCartSummary() {
		$output = [
				'items' => [],
				'quantity' => 0,
				'sum' => 0,
				'date' => "",
				'delivery'=>[],
				'payments'=>[],
				'favorites'=>[]
		];
		if (empty($this->cart['items'])) {
			return $output;
		}
		$sql = "";
		foreach ($this->cart['items'] as $value) {
			$output['quantity'] += $value['qu'];
			$sql .= "\n(select '{$value['id']}' `id`,'{$value['qu']}' `quantity`,'{$value['ac']}' `active`) union";
		}
		$sql = "(select * from (\n" . trim($sql," union\n").') y)';
		$ids = implode(',', array_column($this->cart['items'],'id'));
		$sql = "select x.id,p.Name name,
				x.quantity,x.active,p.Price price, (x.quantity * p.Price) `sum`,
				concat(n.Url,if(p.Alias!='',p.Alias,p.Id),'.html') url,
				f.FileUrl image
				from Products p
				join $sql x on x.id=p.Id
				join s_Navigator n on n.Id=p.NavigatorId
				left join s_Files f on f.TableNameId = p.Id and f.TableName = 'Products' and f.TableNameField = 'Images'
				where p.Id in ($ids)";
		$output['items'] = ConnectWepps::$instance->fetch($sql);
		$output['sum'] = array_sum(array_column($output['items'],'sum'));
		$output['date'] = $this->cart['date'];
		$output['favorites'] = $this->getFavorites();
		return $output;
	}
	
	public function addOrder($settings = array(),$userId=null) {
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
	public function getOrder($id) {
		$obj = new DataWepps("TradeOrders");
		$obj->setJoin('left join GeoCities as c on c.Id = t.City');
		$obj->setConcat('c.Name as CityName');
		$order = $obj->getMax($id)[0];
		return $order;
	}
	public function addOrderPositions($orderId) {
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
	private function _getCartHash(string $jcart='') {
		return UtilsWepps::guid($jcart.ConnectWepps::$projectServices['jwt']['secret']);
	}
	private function _getCartFromCookies(bool $shouldCreate=true) {
		$cart = '';
		if (isset($_COOKIE['wepps_cart']) && @$_COOKIE['wepps_cart_guid']==self::_getCartHash($_COOKIE['wepps_cart'])) {
			$cart = $_COOKIE['wepps_cart'];
		} elseif ($shouldCreate==true) {
			$cart = '{"items":null}';
			UtilsWepps::cookies('wepps_cart',$this->user['JCart']??'');
			UtilsWepps::cookies('wepps_cart_guid',self::_getCartHash($this->user['JCart']??''));
		}
		return $cart;
	}
}

?>