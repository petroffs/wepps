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

namespace WeppsExtensions\Cart\Delivery\Address;

use WeppsCore\Connect;
use WeppsCore\Validator;
use WeppsExtensions\Cart\CartUtils;
use WeppsExtensions\Cart\Delivery\Delivery;

class Address extends Delivery
{
	public function __construct(array $settings, CartUtils $cartUtils)
	{
		parent::__construct($settings, $cartUtils);
	}
	#[\Override]
	public function getOperations(): array
	{
		$headers = $this->cartUtils->getHeaders();
		$jdata = json_decode($this->settings['JSettings'], true);
		$tpl = 'OperationsNotice.tpl';
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
			'token' => Connect::$projectServices['dadata']['token']
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
	#[\Override]
	public function getErrors(array $get): array
	{
		$cartSummary = $this->cartUtils->getCartSummary();
		$errors = [];
		$errors['operations-city'] = Validator::isNotEmpty($get['operations-city'] ?? '', "Не заполнено");
		$errors['operations-address-short'] = Validator::isNotEmpty($get['operations-address-short'] ?? '', "Не заполнено");
		$errors['operations-postal-code'] = Validator::isNotEmpty($get['operations-postal-code'] ?? '', "Не заполнено");
		return $errors;
	}
}