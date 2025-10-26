<?php
namespace WeppsExtensions\Cart\Delivery\Cdek;

use WeppsCore\Cli;
use WeppsCore\Exception;
use WeppsCore\Utils;
use WeppsCore\Connect;
use Curl\Curl;
use WeppsCore\Validator;
use WeppsExtensions\Cart\CartUtils;
use WeppsExtensions\Cart\Delivery\Delivery;

/**
 * Класс интеграции с API CDEK для расчета доставки и управления способами получения.
 * 
 * Использует OAuth 2.0 для аутентификации, кэширует результаты API-запросов и синхронизирует 
 * данные о пунктах выдачи, городах и регионах.
 * 
 * @package Delivery
 * @author Ваше имя
 * @version 1.0
 */
class Cdek extends Delivery
{
	private $account;
	private $password;
	private $url;
	private $office;
	private $tokenFilename;
	private $token;
	private $curl;
	private $counter = 0;
	public function __construct(array $settings, CartUtils $cartUtils)
	{
		parent::__construct($settings, $cartUtils);
		$this->url = Connect::$projectServices['cdek']['url'];
		$this->account = Connect::$projectServices['cdek']['account'];
		$this->password = Connect::$projectServices['cdek']['password'];
		$this->office = Connect::$projectServices['cdek']['office'];
		$this->tokenFilename = __DIR__ . '/files/cdek.conf';
		$this->settings = $settings;
		$this->getToken();
	}

	public function getTariff(): array
	{
		$cartSummary = $this->cartUtils->getCartSummary();
		if (empty($cartSummary)) {
			return [];
		}
		$jsettings = json_decode($this->settings['JSettings'], true);
		$jdata = [
			'tariff_code' => (int) $jsettings['tariff'],
			'from_location' => [
				'code' => (int) Connect::$projectServices['cdek']['office']['sender']
			],
			'to_location' => [
				'code' => (int) $this->settings['CitiesId']
			],
			'services' => [
				[
					'code' => 'INSURANCE',
					'parameter' => (string) $cartSummary['sumActive'] . ".0"
				],
				// [
				// 	'code' => 'SMS'
				// ],
			],
			'packages' => [
				'weight' => (int) $jsettings['weight'] * 1000,
				'length' => (int) $jsettings['length'],
				'width' => (int) $jsettings['width'],
				'height' => (int) $jsettings['height']
			]
		];
		$json = json_encode($jdata, JSON_UNESCAPED_UNICODE);
		$hash = md5($json);
		if (empty($response = $this->cartUtils->getMemcached()->get($hash))) {
			$url = Connect::$projectServices['cdek']['url'] . "/v2/calculator/tariff";
			$response = $this->curl->post($url, $json)->response;
			$response = json_decode($response, true);
			$this->cartUtils->getMemcached()->set($hash, $response, 86400);
		}
		if (empty($response['calendar_min'])) {
			return [];
		}
		$period = ($response['calendar_min'] == $response['calendar_max']) ? $response['calendar_max'] : "{$response['calendar_min']}-{$response['calendar_max']}";
		$price = round($response['total_sum'] / 5) * 5;
		if ($this->settings['FreeLevel'] > 0 && $this->settings['FreeLevel'] <= $cartSummary['sumActive']) {
			$price = 0;
		}
		return [
			'status' => 200,
			'title' => $this->settings['Name'],
			'price' => Utils::round($price),
			'period' => $period
		];
	}
	public function getOperations(): array
	{
		$headers = $this->cartUtils->getHeaders();
		$jdata = json_decode($this->settings['JSettings'], true);
		$tpl = 'OperationsNotice.tpl';
		$data = [];
		$allowBtn = false;
		$cart = $this->cartUtils->getCart();
		switch (@$jdata['tariff']) {
			case 136:
				$headers->css("/ext/Cart/Delivery/Cdek/Pickpoints.{$headers::$rand}.css");
				$headers->js("/ext/Cart/Delivery/Cdek/Pickpoints.{$headers::$rand}.js");
				$headers->js("/ext/Addons/YandexMaps/YandexMaps.{$headers::$rand}.js");
				$headers->css("/ext/Addons/YandexMaps/YandexMaps.{$headers::$rand}.css");
				$tpl = 'Cdek/Pickpoints.tpl';
				$data = [];
				#$from = Connect::$projectServices['cdek']['office']['sender'];
				$to = $cart['citiesId'] ?? 0;
				$sql = "select * from PointsCdek where CitiesId = ? limit 1000";
				$res = Connect::$instance->fetch($sql, [$to]);
				if (empty($res)) {
					break;
				}
				$zoom = 11;
				foreach ($res as $value) {
					$jdata = json_decode($value['JData'], true);
					$row = [
						'Id' => $value['Id'],
						'Name' => $value['Name'],
						'Code' => $jdata['code'],
						'Country' => $jdata['location']['country_code'],
						'City' => $jdata['location']['city'],
						'Address' => $jdata['location']['address'],
						'WorkTime' => $jdata['work_time'],
						'PostalCode' => $jdata['location']['postal_code'],
						'Phone' => $jdata['phones'][0]['number'],
						'Email' => '',
						'Coords' => "{$jdata['location']['latitude']},{$jdata['location']['longitude']}",
						'MapZoom' => $zoom
					];
					array_push($data, $row);
				}
				break;
			case 137:
				$citiesById = $this->deliveryUtils->getCitiesById($cart['citiesId']);
				$headers->js("/ext/Cart/Delivery/Address/Address.{$headers::$rand}.js");
				$headers->css("/ext/Cart/Delivery/Address/Address.{$headers::$rand}.css");
				$headers->css("https://cdn.jsdelivr.net/npm/suggestions-jquery@22.6.0/dist/css/suggestions.min.css");
				$headers->js("https://cdn.jsdelivr.net/npm/suggestions-jquery@22.6.0/dist/js/jquery.suggestions.min.js");
				$tpl = 'Address/Address.tpl';
				$data = [
					'deliveryCtiy' => $citiesById[0],
					'token' => Connect::$projectServices['dadata']['token']
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
	public function getErrors(array $get): array
	{
		$cartSummary = $this->cartUtils->getCartSummary();
		$errors = [];
		switch (@$cartSummary['delivery']['settings']['tariff']) {
			case 136:
				$errors['operations-id'] = Validator::isNotEmpty($get['operations-id'], "Не заполнено");
				$errors['operations-title'] = Validator::isNotEmpty($get['operations-title'], "Не заполнено");
				$errors['operations-city'] = Validator::isNotEmpty($get['operations-city'], "Не заполнено");
				$errors['operations-address-short'] = Validator::isNotEmpty($get['operations-address-short'], "Не заполнено");
				$errors['operations-postal-code'] = Validator::isNotEmpty($get['operations-postal-code'], "Не заполнено");
				break;
			case 137:
			default:
				$errors['operations-city'] = Validator::isNotEmpty($get['operations-city'], "Не заполнено");
				$errors['operations-address-short'] = Validator::isNotEmpty($get['operations-address-short'], "Не заполнено");
				$errors['operations-postal-code'] = Validator::isNotEmpty($get['operations-postal-code'], "Не заполнено");
				break;
		}
		return $errors;
	}
	public function setPoints(): bool
	{
		$func = function (array $args) {
			$data = [
				'country_code' => 'RU',
			];
			$response = $this->curl->get($this->url . '/v2/deliverypoints', $data);
			if (empty($response->response)) {
				return [];
			}
			$jdata = json_decode($response->response, true);
			if (empty($jdata)) {
				return [];
			}
			Connect::$instance->query('truncate PointsCdek');
			$row = [
				'Name' => '',
				'Alias' => '',
				'JData' => '',
				'CitiesId' => '',
			];
			$prepare = Connect::$instance->prepare($row);
			$insert = Connect::$db->prepare("insert into PointsCdek {$prepare['insert']}");
			foreach ($jdata as $value) {
				$row = [
					'Name' => $value['name'],
					'Alias' => $value['code'],
					'JData' => json_encode($value, JSON_UNESCAPED_UNICODE),
					'CitiesId' => $value['location']['city_code'],
				];
				$insert->execute($row);
			}
			return [];
		};
		Connect::$instance->transaction($func, []);
		return true;
	}
	public function setCities(int $page = 0)
	{
		$func = function (array $args) {
			$page = $args['page'] ?? 0;
			$url = $this->url . '/v2/location/cities?country_codes=RU&&size=1000&page=' . $page;
			$cli = new Cli();
			$cli->progress($page, 150);
			$response = $this->curl->get($url);
			if (empty($response->response)) {
				return [];
			}
			$jdata = json_decode($response->response, true);
			if (empty($jdata)) {
				if ($page > 1) {
					return [];
				}
				return [];
			}
			if ($page == 0) {
				Connect::$instance->query('truncate CitiesCdek');
			}
			$row = [
				'Id' => '',
				'Name' => '',
				'RegionsId' => '',
			];
			$prepare = Connect::$instance->prepare($row);
			$insert = Connect::$db->prepare("insert into CitiesCdek {$prepare['insert']}");
			foreach ($jdata as $value) {
				$row = [
					'Id' => $value['code'],
					'Name' => $value['city'],
					'RegionsId' => $value['region_code'],
				];
				$insert->execute($row);
			}
			return [];
		};
		Connect::$instance->transaction($func, ['page' => $page]);
		$page++;
		if ($page <= 150) {
			return self::setCities($page);
		}
		return [];
	}
	public function setRegions()
	{
		$func = function (array $args) {
			$data = [
				'country_codes' => 'RU',
			];
			$response = $this->curl->get($this->url . '/v2/location/regions', $data);
			if (empty($response->response)) {
				return [];
			}
			$jdata = json_decode($response->response, true);
			if (empty($jdata)) {
				return [];
			}
			Connect::$instance->query('truncate RegionsCdek');
			$row = [
				'Id' => '',
				'Name' => '',
				'JData' => '',
			];
			$prepare = Connect::$instance->prepare($row);
			$insert = Connect::$db->prepare("insert into RegionsCdek {$prepare['insert']}");
			foreach ($jdata as $value) {
				$row = [
					'Id' => $value['region_code'],
					'Name' => $value['region'],
					'JData' => json_encode($value, JSON_UNESCAPED_UNICODE),
				];
				$insert->execute($row);
			}
			return [];
		};
		Connect::$instance->transaction($func, []);
		return true;
	}
	public function getPostalcodes()
	{
		$response = $this->curl->get($this->url . '/v2/location/postalcodes?code=' . $this->settings['CitiesId']);
		$jdata = @json_decode($response->response, true);
		return $jdata;
	}
	private function getToken(): void
	{
		$f = file_get_contents($this->tokenFilename);
		$jdata = json_decode($f, true);
		if (empty($jdata) || date('U') >= @$jdata['lifetime']) {
			$curl = new Curl();
			$curl->setHeader('content-type', 'application/x-www-form-urlencoded');
			$body = [
				'grant_type' => 'client_credentials',
				'client_id' => $this->account,
				'client_secret' => $this->password
			];
			$response = $curl->post($this->url . '/v2/oauth/token', $body);
			$jdata = json_decode($response->response, true);
			if (empty($jdata['access_token'])) {
				#Exception::page('Ошибка получения токена CDEK','500', 401);
				Exception::page('Что-то не так с настройками CDEK', "<p>Похоже, в параметрах подключения к сервису CDEK есть ошибка. Из-за этого мы не можем обработать запрос.</p><p>{$response->response}</p><p><b>Что делать?</b></p><p>Проверьте параметры подключения: Убедитесь, что данные из личного кабинета CDEK скопированы правильно и без лишних символов.</p>",401);
				exit();
			}
			$this->token = $jdata['access_token'];
			$jdata['lifetime'] = date('U') + $jdata['expires_in'] - 300;
			$this->curl = new Curl();
			$this->curl->setHeader('authorization', 'Bearer ' . $this->token);
			file_put_contents($this->tokenFilename, json_encode($jdata), JSON_UNESCAPED_UNICODE);
			$this->counter++;
		} elseif (!empty($jdata['access_token'])) {
			$this->token = $jdata['access_token'];
		} else {
			Utils::debug('token error', 31);
			exit();
		}
		$this->curl = new Curl();
		$this->curl->setHeader('content-type', 'application/json;charset=UTF-8');
		$this->curl->setHeader('accept', 'application/json');
		$this->curl->setHeader('authorization', value: 'Bearer ' . $this->token);
	}
}