<?php
namespace WeppsExtensions\Cart;

use WeppsCore\Data;
use WeppsCore\Connect;
use WeppsCore\Tasks;
use WeppsCore\Memcached;
use WeppsCore\TemplateHeaders;
use WeppsCore\Utils;
use WeppsCore\Validator;
use WeppsExtensions\Addons\Messages\Mail\Mail;
use WeppsExtensions\Addons\Messages\Telegram\Telegram;
use WeppsExtensions\Cart\Delivery\DeliveryUtils;
use WeppsExtensions\Cart\Payments\PaymentsUtils;

class CartUtils
{
	private $user = [];
	private $cart = [];
	private $favorites = [];
	private $summary = [];
	private $headers;
	private $memcached;
	public function __construct()
	{
		if (empty(Connect::$projectData['user'])) {
			$this->user['JCart'] = $this->_getCartFromCookies();
		} else {
			$cart = $this->_getCartFromCookies(false);
			$jdata = json_decode($cart, true);
			if (!empty($jdata['items'])) {
				$jdata2 = json_decode(Connect::$projectData['user']['JCart'], true);
				$jdata2['items'] += $jdata['items'];
				Utils::cookies('wepps_cart');
				Utils::cookies('wepps_cart_guid');
				$this->setCart();
			}
			$this->user = $this->getUser(Connect::$projectData['user']);
			$this->favorites = json_decode($this->user['JFav'], true);
		}
		$this->cart = json_decode($this->user['JCart'] ?? '', true) ?? [];
		$this->memcached = new Memcached();
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
		Connect::$instance->query("update s_Users set JFav=? where Id=?", [$json, @$this->user['Id']]);
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
			'count' => array_sum(array_column($this->cart['items']??[], 'qu')),
			'items' => array_values(array_unique(array_map(function($i) : int {
    			return (int) explode('-', $i['id'])[0];
			}, $this->cart['items']??[]))),
			'itemsv' => array_values(array_unique(array_map(function($i) : int {
    			return (int) @explode('-', $i['id'])[1];
			}, $this->cart['items']??[]))),
			'commerce' => Connect::$projectServices['commerce']
		];
	}
	public function setCart(): void
	{
		$this->cart['date'] = date('Y-m-d H:i:s');
		$json = json_encode($this->cart, JSON_UNESCAPED_UNICODE);
		if (empty(Connect::$projectData['user'])) {
			Utils::cookies('wepps_cart', $json);
			Utils::cookies('wepps_cart_guid', Utils::guid($json . Connect::$projectServices['jwt']['secret']));
			return;
		}
		$this->setCartSummary();
		Connect::$instance->query("update s_Users set JCart=? where Id=?", [$json, @$this->user['Id']]);
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
		$deliveryUtils = new DeliveryUtils();
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
		$paymentsUtils = new PaymentsUtils();
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
	public function add(string $id, int $quantity = 1): void
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
			/* if ($quantity==1) {
				$quantity++;
			} */
			$index = array_search($id, $keys);
			if (intval($index) >= 0) {
				$this->cart['items'][$index]['qu'] = $quantity;
			}
			#Utils::debug($this->cart['items'][$index]);
		}
		$this->setCart();
		return;
	}
	public function edit(string $id, int $quantity = 1): void
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
			#Utils::debug($keys);
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
	public function remove(string $id)
	{
		$keys = array_column($this->cart['items'], 'id');
		$index = array_search($id, $keys);
		if (intval($index) >= 0) {
			unset($this->cart['items'][$index]);
			$this->cart['items'] = array_merge($this->cart['items'], []);
		}
		return $this->setCart();
	}
	public function removeCart()
	{
		if (empty($this->cart['items'])) {
			return;
		}
		foreach($this->cart['items'] as $key=>$value) {
			if ($value['ac']==1) {
				unset($this->cart['items'][$key]);
			}
		}
		$this->cart['items'] = array_values($this->cart['items']);
		return $this->setCart();
	}
	public function setCartSummary(): bool
	{
		Connect::$instance->cached('no');
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
			'favorites' => [],
			'isSumActiveEnough' => 0,
		];
		if (empty($this->cart['items'])) {
			return true;
		}
		$sql = "";
		#Utils::debug($this->cart['items'],1);
		$ids = [];
		$idv = [];
		foreach ($this->cart['items'] as $value) {
			$this->summary['quantity'] += $value['qu'];
			$ex = explode('-',$value['id']);
			$sql .= "\n(select '{$ex[0]}'*1 `id`,'".($ex[1]??0)."'*1 `idv`,'{$value['qu']}' `quantity`,'{$value['ac']}' `active`) union";
			$ids[] = $ex[0];
			$idv[] = $ex[1]??0;
		}
		$ids = implode(',',array_unique($ids));
		$idv = implode(',',array_unique($idv));
		$sql = "(select * from (\n" . trim($sql, " union\n") . ') y)';
		
		$sql = "select x.id,x.idv,if(pv.Field1!='',concat(p.Name,' / ',pv.Field1,if(pv.Field2!='',concat(', ',pv.Field2),'')),p.Name) name,
			(x.quantity*1) quantity,if(pv.Field4>0,pv.Field4+0e0,0) stocks,(x.active*1) active,(p.Price+0e0) price, (x.quantity*p.Price) `sum`,(p.PriceBefore+0e0) priceBefore, 
			(x.quantity*p.PriceBefore) `sumBefore`,
			(x.quantity*if(x.active=0,0,if(p.PriceBefore=0,p.Price,p.PriceBefore))) `sumBeforeTotal`,
			if(p.PriceBefore=0,0,(x.quantity * if(x.active=0,0,(p.PriceBefore - p.Price)))) `sumSaving`,
			if(x.active=0,0,x.quantity*1) `quantityActive`,
			if(x.active=0,0,(x.quantity * p.Price)) `sumActive`,
			#if(pv.Stocks)
			concat(n.Url,if(p.Alias!='',p.Alias,p.Id),'.html') url,
			f.FileUrl image
			from $sql x
			left join ProductsVariations pv on x.id=pv.ProductsId and x.idv = pv.Id
			join Products p on p.Id = x.id
			join s_Navigator n on n.Id=p.NavigatorId
			left join s_Files f on f.TableNameId = p.Id and f.TableName = 'Products' and f.TableNameField = 'Images'
			group by x.idv";
		$this->summary['items'] = Connect::$instance->fetch($sql);
		$this->summary['quantity'] = 0;
		$this->summary['quantityActive'] = 0;
		$this->summary['sum'] = 0;
		$this->summary['sumSaving'] = 0;
		$this->summary['sumBefore'] = 0;
		$this->summary['sumActive'] = 0;
		$this->summary['sumTotal'] = 0;
		$this->summary['stocksErrors'] = 0;
		foreach($this->summary['items'] as $value) {
			$this->summary['quantity'] += $value['quantity'];
			$this->summary['quantityActive'] += $value['quantityActive'];
			$this->summary['sum'] += $value['sum'];
			$this->summary['sumSaving'] += $value['sumSaving'];
			$this->summary['sumBefore'] += $value['sumBeforeTotal'];
			$this->summary['sumActive'] += $value['sumActive'];
			$this->summary['sumTotal'] += $value['sumActive'];
			if ($value['stocks']<=0 || $value['stocks']<$value['quantity']) {
				$this->summary['stocksErrors'] = 1;
			}
		}
		/* $this->summary['quantity'] = array_sum(array_column($this->summary['items'], 'quantity'));
		$this->summary['quantityActive'] = array_sum(array_column($this->summary['items'], 'quantityActive'));
		$this->summary['sum'] = array_sum(array_column($this->summary['items'], 'sum'));
		$this->summary['sumSaving'] = array_sum(array_column($this->summary['items'], 'sumSaving'));
		$this->summary['sumBefore'] = array_sum(array_column($this->summary['items'], 'sumBeforeTotal'));
		$this->summary['sumActive'] = $this->summary['sumTotal'] = array_sum(array_column($this->summary['items'], 'sumActive')); */
			
		$this->summary['date'] = $this->cart['date'];
		$this->summary['favorites'] = $this->getFavorites();
		if ($this->summary['sumActive']>=Connect::$projectServices['commerce']['orderAmountMin']) {
			$this->summary['isSumActiveEnough'] = 1;
		}
		if (!empty($this->cart['citiesId'])) {
			$this->summary['delivery']['citiesId'] = $this->cart['citiesId'];
		}
		$this->summary['delivery']['deliveryId'] = $this->cart['deliveryId'] ?? '0';
		if (!empty($this->cart['deliveryId'])) {
			$this->summary['delivery']['extension'] = @$this->cart['deliveryExtension'];
			$this->summary['delivery']['settings'] = @$this->cart['deliverySettings'];
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
		#Utils::debug($json,1);
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
			$sum += Utils::round(($value['price'] - $value['price']*$percentage/100)*$value['quantity']);
		}
		$sum = $this->summary['sumActive'] - $sum;
		return Utils::round($sum);
	}
	public function getCartPositionsRecounter(array $items=[],float $deliveryDiscount=0,float $paymentTariff=0,float $paymentDiscount=0) : array {
		$sum = 0;
		if ($deliveryDiscount>0 || $paymentTariff>0 || $paymentDiscount>0) {
			$sum = array_sum(array_column($items, 'sum'));
		}
		if ($sum==0) {
			//return $items;
		}
		if ($deliveryDiscount>0) {
			foreach($items as $key=>$value) {
				$rate = Utils::round($value['sum']/$sum,6);
				$tariffRecount = - Utils::round($rate*$deliveryDiscount);
				if (empty($items[$key]['tariff'])) {
					$items[$key]['tariff'] = 0;
				}
				$items[$key]['tariff']+=$tariffRecount;
			}
		}
		if ($paymentTariff>0) {
			foreach($items as $key=>$value) {
				$rate = Utils::round($value['sum']/$sum,6);
				$tariffRecount = Utils::round($rate*$paymentTariff);
				if (empty($items[$key]['tariff'])) {
					$items[$key]['tariff'] = 0;
				}
				$items[$key]['tariff']+=$tariffRecount;
			}
		}
		if ($paymentDiscount>0) {
			foreach($items as $key=>$value) {
				$rate = Utils::round($value['sum']/$sum,6);
				$tariffRecount = - Utils::round($rate*$paymentDiscount);
				if (empty($items[$key]['tariff'])) {
					$items[$key]['tariff'] = 0;
				}
				$items[$key]['tariff']+=$tariffRecount;
			}
		}
		foreach($items as $key=>$value) {
			$tariff = (!empty($items[$key]['tariff']))?$items[$key]['tariff']:0;
			$items[$key]['sumTotal']=$value['sum'] + $tariff;
			$items[$key]['priceTotal']= Utils::round($items[$key]['sumTotal']/$value['quantity'],2);
		}
		return $items;
	}
	private function _getCartHash(string $jcart = '')
	{
		return Utils::guid($jcart . Connect::$projectServices['jwt']['secret']);
	}
	private function _getCartFromCookies(bool $shouldCreate = true)
	{
		$cart = '';
		if (isset($_COOKIE['wepps_cart']) && @$_COOKIE['wepps_cart_guid'] == self::_getCartHash($_COOKIE['wepps_cart'])) {
			$cart = $_COOKIE['wepps_cart'];
		} elseif ($shouldCreate == true) {
			$cart = '{"items":null}';
			Utils::cookies('wepps_cart', $this->user['JCart'] ?? '');
			Utils::cookies('wepps_cart_guid', self::_getCartHash($this->user['JCart'] ?? ''));
		}
		return $cart;
	}
	public function getCheckoutData()
	{
		$deliveryUtils = new DeliveryUtils();
		$paymentsUtils = new PaymentsUtils();
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
	public function setHeaders(TemplateHeaders $headers): void
	{
		$this->headers = $headers;
	}
	public function getHeaders(): TemplateHeaders
	{
		if (empty($this->headers)) {
			$this->headers = new TemplateHeaders();
		}
		return $this->headers;
	}
	public function getMemcached() {
		return $this->memcached;
	}
	public function addOrder(array $get) {
		$this->setCartSummary();
		$cartSummary = $this->getCartSummary();
		if (empty($cartSummary['delivery']['extension'])) {
			return [];
		}
		$className = "\WeppsExtensions\\Cart\\Delivery\\{$cartSummary['delivery']['extension']}\\{$cartSummary['delivery']['extension']}";
		/**
		 * @var \WeppsExtensions\Cart\Delivery\Delivery $class
		 */
        $class = new $className([],$this);
		$errors = $class->getErrors($get);
		$errors = Validator::setFormErrorsIndicate($errors, $get['form']);
		if ($errors['count']>0) {
			echo $errors['html'];
			exit();
		}
		$profile = Connect::$projectData['user'];
		$positions = [];
		foreach ($cartSummary['items'] as $value) {
			if (empty($value['active'])) {
				continue;
			}
			$positions[] = [
				'id' => $value['id'],
				'idv' => $value['idv'],
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
			'JData' => json_encode($cartSummary,JSON_UNESCAPED_UNICODE),
			'JPositions' => json_encode($positions,JSON_UNESCAPED_UNICODE),
		];
		$func = function (array $args) {
			$row = $args['row'];
			$get = $args['get'];
			$prepare = Connect::$instance->prepare($row);
			$insert = Connect::$db->prepare("insert Orders {$prepare['insert']}");
			$insert->execute($row);
			$id = Connect::$db->lastInsertId();
			if (!empty($get['comment'])) {
				$row2 = [
					'Name' => 'Msg',
					'OrderId' => $id,
					'UserId' => $row['UserId'],
					'EType' => 'msg',
					'EDate' => $row['ODate'],
					'EText' => trim(strip_tags($get['comment']))
				];
				$prepare = Connect::$instance->prepare($row2);
				$insert = Connect::$db->prepare("insert OrdersEvents {$prepare['insert']}");
				$insert->execute($row2);
			}
			$row['Id'] = $id;
			$row['EText'] = @$row2['EText'];
			$text = $this->getOrderText($row);
			$alias = Utils::guid($id.'_'.time().'_'.Connect::$projectServices['wepps']['sign']);
			Connect::$instance->query("update Orders set OText=?,Alias=? where Id=?",[$text,$alias,$id]);
			$jdata = [
				'id' => (int) $id,
				'email' => true,
				'telegram' => true,
			];
			$tasks = new Tasks();
			$tasks->add('order-new',$jdata,$row['ODate'],$row['UserIP']);
			$this->removeCart();
			return [
				'id' => $id,
				'alias' => $alias,
				'html' => "<script>window.location.href='/cart/order.html?id={$alias}'</script>"
				#'html' => "<script>console.log('{$alias}');</script>"
			];
		};
		return Connect::$instance->transaction($func, ['row' => $row,'get'=>$get]);
	}
	public function getOrderText(array $order) : string {
		$sql = "select * from ServList where Categories='ШаблонЗаказНовый' order by Id desc limit 0,1";
		$res = Connect::$instance->fetch($sql);
		if (empty($text = $res[0]['Descr'])) {
			return '';
		}
		$jdata = json_decode($order['JData'],true);
		$jpositions = json_decode($order['JPositions'],true);
		$positions = "<table width=\"100%\" border=\"1\">";
		$positions .= "<tr>";
		$positions .= "<th width=\"50%\" align=\"left\">Наименование</th>";
		$positions .= "<th width=\"25%\" align=\"center\">Кол-во</th>";
		$positions .= "<th width=\"25%\" align=\"right\">Сумма</th>";
		$positions .= "</tr>";
		foreach($jpositions as $value) {
			$positions .= '<tr>';
			$positions .= '<td align="left">'.$value['name'].'</td>';
			$positions .= '<td align="center">'.$value['quantity'].'</td>';
			$positions .= '<td align="right">'.Utils::round($value['sum'],2,'str').'</td>';
			$positions .= '</tr>';
		}
		$deliveryAddress = (!empty($order['Address'])) ? '<br/>'.$order['PostalCode'] . ', ' . $order['Address'] : '';
		$positions .= '<tr>';
		$positions .= '<td align="left">'.$jdata['delivery']['tariff']['title'].$deliveryAddress.'</td>';
		$positions .= '<td align="center"></td>';
		$positions .= '<td align="right">'.Utils::round($jdata['delivery']['tariff']['price'],2,'str').'</td>';
		$positions .= '</tr>';
		if (!empty($jdata['delivery']['discount']['price'])) {
			$positions .= '<tr>';
			$positions .= '<td align="left">'.$jdata['delivery']['discount']['text'].'</td>';
			$positions .= '<td align="center"></td>';
			$positions .= '<td align="right">- '.Utils::round($jdata['delivery']['discount']['price'],2,'str').'</td>';
			$positions .= '</tr>';
		}
		if (!empty($jdata['payments']['tariff']['price'])) {
			$positions .= '<tr>';
			$positions .= '<td align="left">'.$jdata['payments']['tariff']['text'].'</td>';
			$positions .= '<td align="center"></td>';
			$positions .= '<td align="right">- '.Utils::round($jdata['payments']['tariff']['price'],2,'str').'</td>';
			$positions .= '</tr>';
		}
		if (!empty($jdata['payments']['discount']['price'])) {
			$positions .= '<tr>';
			$positions .= '<td align="left">'.$jdata['payments']['discount']['text'].'</td>';
			$positions .= '<td align="center"></td>';
			$positions .= '<td align="right">- '.Utils::round($jdata['payments']['discount']['price'],2,'str').'</td>';
			$positions .= '</tr>';
		}
		$positions .= '<tr>';
		$positions .= '<td align="left"></td>';
		$positions .= '<td align="center">ИТОГО: </td>';
		$positions .= '<td align="right"><b>'.Utils::round($jdata['sumTotal'],2,'str').'</b></td>';
		$positions .= '</tr>';
		$positions .= "</table>";

		$comment = (!empty($order['EText']))?'<p><p><b>Комментарий</b><br/>'.$order['EText'].'</p>':'';
		$addons = "$comment
		<p><b>Покупатель</b><br/>
		{$order['Name']}<br/>
		{$order['Phone']}<br/>
		{$order['Email']}</p>
		";
		$text = str_replace('[ЗАКАЗ]',$order['Id'],$text);
		$text = str_replace('[НАИМЕНОВАНИЕ]',$order['Name'],$text);
		$text = str_replace('[ИМЯ]',$order['Name'],$text);
		$text = str_replace('[ТОВАРЫ]',$positions,$text);
		$text = str_replace('[ДОПОЛНИТЕЛЬНО]',$addons,$text);
		$text = str_replace('[ПРОЕКТ]',Connect::$projectInfo['name'],$text);
		return $text;
	}
	public function getOrder(int $id,int $userId=0): array
	{
		$obj = new Data("Orders");
		$obj->setJoin('left join Payments p on p.TableNameId=t.Id and p.TableName=\'Orders\' and p.IsPaid=1 and p.IsProcessed=1 and p.DisplayOff=0');
		$obj->setConcat('if(sum(p.PriceTotal)>0,sum(p.PriceTotal),0) PricePaid,if(sum(p.PriceTotal)>0,(t.OSum-sum(p.PriceTotal)),t.OSum) OSumPay,group_concat(p.Id,\':::\',p.Name,\':::\',p.PriceTotal,\':::\',p.MerchantDate,\':::\' separator \';;;\') Payments');
		if ($userId > 0) {
			$obj->setParams([$id,$userId]);
			if (empty($order = @$obj->fetch('t.Id=? and t.UserId=?')[0])) {
				return [];
			}
		} else {
			$obj->setParams([$id]);
			if (empty($order = $obj->fetch('t.Id=?')[0])) {
				return [];
			}
		}
		$order['W_Positions'] = json_decode($order['JPositions'],true);
		$sql = '';
		$sum = 0;
		$order['W_Positions'] = $this->getCartPositionsRecounter($order['W_Positions'],$order['ODeliveryDiscount'],$order['OPaymentTariff'],$order['OPaymentDiscount']);
		foreach ($order['W_Positions'] as $value) {
			$sum += $value['sum'];
			$sql .= "\n(select '{$value['id']}' `id`,'{$value['name']}' `name`,'{$value['quantity']}' `quantity`,'{$value['price']}' `price`,'{$value['sum']}' `sum`,'{$value['priceTotal']}' `priceTotal`,'{$value['sumTotal']}' `sumTotal`) union";
		}
		$sql = "(select * from (\n" . trim($sql," union\n").') y)';
		$ids = implode(',', array_column($order['W_Positions'], 'id'));
		$sql = "select x.id,x.name name,x.quantity,x.price,x.sum,x.priceTotal,x.sumTotal from $sql x left join Products t on x.id=t.Id where x.id in ($ids)";
		$order['W_Positions'] = Connect::$instance->fetch($sql);

		$sum += $order['ODeliveryTariff'];
		$sum -= $order['ODeliveryDiscount'];
		$sum += $order['OPaymentTariff'];
		$sum -= $order['OPaymentDiscount'];

		$order['OSum'] = Utils::round($sum);

		$obj = new Data("OrdersEvents");
		$obj->setParams([$id]);
		$obj->setJoin("join s_Users u on u.Id=t.UserId");
		$obj->setConcat("u.Name UsersName");
		$res = $obj->fetch("t.DisplayOff=0 and t.OrderId=?",2000,1,"t.Priority");
		if (!empty($res)) {
			$order['W_Messages'] = $res;
		}
		#Utils::debug($order,2);
		return $order;
	}
	public function getOrderByGuid(string $guid) : array
	{
		if (strlen($guid)!=36) {
			return [];
		}
		$obj = new Data("Orders");
		$obj->setParams([$guid]);
		$obj->setConcat("if(s3.PaymentsExt!='',PaymentsExt,'PaymentsDefault') PaymentsExt,s3.DescrFinish PaymentDescrFinish");
		$order = @$obj->fetch("t.Alias = ?")[0];
		return $order;
	}
	public function processTask(array $request,Tasks $tasks) {
		$jdata = json_decode($request['BRequest'],true);
		$order = $this->getOrder($jdata['id']);
		if (empty($order)) {
			$response = [
				'message' => 'no order'
			];
			return $tasks->update($request['Id'],$response,400);
		}
		$mail = new Mail('html');
		$subject = 'Новый заказ';
		$text = $order['OText'];
		$outputMessage = "";
		$mail->mail(Connect::$projectInfo['email'], $subject, $text);
		if (!empty($jdata['email'])) {
			$mail->mail($order['Email'], $subject, $text);
			$outputMessage .= " email ok";
		}
		if (!empty($jdata['telegram'])) {
			$text = "<b>НОВЫЙ ЗАКАЗ</b> №{$order['Id']} / {$order['OSum']} ₽\n🙋{$order['Name']}\n📞{$order['Phone']}\n✉️{$order['Email']}\n\n#сайт_{$order['Id']}";
			$tg = new Telegram();
			$res = $tg->send(Connect::$projectServices['telegram']['dev'],$text);
			$jdata = json_decode($res['response'],true);
			$outputMessage .= ($jdata['ok']===true) ? " telegram ok" : " telegram false";
		}
		$outputMessage = trim($outputMessage);
		$response = [
			'message' => $outputMessage
		];
		return $tasks->update($request['Id'],$response,200);
	}
	public function processPaymentTask(array $request,Tasks $tasks) {
		$jdata = json_decode($request['BRequest'],true);
		$order = $this->getOrder($jdata['id']);
		if (empty($order)) {
			$response = [
				'message' => 'no order'
			];
			return $tasks->update($request['Id'],$response,400);
		}
		$mail = new Mail('html');
		$outputMessage = "";
		if (!empty($jdata['email'])) {
			$sql = "select * from ServList where Categories='ШаблонЗаказОплата' order by Id desc limit 0,1";
			$res = Connect::$instance->fetch($sql);
			if (empty($text = $res[0]['Descr'])) {
				$outputMessage .= " email fail";
			} else {
				$subject = ($jdata['status']=='succeeded')?'Заказ оплачен':'Заказ не оплачен - ошибка';
				$text = str_replace('[ЗАКАЗ]',$order['Id'],$text);
				$text = str_replace('[НАИМЕНОВАНИЕ]',$order['Name'],$text);
				$text = str_replace('[ИМЯ]',$order['Name'],$text);
				$text = str_replace('[ТЕКСТ]',$jdata['message'],$text);
				$text = str_replace('[СТАТУС]',$jdata['status'],$text);
				$text = str_replace('[СУММА]',$order['OSum'],$text);
				$text = str_replace('[ПРОЕКТ]',Connect::$projectInfo['name'],$text);
				$mail->mail($order['Email'], $subject, $text);
				$outputMessage .= " email ok";
			}
		}
		if (!empty($jdata['telegram'])) {
			$text = "<b>ЗАКАЗ ОПЛАТА</b> №{$order['Id']}\n\n{$jdata['message']}\n\nстатус платежа: {$jdata['status']}\n\n#сайт_{$jdata['id']}";
			$tg = new Telegram();
			$res = $tg->send(Connect::$projectServices['telegram']['dev'],$text);
			$jdata = json_decode($res['response'],true);
			$outputMessage .= ($jdata['ok']===true) ? " telegram ok" : " telegram fail";
		}
		$outputMessage = trim($outputMessage);
		$response = [
			'message' => $outputMessage
		];
		return $tasks->update($request['Id'],$response,200);
	}
}