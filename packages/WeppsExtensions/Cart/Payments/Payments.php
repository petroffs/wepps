<?php

/*
 * This file is part of Wepps Platform.
 *
 * (c) 2026 PETROFFS LLC (ООО «ПЕТРОФФС»)
 * Registered with Rospatent (Russian Federation): certificate No. 2026694013 of 03.11.2026.
 *
 * SPDX-License-Identifier: LicenseRef-Wepps-EULA
 *
 * Licensed under the Wepps Platform EULA: https://github.com/petroffs/wepps/blob/master/LICENSE
 * Free for non-commercial use (Community). Commercial use requires
 * a written license agreement with PETROFFS LLC.
 * This notice must not be removed or altered (EULA, sec. 2.2).
 *
 * @author Aleksei Petrov <mail@petroffs.com>
 */

namespace WeppsExtensions\Cart\Payments;

use WeppsCore\Utils;
use WeppsExtensions\Cart\CartUtils;

class Payments
{
	protected $settings;
    protected $cartUtils;
	protected $paymentsUtils;
	public function __construct(array $settings = [],CartUtils $cartUtils)
	{
		$this->settings = $settings;
		$this->cartUtils = $cartUtils;
		$this->paymentsUtils = new PaymentsUtils();
	}
	/**
	 * @return (float|int|mixed|string)[]
	 *
	 * @psalm-return array{status?: 0|200, title?: mixed, text?: 'Наценка за выбранный способ оплаты', price?: float|string}
	 */
	public function getTariff(CartUtils $cartUtils): array
	{
		$cartSummary = $cartUtils->getCartSummary();
		if ($cartSummary === []) {
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
				$output['price'] = Utils::round($this->settings['Tariff']);
				break;
		}
		return $output;
	}
	public function getDiscount(CartUtils $cartUtils): array
	{
		$cartSummary = $cartUtils->getCartSummary();
		if ($cartSummary === []) {
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
				$output['price'] = Utils::round($this->settings['Discount']);
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