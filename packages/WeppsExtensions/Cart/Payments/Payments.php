<?php
namespace WeppsExtensions\Cart\Payments;

use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Utils\UtilsWepps;
use WeppsExtensions\Cart\CartUtilsWepps;
use WeppsExtensions\Template\TemplateUtilsWepps;

class PaymentsWepps
{
	private $settings;
	public function __construct(array $settings = [])
	{
		$this->settings = $settings;
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
				#UtilsWepps::debug((float)$this->settings['Tariff'],1);
				$output['price'] = $cartUtils->getCartPercentage((float)$this->settings['Tariff']);
				#$output['price'] = TemplateUtilsWepps::round($this->settings['Tariff'] * $cartSummary['sumActive'] / 100, 0);
				break;
			default:
				$output['price'] = (float) $this->settings['Tariff'];
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
				#$output['price'] = TemplateUtilsWepps::round($this->settings['Discount'] * $cartSummary['sumActive'] / 100, 0);
				break;
			default:
				$output['price'] = (float) $this->settings['Discount'];
				break;
		}
		return $output;
	}
}