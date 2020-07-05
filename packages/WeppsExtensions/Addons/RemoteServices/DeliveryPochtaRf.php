<?php

namespace PPSExtensions\Addons\RemoteServices;

use Curl\Curl;
use PPS\Utils\UtilsPPS;

class DeliveryPochtaRfPPS extends RemoteServicesPPS {
	private $token 		= '';
	private $auth 		= '';
	private $protocol 	= 'https://';
	private $host 		= 'otpravka-api.pochta.ru';
	private $from 		= "630089";
	
	public function __construct($settings) {
		$this->curl = new Curl();
		$this->curl->setHeader('Content-Type', 'application/json;charset=UTF-8');
		$this->curl->setHeader('Accept', 'application/json;charset=UTF-8');
		$this->curl->setHeader('Authorization', 'AccessToken '.$this->token);
		$this->curl->setHeader('X-User-Authorization', 'Basic '.$this->auth);
		$this->settings = $settings;
	}
	
	public function getCleanAddress($address="") {
		$path = "/1.0/clean/address";
		$body = '[{
			"id" : "adr1",
			"original-address" : "'.$address.'"
		}]';
		return $this->getResponse($this->getUrl($path),$body);
	}

	public function getTariff($to=null) {
		$path = "/1.0/tariff";
		$body = ' {
			"index-from": "'.$this->from.'",
			"index-to": "'.$to.'",
			"mail-category": "WITH_DECLARED_VALUE_AND_CASH_ON_DELIVERY",
			"mail-type": "POSTAL_PARCEL",
			"mass": '. $this->settings['weight'] * 1000 .',
			"dimension": {
				"height": '.$this->settings['height'].',
				"length": '.$this->settings['length'].',
				"width": '.$this->settings['width'].'
			},
			"fragile": "true",
			"declared-value": '.$this->settings['summ'].'
		}';
		$response = $this->getResponse($this->getUrl($path),$body);
		if (!isset($response['response']['total-rate'])) {
			return array('price'=>0,'status'=>302,'message'=>'Требуется уточнение');
		}
		$response['response']['delivery-time']['max-days'] = (!empty($response['response']['delivery-time']['max-days'])) ? $response['response']['delivery-time']['max-days'] : '';
		$period = (isset($response['response']['delivery-time']['min-days'])) ? "{$response['response']['delivery-time']['min-days']}-{$response['response']['delivery-time']['max-days']}" : $response['response']['delivery-time']['max-days'];
		$price = round(($response['response']['total-rate']+$response['response']['total-vat'])/(100*5))*5;
		return array('price'=>$price,'status'=>200,'message'=>'OK','period'=>$period);
	}
	
	public function getTariffEms($to=null) {
		$path = "/1.0/tariff";
		$body = ' {
			"index-from": "'.$this->from.'",
			"index-to": "'.$to.'",
			"mail-category": "WITH_DECLARED_VALUE_AND_CASH_ON_DELIVERY",
			"mail-type": "EMS",
			"mass": 500,
			"dimension": {
				"height": '.$this->settings['height'].',
				"length": '.$this->settings['length'].',
				"width": '.$this->settings['width'].'
			},
			"declared-value": '.$this->settings['summ'].'
		}';
		return $this->getResponse($this->getUrl($path),$body);
	}
	
	public function getCityId($city) {
		$path = "/postoffice/1.0/settlement.offices.codes?settlement=".urlencode($city)."";
		return $this->getResponse($this->getUrl($path));
	}
	
	private function getUrl($path) {
		return $this->protocol . $this->host . $path;
	}
}

?>