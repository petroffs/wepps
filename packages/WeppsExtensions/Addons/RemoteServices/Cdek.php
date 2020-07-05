<?php

namespace PPSExtensions\Addons\RemoteServices;

use PPS\Connect\ConnectPPS;
use Curl\Curl;
use PPS\Utils\UtilsPPS;

/*
 * СДЕК
 * добавить логику ПВЗ
 */
class CdekPPS extends RemoteServicesPPS {
	private $login = '0f29bYnPm7pKRezEh3qHFk566Sc62jjR';
	private $password = 'qSJ5A4SKImvGFTltRXyM9fR5awbFJkX9';
	private $url = 'https://integration.cdek.ru';
	
	public function __construct($settings) {
		$this->curl = new Curl();
		$this->curl->setHeader('Content-Type', 'application/json;charset=UTF-8');
		$this->curl->setHeader('Content-Type', 'application/json; charset=utf-8');
		$this->settings = $settings;
	}
	
	public function getTariff($receiver,$sender=146) {
		$this->url = "http://api.cdek.ru/calculator/calculate_price_by_json.php";
		$date = date("Y-m-d",strtotime(date("Y-m-d",strtotime(date("Y-m-d")))." +5 day"));
		$arr = [
				"authLogin" => $this->login,
				"secure" => md5 ( $date . '&' . $this->password ),
				"version" => "1.0",
				"dateExecute" => $date,
				"senderCityId" => $sender,
				"receiverCityId" => $receiver,
				"currency" => "RUB",
				"tariffList" => [
						[
								"priority" => 1,
								"id" => $this->settings['tariff']
						]
				],
				"goods" => [
						[
								"weight" => $this->settings['weight'],
								"length" => $this->settings['length'],
								"width" => $this->settings['width'],
								"height" => $this->settings['height'],
						]
				]
		];
		$json = json_encode($arr,JSON_PRETTY_PRINT);
		$response = $this->getResponse($this->url,$json);
		if (!isset($response['response']['result']['price'])) {
			return array('price'=>0,'status'=>302,'message'=>'Требуется уточнение');
		}
		$price = $response['response']['result']['price'];
		$period = ($response['response']['result']['deliveryPeriodMin']==$response['response']['result']['deliveryPeriodMax']) ? $response['response']['result']['deliveryPeriodMin'] : "{$response['response']['result']['deliveryPeriodMin']}-{$response['response']['result']['deliveryPeriodMax']}";
		return array('price'=>$price,'status'=>200,'message'=>'OK','period'=>$period);
	}
	
	public function getCityId($city) {
	    $city = addslashes($city);
	    $sql = "select * from CitiesCdek where Name='$city'";
	    $res = ConnectPPS::$instance->fetch($sql);
	    if (!empty($res[0]['Id'])) {
	        return $res[0]['Id'];
	    } else {
	        /*
	         * Пересмотреть, возможно этот код легаси
	         * http://integration.cdek.ru/v1/location/cities/ - Не работает!!!
	         */
	        $url = "http://integration.cdek.ru/v1/location/cities/json?cityName=".urlencode($city);
	        $response = $this->getResponse($url);
	        if (!empty($response['response'][0]['cityCode'])) {
	            array_multisort(array_column($response['response'], 'paymentLimit'), SORT_ASC, $response['response']);
	            if (isset($response['response'][0])) {
	                return $response['response'][0]['cityCode'];
	            }
	        }
	    }
	}
	
	public function getCityPoints($city) {
		$cityId = $this->getCityId($city);
	    $url = "https://integration.cdek.ru/pvzlist/v1/json?cityid=$city";
	    $this->cache = 1;
	    $response = $this->getResponse($url);
	    //UtilsPPS::debugf($response);
	    return $response;
	}
}
?>