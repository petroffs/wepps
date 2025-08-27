<?php
namespace WeppsExtensions\Addons\RemoteServices;
use Curl\Curl;
use WeppsExtensions\Addons\RemoteServices\RemoteServices;

class Dadata extends RemoteServices {
	private $token = '';
	private $secret = '';
	
	public function __construct($settings=[]) {
		$this->curl = new Curl();
		$this->curl->setHeader('Content-Type', 'application/json;charset=UTF-8');
		$this->curl->setHeader('Accept', 'application/json;charset=UTF-8');
		$this->curl->setHeader('Authorization', 'Token '.$this->token);
		$this->settings = $settings;
	}
	
	/*
	 * API справочников: отделения Почты России
	 */
	public function getPostalCode($city) {
		$url = "https://suggestions.dadata.ru/suggestions/api/4_1/rs/suggest/postal_office";
		$body = '{"query": "'.addslashes($city).'"}';
		return $this->getResponse($url,$body);
	}
	
	/*
	 * Поиск Идентификатор города в СДЭК, Boxberry и DPD
	 */
	public function getDeliveryCode($cityKladrCode) {
		$url = "https://suggestions.dadata.ru/suggestions/api/4_1/rs/findById/delivery";
		$body = '{"query": "'.addslashes($cityKladrCode).'"}';
		return $this->getResponse($url,$body);
	}
}
?>