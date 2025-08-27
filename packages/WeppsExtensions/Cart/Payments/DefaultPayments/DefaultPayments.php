<?php
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