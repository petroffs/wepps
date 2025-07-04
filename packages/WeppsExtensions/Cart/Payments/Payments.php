<?php
namespace WeppsExtensions\Cart\Payments;

use WeppsCore\Utils\UtilsWepps;
use WeppsExtensions\Cart\CartUtilsWepps;

class PaymentsWepps
{
	protected $settings;
    protected $cartUtils;
	protected $paymentsUtils;
	public function __construct(array $settings = [],CartUtilsWepps $cartUtils)
	{
		$this->settings = $settings;
		$this->cartUtils = $cartUtils;
		$this->paymentsUtils = new PaymentsUtilsWepps();
	}
	public function getTariff(CartUtilsWepps $cartUtils)
	{
		$cartSummary = $cartUtils->getCartSummary();
		if (empty($cartSummary)) {
			return [];
		}
		$output = [
			'status' => ($this->settings['Tariff'] > 0) ? 200 : 0,
			'title' => $this->settings['Name'],
			'text' => 'Наценка за выбранный способ оплаты'
		];
		switch (@$this->settings['IsTariffPercentage']) {
			case 1:
				$output['price'] = $cartUtils->getCartPercentage((float)$this->settings['Tariff']);
				break;
			default:
				$output['price'] = UtilsWepps::round($this->settings['Tariff']);
				break;
		}
		return $output;
	}
	public function getDiscount(CartUtilsWepps $cartUtils): array
	{
		$cartSummary = $cartUtils->getCartSummary();
		if (empty($cartSummary)) {
			return [];
		}
		$output = [
			'status' => ($this->settings['Discount'] > 0) ? 200 : 0,
			'title' => $this->settings['Name'],
			'text' => "Скидка за выбранный способ оплаты",
			
		];
		switch (@$this->settings['IsDiscountPercentage']) {
			case 1:
				$output['price'] = $cartUtils->getCartPercentage((float)$this->settings['Discount']);
				break;
			default:
				$output['price'] = UtilsWepps::round($this->settings['Discount']);
				break;
		}
		return $output;
	}
	public function getOperations(array $order): array {
		#$headers = $this->cartUtils->getHeaders();
		#$headers->js("/ext/Cart/Payments/PaymentsDefault.{$headers::$rand}.js");
		#$headers->css("/ext/Cart/Payments/PaymentsDefault.{$headers::$rand}.css");
		$tpl = 'Default/Default.tpl';
		return [
			'tpl' => $tpl,
			'data' => [
				'order' => $order
			]
		];
	}
}