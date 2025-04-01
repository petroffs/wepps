<?php
namespace WeppsExtensions\Cart\Delivery;

use WeppsCore\Utils\UtilsWepps;
use WeppsCore\Connect\ConnectWepps;
use Curl\Curl;

class DeliveryCdekWepps {
	private $account;
	private $password;
	private $url;
	private $office;
	private $token;
	private $counter=0;
	
	public function __construct($settings) {
		$this->url = ConnectWepps::$projectServices['cdek']['url'];
		$this->account = ConnectWepps::$projectServices['cdek']['account'];
		$this->password = ConnectWepps::$projectServices['cdek']['password'];
		$this->office = ConnectWepps::$projectServices['cdek']['office'];
		$this->tokenFilename = __DIR__ . '/files/cdek.conf';
		$this->settings = $settings;
		$f = file_get_contents($this->tokenFilename);
		$jdata = json_decode($f,true);	
		if (empty($jdata) || date('U')>=@$jdata['lifetime']) {
			$this->getToken();
		} elseif (!empty($jdata['access_token'])) {
			$this->token = $jdata['access_token'];
		} else {
			UtilsWepps::debug('token error',31);
			exit();
		}
		$this->curl = new Curl();
		$this->curl->setHeader('content-type', 'application/json;charset=UTF-8');
		$this->curl->setHeader('accept', 'application/json');
		$this->curl->setHeader('authorization', 'Bearer '.$this->token);
	}
	public function getRegions() {
		$data = [
				'country_codes' => 'RU',
		];
		$response = $this->curl->get($this->url.'/v2/location/regions',$data);
		return $response->response;
	}
	public function getCities() {
		$data = [
				'country_codes' => 'RU',
		];
		$response = $this->curl->get($this->url.'/v2/location/cities',$data);
		return $response->response;
	}
	private function getToken() {
		$curl = new Curl();
		$curl->setHeader('content-type', 'application/x-www-form-urlencoded');
		#$curl->setHeader('content-type', 'application/json;charset=UTF-8');
		#$curl->setHeader('accept', 'application/json');
		$body = [
				'grant_type' => 'client_credentials',
				'client_id' => $this->account,
				'client_secret' => $this->password
		];
		;
		$response = $curl->post($this->url.'/v2/oauth/token',$body);
		$jdata = json_decode($response->response,true);
		if (empty($jdata['access_token'])) {
			echo $response->response;
			exit();
		}
		$this->token = $jdata['access_token'];
		$jdata['lifetime'] = date('U')+$jdata['expires_in']-300;
		$this->curl = new Curl();
		$this->curl->setHeader('authorization', 'Bearer '.$this->token);
		file_put_contents($this->tokenFilename,json_encode($jdata),JSON_UNESCAPED_UNICODE);
		$this->counter++;
	}
	public function getTariff() {
		$this->url = "https://api.cdek.ru/v2/calculator/tariff";
		$this->settings['weight'] = 1;
		$date = date("Y-m-d\TH:i:s+0300",strtotime(date("Y-m-d 20:00:00",strtotime(date("Y-m-d 20:00:00")))." +2 day"));
		$jdata = [
				'type' => "1",
				'date' => $date,
				'currency' => "1",
				'tariff_code' => (string) $this->settings['tariff'],
				'from_location' => [
						"code" => (int) $this->office
				],
				'to_location' => [
						"code" => (int) $this->settings['cityId']
				],
				'services' => [
						[
								'code' => 'INSURANCE',
								'parameter' => (string) $this->settings['summ'].".0"
						],
						/* [
						 'code' => 'SMS'
						 ], */
				],
				'packages' => [
						'weight' => (int) $this->settings['weight'] * 1000,
						'length' => (int) $this->settings['length'],
						'width' => (int) $this->settings['width'],
						'height' => (int) $this->settings['height'],
				],
		];
		$json = json_encode($jdata,JSON_UNESCAPED_UNICODE);
		$response = $this->getResponse($this->url,$json);
		$period = ($response['response']['calendar_min']==$response['response']['calendar_max']) ? $response['response']['calendar_max'] : "{$response['response']['calendar_min']}-{$response['response']['calendar_max']}";
		$price = round($response['response']['total_sum']/5)*5;
		if (!empty($this->settings['freelevel']) && $this->settings['freelevel']<=$this->settings['summ']) {
			if (isset($this->settings['cityRemote']) && $this->settings['cityRemote']==0) {
				return array('price'=>0,'status'=>200,'message'=>'OK','period'=>$period);
			}
		}
		return array('price'=>$price,'status'=>200,'message'=>'OK','period'=>$period);
	}
	public function getCityId($city) {
		$city = addslashes($city);
		$sql = "select * from CitiesCdek where Name='$city'";
		$res = ConnectWepps::$instance->fetch($sql);
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
	public function getCityPoints($cityId) {
		$url = "https://integration.cdek.ru/pvzlist/v1/json?cityid=$cityId";
		$response = $this->getResponse($url);
		return $response;
	}
	public function getOffices($cityId=137) {
		$country = ($this->settings['country']=='Belarus')?'BY':'RU';
		$response = $this->curl->get($this->url."/deliverypoints?country_code={$country}&city_code={$this->settings['cityId']}&is_dressing_room=1");
		$jdata = json_decode($response->response,true);
		if (!empty($jdata['requests'][0]['errors'][0]['code']) && $jdata['requests'][0]['errors'][0]['code']=='v2_token_expired' && $this->counter<=1) {
			$this->getToken();
			$this->counter++;
			return $this->getOffices();
		}
		return $jdata;
	}
	/*
	 * ПВЗ для тарифа 136
	 */
	public function getExtension() {
		if ($this->settings['tariff']!=136) {
			return array();
		}
		$points = $this->getOffices();
		$output = [];
		foreach ($points as $value) {
			#$jdata = json_decode($value['Descr'],true);
			$row = [
					'code'=>$value['code'],
					'postalCode'=>$value['location']['postal_code'],
					'name'=>$value['name'],
					'workTime'=>$value['work_time_list'][0]['time'],
					'coordX'=> str_replace(',', '.', $value['location']['latitude']),
					'coordY'=>str_replace(',', '.', $value['location']['longitude']),
					'isDressingRoom'=>$value['is_dressing_room'],
					'email'=>@$value['email'],
					'phone'=>$value['phones'][0]['number'],
					'city'=>$value['location']['city'],
					'address'=>$value['location']['address'],
			];
			array_push($output, $row);
		}
		return $output;
	}
	public function setCities() {
		#https://api.cdek.ru/v2/location/cities
		$country = 'RU';
		$response = $this->curl->get($this->url."/location/cities?country_codes={$country}&size=10000&page=14");
		$jdata = json_decode($response->response,true);
		UtilsWepps::debug($jdata,2);
	}
}

