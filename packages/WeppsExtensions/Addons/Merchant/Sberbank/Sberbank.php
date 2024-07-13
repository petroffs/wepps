<?php
namespace WeppsExtensions\Addons\Merchant\Sberbank;

use WeppsCore\Utils\UtilsWepps;
use WeppsCore\Exception\ExceptionWepps;
use WeppsExtensions\Cart\CartUtilsWepps;
use WeppsCore\Connect\ConnectWepps;
use WeppsExtensions\Addons\Mail\MailWepps;

class SberbankWepps {
    private $login;
    private $password;
	private $get;
	private $output;
	private $date;
	private $url;
	function __construct($get) {
		
		$this->get = $get;
		$this->date = date("Y-m-d H:i:s");
			
		
		if (ConnectWepps::$projectDev['debug']==1) {
		    /*
		     * Тестовая среда
		     */
		    $this->login = '____-api';
		    $this->password = '___';
		    $this->url = "https://3dsec.sberbank.ru/payment/rest/";
		} else {
		    /*
		     * Боевая среда
		     */
    	    $this->login = '_____-api';
    	    $this->password = '____';
    	    $this->url = "https://securepayments.sberbank.ru/payment/rest/";
		}
		
	    $action = UtilsWepps::trim ( $this->get ['action'] );
		if ($action == '')
			ExceptionWepps::error404 ();
		
		switch ($action) {
		    case "form":
		        $sql = "select * from TradeOrders where Id='{$this->get['id']}'";
		        $res = ConnectWepps::$instance->fetch($sql);
		        if (!isset($res[0]['Id'])) {
		            ExceptionWepps::error404();
		        }
		        $orderContent = $res[0];
                $date = new \DateTime($orderContent['ODate']);
		        if ($orderContent['OBuyOrderId']=="") $orderContent['OBuyOrderId'] = $orderContent['Id']."_0";
		        $dateU = (int)substr($orderContent['OBuyOrderId'],strpos($orderContent['OBuyOrderId'],"_")+1)+1;
		        $dateU = ($dateU=="") ? 0 : $dateU;
		        
		        $orderIdU = "{$this->get['id']}_{$dateU}";
		        $sql = "update TradeOrders set OBuyOrderId='$orderIdU',OBuyMerchant='Sberbank' where Id= '{$this->get['id']}'";
                ConnectWepps::$instance->query($sql);
		        
		        $data = array();
		        $data['userName'] = $this->login;
		        $data['password'] = $this->password;
		        $data['orderNumber'] = urlencode($orderIdU);
		        $data['amount'] = $orderContent['Summ']*100;
		        $data['email'] = $orderContent['Email'];
		        $data['returnUrl'] = ConnectWepps::$projectDev['protocol'].ConnectWepps::$projectDev['host']."/ext/Addons/Merchant/Sberbank/Request.php?action=success";
		        $data['failUrl'] = ConnectWepps::$projectDev['protocol'].ConnectWepps::$projectDev['host']."/ext/Addons/Merchant/SberbankRequest.php?action=fail";
		        $data['description'] = "Оплата товаров по заказу №".CartUtilsWepps::setOrderNumber($orderContent['Id'])." от ".$date->format('d.m.Y H:i:s');
		        
		        
		        /*
		         * фискализация
		         */
		        
		        $sql = "select * from TradeClientsHistory where OrderId='{$orderContent['Id']}'";
		        $res = ConnectWepps::$instance->fetch($sql);
		        $positions = array();
		        foreach ($res as $key=>$value) {
		        	$positions[] = array(
		        			'positionId'=>$key+1,
		        			'name'=>$value['Name'],
		        			'quantity'=>array('value'=>$value['ItemQty'],'measure'=>'шт'),
		        			'itemCode'=>$value['ProductId'],
		        			'itemPrice'=>$value['Price']*100,
		        			'itemAmount'=>$value['Summ']*100,
		        			'tax'=>array("taxType"=>0,"taxSum"=>0),
		        			'itemAttributes'=>array('attributes'=>array(array("name"=>"paymentMethod","value"=>"1"),array("name"=>"paymentObject","value"=>"1"))),
		        	);
		        }
		        
		        $phone = UtilsWepps::phone($orderContent['Phone']);
		        
		        $orderBundle = array(
		        		'customerDetails'=>array('phone'=>"{$phone['view2']}",'email'=>$orderContent['Email']),
		        		'cartItems'=>array('items'=>$positions),
		        		//'totalAmount'=>$orderContent['Summ']
		        );
		        $orderBundleJson = json_encode($orderBundle,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT|JSON_NUMERIC_CHECK);
		        $data['orderBundle'] = $orderBundleJson;
		        $response = $this->gateway('register.do',$data);
		        if (isset($response['errorCode'])) {
		            echo 'Ошибка #' . $response['errorCode'] . ': ' . $response['errorMessage'];
		        } else {
		            $sql = "update TradeOrders set OBuyOrderIdResponse='{$response['orderId']}' where Id='{$this->get['id']}'";
		            ConnectWepps::$instance->query($sql);
		            header('Location: ' . $response['formUrl']);
		            exit();
		        }
		        break;
			case "success" :
			    $sql = "update TradeOrders set OBuySumm=Summ,
                        OBuyDate='{$this->date}' 
                        where OBuyOrderIdResponse='{$this->get['orderId']}' and OBuyMerchant='Sberbank'";
			    ConnectWepps::$instance->query($sql);
			    $this->output = array (
						'action' => $action,
						'error'> 0,
				);
				$_SESSION['merch'] = array();
				$_SESSION['merch']['MessageStatus'] = 1;
				$_SESSION['merch']['Color'] = "#75cc4a";
				$_SESSION['merch']['Message'] = "Платеж успешно проведен";
				header("location: /profile/orders.html");
				exit();
				
				break;
			case "fail" :
			    UtilsWepps::debug('fail',1);
				$this->output = array (
						'action' => $action,
						'error' => 1 
				);
				$_SESSION['merch'] = array();
				$_SESSION['merch']['MessageStatus'] = 0;
				$_SESSION['merch']['Color'] = "red";
				$_SESSION['merch']['Message'] = "Платеж не проведен";
				header("location: /profile/orders.html");
				exit();
				break;
			default :
				break;
		}
	}
	function output() {
		$res = $this->output;
		
		if (isset ( $res ['output'] ) && $res ['output'] != '') {
			header ( "Content-Type:text/xml; charset=utf-8" );
			echo $res ['output'];
		} else {
			echo '...';
		}
		
		if (isset ( $res ['error'] ) && $res['error']!=0 ) {
			$t = UtilsWepps::debug( $res, 0, false );
			$t .= UtilsWepps::debug ( $this->get, 0, false );
			$obj = new MailWepps('html');
			$obj->mail ( ConnectWepps::$projectDev['email'], "Платежи Сбербанк", $t );
		}
	}
	
	/**
	 * ФУНКЦИЯ ДЛЯ ВЗАИМОДЕЙСТВИЯ С ПЛАТЕЖНЫМ ШЛЮЗОМ
	 *
	 * Для отправки POST запросов на платежный шлюз используется
	 * стандартная библиотека cURL.
	 *
	 * ПАРАМЕТРЫ
	 *		method		Метод из API.
	 * 		data		Массив данных.
	 *
	 * ОТВЕТ
	 *		response	Ответ.
	 */
	
	function gateway($method, $data) {
	    $curl = curl_init(); // Инициализируем запрос
	    curl_setopt_array($curl, array(
	        CURLOPT_URL =>  $this->url.$method,
	        CURLOPT_RETURNTRANSFER => true, // Возвращать ответ
	        CURLOPT_POST => true, // Метод POST
	        CURLOPT_POSTFIELDS => http_build_query($data) // Данные в запросе
	    ));
	    $response = curl_exec($curl); // Выполненяем запрос
	    $response = json_decode($response, true); // Декодируем из JSON в массив
	    curl_close($curl); // Закрываем соединение
	    //$this->debug($response,1);
	    return $response; // Возвращаем ответ
	}
}

?>