<?php
namespace WeppsExtensions\Cart\Delivery\RussianPost;

use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Utils\UtilsWepps;
use WeppsCore\Validator\ValidatorWepps;
use WeppsExtensions\Cart\CartUtilsWepps;
use Curl\Curl;
use WeppsExtensions\Cart\Delivery\Cdek\CdekWepps;
use WeppsExtensions\Cart\Delivery\DeliveryWepps;

class RussianPostWepps extends DeliveryWepps
{
    public function __construct(array $settings, CartUtilsWepps $cartUtils)
    {
        parent::__construct($settings, $cartUtils);
    }
    public function getTariff(): array
    {
        $cartSummary = $this->cartUtils->getCartSummary();
        if (empty($cartSummary)) {
            return [];
        }
        if (empty($this->settings['PostalCode'])) {
            $obj = new CdekWepps($this->settings,$this->cartUtils);
            $this->settings['PostalCode'] = $obj->getPostalcodes();
            if (empty($this->settings['PostalCode'])) {
                $output = [
                    'status' => 400,
                    'title' => $this->settings['Name'],
                    'text' => 'Ошибка расчета',
                    'price' => 0,
                    'period' => '0'
                ];
                return $output;
            }
        }
        $from = ConnectWepps::$projectServices['russianpost']['office']['sender'];
        $to = $this->settings['PostalCode'];
        $weight = "1000";
        $sum = $cartSummary['sumActive'] * 100;
        $url = "https://delivery.pochta.ru/v2/calculate/tariff/delivery?json&object=54020&from=$from&to=$to&transtype=1&weight=$weight&size=30x30x10&group=0&closed=1&sumoc=$sum";
        
        $hash = md5($url);
        if (empty($jdata = $this->cartUtils->getMemcached()->get($hash))) {
            $curl = new Curl();
            $curl->setHeader('Content-Type', 'application/json;charset=UTF-8');
            $response = $curl->get($url)->response;
            $jdata = json_decode($response, true);
            $this->cartUtils->getMemcached()->set($hash,$jdata,86400);
        }
        $period = "-";
        if (!empty($jdata['delivery']['min'])) {
            $period = "{$jdata['delivery']['min']}-{$jdata['delivery']['max']}";
            if ($jdata['delivery']['min'] == $jdata['delivery']['max']) {
                $period = $jdata['delivery']['min'];
            }
        }
        $price = round(($jdata['paynds']) / (100 * 5)) * 5;
        $output = [
            'status' => 200,
            'title' => $this->settings['Name'],
            'text' => 'Тариф способа доставки',
            'price' => UtilsWepps::round($price),
            'period' => $period
        ];
        return $output;
    }
    public function getOperations(): array
    {
        $headers = $this->cartUtils->getHeaders();
        $jdata = json_decode($this->settings['JSettings'], true);
        $data = [];
        $allowBtn = false;
        $cart = $this->cartUtils->getCart();
        $citiesById = $this->deliveryUtils->getCitiesById($cart['citiesId']);
        $headers->js("/ext/Cart/Delivery/Address/Address.{$headers::$rand}.js");
        $headers->css("/ext/Cart/Delivery/Address/Address.{$headers::$rand}.css");
        $headers->css("https://cdn.jsdelivr.net/npm/suggestions-jquery@22.6.0/dist/css/suggestions.min.css");
        $headers->js("https://cdn.jsdelivr.net/npm/suggestions-jquery@22.6.0/dist/js/jquery.suggestions.min.js");
        $tpl = 'Address/Address.tpl';
        $data = [
            'deliveryCtiy' => $citiesById[0],
            'token' => ConnectWepps::$projectServices['dadata']['token']
        ];
        $allowBtn = true;
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
		$errors['operations-city'] = ValidatorWepps::isNotEmpty($get['operations-city'], "Не заполнено");
		$errors['operations-address-short'] = ValidatorWepps::isNotEmpty($get['operations-address-short'], "Не заполнено");
		$errors['operations-postal-code'] = ValidatorWepps::isNotEmpty($get['operations-postal-code'], "Не заполнено");
		return $errors;
	}
}