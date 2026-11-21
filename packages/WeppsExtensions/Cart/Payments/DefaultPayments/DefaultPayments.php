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

namespace WeppsExtensions\Cart\Payments\DefaultPayments;

use WeppsCore\Utils;
use WeppsExtensions\Cart\CartUtils;
use WeppsExtensions\Cart\Payments\Payments;

class DefaultPayments extends Payments
{
	public function __construct(array $settings = [], CartUtils $cartUtils)
	{
		parent::__construct($settings,$cartUtils);
	}
	public function getOrderComplete()
	{
		Utils::debug(1, 1);
	}
}