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

namespace WeppsExtensions\Cart\Payments\Qrcode;

use WeppsExtensions\Cart\CartUtils;
use WeppsExtensions\Cart\Payments\Payments;

class Qrcode extends Payments
{
	public function __construct(array $settings = [], CartUtils $cartUtils)
	{
		parent::__construct($settings, $cartUtils);
	}
	#[\Override]
	public function getOperations($order): array
	{
		#$headers = $this->cartUtils->getHeaders();
		#$headers->js("/ext/Cart/Payments/PaymentsQR.{$headers::$rand}.js");
		#$headers->css("/ext/Cart/Payments/PaymentsQR.{$headers::$rand}.css");
		$tpl = 'Qrcode/Qrcode.tpl';
		return [
			'tpl' => $tpl,
			'data' => [
				'order' => $order
			]
		];
	}
}