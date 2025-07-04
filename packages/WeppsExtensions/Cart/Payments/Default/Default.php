<?php
namespace WeppsExtensions\Cart\Payments\Default;

use WeppsCore\Utils\UtilsWepps;
use WeppsExtensions\Cart\CartUtilsWepps;
use WeppsExtensions\Cart\Payments\PaymentsWepps;

class DefaultWepps extends PaymentsWepps
{
	public function __construct(array $settings = [], CartUtilsWepps $cartUtils)
	{
		parent::__construct($settings,$cartUtils);
	}
	public function getOrderComplete()
	{
		UtilsWepps::debug(1, 1);
	}
}