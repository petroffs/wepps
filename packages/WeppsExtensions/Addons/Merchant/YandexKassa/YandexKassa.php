<?php

/*
 * В htaccess требуется прописать 
 * RewriteRule ^yandexkassa/(.+)/$ packages/WeppsExtensions/Addons/Merchant/YandexKassa/Request.php?action=$1&%{QUERY_STRING} [L]
 */

namespace WeppsExtensions\Addons\Merchant\YandexKassa;

use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\Utils\UtilsWepps;
use WeppsExtensions\Mail\MailWepps;
use YandexCheckout\Client;

use YandexCheckout\Model\Notification\NotificationSucceeded;
use YandexCheckout\Model\Notification\NotificationWaitingForCapture;
use YandexCheckout\Model\NotificationEventType;
use WeppsCore\Core\DataWepps;
use WeppsExtensions\Cart\CartUtilsWepps;

class YandexKassaWepps {
	private $shopId;
	private $secretKey;
	private $currency;
	private $get;
	private $date;
	private $output;
	
	function __construct($get) {
		$this->get = $get;
		$this->date = date("Y-m-d H:i:s");
	
		if (ConnectWepps::$projectDev['debug']==1) {
			/*
			 * Тестовая среда
			 */
			$this->shopId = '55538_TEST';
			$this->secretKey = 'test_Kkke3N';
			$this->currency = 'RUB';
		} else {
			/*
			 * Боевая среда
			 */
			$this->shopId = '____________';
			$this->secretKey = '____________________';
			$this->currency = '___________';
		}
		
		$action = UtilsWepps::getStringFormatted ( $this->get ['action'] );
		switch ($action) {
			case "form" :
				if (empty($this->get['id'])) {
					ExceptionWepps::error404();
				}
				
				/*
				 * Формирование перехода в платежную систему
				 */
				$this->output = '{"error":0}';
				
				/*
				 * Получить заказ и сформировать массив, записать ответ от системы (OBuyOrderIdResponse)
				 */
				$sql = "select * from TradeOrders where Id='{$this->get['id']}'";
				$res = ConnectWepps::$instance->fetch($sql);
				if (!isset($res[0]['Id'])) {
					ExceptionWepps::error404();
				}
				$order = $res[0];
				
				if ($order['OBuyOrderId']=="") $order['OBuyOrderId'] = $order['Id']."_0";
				$dateU = (int)substr($order['OBuyOrderId'],strpos($order['OBuyOrderId'],"_")+1)+1;
				$dateU = ($dateU=="") ? 0 : $dateU;
				
				$orderIdU = "{$this->get['id']}_{$dateU}";
				
				$client = new Client();
				$client->setAuth($this->shopId, $this->secretKey);
				$card = (!empty($_SESSION['user']['CardNum']))?$_SESSION['user']['CardNum']:'НЕТ';
				$payment = $client->createPayment(
						array(
								'amount' => array(
										'value' => $order['Summ'],
										'currency' => $this->currency,
								),
								'confirmation' => array(
										'type' => 'redirect',
								    'return_url' => ConnectWepps::$projectDev['protocol'].ConnectWepps::$projectDev['host'].'/yandexkassa/return/?token='.md5($order['Name'].$order['Email'].$orderIdU),
								),
								"payment_method_data"=> array(
									"type"=>"bank_card"
								),
								'capture' => true,
								'description' => "Заказ №{$order['Id']} на сумму {$order['Summ']} BYN. Карта лояльности {$card}"
						),
						uniqid('', true)
						);
				$sql = "update TradeOrders set OBuyOrderId='$orderIdU',OBuyOrderIdResponse='{$payment->jsonSerialize()['id']}',OBuyMerchant='YandexKassa' where Id= '{$this->get['id']}'";
				ConnectWepps::$instance->query($sql);
				$payment->jsonSerialize()['confirmation']['confirmation_url'];
				header("location: " . $payment->jsonSerialize()['confirmation']['confirmation_url']);
				break;
			case "return":
				/*
				 * Переход на страницу с указанием о статусе платежа
				 */
				if (empty($this->get['token'])) {
					$output = [
							'action' => 'payment',
							'message' => 'Ошибка',
							'error' => 2
					];
				} else {
					//$sql = "select * from TradeOrders where ";
					$obj = new DataWepps ( "TradeOrders" );
					$order = $obj->getMax ( "md5(concat(t.Name,t.Email,t.OBuyOrderId))='{$this->get['token']}'" ) [0];
					if (empty ( $order )) {
						$output = [
								'action' => 'payment',
								'message' => 'Заказ с указанным токеном не найден',
								'error' => 3
						];
					} else {
						//$source = file_get_contents('php://input' );
						//$response = $this->getNotification($source);
						if ($order['OBuySumm']==$order['Summ']) {
							$_SESSION['merch'] = [
									'MessageStatus'=>1,
									'Color'=>'#75cc4a',
									'Message'=>"Ваш заказ №{$order['Id']} успешно зарезервирован",
							];
							header('location: /cart/');
							exit();
						} else {
							$output = [
									'action' => 'payment',
									'message' => 'Сумма не совпадает',
									'error' => 4
							];
						}
					}
				}
				break;
			case "check":
				/*
				 * Получение статусов от системы
				 */
				$source = file_get_contents('php://input' );
				$response = $this->getNotification ( $source );
				UtilsWepps::debugf($response);
				break;
			case "test":
				/*
				 * Проверить заказ с ID=335911
				 * 325.88
				 */
				$source = '{
							  "type" : "notification",
							  "event" : "payment.succeeded",
							  "object" : {
							    "id" : "25306d4c-000f-5000-a000-1b3bbff919dc",
							    "status" : "succeeded",
							    "paid" : true,
							    "amount" : {
							      "value" : "101.00",
							      "currency" : "RUB"
							    },
							    "authorization_details" : {
							      "rrn" : "983904385378",
							      "auth_code" : "384080"
							    },
							    "captured_at" : "2019-10-09T22:05:14.073Z",
							    "created_at" : "2019-10-09T22:05:11.590Z",
							    "description" : "Заказ №1",
							    "metadata" : { },
							    "payment_method" : {
							      "type" : "bank_card",
							      "id" : "25366cf7-000f-5000-a000-1cf91487e03c",
							      "saved" : false,
							      "card" : {
							        "first6" : "555555",
							        "last4" : "4444",
							        "expiry_month" : "12",
							        "expiry_year" : "2020",
							        "card_type" : "MasterCard"
							      },
							      "title" : "Bank card *4444"
							    },
							    "recipient" : {
							      "account_id" : "642938",
							      "gateway_id" : "1627922"
							    },
							    "refundable" : true,
							    "refunded_amount" : {
							      "value" : "0.00",
							      "currency" : "RUB"
							    },
							    "test" : true
							  }
							}';
				
				$response = $this->getNotification($source);
				
				//UtilsWepps::debugf($response,1);
				
				if ($response['error']==0) {
					$_SESSION['merch'] = [
							'MessageStatus'=>1,
							'Color'=>'#75cc4a',
							'Message'=>$response['message'],
					];
					header('location: /cart/');
					exit();
					
				}
				
				
				
				$this->output = json_encode($response,JSON_UNESCAPED_UNICODE);
				break;
			default :
				$source = file_get_contents('php://input' );
				$response = $this->getNotification ( $source );
				UtilsWepps::debugf($response);
				break;
		}
		$this->output = json_encode($response,JSON_UNESCAPED_UNICODE);
	}
	public function getOutput() {
		return $this->output;
	}
	private function getNotification($source='') {
		if ($source=='') {
			$output = [
					'action' => 'payment',
					'message' => 'Ошибка',
					'error' => 2
			];
			return $output;
		}
		
		$body = json_decode ( $source, true );
		switch ($body ['event']) {
			case "payment.succeeded":
				/*
				 * Заказ
				 */
				$obj = new DataWepps ( "TradeOrders" );
				//$body['object']['payment_method']['id']
				//$body['object']['id']
				$order = $obj->getMax ( "t.OBuyOrderIdResponse='{$body['object']['id']}' and t.OBuyMerchant='YandexKassa'" ) [0];
				if (empty ( $order )) {
				
					$output = [ 
							'action' => 'payment',
							'message' => 'Заказ не найден',
							'error' => 1
					];
					return $output;
				}

				/*
				 * Записать информацию об оплате в БД
				 * Выслать уведомление магазину, клиенту
				 */
				$subject = "Платеж успешно проведен.";
				$message = "Платеж по заказу №{$order['Id']} успешно проведен.";
				$sql = "update TradeOrders set OBuySumm=Summ,
                        OBuyDate='{$this->date}'
                        where OBuyOrderIdResponse='{$body['object']['payment_method']['id']}' and OBuyMerchant='YandexKassa'";
				ConnectWepps::$instance->query ( $sql );
				$output = [ 
						'action' => 'payment',
						'message' => $subject,
						'error' => 0];
				$mail = new MailWepps();
				$mail->mail(ConnectWepps::$projectDev['email'], $subject, $message);
				CartUtilsWepps::sendOrderNotify($order);
				return $output;
				break;
			case "payment.waiting_for_capture":
				exit();
				break;
			case "payment.canceled":
				exit();
				break;
			case "refund.succeeded":
				exit();
				break;
			default:
				$output = [
				'action' => 'payment',
				'message' => 'Ошибка',
				'error' => 3
				];
				return $output;
				break;
		}
	}
}


?>