<?php
namespace WeppsExtensions\Cart\Delivery\DeliveryAddress;

use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Validator\ValidatorWepps;
use WeppsExtensions\Cart\CartUtilsWepps;
use WeppsExtensions\Cart\Delivery\DeliveryWepps;

class DeliveryAddressWepps extends DeliveryWepps
{
	public function __construct(array $settings, CartUtilsWepps $cartUtils)
	{
		parent::__construct($settings, $cartUtils);
	}
	public function getOperations(): array
	{
		$headers = $this->cartUtils->getHeaders();
		$jdata = json_decode($this->settings['JSettings'], true);
		$tpl = 'OperationsNotice.tpl';
		$data = [];
		$allowBtn = false;
		$cart = $this->cartUtils->getCart();
		$citiesById = $this->deliveryUtils->getCitiesById($cart['citiesId']);
		$headers->js("/ext/Cart/Delivery/DeliveryAddress/OperationsAddress.{$headers::$rand}.js");
		$headers->css("/ext/Cart/Delivery/DeliveryAddress/OperationsAddress.{$headers::$rand}.css");
		$headers->css("https://cdn.jsdelivr.net/npm/suggestions-jquery@22.6.0/dist/css/suggestions.min.css");
		$headers->js("https://cdn.jsdelivr.net/npm/suggestions-jquery@22.6.0/dist/js/jquery.suggestions.min.js");
		$tpl = 'DeliveryAddress/OperationsAddress.tpl';
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