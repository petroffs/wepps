<?php
namespace WeppsExtensions\Cart;

use WeppsCore\Core\DataWepps;
use WeppsCore\Connect\ConnectWepps;
use WeppsCore\TextTransforms\TextTransformsWepps;
use WeppsCore\Utils\MemcachedWepps;
use WeppsCore\Utils\TemplateHeadersWepps;
use WeppsCore\Utils\UtilsWepps;
use WeppsCore\Validator\ValidatorWepps;
use WeppsExtensions\Cart\Delivery\DeliveryUtilsWepps;
use WeppsExtensions\Cart\Payments\PaymentsUtilsWepps;


class CartUtilsWepps
{
	private $user = [];
	private $cart = [];
	private $favorites = [];
	private $summary = [];
	private $headers;
	private $memcached;
	public function __construct()
	{
		if (empty(ConnectWepps::$projectData['user'])) {
			$this->user['JCart'] = $this->_getCartFromCookies();
		} else {
			$cart = $this->_getCartFromCookies(false);
			$jdata = json_decode($cart, true);
			if (!empty($jdata['items'])) {
				$jdata2 = json_decode(ConnectWepps::$projectData['user']['JCart'], true);
				$jdata2['items'] += $jdata['items'];
				UtilsWepps::cookies('wepps_cart');
				UtilsWepps::cookies('wepps_cart_guid');
				$this->setCart();
			}
			$this->user = $this->getUser(ConnectWepps::$projectData['user']);
			$this->favorites = json_decode($this->user['JFav'], true);
		}
		$this->cart = json_decode($this->user['JCart'] ?? '', true) ?? [];
		$this->memcached = new MemcachedWepps();
	}
	public function getUser(array $user): array
	{
		return $this->user = $user;
	}
	public function setFavorites(int $id)
	{
		$this->favorites['items'] ?? [];
		$keys = array_column($this->favorites['items'], 'id');
		if (!in_array($id, $keys)) {
			array_push($this->favorites['items'], [
				'id' => $id,
			]);
		} else {
			$index = array_search($id, $keys);
			unset($this->favorites['items'][$index]);
			$this->favorites['items'] = array_merge($this->favorites['items'], []);
		}
		$this->favorites['date'] = date('Y-m-d H:i:s');
		$json = json_encode($this->favorites);
		ConnectWepps::$instance->query("update s_Users set JFav=? where Id=?", [$json, @$this->user['Id']]);
		return $this->favorites;
	}
	public function getFavorites(): array
	{
		return $this->favorites ?? [];
	}
	public function getCart(string $cart = ''): array
	{
		return $this->cart;
	}
	public function getCartMetrics() {
		return [
			'items' => array_sum(array_column($this->cart['items']??[], 'qu'))
		];
	}
	public function setCart(): void
	{
		$this->cart['date'] = date('Y-m-d H:i:s');
		$json = json_encode($this->cart, JSON_UNESCAPED_UNICODE);
		if (empty(ConnectWepps::$projectData['user'])) {
			UtilsWepps::cookies('wepps_cart', $json);
			UtilsWepps::cookies('wepps_cart_guid', UtilsWepps::guid($json . ConnectWepps::$projectServices['jwt']['secret']));
			return;
		}
		$this->setCartSummary();
		ConnectWepps::$instance->query("update s_Users set JCart=? where Id=?", [$json, @$this->user['Id']]);
		return;
	}
	public function setCartCitiesId(string $citiesId): void
	{
		$this->cart['citiesId'] = $citiesId;
		unset($this->cart['deliveryId']);
		unset($this->cart['paymentsId']);
		$this->setCart();
	}
	public function setCartDelivery(string $deliveryId): void
	{
		$this->cart['deliveryId'] = $deliveryId;
		unset($this->cart['paymentsId']);
		$deliveryUtils = new DeliveryUtilsWepps();
		$this->setCart();
		$this->setCartSummary();
		$tariffs = $deliveryUtils->getTariffsByCitiesId($this->cart['citiesId'], $this, $deliveryId);
		if (!empty($tariffs[0])) {
			$this->cart['deliveryTariff'] = $tariffs[0]['Addons']['tariff'];
			$this->cart['deliveryDiscount'] = $tariffs[0]['Addons']['discount'];
			$this->cart['deliveryExtension'] = $tariffs[0]['Addons']['extension'];
			$this->cart['deliverySettings'] = json_decode($tariffs[0]['JSettings'],true);
		}
		$this->setCart();
	}
	public function setCartDeliveryOperations(array $operations = [])
	{
		$this->cart['deliveryOperations'] = $operations;
		$this->setCart();
	}
	public function setCartPayments(string $paymentsId): void
	{
		$this->cart['paymentsId'] = $paymentsId;
		$paymentsUtils = new PaymentsUtilsWepps();
		$this->setCart();
		$this->setCartSummary();
		$tariffs = $paymentsUtils->getByDeliveryId($this->cart['deliveryId'], $this, $paymentsId);
		if (!empty($tariffs[0])) {
			$this->cart['paymentsTariff'] = $tariffs[0]['Addons']['tariff'];
			$this->cart['paymentsDiscount'] = $tariffs[0]['Addons']['discount'];
			$this->cart['paymentsExtension'] = $tariffs[0]['Addons']['extension'];
		}
		$this->setCart();
	}
	public function add(int $id, int $quantity = 1): void
	{
		if (empty($this->cart['items'])) {
			$this->cart['items'] = [];
		}
		$keys = array_column($this->cart['items'], 'id');
		if (!in_array($id, $keys)) {
			array_push($this->cart['items'], [
				'id' => $id,
				'ac' => 1,
				'qu' => $quantity
			]);
		} else {
			$index = array_search($id, $keys);
			if (intval($index) >= 0) {
				$this->cart['items'][$index]['qu'] += $quantity;
			}
		}
		$this->setCart();
		return;
	}
	public function edit(int $id, int $quantity = 1): void
	{
		$keys = array_column($this->cart['items'], 'id');
		if (!in_array($id, $keys)) {
			array_push($this->cart['items'], [
				'id' => $id,
				'ac' => 1,
				'qu' => $quantity
			]);
		} else {
			$index = array_search($id, $keys);
			if (intval($index) >= 0) {
				$this->cart['items'][$index]['qu'] = $quantity;
				$this->cart['items'][$index]['ac'] = 1;
			}
		}
		$this->setCart();
		return;
	}
	public function check(string $ids = '')
	{
		$ex = explode(',', $ids);
		foreach ($this->cart['items'] as $key => $value) {
			$this->cart['items'][$key]['ac'] = (in_array($value['id'], $ex)) ? 1 : 0;
		}
		return $this->setCart();
	}
	public function remove(int $id)
	{
		$keys = array_column($this->cart['items'], 'id');
		$index = array_search($id, $keys);
		if (intval($index) >= 0) {
			unset($this->cart['items'][$index]);
			$this->cart['items'] = array_merge($this->cart['items'], []);
		}
		return $this->setCart();
	}
	public function setCartSummary(): bool
	{
		$this->summary = [
			'items' => [],
			'quantity' => 0,
			'quantityActive' => 0,
			'sum' => 0,
			'sumSaving' => 0,
			'sumBefore' => 0,
			'sumActive' => 0,
			'sumTotal' => 0,
			'date' => "",
			'delivery' => [],
			'payments' => [],
			'favorites' => []
		];
		if (empty($this->cart['items'])) {
			return true;
		}
		$sql = "";
		foreach ($this->cart['items'] as $value) {
			$this->summary['quantity'] += $value['qu'];
			$sql .= "\n(select '{$value['id']}' `id`,'{$value['qu']}' `quantity`,'{$value['ac']}' `active`) union";
		}
		$sql = "(select * from (\n" . trim($sql, " union\n") . ') y)';
		$ids = implode(',', array_column($this->cart['items'], 'id'));
		$sql = "select x.id,p.Name name,
				(x.quantity*1) quantity,(x.active*1) active,(p.Price+0e0) price, (x.quantity*p.Price) `sum`,(p.PriceBefore+0e0) priceBefore, 
				(x.quantity*p.PriceBefore) `sumBefore`,
				(x.quantity*if(x.active=0,0,if(p.PriceBefore=0,p.Price,p.PriceBefore))) `sumBeforeTotal`,
				if(p.PriceBefore=0,0,(x.quantity * if(x.active=0,0,(p.PriceBefore - p.Price)))) `sumSaving`,
				if(x.active=0,0,x.quantity*1) `quantityActive`,
				if(x.active=0,0,(x.quantity * p.Price)) `sumActive`,
				concat(n.Url,if(p.Alias!='',p.Alias,p.Id),'.html') url,
				f.FileUrl image
				from Products p
				join $sql x on x.id=p.Id
				join s_Navigator n on n.Id=p.NavigatorId
				left join s_Files f on f.TableNameId = p.Id and f.TableName = 'Products' and f.TableNameField = 'Images'
				where p.Id in ($ids)";
		$this->summary['items'] = ConnectWepps::$instance->fetch($sql);
		$this->summary['quantity'] = array_sum(array_column($this->summary['items'], 'quantity'));
		$this->summary['quantityActive'] = array_sum(array_column($this->summary['items'], 'quantityActive'));
		$this->summary['sum'] = array_sum(array_column($this->summary['items'], 'sum'));
		$this->summary['sumSaving'] = array_sum(array_column($this->summary['items'], 'sumSaving'));
		$this->summary['sumBefore'] = array_sum(array_column($this->summary['items'], 'sumBeforeTotal'));
		$this->summary['sumActive'] = $this->summary['sumTotal'] = array_sum(array_column($this->summary['items'], 'sumActive'));
		$this->summary['date'] = $this->cart['date'];
		$this->summary['favorites'] = $this->getFavorites();
		if (!empty($this->cart['citiesId'])) {
			$this->summary['delivery']['citiesId'] = $this->cart['citiesId'];
		}
		$this->summary['delivery']['deliveryId'] = $this->cart['deliveryId'] ?? '0';
		if (!empty($this->cart['deliveryId'])) {
			$this->summary['delivery']['extension'] = $this->cart['deliveryExtension'];
			$this->summary['delivery']['settings'] = $this->cart['deliverySettings'];
		}
		if (!empty($this->cart['deliveryTariff'])) {
			$this->summary['delivery']['tariff'] = $this->cart['deliveryTariff'];
			$this->summary['sumTotal'] += $this->cart['deliveryTariff']['price'];
		}
		if (!empty($this->cart['deliveryDiscount'])) {
			$this->summary['delivery']['discount'] = $this->cart['deliveryDiscount'];
			$this->summary['sumTotal'] -= $this->cart['deliveryDiscount']['price'];
		}
		if (!empty($this->cart['paymentsId'])) {
			$this->summary['payments']['paymentsId'] = $this->cart['paymentsId'];
			$this->summary['payments']['extension'] = $this->cart['paymentsExtension'];
		}
		if (!empty($this->cart['paymentsTariff'])) {
			$this->summary['payments']['tariff'] = $this->cart['paymentsTariff'];
			$this->summary['sumTotal'] += $this->cart['paymentsTariff']['price'];
		}
		if (!empty($this->cart['paymentsDiscount'])) {
			$this->summary['payments']['discount'] = $this->cart['paymentsDiscount'];
			$this->summary['sumTotal'] -= $this->cart['paymentsDiscount']['price'];
		}
		#$json = json_encode($this->summary,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
		#UtilsWepps::debug($json,1);
		return true;
	}
	public function getCartSummary(): array
	{
		return $this->summary;
	}
	public function getCartPercentage(float $percentage = 0) : float {
		$sum = 0;
		foreach ($this->summary['items'] as $value) {
			if ($value['active']!=1) {
				continue;
			}
			$sum += UtilsWepps::round(($value['price'] - $value['price']*$percentage/100)*$value['quantity']);
		}
		$sum = $this->summary['sumActive'] - $sum;
		return UtilsWepps::round($sum);
	}
	public function getCartPositionsRecounter(array $items=[],float $deliveryDiscount=0,float $paymentTariff=0,float $paymentDiscount=0) : array {
		$sum = 0;
		if ($deliveryDiscount>0 || $paymentTariff>0 || $paymentDiscount>0) {
			$sum = array_sum(array_column($items, 'sum'));
		}
		if ($sum==0) {
			return $items;
		}
		if ($deliveryDiscount>0) {
			foreach($items as $key=>$value) {
				$rate = UtilsWepps::round($value['sum']/$sum,6);
				$tariffRecount = - UtilsWepps::round($rate*$deliveryDiscount);
				$items[$key]['tariff']??0;
				$items[$key]['tariff']+=$tariffRecount;
			}
		}
		if ($paymentTariff>0) {
			foreach($items as $key=>$value) {
				$rate = UtilsWepps::round($value['sum']/$sum,6);
				$tariffRecount = UtilsWepps::round($rate*$paymentTariff);
				$items[$key]['tariff']??0;
				$items[$key]['tariff']+=$tariffRecount;
			}
		}
		if ($paymentDiscount>0) {
			foreach($items as $key=>$value) {
				$rate = UtilsWepps::round($value['sum']/$sum,6);
				$tariffRecount = - UtilsWepps::round($rate*$paymentDiscount);
				$items[$key]['tariff']??0;
				$items[$key]['tariff']+=$tariffRecount;
			}
		}
		foreach($items as $key=>$value) {
			$items[$key]['sumTotal']=$value['sum'] + $items[$key]['tariff'];
			$items[$key]['priceTotal']= UtilsWepps::round($items[$key]['sumTotal']/$value['quantity'],2);
		}
		return $items;
	}
	private function _getCartHash(string $jcart = '')
	{
		return UtilsWepps::guid($jcart . ConnectWepps::$projectServices['jwt']['secret']);
	}
	private function _getCartFromCookies(bool $shouldCreate = true)
	{
		$cart = '';
		if (isset($_COOKIE['wepps_cart']) && @$_COOKIE['wepps_cart_guid'] == self::_getCartHash($_COOKIE['wepps_cart'])) {
			$cart = $_COOKIE['wepps_cart'];
		} elseif ($shouldCreate == true) {
			$cart = '{"items":null}';
			UtilsWepps::cookies('wepps_cart', $this->user['JCart'] ?? '');
			UtilsWepps::cookies('wepps_cart_guid', self::_getCartHash($this->user['JCart'] ?? ''));
		}
		return $cart;
	}
	public function getCheckoutData()
	{
		$deliveryUtils = new DeliveryUtilsWepps();
		$paymentsUtils = new PaymentsUtilsWepps();
		$cartSummary = $this->getCartSummary();
		$delivery = [];
		$deliveryOperations = [];
		$deliveryActive = "";
		$payments = [];
		$paymentsActive = "";
		if (!empty($cartSummary['delivery']['citiesId'])) {
			$cartCity = $deliveryUtils->getCitiesById($cartSummary['delivery']['citiesId']);
			if (!empty($cartCity[0]['Id'])) {
				$deliveryActive = "0";
				$payments = [];
				$paymentsActive = "0";
				$delivery = $deliveryUtils->getTariffsByCitiesId($cartCity[0]['Id'], $this);
				if (!empty($cartSummary['delivery']['deliveryId'])) {
					$deliveryActive = (string) $cartSummary['delivery']['deliveryId'];
					$deliveryOperations = $deliveryUtils->getOperations();
					$payments = $paymentsUtils->getByDeliveryId($deliveryActive, $this);
					if (!empty($cartSummary['payments']['paymentsId'])) {
						$paymentsActive = $cartSummary['payments']['paymentsId'];
					}
				}
			}
		}
		return [
			'city' => @$cartCity[0],
			'delivery' => $delivery,
			'deliveryActive' => $deliveryActive,
			'deliveryOperations' => $deliveryOperations,
			'payments' => $payments,
			'paymentsActive' => $paymentsActive,
		];
	}
	public function setHeaders(TemplateHeadersWepps $headers): void
	{
		$this->headers = $headers;
	}
	public function getHeaders(): TemplateHeadersWepps
	{
		if (empty($this->headers)) {
			$this->headers = new TemplateHeadersWepps();
		}
		return $this->headers;
	}
	public function getMemcached() {
		return $this->memcached;
	}
	public function addOrder(array $get) {
		#$deliveryUtils = new DeliveryUtilsWepps();
		#$paymentsUtils = new PaymentsUtilsWepps();
		$this->setCartSummary();
		$cartSummary = $this->getCartSummary();
		
		/**
		 * erors check
		 * display errors
		 * place order
		 * payment ext-s
		 */
		if (empty($cartSummary['delivery']['extension'])) {
			return [];
		}
		$className = "\WeppsExtensions\\Cart\\Delivery\\{$cartSummary['delivery']['extension']}";
		/**
		 * @var \WeppsExtensions\Cart\Delivery\DeliveryWepps $class
		 */
        $class = new $className([],$this);

		/**
		 * errors check
		 * 
		 * Получить ошибки и вывести в методе, браузере
		 * 
		 * return - ошибки, html, etc
		 * 
		 */
		$errors = $class->getErrors($get);
		$errors = ValidatorWepps::setFormErrorsIndicate($errors, $get['form']);
		if ($errors['count']>0) {
			echo $errors['html'];
			exit();
		}
		$profile = ConnectWepps::$projectData['user'];
		$positions = [];
		foreach ($cartSummary['items'] as $value) {
			if (empty($value['active'])) {
				continue;
			}
			$positions[] = [
				'id' => $value['id'],
				'name' => $value['name'],
				'quantity' => $value['quantity'],
				'price' => $value['price'],
				'sum' => $value['sum'],
			];
		}
		$positions = $this->getCartPositionsRecounter($positions,$cartSummary['delivery']['discount']['price'],$cartSummary['payments']['tariff']['price'],$cartSummary['payments']['discount']['price']);
		$row = [
			'Name' => $profile['Name'],
			'UserId' => $profile['Id'],
			'UserIP' => $_SERVER['REMOTE_ADDR']??'',
			'Phone' => $profile['Phone'],
			'Email' => $profile['Email'],
			'OStatus' => '1',
			'OSum' => $cartSummary['sumTotal'],
			'ODate' => date('Y-m-d H:i:s'),
			#'OText' => '',
			'ODelivery' => $cartSummary['delivery']['deliveryId'],
			'ODeliveryTariff' => $cartSummary['delivery']['tariff']['price'],
			'ODeliveryDiscount' => $cartSummary['delivery']['discount']['price'],
			'OPayment' => $cartSummary['payments']['paymentsId'],
			'OPaymentTariff' => $cartSummary['payments']['tariff']['price'],
			'OPaymentDiscount' => $cartSummary['payments']['discount']['price'],
			'Address' => $get['operations-address']??'',
			'City' => $get['operations-city']??'',
			'CityId' => $cartSummary['delivery']['citiesId'],
			'PostalCode' => $get['operations-postal-code']??'',
			#'OComment' => @$get['comment'],
			'JData' => json_encode($cartSummary,JSON_UNESCAPED_UNICODE),
			'JPositions' => json_encode($positions,JSON_UNESCAPED_UNICODE),
		];
		$func = function (array $args) {
			$row = $args['row'];
			$get = $args['get'];
			$prepare = ConnectWepps::$instance->prepare($row);
			$insert = ConnectWepps::$db->prepare("insert Orders {$prepare['insert']}");
			$insert->execute($row);
			$id = ConnectWepps::$db->lastInsertId();
			if (!empty($get['comment'])) {
				$row = [
					'Name' => 'Msg',
					'OrderId' => $id,
					'UserId' => $row['UserId'],
					'EType' => 'msg',
					'EDate' => $row['ODate'],
					'EText' => trim(strip_tags($get['comment']))
				];
				$prepare = ConnectWepps::$instance->prepare($row);
				$insert = ConnectWepps::$db->prepare("insert OrdersEvents {$prepare['insert']}");
				$insert->execute($row);
				$text = "Уведомление клиенту о заказе";
				ConnectWepps::$instance->query("update Orders set OText=? where Id=?",[$text,$id]);
			}
			return [
				'id' => $id
			];
		};
		$response = ConnectWepps::$instance->transaction($func, ['row' => $row,'get'=>$get]);
		UtilsWepps::debug($response,31);

		/**
		 * place order
		 * 
		 * user info
		 * positions
		 * payment
		 * delivery
		 * etc
		 * 
		 * hash заказа, переход на фин. страницу
		 * paynment ext-s - подключение оплаты, или др. сценарий
		 */
		return [];
	}

	public function getOrderText(array $order) : string {
		$sql = "select * from ServList where Categories='ШаблонЗаказНовый' order by Id desc limit 0,1";
		$res = ConnectWepps::$instance->fetch($sql);
		if (empty($text = $res[0]['Descr'])) {
			return '';
		}
		$jdata = json_decode($order['JData'],true);
		$jpositions = json_decode($order['JPositions'],true);
		#UtilsWepps::debug($jdata,21);
		#UtilsWepps::debug($jdata['delivery']['tariff']['title'],21);
		$positions = "<table width=\"100%\" cellpadding=\"10\" border=\"1\">";
		$positions .= "<tr>";
		$positions .= "<th width=\"50%\" align=\"left\">Наименование</th>";
		$positions .= "<th width=\"25%\" align=\"center\">Кол-во</th>";
		$positions .= "<th width=\"25%\" align=\"right\">Сумма</th>";
		$positions .= "</tr>";
		foreach($jpositions as $value) {
			$positions .= '<tr>';
			$positions .= '<td align="left">'.$value['name'].'</td>';
			$positions .= '<td align="center">'.$value['quantity'].'</td>';
			$positions .= '<td align="right">'.UtilsWepps::round($value['sum'],2,'str').'</td>';
			$positions .= '</tr>';
		}
		$positions .= '<tr>';
		$positions .= '<td align="left">'.$jdata['delivery']['tariff']['title'].'</td>';
		$positions .= '<td align="center"></td>';
		$positions .= '<td align="right">'.UtilsWepps::round($jdata['delivery']['tariff']['price'],2,'str').'</td>';
		$positions .= '</tr>';
		if (!empty($jdata['delivery']['discount']['price'])) {
			$positions .= '<tr>';
			$positions .= '<td align="left">'.$jdata['delivery']['discount']['text'].'</td>';
			$positions .= '<td align="center"></td>';
			$positions .= '<td align="right">- '.UtilsWepps::round($jdata['delivery']['discount']['price'],2,'str').'</td>';
			$positions .= '</tr>';
		}
		if (!empty($jdata['payments']['tariff']['price'])) {
			$positions .= '<tr>';
			$positions .= '<td align="left">'.$jdata['payments']['tariff']['text'].'</td>';
			$positions .= '<td align="center"></td>';
			$positions .= '<td align="right">- '.UtilsWepps::round($jdata['payments']['tariff']['price'],2,'str').'</td>';
			$positions .= '</tr>';
		}
		if (!empty($jdata['payments']['discount']['price'])) {
			$positions .= '<tr>';
			$positions .= '<td align="left">'.$jdata['payments']['discount']['text'].'</td>';
			$positions .= '<td align="center"></td>';
			$positions .= '<td align="right">- '.UtilsWepps::round($jdata['payments']['discount']['price'],2,'str').'</td>';
			$positions .= '</tr>';
		}
		$positions .= '<tr>';
		$positions .= '<td align="left"></td>';
		$positions .= '<td align="center">ИТОГО: </td>';
		$positions .= '<td align="right"><b>'.UtilsWepps::round($jdata['sumTotal'],2,'str').'</b></td>';
		$positions .= '</tr>';
		$positions .= "</table>";

		$addons = "

		Адрес:
		Способ доставки:
		Способ оплаты:
		Комментарий:

		<b>Покупатель</b><br/>
		{$order['Name']}<br/>
		{$order['Phone']}<br/>
		{$order['Email']}<br/>
		";
		$text = str_replace('[ЗАКАЗ]',$order['Id'],$text);
		$text = str_replace('[НАИМЕНОВАНИЕ]',$order['Name'],$text);
		$text = str_replace('[ИМЯ]',$order['Name'],$text);
		$text = str_replace('[ТОВАРЫ]',$positions,$text);
		$text = str_replace('[ДОПОЛНИТЕЛЬНО]',$addons,$text);
		$text = str_replace('[ПРОЕКТ]',ConnectWepps::$projectInfo['name'],$text);
		return $text;
	}

	/**
	 * ! Далее переделка
	 */

	public function addOrder_LEGACY($settings = array(), $userId = null)
	{
		if (!isset($_SESSION['user']) && $userId == null)
			return array('error' => 1);
		$cartSummary = self::cartSummary();
		$userId = ($userId == null) ? $_SESSION['user']['Id'] : $userId;
		$obj = new DataWepps("s_Users");
		$user = $obj->fetchmini($userId)[0];
		$obj->set($user['Id'], array(
			'CityRegion' => $cartSummary['cartAdd']['cityChecked'],
			'City' => $cartSummary['cartAdd']['city'],
			'Address' => $settings['address'],
			'AddressIndex' => $settings['addressIndex'],
		));

		//exit();
		$row = [];
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
		$order = self::getOrder($orderId);
		self::addOrderPositions($orderId);
		$text = "<h4>ЗАКАЗ №" . TextTransformsWepps::getNumberOrder($orderId) . " : " . $order['Name'] . "</h4>\n\n";
		if (strlen($row['OComment']) != 0)
			$text .= "<h4>ПРИМЕЧАНИЯ К ЗАКАЗУ</h4>\n" . $order['OComment'] . "\n\n";
		$text .= "<h4>ТОВАРЫ ЗАКАЗА</h4>\n";

		$text .= "<table cellpadding=\"5\">";
		foreach ($cartSummary['cart'] as $key => $value) {

			$text .= "
			<tr>
			<td>
			<a href=\"http://{$_SERVER['HTTP_HOST']}{$value['Data']['Url']}\"><img src=\"http://{$_SERVER['HTTP_HOST']}{$value['Data']['Image_FileUrl']}\" width=\"50\"/></a>
			</td>
			<td>
			<strong>{$value['Data']['ProductType_NameOsn']} {$value['Data']['Name']}</strong> ({$value['Data']['OptionColor']})<br/>
			Артикул внутренний: {$value['Data']['ArticulInner']}<br/>
		
			Размер: {$value['Data']['OptionSize']}<br/>
		
			{$value['Data']['PriceAmount']} x " . $value['Data']['OptionQty'] * $value['Qty'] . " = {$value['PriceAmount']} Р.
			</td>
			</tr>
			";
		}
		$text .= "</table>";

		$str = ($cartSummary['cartAdd']['deliveryPrice'] != 0) ? " (" . TextTransformsWepps::money($cartSummary['cartAdd']['deliveryPrice']) . " Р.)" : "";
		$text .= "<p><b>ДОСТАВКА</b>: " . $order['ODelivery_Name'] . $str . "</p>\n";
		$str = ($cartSummary['cartAdd']['paymentPrice'] != 0) ? " (" . TextTransformsWepps::money($cartSummary['cartAdd']['paymentPrice']) . " Р.)" : "";
		$text .= "<p><b>ОПЛАТА:</b> " . $order['OPayment_Name'] . $str . "</p>\n";
		$text .= "<p><b>ИТОГО К ОПЛАТЕ:</b> " . TextTransformsWepps::money($cartSummary['priceTotal']) . " Р.</p>\n\n";
		$text .= "<p><b>ИНФОРМАЦИЯ О КЛИЕНТЕ</b><br/>\n";
		$text .= $user['Name'] . "<br/>\n";
		$text .= "Адрес доставки: " . $order['AddressIndex'] . ", " . $order['City'] . "<br/>\n";
		$text .= $order['Address'] . "<br/>\n";
		$text .= "Контактный телефон: " . $order['Phone'] . "<br/>\n";
		$text .= $order['Email'];
		$text .= "</p>";


		// 		UtilsWepps::debug($text,0);
// 		UtilsWepps::debug($cartSummary,1);

		//$text = nl2br($text);

		ConnectWepps::$instance->query("update TradeOrders set OText='$text' where Id='$orderId'");
		$from = ($order['Email']) ? "=?utf-8?B?" . base64_encode($order['Name']) . "?=" . " <" . $order['Email'] . ">" : ConnectWepps::$projectInfo['name'] . " <" . ConnectWepps::$projectInfo['email'] . ">";

		//UtilsWepps::mail(ConnectWepps::$projectInfo['email'], "Новый заказ", $text);
		//exit();
		return $orderId;
	}
	public function getOrder($id)
	{
		$obj = new DataWepps("TradeOrders");
		$obj->setJoin('left join GeoCities as c on c.Id = t.City');
		$obj->setConcat('c.Name as CityName');
		$order = $obj->fetch($id)[0];
		return $order;
	}
	public function addOrderPositions($orderId)
	{
		$dateCurr = date("Y-m-d H:i:s");
		$cartSummary = self::cartSummary();
		$date = date('Y-m-d H:i:s');
		$obj = new DataWepps("TradeClientsHistory");
		foreach ($cartSummary['cart'] as $key => $value) {
			$row = [];
			$row['Name'] = "{$value['Data']['ProductType_NameOsn']} {$value['Data']['Name']}";
			$row['ItemQty'] = $value['Data']['OptionQty'] * $value['Qty'];
			$row['Price'] = $value['Data']['PriceAmount'];
			$row['Summ'] = round($row['Price'] * $row['ItemQty']);
			$row['ClDate'] = $dateCurr;
			$row['ClientId'] = "";
			$row['ProductId'] = $value['Data']['Id'];
			$row['TStatus'] = 1;
			$row['OrderId'] = $orderId;
			$row['ProductIdLink'] = "http://" . $_SERVER['HTTP_HOST'] . $value['Data']['Url'] . "";
			$row['ArticulInner'] = $value['Data']['ArticulInner'];
			$row['TradeProductType'] = $value['Data']['ProductType_NameOsn'];
			$row['TradeArticul'] = $value['Data']['Name'];
			$row['TradeName'] = $value['Data']['Articul'];
			$row['TradeColor'] = $value['Data']['OptionColor'];
			$str = str_replace(",", "-" . $value['Qty'] . ", ", $value['Data']['OptionSize']) . "-" . $value['Qty'];
			$row['TradeSizes'] = $str;
			$obj->add($row);
		}
		return array('success' => 1);
	}
}