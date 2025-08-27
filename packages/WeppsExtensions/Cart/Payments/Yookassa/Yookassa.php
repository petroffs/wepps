<?php
namespace WeppsExtensions\Cart\Payments\Yookassa;

use WeppsCore\Connect;
use WeppsCore\Smarty;
use WeppsCore\Exception;
use WeppsCore\Logs;
use WeppsCore\Utils;
use WeppsExtensions\Addons\Jwt\Jwt;
use WeppsExtensions\Cart\CartTemplates;
use WeppsExtensions\Cart\CartUtils;
use WeppsExtensions\Cart\Payments\Payments;
use YooKassa\Client;

class Yookassa extends Payments
{
	private $client;
	private $shopId;
	private $secretKey;
	private $currency;
	private $vatCode;
	public function __construct(array $settings = [], CartUtils $cartUtils)
	{
		$alias = (Connect::$projectDev['debug']==1) ? 'dev':'pro';
		parent::__construct($settings, $cartUtils);
		$this->settings = $settings;
		$this->shopId = Connect::$projectServices['yookassa'][$alias]['shopId'];
		$this->secretKey = Connect::$projectServices['yookassa'][$alias]['secretKey'];
		$this->currency = Connect::$projectServices['yookassa'][$alias]['currency'];
		$this->vatCode = Connect::$projectServices['yookassa'][$alias]['vatCode'];
		$this->client = new Client();
		$this->client->setAuth($this->shopId, $this->secretKey);
	}
	public function getOperations($order): array
	{
		#$headers = $this->cartUtils->getHeaders();
		#$headers->js("/path.{$headers::$rand}.js");
		#$headers->css("/path.{$headers::$rand}.css");
		$sql = "select * from Payments where TableName='Orders' and TableNameId=? and Name='Yookassa' and IsPaid=1 and IsProcessed=1";
		$res = Connect::$instance->fetch($sql,[$order['Id']]);
		$tpl = 'Yookassa/Yookassa.tpl';
		return [
			'tpl' => $tpl,
			'data' => [
				'order' => $order,
				'payments' => $res
			]
		];
	}
	public function form()
	{
		if (empty($this->settings['id'])) {
			Exception::error404();
		}
		$sql = "select * from Orders where Alias=?";
		$res = Connect::$instance->fetch($sql,[$this->settings['id']]);
		## https://platform.wepps/cart/order.html?id=48cf279e-17c7-54bc-4999-499eb6feb56c
		## https://platform.wepps/ext/Cart/Payments/Yookassa/Request.php?action=form&id=48cf279e-17c7-54bc-4999-499eb6feb56c
		## https://platform.wepps/ext/Cart/Payments/Yookassa/Request.php?action=return&token=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ0eXAiOiJvcmQiLCJpZCI6NTIsInBheSI6MywiZXhwIjoxNzUxNjY5ODQ2fQ.y6K8Hk0nUbTqJAQFqR3KkY6_tq89wS9_OwkdQQRyLKw
		if (!isset($res[0]['Id'])) {
			Exception::error404();
		}
		$order = $res[0];
		$products = json_decode($order['JPositions'],true);
		$cartUtils = new CartUtils();
		$products = $cartUtils->getCartPositionsRecounter($products,$order['ODeliveryDiscount'],$order['OPaymentTariff'],$order['OPaymentDiscount']);
		//Utils::debug($products,1);
		$sql = "select * from Payments where TableName='Orders' and TableNameId=? and Name='Yookassa' and IsPaid=1 and IsProcessed=1";
		$res = Connect::$instance->fetch($sql,[$order['Id']]);
		
		if (!empty($res[0])) {
			$smarty = Smarty::getSmarty();
			$cartUtils = new CartUtils();
			$cartTemplates = new CartTemplates($smarty,$cartUtils);
			$data = [
				'status' => 200,
				'title' => 'Оплата уже проведена ранее!',
				'text' => [
					'id' => $order['Id']
				]
			];	
			$cartTemplates->page($data,__DIR__ .'/ReturnSuccess.tpl');
		}
		$sql = "select * from Payments where TableName='Orders' and TableNameId=? and Name='Yookassa' and IsPaid=0 and IsProcessed=0";
		$res = Connect::$instance->fetch($sql,[$order['Id']]);
		if (empty($res)) {
			$row = [
				'Name' => 'Yookassa',
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
			$prepare = Connect::$instance->prepare($row);
			$sql = "insert into Payments {$prepare['insert']}";
			Connect::$instance->query($sql,$prepare['row']);
			$id = Connect::$db->lastInsertId();
		} else {
			$id = $res[0]['Id'];
		}
		$jwt = new Jwt();
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
				'return_url' => Connect::$projectDev['protocol'] . Connect::$projectDev['host'] . '/ext/Cart/Payments/Yookassa/Request.php?action=return&token=' . $token,
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
		Connect::$instance->query($sql,[$mid,json_encode($paymentData,JSON_UNESCAPED_UNICODE),json_encode($jdata,JSON_UNESCAPED_UNICODE),$id]);
		return [
			'url' => $url
		];
	}
	public function return() {
		if (empty($this->settings['token'])) {
			Exception::error404();
		}
		$smarty = Smarty::getSmarty();
		$cartUtils = new CartUtils();
		$cartTemplates = new CartTemplates($smarty,$cartUtils);
		$jwt = new Jwt();
		$payload = $jwt->token_decode($this->settings['token']);		
		if ($payload['status']!=200) {
			$data = [
				'status' => $payload['status'],
				'title' => 'Ошибка',
				'text' => $payload['message']
			];	
			$cartTemplates->page($data,__DIR__ .'/ReturnError.tpl');
		}
		$sql = "select * from Payments where TableName='Orders' and TableNameId=? and Id=?";
		$res = Connect::$instance->fetch($sql,[$payload['payload']['id'],$payload['payload']['pay']]);
		if (empty($res[0])) {
			$data = [
				'status' => 404,
				'title' => 'Ошибка',
				'text' => 'Платеж не найден'
			];	
			$cartTemplates->page($data,__DIR__ .'/ReturnError.tpl');
		} else if ($res[0]['IsPaid']==1 && $res[0]['IsProcessed']==1) {
			$data = [
				'status' => 200,
				'title' => 'Оплата прошла успешно!',
				'text' => $payload['payload']
			];	
			$cartTemplates->page($data,__DIR__ .'/ReturnSuccess.tpl');
		}
		$paymentInfo = $this->client->getPaymentInfo($res[0]['MerchantId']);
		if ($paymentInfo->status=='succeeded') {
			$sql = "update Payments set IsPaid=1,IsProcessed=1,MerchantResponseDate=now() where Id=?";
			Connect::$instance->query($sql,[$res[0]['Id']]);
			$data = [
				'status' => 200,
				'title' => 'Оплата прошла успешно!',
				'text' => $payload['payload']
			];	
			$cartTemplates->page($data,__DIR__ .'/ReturnSuccess.tpl');
		} else {
			$data = [
				'status' => 200,
				'title' => 'Ошибка',
				'text' => 'Ошибка выясняется'
			];	
			$cartTemplates->page($data,__DIR__ .'/ReturnError.tpl');
		}
	}
	public function webhook() {
		$json = file_get_contents('php://input');
		$jdata = json_decode($json,true);
		if (empty($id = $jdata['object']['id'])) {
			Exception::error(400);
		}
		$logs = new Logs();
		$logs->add('yookassa',$jdata,'','','post');
		Exception::error(200);
	}
	public function processLog(array $request,Logs $logs) {
		$jdata = json_decode($request['BRequest'],true);
		if (empty($id = $jdata['object']['id'])) {
			$response = [
				'message' => 'no object'
			];
			return $logs->update((int)$request['Id'],$response,400);
		}
		$sql = "select Id from Payments where MerchantId=?";
		$res = Connect::$instance->fetch($sql,[$id]);
		if (empty($payment = @$res[0])) {
			$response = [
				'message' => 'no payment'
			];
			return $logs->update((int)$request['Id'],$response,400);
		}
		$row = [
			'IsProcessed' => 1,
		];
		switch (@$jdata['object']['status']) {
			case 'succeeded':
				$row['IsPaid'] = 1;
				$response = [
					'message' => 'payment ok',
					'status' => $jdata['object']['status'],
				];
				$status = 200;
				break;
			default:
				$response = [
					'message' => 'payment fail',
					'status' => @$jdata['object']['status']
				];
				$status = 400;
				break;
		}
		if (!empty($jdata['object']['metadata']['order_id'])) {
			$responsePayment = [
				'id' => (int) $jdata['object']['metadata']['order_id'],
				'message' => $this->getStatuses()[$response['status']],
				'status' => $response['status'],
				'email' => true,
				'telegram' => true
			];
			$logs->add('order-payment', $responsePayment,'','','');
		}
		$prepare = Connect::$instance->prepare($row,[
			'MerchantResponseDate' => [
				'fn' => 'now()',
				'rm' => 1
			]
		]);
		$sql = "update Payments set {$prepare['update']} where Id=:Id";
		$arr = array_merge($prepare['row'],['Id'=>$payment['Id']]);
		Connect::$instance->query($sql,$arr);
		return $logs->update((int)$request['Id'],$response,$status);
	}
	private function getStatuses() {
		return [
			'succeeded' => 'Оплата прошла успешно!',
			'3d_secure_failed' => 'Не пройдена аутентификация по 3-D Secure. При новой попытке оплаты вам следует использовать другое платежное средство или обратиться в банк за уточнениями.',
			'call_issuer' => 'Оплата данным платежным средством отклонена по неизвестным причинам. Вам следует обратиться в организацию, выпустившую платежное средство.',
			'canceled_by_merchant' => 'Платеж отменен по API при оплате в две стадии.',
			'card_expired' => 'Истек срок действия банковской карты. При новой попытке оплаты вам следует использовать другое платежное средство.',
			'country_forbidden' => 'Нельзя заплатить банковской картой, выпущенной в этой стране. При новой попытке оплаты вам следует использовать другое платежное средство.',
			'deal_expired' => 'Закончился срок жизни сделки.',
			'expired_on_capture' => 'Истек срок списания оплаты у двухстадийного платежа.',
			'expired_on_confirmation' => 'Истек срок оплаты.',
			'fraud_suspected' => 'Платеж заблокирован из-за подозрения в мошенничестве. При новой попытке оплаты вам следует использовать другое платежное средство',
			'general_decline' => 'Причина не детализирована.',
			'identification_required' => 'Превышены ограничения на платежи для кошелька ЮMoney.',
			'insufficient_funds' => 'Не хватает средств для оплаты.',
			'internal_timeout' => 'Технические неполадки на стороне ЮKassa: не удалось обработать запрос в течение 30 секунд.',
			'invalid_card_number' => 'Неправильно указан номер карты. При новой попытке оплаты вам следует ввести корректные данные',
			'invalid_csc' => 'Неправильно указан код CVV2 (CVC2, CID). При новой попытке оплаты вам следует ввести корректные данные.',
			'issuer_unavailable' => 'Организация, выпустившая платежное средство, недоступна. При новой попытке оплаты вам следует использовать другое платежное средство или повторить оплату позже.',
			'payment_method_limit_exceeded' => 'Исчерпан лимит платежей. При новой попытке оплаты вам следует использовать другое платежное средство или повторить оплату на следующий день.',
			'payment_method_restricted' => 'Запрещены операции данным платежным средством (например, карта заблокирована из-за утери, кошелек — из-за взлома мошенниками). Вам следует обратиться в организацию, выпустившую платежное средство.',
			'permission_revoked' => 'Нельзя провести безакцептное списание: вы отозвали разрешение на автоплатежи.',
			'unsupported_mobile_operator' => 'Нельзя заплатить с номера телефона этого мобильного оператора. При новой попытке оплаты вам следует использовать другое платежное средство',
		];
	}
}