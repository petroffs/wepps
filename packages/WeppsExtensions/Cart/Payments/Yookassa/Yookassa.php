<?php
namespace WeppsExtensions\Cart\Payments\Yookassa;

use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\Utils\UtilsWepps;
use WeppsExtensions\Addons\Jwt\JwtWepps;
use WeppsExtensions\Cart\CartUtilsWepps;
use WeppsExtensions\Cart\Payments\PaymentsWepps;
use YooKassa\Client;

/* if (!function_exists('autoLoader')) {
	throw new \RuntimeException(
        'This file should be loaded via autoLoader.'
    );
} */

class YookassaWepps extends PaymentsWepps
{
	private $client;
	private $shopId;
	private $secretKey;
	private $currency;
	private $vatCode;
	public function __construct(array $settings = [], CartUtilsWepps $cartUtils)
	{
		$alias = (ConnectWepps::$projectDev['debug']==1) ? 'dev':'pro';
		parent::__construct($settings, $cartUtils);
		$this->settings = $settings;
		$this->shopId = ConnectWepps::$projectServices['yookassa'][$alias]['shopId'];
		$this->secretKey = ConnectWepps::$projectServices['yookassa'][$alias]['secretKey'];
		$this->currency = ConnectWepps::$projectServices['yookassa'][$alias]['currency'];
		$this->vatCode = ConnectWepps::$projectServices['yookassa'][$alias]['vatCode'];
		$this->client = new Client();
		$this->client->setAuth($this->shopId, $this->secretKey);
	}
	public function getOperations($order): array
	{
		#$headers = $this->cartUtils->getHeaders();
		#$headers->js("/path.{$headers::$rand}.js");
		#$headers->css("/path.{$headers::$rand}.css");
		$tpl = 'Yookassa/Yookassa.tpl';
		return [
			'tpl' => $tpl,
			'data' => [
				'order' => $order
			]
		];
	}
	public function form()
	{
		if (empty($this->settings['id'])) {
			ExceptionWepps::error404();
		}
		$sql = "select * from Orders where Alias=?";
		$res = ConnectWepps::$instance->fetch($sql,[$this->settings['id']]);
		## https://platform.wepps/ext/Cart/Payments/PaymentsYookassa/Request.php?action=form&id=48cf279e-17c7-54bc-4999-499eb6feb56c
		## https://platform.wepps/ext/Cart/Payments/Yookassa/Request.php?action=return&token=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ0eXAiOiJvcmQiLCJpZCI6NTIsInBheSI6MywiZXhwIjoxNzUxNjY5ODQ2fQ.y6K8Hk0nUbTqJAQFqR3KkY6_tq89wS9_OwkdQQRyLKw
		if (!isset($res[0]['Id'])) {
			ExceptionWepps::error404();
		}
		$order = $res[0];
		$products = json_decode($order['JPositions'],true);
		$cartUtils = new CartUtilsWepps();
		$products = $cartUtils->getCartPositionsRecounter($products,$order['ODeliveryDiscount'],$order['OPaymentTariff'],$order['OPaymentDiscount']);
		$sql = "select * from Payments where TableName='Orders' and TableNameId=? and Name='Yookassa' and IsPaid=0 and IsProcessed=0";
		$res = ConnectWepps::$instance->fetch($sql,[$order['Id']]);
		if (empty($res)) {
			$row = [
				'Name' => 'Оплата Yookassa',
				'PriceTotal' => $order['OSum'],
				'IsPaid' => 0,
				'IsProcessed' => 0,
				'TableName' => 'Orders',
				'TableNameId' => $order['Id'],
				'JData' => '',
				'MerchantId' => '',
				'MerchantRequest' => '',
				'MerchantDate' => date('Y-m-d H:i:s'),
			];
			$prepare = ConnectWepps::$instance->prepare($row);
			$sql = "insert into Payments {$prepare['insert']}";
			ConnectWepps::$instance->query($sql,$prepare['row']);
			$id = ConnectWepps::$db->lastInsertId();
		} else {
			$id = $res[0]['Id'];
		}
		
		$jwt = new JwtWepps();
		$token = $jwt->token_encode([
			'typ' => 'ord',
			'id' => $order['Id'],
			'pay' => $id
		],3600);
		$items = [];
		foreach ($products as $value) {
			$row = [
				'description' => $value['name'],
				'quantity' => doubleval($value['quantity']),
				'amount' => [
					'value' => doubleval($value['priceTotal']),
					'currency' => $this->currency
				],
				'vat_code' => $this->vatCode,
				'measure' => 'piece',
				'payment_subject' => 'commodity',
				'payment_mode' => 'full_payment',
			];
			array_push($items, $row);
		}
		if ($order['ODeliveryTariff']>0) {
			$row = [
				'description' => 'Доставка',
				'quantity' => 1,
				'amount' => [
					'value' => doubleval($order['ODeliveryTariff']),
					'currency' => $this->currency
				],
				'vat_code' => $this->vatCode,
				'measure' => 'piece',
				'payment_subject' => 'service',
				'payment_mode' => 'full_payment',
			];
			array_push($items, $row);
		}
		$paymentData = [
			'amount' => [
				'value' => $order['OSum'],
				'currency' => $this->currency,
			],
			'confirmation' => [
				'type' => 'redirect',
				'return_url' => ConnectWepps::$projectDev['protocol'] . ConnectWepps::$projectDev['host'] . '/ext/Cart/Payments/Yookassa/Request.php?action=return&token=' . $token,
			],
			/* "payment_method_data" => [
				"type" => "bank_card"
			], */
			'capture' => true,
			'description' => "Заказ №{$order['Id']} на сумму {$order['OSum']} ₽",
			'metadata' => [
				'order_id' => (string) $order['Id']
			],
			'receipt' => [
				'customer' => [
					'full_name' => $order['Name'],
					'email' => $order['Email'],
					'phone' => $order['Phone'],
				],
				'items' => $items
			]
		];
		$payment = $this->client->createPayment($paymentData, md5($order['Alias'].rand()));
		$jdata = $payment->jsonSerialize();
		$url = $jdata['confirmation']['confirmation_url'];
		$mid = $jdata['id'];
		$sql = "update Payments set MerchantId=?,MerchantRequest=?,MerchantResponse=? where Id=?";
		ConnectWepps::$instance->query($sql,[$mid,json_encode($paymentData,JSON_UNESCAPED_UNICODE),json_encode($jdata,JSON_UNESCAPED_UNICODE),$id]);
		#UtilsWepps::debug(1,1);
		return [
			'url' => $url
		];
	}
	public function return() {
		if (empty($this->settings['token'])) {
			ExceptionWepps::error404();
		}
		$jwt = new JwtWepps();
		$payload = $jwt->token_decode($this->settings['token']);
		if ($payload['status']!=200) {
			return [];
		}
		$sql = "select * from Payments where TableName='Orders' and TableNameId=? and Id=?";
		$res = ConnectWepps::$instance->fetch($sql,[$payload['payload']['id'],$payload['payload']['pay']]);
		
		if (empty($res[0])) {
			return [];
		}
		$paymentInfo = $this->client->getPaymentInfo($res[0]['MerchantId']);
		#UtilsWepps::debug(,2);
		if ($paymentInfo->status=='succeeded') {
			$sql = "update Payments set IsPaid=1,IsProcessed=1,MerchantResponseDate=now() where Id=?";
			ConnectWepps::$instance->query($sql,[$res[0]['Id']]);
		} else {
			return [];
		}
		UtilsWepps::debug(1,1);
		
	}
	public function check() {
		UtilsWepps::debug('check',1);
	}
	public function test() {
		UtilsWepps::debug('test',1);
	}
}