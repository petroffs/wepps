<?php
namespace WeppsExtensions\Cart\Payments\PaymentsYookassa;

use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\Utils\UtilsWepps;
use WeppsExtensions\Cart\CartUtilsWepps;
use WeppsExtensions\Cart\Payments\PaymentsWepps;

/* if (!function_exists('autoLoader')) {
	throw new \RuntimeException(
        'This file should be loaded via autoLoader.'
    );
} */

class PaymentsYookassaWepps extends PaymentsWepps
{
	public function __construct(array $settings = [], CartUtilsWepps $cartUtils)
	{
		parent::__construct($settings, $cartUtils);
		$this->settings = $settings;
	}
	public function getOperations($order): array
	{
		#$headers = $this->cartUtils->getHeaders();
		#$headers->js("/path.{$headers::$rand}.js");
		#$headers->css("/path.{$headers::$rand}.css");
		$tpl = 'PaymentsYookassa/PaymentsYookassa.tpl';
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
		UtilsWepps::debug($res,1);


		if (!isset($res[0]['Id'])) {
			ExceptionWepps::error404();
		}
		$order = $res[0];

		if ($order['OBuyOrderId'] == "")
			$order['OBuyOrderId'] = $order['Id'] . "_0";
		$dateU = (int) substr($order['OBuyOrderId'], strpos($order['OBuyOrderId'], "_") + 1) + 1;
		$dateU = ($dateU == "") ? 0 : $dateU;
		$orderIdU = "{$this->get['id']}_{$dateU}";

		$sql = "select * from TradeClientsHistory where OrderId='{$order['Id']}'";
		$res = ConnectWepps::$instance->fetch($sql);
		$goods = [];
		foreach ($res as $value) {
			$row = [
				'description' => $value['Name'],
				'quantity' => doubleval($value['ItemQty']),
				'amount' => array(
					'value' => doubleval($value['Price']),
					'currency' => 'RUB'
				),
				'vat_code' => 1,
				'measure' => 'piece',
				'payment_subject' => (stristr($value['Name'], "Доставка")) ? 'service' : 'commodity',
				'payment_mode' => 'full_payment',
				#'payment_mode' => 'full_prepayment',
			];
			array_push($goods, $row);
		}
		$paymentData = array(
			'amount' => array(
				'value' => $order['Summ'],
				'currency' => $this->currency,
			),
			'confirmation' => array(
				'type' => 'redirect',
				'return_url' => ConnectWepps::$projectDev['protocol'] . ConnectWepps::$projectDev['host'] . '/ext/Cart/Payments/RequestYookassa.php?action=return&token=' . md5($order['Name'] . $order['Email'] . $orderIdU),
			),
			"payment_method_data" => array(
				"type" => "bank_card"
			),
			'capture' => true,
			'description' => "Заказ №{$order['Id']} на сумму {$order['Summ']} ₽",
			'metadata' => array(
				'order_id' => (string) $order['Id']
			),
			'receipt' => array(
				'customer' => array(
					'full_name' => $order['Name'],
					'email' => $order['Email'],
					'phone' => $order['Phone'],
				),
				'items' => $goods
			)
		);
		#UtilsPPS::debug($paymentData,1);
		$payment = $client->createPayment($paymentData, uniqid('', true));
		$payment->jsonSerialize()['confirmation']['confirmation_url'];
		$sql = "update TradeOrders set OBuyOrderId='$orderIdU',OBuyOrderIdResponse='{$payment->jsonSerialize()['id']}',OBuyMerchant='Yookassa' where Id='{$this->get['id']}'";
		ConnectPPS::$instance->query($sql);
		header("location: " . $payment->jsonSerialize()['confirmation']['confirmation_url']);

	}
	public function return() {
		UtilsWepps::debug('return',1);
	}
	public function check() {
		UtilsWepps::debug('check',1);
	}
	public function test() {
		UtilsWepps::debug('test',1);
	}
}