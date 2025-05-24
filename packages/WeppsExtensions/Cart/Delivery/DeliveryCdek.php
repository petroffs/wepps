<?php
namespace WeppsExtensions\Cart\Delivery;

use WeppsCore\Utils\CliWepps;
use WeppsCore\Utils\UtilsWepps;
use WeppsCore\Connect\ConnectWepps;
use Curl\Curl;
use WeppsExtensions\Cart\CartUtilsWepps;

class DeliveryCdekWepps extends DeliveryWepps
{
	private $account;
	private $password;
	private $url;
	private $office;
	private $tokenFilename;
	private $token;
	private $curl;
	private $counter = 0;
	public function __construct(array $settings, CartUtilsWepps $cartUtils)
	{
		parent::__construct($settings, $cartUtils);
		$this->url = ConnectWepps::$projectServices['cdek']['url'];
		$this->account = ConnectWepps::$projectServices['cdek']['account'];
		$this->password = ConnectWepps::$projectServices['cdek']['password'];
		$this->office = ConnectWepps::$projectServices['cdek']['office'];
		$this->tokenFilename = __DIR__ . '/files/cdek.conf';
		$this->settings = $settings;
		$f = file_get_contents($this->tokenFilename);
		$jdata = json_decode($f, true);
		if (empty($jdata) || date('U') >= @$jdata['lifetime']) {
			$this->getToken();
		} elseif (!empty($jdata['access_token'])) {
			$this->token = $jdata['access_token'];
		} else {
			UtilsWepps::debug('token error', 31);
			exit();
		}
		$this->curl = new Curl();
		$this->curl->setHeader('content-type', 'application/json;charset=UTF-8');
		$this->curl->setHeader('accept', 'application/json');
		$this->curl->setHeader('authorization', value: 'Bearer ' . $this->token);
	}

	public function getTariff(): array
	{
		return $output = [
			'status' => 200,
			'title' => $this->settings['Name'],
			'price' => $this->settings['Tariff'],
			'period' => '1-3'
		];
	}
	public function getOperations(): array
	{
		$headers = $this->cartUtils->getHeaders();
		$jdata = json_decode($this->settings['JSettings'],true);
		$tpl = 'OperationsNotice.tpl';
		$data = [];
		$allowBtn = false;
		$cart = $this->cartUtils->getCart();
		switch (@$jdata['tariff']) {
			case 136:
				$headers->css("/ext/Cart/Delivery/OperationsPickpoints.{$headers::$rand}.css");
				$headers->js("/ext/Cart/Delivery/OperationsPickpoints.{$headers::$rand}.js");
				$headers->js("/ext/Addons/YandexMaps/YandexMaps.{$headers::$rand}.js");
				$headers->css("/ext/Addons/YandexMaps/YandexMaps.{$headers::$rand}.css");
				$tpl = 'OperationsPickpoints.tpl';
				$data = [];
				#$from = ConnectWepps::$projectServices['cdek']['office']['sender'];
				$to = $cart['citiesId']??0;
				$sql = "select * from PointsCdek where CitiesId = ? limit 1000";
				$res = ConnectWepps::$instance->fetch($sql,[$to]);
				if (empty($res)) {
					break;
				}
				$zoom = 11;
				foreach ($res as $value) {
					$jdata = json_decode($value['JData'],true);
					$row = [
						'Id' => $value['Id'],
						'Name' => $value['Name'],
						'Code' => $jdata['code'],
						'Country' => $jdata['location']['country_code'],
						'City' => $jdata['location']['city'],
						'Address' => $jdata['location']['address'],
						'WorkTime' => $jdata['work_time'],
						'PostalCode' => $jdata['location']['postal_code'],
						'Phone'=>$jdata['phones'][0]['number'],
						'Email' => '',
						'Coords' => "{$jdata['location']['latitude']},{$jdata['location']['longitude']}",
						'MapZoom' => $zoom
					];
					array_push($data,$row);
				}
				break;
			case 137:
				$citiesById = $this->deliveryUtils->getCitiesById($cart['citiesId']);
				$headers->js("/ext/Cart/Delivery/OperationsAddress.{$headers::$rand}.js");
				$tpl = 'OperationsAddress.tpl';
				$data = [
					'deliveryCtiy' => $citiesById[0]
				];
				$allowBtn = true;
				break;
			default:

				break;
		}
		return [
			'title' => $this->settings['Name'],
			'ext' => $this->settings['DeliveryExt'],
			'tpl' => $tpl,
			'data' => $data,
			'active' => self::getOperationsActive($cart),
			'allowOrderBtn' => $allowBtn
		];
	}

	public function setPoints(): bool
	{
		$func = function (array $args) {
			$data = [
				'country_code' => 'RU',
			];
			$response = $this->curl->get($this->url . '/v2/deliverypoints', $data);
			if (empty($response->response)) {
				return false;
			}
			$jdata = json_decode($response->response, true);
			#$test = '[{"code":"SVT50","name":"SVT50, Севастополь, пр-т Генерала Острякова","uuid":"e901b773-839f-4195-9341-93bd1bb5eb54","address_comment":"Напротив ТЦ Московский","nearest_station":"Кинотеатр Москва","work_time":"Пн-Пт 10:00-20:00, Сб-Вс 10:00-18:00","phones":[{"number":"+79184256050"}],"email":"s.sitdikov@cdek.ru","note":"Напротив ТЦ Московский","type":"PVZ","owner_code":"CDEK","take_only":false,"is_handout":true,"is_reception":true,"is_dressing_room":true,"is_ltl":false,"have_cashless":true,"have_cash":true,"have_fast_payment_system":false,"allowed_cod":true,"office_image_list":[{"url":"https://gateway.cdek.ru/file-storage/web/object/office-photo/790a0229-42ce-40be-8480-50b3a2cf5460"},{"url":"https://gateway.cdek.ru/file-storage/web/object/office-photo/da880208-fe79-4d86-a78d-188627174a1a"},{"url":"https://gateway.cdek.ru/file-storage/web/object/office-photo/ce69f1e1-25fa-4b65-b3dc-184fe5f645a7"}],"work_time_list":[{"day":1,"time":"10:00/20:00"},{"day":2,"time":"10:00/20:00"},{"day":3,"time":"10:00/20:00"},{"day":4,"time":"10:00/20:00"},{"day":5,"time":"10:00/20:00"},{"day":6,"time":"10:00/18:00"},{"day":7,"time":"10:00/18:00"}],"work_time_exception_list":[{"date_start":"2025-03-18","date_end":"2025-03-18","time_start":"10:00","time_end":"18:00","is_working":true}],"weight_min":0.0,"weight_max":100.0,"work_time_exceptions":[{"date":"2025-03-18","time":"10:00/18:00","is_working":true}],"location":{"country_code":"RU","region_code":975,"region":"Севастополь","city_code":15256,"city":"Севастополь","fias_guid":"6fdecb78-893a-4e3f-a5ba-aa062459463b","postal_code":"299029","longitude":33.519646,"latitude":44.580578,"address":"пр-т Генерала Острякова, 33","address_full":"299029, Россия, Севастополь, Севастополь, пр-т Генерала Острякова, 33","city_uuid":"8c31a5d0-d594-47ca-bfb1-e3e3b8a849ba"},"fulfillment":false},{"code":"RBC2","name":"RBC2, Рубцовск, ул. Октябрьская","uuid":"ac3274aa-9fc4-4281-9f07-0e52f1f2ecdf","nearest_station":"ул. Октябрьская","work_time":"Пн-Пт 09:00-20:00, Сб-Вс 09:00-20:00","phones":[{"number":"+79230017300"}],"email":"rubtcovsk@edostavka.ru","type":"PVZ","owner_code":"CDEK","take_only":false,"is_handout":true,"is_reception":true,"is_dressing_room":true,"is_ltl":false,"have_cashless":true,"have_cash":true,"have_fast_payment_system":false,"allowed_cod":true,"site":"https://www.cdek.ru/contacts/g_rubcovsk_pr-kt_rubcovskiy_dom_17.html","office_image_list":[{"url":"https://gateway.cdek.ru/file-storage/web/object/office-photo/35a4e666-23e8-4687-9919-120dc7ebe550"},{"url":"https://gateway.cdek.ru/file-storage/web/object/office-photo/6e364183-1d00-417a-9f4f-f597c3f07a68"},{"url":"https://gateway.cdek.ru/file-storage/web/object/office-photo/85206d6a-db8d-4bc7-b69a-2b1667d83a27"}],"work_time_list":[{"day":1,"time":"09:00/20:00"},{"day":2,"time":"09:00/20:00"},{"day":3,"time":"09:00/20:00"},{"day":4,"time":"09:00/20:00"},{"day":5,"time":"09:00/20:00"},{"day":6,"time":"09:00/20:00"},{"day":7,"time":"09:00/20:00"}],"work_time_exception_list":[],"weight_min":0.0,"weight_max":1000.0,"location":{"country_code":"RU","region_code":2,"region":"Алтайский край","city_code":798,"city":"Рубцовск","fias_guid":"65db5c88-c65c-43f0-9c21-13e15a032d4a","postal_code":"658201","longitude":81.21582,"latitude":51.527237,"address":"ул. Октябрьская, 104","address_full":"658201, Россия, Алтайский край, Рубцовск, ул. Октябрьская, 104","city_uuid":"4d39d45a-912d-43a8-9a18-c5f8f88da4ed"},"fulfillment":false},{"code":"TMK1","name":"TMK1, Темрюк, ул. Анджиевского","uuid":"0e0135c1-d689-432d-a59b-9c9a7f8ce3c9","address_comment":"150 м. от остановки ПМК, в этом же помещении находится магазин Светофор.","nearest_station":"ПМК","work_time":"Пн-Вс 09:00-19:00","phones":[{"number":"+79385067000"}],"email":"k.kroshkin@cdek.ru","note":"150 м. от остановки ПМК, в этом же помещении находится магазин Светофор.","type":"PVZ","owner_code":"CDEK","take_only":false,"is_handout":true,"is_reception":true,"is_dressing_room":true,"is_ltl":true,"have_cashless":true,"have_cash":true,"have_fast_payment_system":true,"allowed_cod":true,"work_time_list":[{"day":1,"time":"09:00/19:00"},{"day":2,"time":"09:00/19:00"},{"day":3,"time":"09:00/19:00"},{"day":4,"time":"09:00/19:00"},{"day":5,"time":"09:00/19:00"},{"day":6,"time":"09:00/19:00"},{"day":7,"time":"09:00/19:00"}],"work_time_exception_list":[],"weight_min":0.0,"weight_max":100000.0,"location":{"country_code":"RU","region_code":7,"region":"Краснодарский край","city_code":1065,"city":"Темрюк","fias_guid":"3d6724a2-9641-44e6-bc69-097d93c01cb9","postal_code":"353505","longitude":37.411644,"latitude":45.258137,"address":"ул. Анджиевского, 1/3","address_full":"353505, Россия, Краснодарский край, Темрюк, ул. Анджиевского, 1/3","city_uuid":"ff569330-a203-4ffe-9746-98db9d7bbc82"},"fulfillment":true}]';
			#$jdata = json_decode($test,true);
			if (empty($jdata)) {
				return false;
			}
			ConnectWepps::$instance->query('truncate PointsCdek');
			$row = [
				'Name' => '',
				'Alias' => '',
				'JData' => '',
				'CitiesId' => '',
			];
			$prepare = ConnectWepps::$instance->prepare($row);
			$insert = ConnectWepps::$db->prepare("insert into PointsCdek {$prepare['insert']}");
			foreach ($jdata as $value) {
				$row = [
					'Name' => $value['name'],
					'Alias' => $value['code'],
					'JData' => json_encode($value, JSON_UNESCAPED_UNICODE),
					'CitiesId' => $value['location']['city_code'],
				];
				$insert->execute($row);
			}
		};
		ConnectWepps::$instance->transaction($func, []);
		return true;
	}
	public function setCities(int $page = 0)
	{
		$func = function (array $args) {
			$page = $args['page'] ?? 0;
			//$response = $this->curl->get($this->url . '/v2/location/cities?country_codes=RU&size=1000&page='.(string)$page);
			$url = $this->url . '/v2/location/cities?country_codes=RU&&size=1000&page=' . $page;
			$cli = new CliWepps();
			$cli->progress($page, 150);
			#$cli->info(text: $url);
			$response = $this->curl->get($url);
			if (empty($response->response)) {
				return false;
			}
			$jdata = json_decode($response->response, true);
			if (empty($jdata)) {
				if ($page > 1) {
					return true;
				}
				return false;
			}
			if ($page == 0) {
				ConnectWepps::$instance->query('truncate CitiesCdek');
			}
			$row = [
				'Id' => '',
				'Name' => '',
				'RegionsId' => '',
				//'JData' => '',
			];
			$prepare = ConnectWepps::$instance->prepare($row);
			$insert = ConnectWepps::$db->prepare("insert into CitiesCdek {$prepare['insert']}");
			foreach ($jdata as $value) {
				$row = [
					'Id' => $value['code'],
					'Name' => $value['city'],
					'RegionsId' => $value['region_code'],
					//'JData' => json_encode($value, JSON_UNESCAPED_UNICODE),
				];
				$insert->execute($row);
			}
		};
		ConnectWepps::$instance->transaction($func, ['page' => $page]);
		$page++;
		if ($page <= 150) {
			return self::setCities($page);
		}
		return true;
	}
	public function setRegions()
	{
		$func = function (array $args) {
			$data = [
				'country_codes' => 'RU',
			];
			$response = $this->curl->get($this->url . '/v2/location/regions', $data);
			if (empty($response->response)) {
				return false;
			}
			$jdata = json_decode($response->response, true);
			if (empty($jdata)) {
				return false;
			}
			ConnectWepps::$instance->query('truncate RegionsCdek');
			$row = [
				'Id' => '',
				'Name' => '',
				'JData' => '',
			];
			$prepare = ConnectWepps::$instance->prepare($row);
			$insert = ConnectWepps::$db->prepare("insert into RegionsCdek {$prepare['insert']}");
			foreach ($jdata as $value) {
				$row = [
					'Id' => $value['region_code'],
					'Name' => $value['region'],
					'JData' => json_encode($value, JSON_UNESCAPED_UNICODE),
				];
				$insert->execute($row);
			}
		};
		ConnectWepps::$instance->transaction($func, []);
		return true;
	}
	private function getToken()
	{
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
		$response = $curl->post($this->url . '/v2/oauth/token', $body);
		$jdata = json_decode($response->response, true);
		if (empty($jdata['access_token'])) {
			echo $response->response;
			exit();
		}
		$this->token = $jdata['access_token'];
		$jdata['lifetime'] = date('U') + $jdata['expires_in'] - 300;
		$this->curl = new Curl();
		$this->curl->setHeader('authorization', 'Bearer ' . $this->token);
		file_put_contents($this->tokenFilename, json_encode($jdata), JSON_UNESCAPED_UNICODE);
		$this->counter++;
	}



	/**
	 * ! remove/update
	 */

	public function getOffices($cityId = 137)
	{
		$country = ($this->settings['country'] == 'Belarus') ? 'BY' : 'RU';
		$response = $this->curl->get($this->url . "/deliverypoints?country_code={$country}&city_code={$this->settings['cityId']}&is_dressing_room=1");
		$jdata = json_decode($response->response, true);
		if (!empty($jdata['requests'][0]['errors'][0]['code']) && $jdata['requests'][0]['errors'][0]['code'] == 'v2_token_expired' && $this->counter <= 1) {
			$this->getToken();
			$this->counter++;
			return $this->getOffices();
		}
		return $jdata;
	}
	/*
	 * ПВЗ для тарифа 136
	 */
	public function getExtension()
	{
		if ($this->settings['tariff'] != 136) {
			return [];
		}
		$points = $this->getOffices();
		$output = [];
		foreach ($points as $value) {
			#$jdata = json_decode($value['Descr'],true);
			$row = [
				'code' => $value['code'],
				'postalCode' => $value['location']['postal_code'],
				'name' => $value['name'],
				'workTime' => $value['work_time_list'][0]['time'],
				'coordX' => str_replace(',', '.', $value['location']['latitude']),
				'coordY' => str_replace(',', '.', $value['location']['longitude']),
				'isDressingRoom' => $value['is_dressing_room'],
				'email' => @$value['email'],
				'phone' => $value['phones'][0]['number'],
				'city' => $value['location']['city'],
				'address' => $value['location']['address'],
			];
			array_push($output, $row);
		}
		return $output;
	}
}