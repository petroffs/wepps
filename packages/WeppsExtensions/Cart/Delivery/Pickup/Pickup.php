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

namespace WeppsExtensions\Cart\Delivery\Pickup;

use WeppsCore\Connect;
use WeppsExtensions\Cart\CartUtils;
use WeppsExtensions\Cart\Delivery\Delivery;

class Pickup extends Delivery
{
	public function __construct(array $settings, CartUtils $cartUtils)
	{
		parent::__construct($settings, $cartUtils);
	}
	#[\Override]
	/**
	 * @return ((mixed|null)[]|mixed|string|true)[]
	 *
	 * @psalm-return array{title: mixed, ext: mixed, tpl: 'Pickup/Notice.tpl', data: array{text: mixed, address: mixed|null}, active: mixed, allowOrderBtn: true}
	 */
	public function getOperations()
	{
		$headers = $this->cartUtils->getHeaders();
		#$headers->js("/ext/Cart/Delivery/Notice.{$headers::$rand}.js");
		$headers->css("/ext/Cart/Delivery/Pickup/Notice.{$headers::$rand}.css");
		$tpl = 'Pickup/Notice.tpl';
		$data = [
			'text' => $this->settings['Descr'],
			'address' => Connect::$projectInfo['address'],
		];
		$allowBtn = true;
		return [
			'title' => $this->settings['Name'],
			'ext' => $this->settings['DeliveryExt'],
			'tpl' => $tpl,
			'data' => $data,
			'active' => @$this->cartUtils->getCart()['deliveryOperations'],
			'allowOrderBtn' => $allowBtn
		];
	}
}
